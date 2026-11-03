<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParcelEvent extends Model
{
    protected $fillable = [
        'order_id',
        'hub_id',
        'rider_id',
        'event_type',
        'from_status',
        'to_status',
        'note',
        'actor_user_id',
    ];

    const TYPE_STATUS_CHANGED = 'status_changed';

    const TYPE_PICKED_UP = 'picked_up';

    const TYPE_SORTED = 'sorted';

    const TYPE_BAG_SEALED = 'bag_sealed';

    const TYPE_BAG_DISPATCHED = 'bag_dispatched';

    const TYPE_BAG_RECEIVED = 'bag_received';

    const TYPE_BAG_MISSING = 'bag_missing';

    const TYPE_ASSIGNED = 'assigned_to_rider';

    const TYPE_OUT_FOR_DELIVERY = 'out_for_delivery';

    const TYPE_DELIVERED = 'delivered';

    const TYPE_DELIVERY_ATTEMPT = 'delivery_attempt';

    const TYPE_RETURNED = 'returned';

    const TYPE_CANCELLED = 'cancelled';

    const TYPE_COMPLETED = 'completed';

    const TYPE_STALE = 'stale';

    const TYPE_LABELS = [
        self::TYPE_STATUS_CHANGED => 'Status updated',
        self::TYPE_PICKED_UP => 'Parcel picked up',
        self::TYPE_SORTED => 'Sorted for delivery',
        self::TYPE_BAG_SEALED => 'Parcel sealed in bag',
        self::TYPE_BAG_DISPATCHED => 'Bag dispatched',
        self::TYPE_BAG_RECEIVED => 'Arrived at hub',
        self::TYPE_BAG_MISSING => 'Parcel missing from bag',
        self::TYPE_ASSIGNED => 'Rider assigned for delivery',
        self::TYPE_OUT_FOR_DELIVERY => 'Out for delivery',
        self::TYPE_DELIVERED => 'Parcel delivered',
        self::TYPE_DELIVERY_ATTEMPT => 'Delivery attempt unsuccessful',
        self::TYPE_RETURNED => 'Parcel returned to seller',
        self::TYPE_CANCELLED => 'Order cancelled',
        self::TYPE_COMPLETED => 'Order completed',
        self::TYPE_STALE => 'Parcel needs attention',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function hub()
    {
        return $this->belongsTo(LogisticsProfile::class, 'hub_id');
    }

    public function rider()
    {
        return $this->belongsTo(CourierProfile::class, 'rider_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function getTitleAttribute(): string
    {
        if ($this->event_type === self::TYPE_STATUS_CHANGED && $this->to_status) {
            return Order::TIMELINE_TITLES[$this->to_status]
                ?? str_replace('_', ' ', $this->to_status);
        }

        if ($this->event_type === self::TYPE_BAG_RECEIVED && $this->hub?->business_name) {
            return 'Arrived at '.$this->hub->business_name.' hub';
        }

        return self::TYPE_LABELS[$this->event_type]
            ?? str_replace('_', ' ', $this->event_type);
    }
}
