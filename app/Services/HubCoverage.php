<?php

namespace App\Services;

use App\Models\HubServiceArea;
use App\Models\HubServiceAreaBarangay;
use App\Models\LogisticsProfile;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves which hub picks up an address and which hub delivers it.
 *
 * A barangay belongs to exactly one hub, so the barangay mapping is the first
 * source of truth. Municipalities that have not been split into barangays yet
 * fall back to the single hub covering that municipality.
 */
class HubCoverage
{
    /**
     * The hub that owns an address. Returns null when no single hub owns it,
     * or when the owning hub is not approved and active.
     */
    public function hubForAddress(
        ?string $province,
        ?string $municipality,
        ?string $barangay = null
    ): ?LogisticsProfile {
        $provinceKey = HubServiceArea::nameKey($province);
        $municipalityKey = HubServiceArea::nameKey($municipality);
        $barangayKey = HubServiceArea::nameKey($barangay);

        if ($provinceKey === '' || $municipalityKey === '') {
            return null;
        }

        if ($barangayKey !== '') {
            $row = HubServiceAreaBarangay::query()
                ->with('area.logisticsProfile')
                ->where('province_key', $provinceKey)
                ->where('municipality_key', $municipalityKey)
                ->where('barangay_key', $barangayKey)
                ->first();

            // The barangay is already claimed. If its hub is unavailable, no
            // other hub may take it.
            if ($row) {
                $hub = $row->area?->logisticsProfile;

                return $hub?->isAvailable() ? $hub : null;
            }
        }

        // No barangay mapping: only a municipality with exactly one covering
        // hub is unambiguous.
        $hubs = LogisticsProfile::available()
            ->covering($province, $municipality)
            ->get();

        return $hubs->count() === 1 ? $hubs->first() : null;
    }

    /**
     * Resolve the pickup hub and delivery hub for a seller/buyer pair.
     * Both ends must belong to the same logistics company.
     *
     * @return array{origin: LogisticsProfile, destination: LogisticsProfile}|null
     */
    public function routeFor(
        User $seller,
        ?string $buyerProvince,
        ?string $buyerMunicipality,
        ?string $buyerBarangay = null
    ): ?array {
        $sellerProfile = $seller->sellerProfile;

        $origin = $this->hubForAddress(
            $sellerProfile?->province,
            $sellerProfile?->municipality,
            $sellerProfile?->barangay
        );

        $destination = $this->hubForAddress(
            $buyerProvince,
            $buyerMunicipality,
            $buyerBarangay
        );

        if (! $origin || ! $destination || ! $origin->isSameCompanyAs($destination)) {
            return null;
        }

        return ['origin' => $origin, 'destination' => $destination];
    }

    /**
     * The delivery hub for an order already held by a hub. The destination
     * must belong to the same company as the hub doing the sorting.
     */
    public function destinationHubFor(
        LogisticsProfile $originHub,
        ?string $province,
        ?string $municipality,
        ?string $barangay = null
    ): ?LogisticsProfile {
        $destination = $this->hubForAddress($province, $municipality, $barangay);

        if (! $destination || ! $originHub->isSameCompanyAs($destination)) {
            return null;
        }

        return $destination;
    }

    /**
     * The zone label used at sorting time. Prefers the destination hub's own
     * zone for the municipality, then falls back to the municipality name.
     */
    public function deliveryAreaLabel(
        LogisticsProfile $hub,
        ?string $province,
        ?string $municipality
    ): string {
        $areas = $hub->relationLoaded('serviceAreas')
            ? $hub->serviceAreas
            : $hub->serviceAreas()->get();

        $area = $areas->first(
            fn (HubServiceArea $candidate) => $candidate->area_key === HubServiceArea::keyFor($province, $municipality)
        );

        return $area?->zone_name ?: (trim((string) $municipality) ?: 'delivery area');
    }

    /**
     * @return Collection<int, LogisticsProfile>
     */
    public function hubsFor(
        User $seller,
        ?string $buyerProvince,
        ?string $buyerMunicipality,
        ?string $buyerBarangay = null
    ): Collection {
        $route = $this->routeFor($seller, $buyerProvince, $buyerMunicipality, $buyerBarangay);

        return $route ? collect([$route['origin']]) : collect();
    }

    public function isCovered(
        User $seller,
        ?string $buyerProvince,
        ?string $buyerMunicipality,
        ?string $buyerBarangay = null
    ): bool {
        return $this->routeFor($seller, $buyerProvince, $buyerMunicipality, $buyerBarangay) !== null;
    }
}
