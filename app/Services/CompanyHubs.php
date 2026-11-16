<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\RiderRate;
use App\Models\ShippingRate;
use App\Models\User;
use App\Notifications\HubInvitationNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyHubs
{
    public function __construct(private HubServiceAreaService $areas) {}

    /** @param array<string, mixed> $data */
    public function save(Company $company, array $data, ?LogisticsProfile $hub = null): LogisticsProfile
    {
        $data['barangays'] = array_values($data['barangays']);

        try {
            return DB::transaction(function () use ($company, $data, $hub): LogisticsProfile {
                $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
                abort_unless($company->status === 'approved', 403);
                $creating = $hub === null;
                if ($hub) {
                    $hub = LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
                    abort_unless((int) $hub->company_id === (int) $company->id, 403);
                    $manager = User::whereKey($hub->user_id)->lockForUpdate()->firstOrFail();
                    $emailChanged = $manager->email !== $data['manager_email'];
                    $locationChanged = $hub->province !== $data['province'] || $hub->municipality !== $data['municipality'];
                    if ($locationChanged) {
                        foreach ($hub->serviceAreas()->get() as $area) {
                            $this->areas->deleteArea($hub, $area);
                        }
                    }
                } else {
                    $manager = new User(['role' => 'logistics', 'account_status' => 'active', 'password' => Hash::make(Str::random(64))]);
                    $hub = new LogisticsProfile(['company_id' => $company->id, 'public_code' => (string) Str::uuid(), 'status' => 'approved', 'reviewed_at' => now(), 'company_name' => mb_substr($company->name, 0, 150)]);
                    $emailChanged = true;
                }
                $manager->fill(['name' => $data['manager_name'], 'email' => $data['manager_email'], 'phone' => $data['manager_phone']]);
                if ($emailChanged) {
                    $manager->forceFill(['password' => Hash::make(Str::random(64)), 'email_verified_at' => null, 'remember_token' => null]);
                    $hub->manager_password_set_at = null;
                }
                $managerChanged = $manager->isDirty(['name', 'email', 'phone']);
                $manager->save();
                if ($emailChanged) {
                    app(CompanyManagers::class)->revokeSessions($manager);
                }
                $data['street_address'] = $creating || $hub->province !== $data['province'] || $hub->municipality !== $data['municipality'] || $hub->barangay !== $data['barangay']
                    ? implode(', ', [$data['barangay'], $data['municipality'], $data['province']])
                    : $hub->street_address;
                $hub->fill(collect($data)->only(['business_name', 'street_address', 'province', 'municipality', 'contact_no', 'latitude', 'longitude'])->all());
                $hub->fill(['user_id' => $manager->id, 'first_name' => $data['manager_name'], 'last_name' => '', 'barangay' => $data['barangay']]);
                $hub->save();
                if ($creating && $company->default_rider_rate !== null) {
                    $lowest = ShippingRate::query()->orderBy('id')->sharedLock()->get()->min('fee');
                    if ($lowest === null || (float) $company->default_rider_rate >= (float) $lowest) {
                        throw ValidationException::withMessages(['hub' => 'Update the company default rider rate in Settings before creating a hub. It must be lower than the lowest shipping fee.']);
                    }
                    RiderRate::create(['logistics_profile_id' => $hub->id, 'amount' => $company->default_rider_rate, 'effective_from' => now(), 'created_by' => auth()->id()]);
                }

                $riders = $hub->serviceAreas()->with('barangays')->get()->flatMap->barangays->pluck('rider_id', 'barangay')->all();
                $this->areas->claimBarangays($hub, ['barangays' => $data['barangays'], 'riders' => $riders]);
                if ($emailChanged) {
                    $this->invite($hub);
                }

                CompanyActivity::record($company->id, $creating ? 'hub.created' : 'hub.edited', $hub);
                if (! $creating && $managerChanged) {
                    CompanyActivity::record($company->id, 'manager.edited', $hub);
                }

                return $hub;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (User::withEmail($data['manager_email'])->where('role', 'logistics')->when($hub, fn ($query) => $query->whereKeyNot($hub->user_id))->exists()) {
                throw ValidationException::withMessages(['manager_email' => 'This email is already registered as a hub manager.']);
            }
            throw $exception;
        }
    }

    public function invite(LogisticsProfile $hub): void
    {
        DB::transaction(function () use ($hub): void {
            $hub = LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
            abort_if($hub->manager_password_set_at !== null, 422, 'This manager has already set a password.');
            $resent = $hub->manager_invite_token !== null;
            $hub->update(['manager_invite_token' => Str::random(64), 'manager_invite_expires_at' => now()->addHours(48)]);
            $hub->user->notify((new HubInvitationNotification($hub))->afterCommit());
            CompanyActivity::record($hub->company_id, $resent ? 'manager.invite_resent' : 'manager.invited', $hub);
        });
    }

    public function setSuspended(LogisticsProfile $hub, bool $suspended): void
    {
        DB::transaction(function () use ($hub, $suspended): void {
            $hub = LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
            if ($suspended) {
                $count = Order::heldBy($hub->id)->whereNotIn('status', HubServiceAreaService::FINAL_STATUSES)->count();
                if ($count > 0) {
                    throw ValidationException::withMessages(['hub' => "Cannot suspend this hub: it currently holds {$count} active parcel(s)."]);
                }
            }
            $hub->user()->update(['account_status' => $suspended ? 'suspended' : 'active']);
            CompanyActivity::record($hub->company_id, $suspended ? 'hub.suspended' : 'hub.reactivated', $hub);
        }, 3);
    }
}
