<?php

namespace App\Services;

use App\Models\LogisticsProfile;
use App\Models\User;
use App\Notifications\ManagerPasswordResetNotification;
use App\Support\Portal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CompanyManagers
{
    public function authorizeHub(User $companyUser, LogisticsProfile $hub): void
    {
        abort_unless($companyUser->role === 'logistics_company' && $hub->company_id !== null
            && (int) $hub->company_id === (int) $companyUser->company_id
            && $hub->user?->role === 'logistics', 403);
    }

    public function update(User $companyUser, LogisticsProfile $hub, string $name, string $phone): void
    {
        DB::transaction(function () use ($companyUser, $hub, $name, $phone): void {
            $hub = LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
            $this->authorizeHub($companyUser, $hub);
            $hub->user()->update(['name' => $name, 'phone' => $phone]);
            $hub->update(['first_name' => $name, 'last_name' => '', 'middle_initial' => null]);
            CompanyActivity::record($hub->company_id, 'manager.edited', $hub);
        });
    }

    public function setSuspended(User $companyUser, LogisticsProfile $hub, bool $suspended): void
    {
        DB::transaction(function () use ($companyUser, $hub, $suspended): void {
            $hub = LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
            $this->authorizeHub($companyUser, $hub);
            $manager = User::whereKey($hub->user_id)->lockForUpdate()->firstOrFail();
            $manager->forceFill(['logistics_login_suspended_at' => $suspended ? now() : null])->save();
            CompanyActivity::record($hub->company_id, $suspended ? 'manager.suspended' : 'manager.reactivated', $hub);
            if ($suspended) {
                $this->revokeSessions($manager);
            }
        });
    }

    public function forceLogout(User $companyUser, LogisticsProfile $hub): void
    {
        DB::transaction(function () use ($companyUser, $hub): void {
            $hub = LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
            $this->authorizeHub($companyUser, $hub);
            $this->revokeSessions(User::whereKey($hub->user_id)->lockForUpdate()->firstOrFail());
            CompanyActivity::record($hub->company_id, 'manager.logged_out', $hub);
        });
    }

    public function sendReset(User $companyUser, LogisticsProfile $hub): void
    {
        $this->authorizeHub($companyUser, $hub);
        abort_unless($hub->manager_password_set_at !== null, 422, 'This manager has not accepted their invitation. Resend the invite instead.');
        $manager = $hub->user;
        $url = Portal::url('logistics', URL::temporarySignedRoute('company.manager-password.show', now()->addHour(), [
            'hub' => $hub->public_code, 'credential' => $this->credential($manager),
        ], absolute: false));
        DB::transaction(function () use ($manager, $url, $hub): void {
            $manager->notify((new ManagerPasswordResetNotification($url, $hub->business_name))->afterCommit());
            CompanyActivity::record($hub->company_id, 'manager.reset_sent', $hub);
        });
    }

    public function credential(User $manager): string
    {
        return hash_hmac('sha256', $manager->email.'|'.$manager->password, config('app.key'));
    }

    public function revokeSessions(User $manager): void
    {
        $manager->forceFill(['logistics_session_version' => (int) $manager->logistics_session_version + 1, 'remember_token' => Str::random(60)])->save();
        if (config('session.driver') === 'database') {
            DB::afterCommit(function () use ($manager): void {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $manager->id)->delete();
            });
        }
    }
}
