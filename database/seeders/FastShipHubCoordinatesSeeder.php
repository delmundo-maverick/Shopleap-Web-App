<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\HubServiceArea;
use App\Models\LogisticsProfile;
use App\Services\ActivityLogger;
use App\Support\PhilippinesCoordinates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class FastShipHubCoordinatesSeeder extends Seeder
{
    /**
     * Approximate barangay location absent from the geocoder.
     * Source: https://www.philatlas.com/luzon/r04a/laguna/nagcarlan/silangan-kabubuhayan.html
     *
     * @var array<string, array{latitude: float, longitude: float}>
     */
    private const BARANGAY_REFERENCES = [
        'laguna|nagcarlan|silangan kabubuhayan' => ['latitude' => 14.1918, 'longitude' => 121.4025],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo hub coordinates may only be filled locally or in testing.');
        }

        $company = Company::where('name', 'FastShip Express')->where('status', 'approved')->sole();
        $hubs = $company->hubs()->where(fn ($query) => $query->whereNull('latitude')->orWhereNull('longitude'))->get();
        $updated = 0;
        foreach ($hubs as $hub) {
            $this->command?->line('Locating '.$hub->business_name);
            try {
                $coordinates = $this->locate($hub);
                if ($coordinates === null) {
                    $this->command?->warn('No matching barangay found: '.$hub->business_name.'. Set its pin in Edit hub.');

                    continue;
                }
                $updated += LogisticsProfile::whereKey($hub->id)->where('company_id', $company->id)
                    ->where('province', $hub->province)->where('municipality', $hub->municipality)->where('barangay', $hub->barangay)
                    ->where(fn ($query) => $query->whereNull('latitude')->orWhereNull('longitude'))->update($coordinates);
            } catch (Throwable $exception) {
                report($exception);
                $this->command?->warn('Location lookup unavailable for '.$hub->business_name.'. Rerun this seeder to retry.');
            }
        }

        if ($updated > 0) {
            ActivityLogger::log('fastship.coordinates_seeded', 'logistics', 'Filled missing demo branch pins from saved barangay locations.', $company, ['hubs_updated' => $updated]);
        }
        $remaining = $company->hubs()->where(fn ($query) => $query->whereNull('latitude')->orWhereNull('longitude'))->count();
        $this->command?->info('Added '.$updated.' approximate barangay pins. '.$remaining.' hubs still need a location. Existing pins were preserved.');
    }

    /** @return array{latitude: float, longitude: float}|null */
    private function locate(LogisticsProfile $hub): ?array
    {
        $queries = array_unique([
            $hub->barangay.', '.$hub->municipality.', '.$hub->province.', Philippines',
            trim(preg_replace('/\([^)]*\)/', '', $hub->barangay)).', '.preg_replace('/\bCity of\s+/i', '', $hub->municipality).', '.$hub->province.', Philippines',
        ]);
        foreach ($queries as $query) {
            $features = Cache::store('file')->remember('fastship-photon:'.sha1($query), 86400, function () use ($query): array {
                usleep(1100000);

                return Http::acceptJson()->connectTimeout(5)->timeout(15)
                    ->get('https://photon.komoot.io/api/', ['q' => $query, 'countrycode' => 'PH', 'bbox' => '116,4,127,22', 'limit' => 20, 'lang' => 'en'])
                    ->throw()->json('features', []);
            });
            foreach ($features as $feature) {
                $properties = $feature['properties'] ?? [];
                $coordinates = $feature['geometry']['coordinates'] ?? [];
                $names = array_map($this->normalize(...), array_intersect_key($properties, array_flip(['name', 'district', 'locality'])));
                $address = $this->normalize(implode(' ', array_filter($properties, is_scalar(...))));
                if (($feature['geometry']['type'] ?? '') !== 'Point' || count($coordinates) !== 2
                    || ! is_numeric($coordinates[0]) || ! is_numeric($coordinates[1])
                    || ! in_array($this->normalize($hub->barangay), $names, true)
                    || ! str_contains($address, $this->normalize($hub->municipality))
                    || ! str_contains($address, $this->normalize($hub->province))
                    || ! PhilippinesCoordinates::contains((float) $coordinates[1], (float) $coordinates[0])) {
                    continue;
                }

                return ['latitude' => round((float) $coordinates[1], 7), 'longitude' => round((float) $coordinates[0], 7)];
            }
        }

        $reference = self::BARANGAY_REFERENCES[HubServiceArea::placeKey($hub->province, $hub->municipality, $hub->barangay)] ?? null;

        return $reference && PhilippinesCoordinates::contains($reference['latitude'], $reference['longitude']) ? $reference : null;
    }

    private function normalize(string $name): string
    {
        $name = preg_replace('/\([^)]*\)/', '', Str::lower(Str::ascii($name)));
        $name = preg_replace('/\b(barangay|brgy|city of|municipality of|city)\b/', '', $name);
        $numerals = ['i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4, 'v' => 5, 'vi' => 6, 'vii' => 7, 'viii' => 8, 'ix' => 9, 'x' => 10];
        $name = preg_replace_callback('/\b(i|ii|iii|iv|v|vi|vii|viii|ix|x)\b/', fn (array $match): string => (string) $numerals[$match[0]], $name);

        return preg_replace('/[^a-z0-9]/', '', $name);
    }
}
