<?php

namespace App\Services;

use App\Models\CourierProfile;
use App\Models\HubServiceArea;

/**
 * The single coverage rule used by every path that assigns an order to a rider.
 *
 * A rider covers an address when the address is inside their hub's service areas
 * and they are the rider for that barangay, or the rider for its municipality.
 * The same rider may cover pickups (seller address) and deliveries (buyer address).
 */
class RiderCoverage
{
    public static function coversAddress(
        CourierProfile $rider,
        ?string $province,
        ?string $municipality,
        ?string $barangay,
        ?int $hubId = null
    ): bool {
        if ($rider->status !== 'approved' || ! $rider->is_active) {
            return false;
        }

        if ($hubId !== null && (int) $rider->logistics_profile_id !== $hubId) {
            return false;
        }

        if (trim((string) $province) === '' || trim((string) $municipality) === '') {
            return false;
        }

        $areaKey = HubServiceArea::keyFor($province, $municipality);

        $area = HubServiceArea::where('logistics_profile_id', $rider->logistics_profile_id)
            ->with('barangays')
            ->get()
            ->first(fn (HubServiceArea $candidate) => $candidate->area_key === $areaKey);

        if (! $area) {
            return false;
        }

        $barangayKey = HubServiceArea::nameKey($barangay);

        if ($barangayKey !== '') {
            $mapped = $area->barangays->first(
                fn ($row) => $row->barangay_key === $barangayKey
            );

            if ($mapped && $mapped->rider_id !== null) {
                return (int) $mapped->rider_id === (int) $rider->id;
            }
        }

        // No barangay rider: the municipality rider covers the remaining barangays.
        return $area->rider_id !== null && (int) $area->rider_id === (int) $rider->id;
    }
}
