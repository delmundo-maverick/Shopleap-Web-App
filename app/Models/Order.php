<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'seller_id',
        'buyer_id',
        'buyer_name',
        'status',
        'total_amount',
        'commission_amount',
        'ship_province',
        'ship_municipality',
        'ship_barangay',
        'ship_street',
        'ship_contact_no',
        'logistics_profile_id',
        'pickup_rider_id',
        'delivery_rider_id',
        'delivery_area',
        'pickup_status',
        'pickup_date',
        'pickup_time_slot',
        'pickup_decline_reason',
        'delivery_attempts',
        'waybill_printed_at',
        'stock_restored_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'waybill_printed_at' => 'datetime',
            'stock_restored_at' => 'datetime',
        ];
    }

    // ── Status chain (Buyer → Seller → Rider → Sorting Center → Rider → Buyer) ──
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

    // Seller: statuses they may set by hand. READY_FOR_PICKUP is deliberately absent —
    // it can only be entered through the "Request pickup" form (hub + date + window).
    const SELLER_EDITABLE_STATUSES = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_CANCELLED,
    ];
    const SELLER_MODIFIABLE_FROM = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
    ];

    // Seller sidebar links
    const GROUP_NEW = [self::STATUS_PLACED];
    const GROUP_PREPARE = [self::STATUS_CONFIRMED, self::STATUS_PREPARING, self::STATUS_READY_FOR_PICKUP];

    // Buyer order tabs. DELIVERY_FAILED is recoverable (reschedule), so it stays under "To Deliver".
    const GROUP_TO_SHIP = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
    ];
    const GROUP_TO_DELIVER = [
        self::STATUS_PICKED_UP,
        self::STATUS_AT_SORTING_CENTER,
        self::STATUS_SORTED,
        self::STATUS_ASSIGNED_TO_RIDER,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_DELIVERY_FAILED,
    ];
    const GROUP_COMPLETED = [self::STATUS_COMPLETED];
    const GROUP_RETURN_REFUND = [self::STATUS_RETURNED];
    const GROUP_CANCELLED = [self::STATUS_CANCELLED];

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

    // Seller reports
    const GROUP_REALIZED = [self::STATUS_COMPLETED];
    const GROUP_IN_PROGRESS = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_PICKED_UP,
        self::STATUS_AT_SORTING_CENTER,
        self::STATUS_SORTED,
        self::STATUS_ASSIGNED_TO_RIDER,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_DELIVERY_FAILED,
    ];
    const GROUP_LOST = [self::STATUS_CANCELLED, self::STATUS_RETURNED];

    // Waybill can be (re)printed once the seller is preparing the order
    const WAYBILL_STATUSES = [
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_PICKED_UP,
        self::STATUS_AT_SORTING_CENTER,
        self::STATUS_SORTED,
        self::STATUS_ASSIGNED_TO_RIDER,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
    ];

    const PICKUP_SLOTS = [
        'morning' => 'Morning (8:00 AM – 12:00 PM)',
        'afternoon' => 'Afternoon (1:00 PM – 5:00 PM)',
    ];

    const MAX_DELIVERY_ATTEMPTS = 3;

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

    // Buyer-facing wording for the tracking timeline
    const TIMELINE_TITLES = [
        self::STATUS_PLACED => 'Order placed',
        self::STATUS_CONFIRMED => 'Seller confirmed your order',
        self::STATUS_PREPARING => 'Seller is preparing your order',
        self::STATUS_READY_FOR_PICKUP => 'Parcel is packed and ready for pickup',
        self::STATUS_PICKED_UP => 'Parcel picked up by courier',
        self::STATUS_AT_SORTING_CENTER => 'Parcel arrived at the sorting center',
        self::STATUS_SORTED => 'Parcel sorted for delivery',
        self::STATUS_ASSIGNED_TO_RIDER => 'Rider assigned for delivery',
        self::STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
        self::STATUS_DELIVERED => 'Parcel delivered',
        self::STATUS_COMPLETED => 'Order completed',
        self::STATUS_CANCELLED => 'Order cancelled',
        self::STATUS_DELIVERY_FAILED => 'Delivery attempt unsuccessful',
        self::STATUS_RETURNED => 'Parcel returned to seller',
    ];

    // ── Relations ──
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

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function pickupRider()
    {
        return $this->belongsTo(CourierProfile::class, 'pickup_rider_id');
    }

    public function deliveryRider()
    {
        return $this->belongsTo(CourierProfile::class, 'delivery_rider_id');
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    // ── Accessors ──
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? str_replace('_', ' ', $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-light-gray text-charcoal';
    }

    /**
     * Delivery destination. Reads the checkout snapshot, falling back to the
     * buyer's live profile for orders placed before snapshots existed.
     */
    public function getShipToAttribute(): array
    {
        $p = $this->buyer?->buyerProfile;

        return [
            'name' => $this->buyer_name ?? $this->buyer?->name,
            'contact_no' => $this->ship_contact_no ?? $p?->contact_no,
            'province' => $this->ship_province ?? $p?->province,
            'municipality' => $this->ship_municipality ?? $p?->municipality,
            'barangay' => $this->ship_barangay ?? $p?->barangay,
            'street' => $this->ship_street ?? ($p ? trim(($p->house_number ?? '') . ' ' . ($p->street ?? '')) : null),
        ];
    }

    // ── Tracking timeline ──

    /** Public event — shown to the buyer, seller and logistics. */
    public function logStatusEvent(?string $note = null, ?int $actorId = null): OrderEvent
    {
        return $this->events()->create([
            'actor_id' => $actorId ?? auth()->id(),
            'status' => $this->status,
            'title' => self::TIMELINE_TITLES[$this->status] ?? $this->status_label,
            'note' => $note,
        ]);
    }

    /** Internal note — seller and logistics only, never shown to the buyer. */
    public function logInternalEvent(string $title, ?string $note = null, ?int $actorId = null): OrderEvent
    {
        return $this->events()->create([
            'actor_id' => $actorId ?? auth()->id(),
            'status' => null,
            'title' => $title,
            'note' => $note,
        ]);
    }

    /** Put reserved stock back after a cancellation or return. Safe to call twice. */
    public function restoreStock(): void
    {
        if ($this->stock_restored_at) {
            return;
        }

        $this->loadMissing('items');

        DB::transaction(function () {
            foreach ($this->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::whereKey($item->product_variant_id)->increment('stock_quantity', $item->quantity);
                } elseif ($item->product_id) {
                    Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
                }
            }

            $this->update(['stock_restored_at' => now()]);
        });
    }
}
