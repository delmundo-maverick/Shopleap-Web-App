<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Super admins keep full visibility across every hub. */
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'super_admin' ? true : null;
    }

    /** The hub that currently holds the parcel may act on it. */
    public function actAsHoldingHub(User $user, Order $order): bool
    {
        $hub = $user->role === 'logistics' ? $user->logisticsProfile : null;
        $holdingHubId = $order->current_hub_id ?? $order->logistics_profile_id;

        return $hub !== null
            && $holdingHubId !== null
            && (int) $holdingHubId === (int) $hub->id;
    }

    /** Pickup work belongs to the hub that requested/owns the pickup. */
    public function actAsOriginHub(User $user, Order $order): bool
    {
        $hub = $user->role === 'logistics' ? $user->logisticsProfile : null;

        if ($hub === null) {
            return false;
        }

        $originId = $order->origin_hub_id ?? $order->logistics_profile_id;

        return $originId !== null && (int) $originId === (int) $hub->id;
    }
}
