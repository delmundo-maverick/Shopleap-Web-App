<?php

namespace App\Services;

use App\Models\ShippingRate;
use App\Models\User;

class ShippingFeeCalculator
{
    /** Fee for ONE seller's parcel. Call once per seller group at checkout. */
    public function calculate(User $seller, ?string $province, ?string $municipality): array
    {
        $zone = $this->resolveZone($seller, $province, $municipality);

        $rate = ShippingRate::where('zone', $zone)->where('is_active', true)->first();
        $fee = $rate ? (float) $rate->fee : (float) ShippingRate::FALLBACK_FEES[$zone];

        return ['fee' => round($fee, 2), 'zone' => $zone];
    }

    public function resolveZone(User $seller, ?string $province, ?string $municipality): string
    {
        $profile = $seller->sellerProfile;

        $sellerProvince = $this->normalize($profile?->province);
        $sellerMunicipality = $this->normalize($profile?->municipality);
        $buyerProvince = $this->normalize($province);
        $buyerMunicipality = $this->normalize($municipality);

        if ($sellerProvince === '' || $buyerProvince === '' || $sellerProvince !== $buyerProvince) {
            return ShippingRate::ZONE_DIFFERENT_PROVINCE;
        }

        if ($sellerMunicipality !== '' && $sellerMunicipality === $buyerMunicipality) {
            return ShippingRate::ZONE_SAME_MUNICIPALITY;
        }

        return ShippingRate::ZONE_SAME_PROVINCE;
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }
}
