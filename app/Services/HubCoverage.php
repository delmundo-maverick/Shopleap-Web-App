<?php

namespace App\Services;

use App\Models\LogisticsProfile;
use App\Models\User;

class HubCoverage
{
    public function hubsFor(User $seller, ?string $buyerProvince, ?string $buyerMunicipality)
    {
        $profile = $seller->sellerProfile;

        return LogisticsProfile::serving(
            $profile?->province,
            $profile?->municipality,
            $buyerProvince,
            $buyerMunicipality,
        );
    }

    public function isCovered(User $seller, ?string $buyerProvince, ?string $buyerMunicipality): bool
    {
        return $this->hubsFor($seller, $buyerProvince, $buyerMunicipality)->exists();
    }
}
