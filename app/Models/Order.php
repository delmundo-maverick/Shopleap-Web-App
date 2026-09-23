<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'seller_id',
        'buyer_id',
        'buyer_name',
        'status',
        'total_amount',
        'commission_amount',
    ];

    // Full chain, per the ERP flow: Buyer → Seller → Rider → Sorting Center → Rider → Buyer
    const STATUS_PLACED = 'PLACED';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_PREPARING = 'PREPARING';
    const STATUS_READY_FOR_PICKUP = 'READY_FOR_PICKUP';
    const STATUS_PICKED_UP = 'PICKED_UP';
    const STATUS_AT_SORTING_CENTER = 'AT_SORTING_CENTER';
    const STATUS_SORTED = 'SORTED';
    const STATUS_ASSIGNED_TO_RIDER = 'ASSIGNED_TO_RIDER';
    const STATUS_OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    const STATUS_DELIVERED = 'DELIVERED';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_DELIVERY_FAILED = 'DELIVERY_FAILED';
    const STATUS_RETURNED = 'RETURNED';

    const GROUP_TO_SHIP = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP
    ];

    const GROUP_TO_DELIVER = [
        self::STATUS_PICKED_UP,
        self::STATUS_AT_SORTING_CENTER,
        self::STATUS_SORTED,
        self::STATUS_ASSIGNED_TO_RIDER,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED
    ];

    const GROUP_COMPLETED = [
        self::STATUS_COMPLETED
    ];

    const GROUP_RETURN_REFUND = [
        self::STATUS_RETURNED
    ];

    const GROUP_CANCELLED = [
        self::STATUS_CANCELLED,
        self::STATUS_DELIVERY_FAILED
    ];


    const SELLER_EDITABLE_STATUSES = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_CANCELLED,
    ];

    const TAB_GROUPS = [
        'to_ship' => self::GROUP_TO_SHIP,
        'to_deliver' => self::GROUP_TO_DELIVER,
        'completed' => self::GROUP_COMPLETED,
        'return_refund' => self::GROUP_RETURN_REFUND,
        'cancelled' => self::GROUP_CANCELLED,
    ];

    const TAB_LABELS = [
        'all' => 'All',
        'to_ship' => 'To Ship',
        'to_deliver' => 'To Deliver',
        'completed' => 'Completed',
        'return_refund' => 'Return/Refund',
        'cancelled' => 'Cancelled',
    ];

    const STATUS_LABELS = [
        self::STATUS_PLACED => 'Placed',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_PREPARING => 'Preparing',
        self::STATUS_READY_FOR_PICKUP => 'Ready for Pickup',
        self::STATUS_PICKED_UP => 'Picked Up',
        self::STATUS_AT_SORTING_CENTER => 'At Sorting Center',
        self::STATUS_SORTED => 'Sorted',
        self::STATUS_ASSIGNED_TO_RIDER => 'Assigned to Rider',
        self::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_DELIVERY_FAILED => 'Delivery Failed',
        self::STATUS_RETURNED => 'Returned',
    ];

    const STATUS_COLORS = [
        self::STATUS_PLACED => 'bg-orange-100 text-orange-600',
        self::STATUS_CONFIRMED => 'bg-blue-100 text-blue-600',
        self::STATUS_PREPARING => 'bg-indigo-100 text-indigo-600',
        self::STATUS_READY_FOR_PICKUP => 'bg-indigo-100 text-indigo-600',
        self::STATUS_PICKED_UP => 'bg-primary/10 text-primary',
        self::STATUS_AT_SORTING_CENTER => 'bg-primary/10 text-primary',
        self::STATUS_SORTED => 'bg-primary/10 text-primary',
        self::STATUS_ASSIGNED_TO_RIDER => 'bg-primary/10 text-primary',
        self::STATUS_OUT_FOR_DELIVERY => 'bg-primary/10 text-primary',
        self::STATUS_DELIVERED => 'bg-leaf-green/10 text-leaf-green',
        self::STATUS_COMPLETED => 'bg-leaf-green/10 text-leaf-green',
        self::STATUS_CANCELLED => 'bg-sale-red/10 text-sale-red',
        self::STATUS_DELIVERY_FAILED => 'bg-sale-red/10 text-sale-red',
        self::STATUS_RETURNED => 'bg-sale-red/10 text-sale-red',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? str_replace('_', ' ', $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-light-gray text-charcoal';
    }
}
