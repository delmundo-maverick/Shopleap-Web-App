<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

class MessagingPolicy
{
    public static function canMessage(User $a, User $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        $allowed = config('messaging_permissions');

        $roleOk = in_array($b->role, $allowed[$a->role] ?? [], true)
            || in_array($a->role, $allowed[$b->role] ?? [], true);

        return $roleOk && self::courierScopeOk($a, $b);
    }

    /**
     * Restrict who can contact a courier.
     *
     * - Super admins can contact any courier.
     * - Logistics users can contact couriers assigned to their hub.
     * - Sellers can contact couriers assigned to their orders
     *   that are still waiting for pickup.
     */
    private static function courierScopeOk(User $a, User $b): bool
    {
        [$rider, $other] = match (true) {
            $a->role === 'courier' => [$a, $b],
            $b->role === 'courier' => [$b, $a],
            default => [null, null],
        };

        if (! $rider) {
            return true;
        }

        $profile = $rider->courierProfile;

        if (! $profile) {
            return false;
        }

        return match ($other->role) {
            'super_admin' => true,

            'logistics' =>
            $other->logisticsProfile?->id === $profile->logistics_profile_id,

            'seller' => Order::where('pickup_rider_id', $profile->id)
                ->where('seller_id', $other->id)
                ->where('status', Order::STATUS_READY_FOR_PICKUP)
                ->exists(),

            default => false,
        };
    }

    public static function allowedRolesFor(User $user): array
    {
        return config('messaging_permissions')[$user->role] ?? [];
    }
}
