<?php

namespace App\Services;

use App\Models\HubServiceArea;
use App\Models\HubServiceAreaBarangay;
use App\Models\LogisticsProfile;
use Illuminate\Support\Collection;

/** Lists the barangays a hub owns, so registration and the app use the same source. */
class HubBarangayCatalog
{
    /**
     * @return Collection<int, array{
     *     service_area_id: int,
     *     province: string,
     *     municipality: string,
     *     barangay: string,
     *     place_key: string,
     *     rider_id: int|null,
     *     rider_name: string|null
     * }>
     */
    public static function forHub(LogisticsProfile $hub): Collection
    {
        return $hub->serviceAreas()
            ->with('barangays.rider')
            ->orderBy('province')
            ->orderBy('municipality')
            ->get()
            ->flatMap(fn (HubServiceArea $area) => $area->barangays
                ->sortBy('barangay')
                ->map(fn (HubServiceAreaBarangay $barangay) => [
                    'service_area_id' => $area->id,
                    'province' => $area->province,
                    'municipality' => $area->municipality,
                    'barangay' => $barangay->barangay,
                    'place_key' => HubServiceArea::placeKey(
                        $area->province,
                        $area->municipality,
                        $barangay->barangay
                    ),
                    'rider_id' => $barangay->rider_id,
                    'rider_name' => $barangay->rider?->full_name,
                ]))
            ->values();
    }

    /**
     * @return array{
     *     service_area_id: int,
     *     province: string,
     *     municipality: string,
     *     barangay: string,
     *     place_key: string,
     *     rider_id: int|null,
     *     rider_name: string|null
     * }|null
     */
    public static function findForHub(
        LogisticsProfile $hub,
        ?string $province,
        ?string $municipality,
        ?string $barangay
    ): ?array {
        $key = HubServiceArea::placeKey($province, $municipality, $barangay);

        return self::forHub($hub)->firstWhere('place_key', $key);
    }
}
