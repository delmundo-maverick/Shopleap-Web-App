<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PlatformSetting;

class RiderEarnings
{
    /** A delivery counts once the parcel is delivered. */
    public const EARNED_STATUSES = [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED];

    /** Flat fee per delivered parcel. Admin can change it via the platform setting `rider_delivery_fee`. */
    public static function feePerDelivery(): float
    {
        return max(0, (float) PlatformSetting::get('rider_delivery_fee', 50));
    }
}
