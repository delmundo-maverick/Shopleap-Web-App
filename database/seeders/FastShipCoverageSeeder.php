<?php

namespace Database\Seeders;

use App\Models\BuyerAddress;
use App\Models\BuyerProfile;
use App\Models\Company;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\HubServiceAreaBarangay;
use App\Models\LogisticsProfile;
use App\Models\PsgcBarangay;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\HubCoverage;
use App\Services\HubServiceAreaService;
use App\Services\RiderCoverage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Throwable;

class FastShipCoverageSeeder extends Seeder
{
    /** @var list<array<string, string>> */
    private array $credentials = [];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo branch and rider accounts may only be created locally or in testing.');
        }

        $this->credentials = [];
        $company = Company::where('name', 'FastShip Express')->where('status', 'approved')->sole();
        $areas = app(HubServiceAreaService::class);
        $locations = $this->locations($company);

        foreach ($locations->groupBy(fn (array $place): string => HubServiceArea::keyFor($place['province'], $place['municipality'])) as $places) {
            $place = $places->first();
            $this->command?->line('Checking '.$place['municipality'].', '.$place['province']);
            if (! $areas->ensureBarangayData(new LogisticsProfile($place))) {
                throw new RuntimeException('Could not load the official barangays for '.$place['municipality'].', '.$place['province']);
            }
            $valid = PsgcBarangay::forMunicipality($place['province'], $place['municipality'])
                ->pluck('barangay')->map(fn (string $name): string => HubServiceArea::nameKey($name));
            foreach ($places as $location) {
                if (! $valid->contains(HubServiceArea::nameKey($location['barangay']))) {
                    throw new RuntimeException('Address needs correction before provisioning: '.implode(', ', $location));
                }
            }
        }

        $directory = storage_path('app/private/fastship');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/accounts-'.now()->format('Ymd-His').'-'.Str::random(8).'.csv';
        $file = fopen($path, 'x');
        if ($file === false) {
            throw new RuntimeException('Could not create the private account credentials file.');
        }

        try {
            DB::transaction(function () use ($company, $locations, $areas, $file): void {
                Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
                foreach ($locations->groupBy(fn (array $place): string => HubServiceArea::keyFor($place['province'], $place['municipality'])) as $places) {
                    $hub = $this->hubFor($company, $places->first());
                    $area = $areas->homeArea($hub);
                    $current = $area->barangays()->with('rider.user')->lockForUpdate()->get();
                    $names = $current->pluck('barangay')->all();
                    $riders = $current->pluck('rider_id', 'barangay')->all();

                    foreach ($places as $place) {
                        $mapped = HubServiceAreaBarangay::with('area')->where('province_key', HubServiceArea::nameKey($place['province']))
                            ->where('municipality_key', HubServiceArea::nameKey($place['municipality']))
                            ->where('barangay_key', HubServiceArea::nameKey($place['barangay']))->lockForUpdate()->first();
                        if ($mapped && (int) $mapped->area->logistics_profile_id !== (int) $hub->id) {
                            throw new RuntimeException('This barangay is already owned by another hub: '.implode(', ', $place));
                        }
                        $rider = $this->riderFor($hub, $place);
                        $names[] = $place['barangay'];
                        $riders[$place['barangay']] = $rider->id;
                    }

                    $areas->claimBarangays($hub, ['barangays' => $names, 'riders' => $riders]);
                }

                $this->verifyCoverage($company, $locations);
                if (fputcsv($file, ['role', 'hub', 'province', 'municipality', 'barangay', 'email', 'password'], ',', '"', '') === false) {
                    throw new RuntimeException('Could not write the credentials file.');
                }
                foreach ($this->credentials as $credential) {
                    if (fputcsv($file, array_values($credential), ',', '"', '') === false) {
                        throw new RuntimeException('Could not write account credentials.');
                    }
                }
                if (! fflush($file)) {
                    throw new RuntimeException('Could not save account credentials.');
                }
                ActivityLogger::log('fastship.coverage_seeded', 'logistics', 'Filled missing FastShip demo branch and rider coverage.', $company,
                    ['locations' => $locations->count(), 'accounts_created' => count($this->credentials)]);
            });
        } catch (Throwable $exception) {
            fclose($file);
            File::delete($path);
            throw $exception;
        }

        fclose($file);
        $counts = collect($this->credentials)->countBy('role');
        $this->command?->info('Created '.($counts['logistics'] ?? 0).' branch managers and '.($counts['courier'] ?? 0).' riders. Verified '.$locations->count().' locations.');
        $this->command?->info('Private credentials: '.$path);
        $this->call(FastShipHubCoordinatesSeeder::class);
    }

    /** @return Collection<int, array{province: string, municipality: string, barangay: string}> */
    private function locations(Company $company): Collection
    {
        $locations = collect();
        foreach ([SellerProfile::class => 'seller', BuyerProfile::class => 'buyer'] as $model => $role) {
            $locations = $locations->concat($model::whereHas('user', fn ($query) => $query->where('role', $role))
                ->get(['province', 'municipality', 'barangay'])->toArray());
        }
        $locations = $locations->concat(BuyerAddress::whereHas('user', fn ($query) => $query->where('role', 'buyer'))
            ->get(['province', 'city_municipality as municipality', 'barangay'])->toArray());
        $hubs = $company->hubs()->with('serviceAreas.barangays')->get();
        foreach ($hubs as $hub) {
            $locations->push($hub->only(['province', 'municipality', 'barangay']));
            foreach ($hub->serviceAreas as $area) {
                foreach ($area->barangays as $barangay) {
                    $locations->push(['province' => $area->province, 'municipality' => $area->municipality, 'barangay' => $barangay->barangay]);
                }
            }
        }

        return $locations->map(function (array $place): array {
            $place = array_map(trim(...), $place);
            if (in_array('', $place, true)) {
                throw new RuntimeException('A current user or branch has an incomplete location. Correct it before provisioning.');
            }

            return $place;
        })->unique(fn (array $place): string => HubServiceArea::placeKey(...array_values($place)))
            ->sortBy(fn (array $place): string => implode('|', $place))->values();
    }

    /** @param array{province: string, municipality: string, barangay: string} $place */
    private function hubFor(Company $company, array $place): LogisticsProfile
    {
        $hubs = $company->hubs()->with('user')->get()->filter(fn (LogisticsProfile $hub): bool => HubServiceArea::keyFor($hub->province, $hub->municipality) === HubServiceArea::keyFor($place['province'], $place['municipality']));
        if ($hubs->count() > 1) {
            throw new RuntimeException('Multiple branches exist in '.$place['municipality'].'. Assign the uncovered barangays explicitly.');
        }
        if ($hub = $hubs->first()) {
            if (! $hub->isAvailable()) {
                throw new RuntimeException('An existing branch is unavailable in '.$place['municipality'].'. Its status was preserved.');
            }

            return $hub;
        }

        $manager = $this->createAccount('logistics', $place, 'FastShip '.$place['municipality'].' Demo Manager');
        $hub = LogisticsProfile::create([
            'company_id' => $company->id, 'company_name' => $company->name, 'public_code' => (string) Str::uuid(),
            'business_name' => 'FastShip '.$place['province'].' '.$place['municipality'].' Hub',
            'user_id' => $manager->id, 'first_name' => $manager->name, 'last_name' => '', 'contact_no' => '00000000000',
            ...$place, 'street_address' => implode(', ', array_reverse($place)),
            'status' => 'approved', 'reviewed_at' => now(), 'manager_password_set_at' => now(),
        ]);

        return $hub;
    }

    /** @param array{province: string, municipality: string, barangay: string} $place */
    private function riderFor(LogisticsProfile $hub, array $place): CourierProfile
    {
        $rider = $hub->riders()->with('user')->where('status', 'approved')->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('role', 'courier')->where('account_status', 'active')->whereNotNull('email_verified_at'))
            ->get()->first(fn (CourierProfile $candidate): bool => RiderCoverage::coversAddress($candidate, ...array_values($place), hubId: $hub->id));
        if ($rider) {
            return $rider;
        }

        $rider = $hub->riders()->with('user')->where('status', 'approved')->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('role', 'courier')->where('account_status', 'active')->whereNotNull('email_verified_at'))
            ->get()->first(fn (CourierProfile $candidate): bool => HubServiceArea::placeKey($candidate->province, $candidate->municipality, $candidate->barangay) === HubServiceArea::placeKey(...array_values($place)));
        if ($rider) {
            return $rider;
        }

        $user = $this->createAccount('courier', $place, 'FastShip '.$place['barangay'].' Demo Rider');

        return CourierProfile::create([
            'user_id' => $user->id, 'logistics_profile_id' => $hub->id, 'first_name' => $place['barangay'], 'last_name' => 'Demo Rider',
            'sex' => 'male', 'contact_no' => '00000000000', 'birthday' => '2000-01-01', 'age' => now()->year - 2000,
            ...$place, 'street_address' => implode(', ', array_reverse($place)), 'vehicle_type' => 'Motorcycle',
            'plate_number' => 'DEMO-'.Str::upper(Str::random(8)), 'or_cr_upload_path' => '', 'license_upload_path' => '',
            'delivery_area' => $place['barangay'], 'status' => 'approved', 'is_active' => true, 'reviewed_at' => now(),
        ]);
    }

    /** @param array{province: string, municipality: string, barangay: string} $place */
    private function createAccount(string $role, array $place, string $name): User
    {
        $slug = Str::slug($place['province'].'-'.$place['municipality'].($role === 'courier' ? '-'.$place['barangay'] : ''));
        $email = 'fastship.'.($role === 'courier' ? 'rider' : 'hub').'.'.$slug.'@example.com';
        if (User::withEmail($email)->where('role', $role)->exists()) {
            throw new RuntimeException('A matching demo login already exists without usable coverage: '.$email);
        }
        $password = Str::before($email, '@');
        $user = User::create(['role' => $role, 'name' => $name, 'email' => $email, 'password' => $password,
            'phone' => '00000000000', 'account_status' => 'active', 'email_verified_at' => now()]);
        $this->credentials[] = ['role' => $role, 'hub' => $place['municipality'], ...$place, 'email' => $email, 'password' => $password];

        return $user;
    }

    /** @param Collection<int, array{province: string, municipality: string, barangay: string}> $locations */
    private function verifyCoverage(Company $company, Collection $locations): void
    {
        foreach ($locations as $place) {
            $hub = app(HubCoverage::class)->hubForAddress(...array_values($place));
            if (! $hub || (int) $hub->company_id !== (int) $company->id) {
                throw new RuntimeException('FastShip hub coverage is missing for '.implode(', ', $place));
            }
            $riders = $hub->riders()->where('status', 'approved')->where('is_active', true)
                ->whereHas('user', fn ($query) => $query->where('role', 'courier')->where('account_status', 'active')->whereNotNull('email_verified_at'))->get();
            if (! $riders->contains(fn (CourierProfile $rider): bool => RiderCoverage::coversAddress($rider, ...array_values($place), hubId: $hub->id))) {
                throw new RuntimeException('Rider coverage is missing for '.implode(', ', $place));
            }
        }
    }
}
