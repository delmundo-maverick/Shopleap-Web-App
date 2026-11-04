<?php

namespace App\Services;

use App\Models\HubServiceArea;
use App\Models\PsgcBarangay;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class BuyerAddressLocations
{
    /** @return array<int, array{name: string, code: string}> */
    public function places(string $level, ?string $code = null): array
    {
        $path = match ($level) {
            'municipality' => (str_starts_with((string) $code, '13') ? 'regions/' : 'provinces/').$code.'/cities-municipalities/',
            'barangay' => 'cities-municipalities/'.$code.'/barangays/',
            default => 'provinces/',
        };

        try {
            $rows = Cache::remember('buyer-address-psgc:'.$path, 86400, fn (): array => Http::connectTimeout(3)->timeout(10)->get('https://psgc.gitlab.io/api/'.$path)->throw()->json());
            if ($level === 'province') {
                $local = PsgcBarangay::select('province as name', 'province_code as code')->distinct()->get()->toArray();
                $regions = Cache::remember('buyer-address-psgc:regions/', 86400, fn (): array => Http::connectTimeout(3)->timeout(10)->get('https://psgc.gitlab.io/api/regions/')->throw()->json());
                $ncr = collect($regions)->filter(fn (array $region): bool => str_starts_with($region['code'], '13'))->all();
                $rows = collect(array_merge($rows, $local, $ncr))->unique('code')->values()->all();
            }

            return $rows;
        } catch (\Throwable $exception) {
            report($exception);
            $query = PsgcBarangay::query();
            [$nameField, $codeField] = match ($level) {
                'municipality' => ['municipality', 'municipality_code'],
                'barangay' => ['barangay', 'barangay_code'],
                default => ['province', 'province_code'],
            };
            if ($level === 'municipality') {
                $query->where('province_code', $code);
            }
            if ($level === 'barangay') {
                $query->where('municipality_code', $code);
            }
            $rows = $query->select($nameField.' as name', $codeField.' as code')->distinct()->get()->toArray();
            if (! $rows) {
                throw ValidationException::withMessages(['province' => 'Location data is temporarily unavailable. Please try again.']);
            }

            return $rows;
        }
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        $province = $this->match($this->places('province'), $data['province'], 'province');
        $city = $this->match($this->places('municipality', $province['code']), $data['city_municipality'], 'city_municipality');
        $barangay = $this->match($this->places('barangay', $city['code']), $data['barangay'], 'barangay');
        $data['province'] = $province['name'];
        $data['city_municipality'] = $city['name'];
        $data['barangay'] = $barangay['name'];

        return $data;
    }

    /** @param array<int, array{name: string, code: string}> $places
     * @return array{name: string, code: string}
     */
    private function match(array $places, string $name, string $field): array
    {
        foreach ($places as $place) {
            if (HubServiceArea::nameKey($place['name']) === HubServiceArea::nameKey($name)) {
                return $place;
            }
        }
        throw ValidationException::withMessages([$field => 'Choose a valid location from the list.']);
    }
}
