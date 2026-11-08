<?php

namespace App\Models;

use App\Services\Notifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'seller_id',
        'buyer_id',
        'address_id',
        'shipping_address',
        'buyer_name',
        'status',
        'total_amount',
        'shipping_fee',
        'shipping_zone',
        'buyer_note',
        'commission_amount',
        'shop_voucher_id',
        'voucher_code',
        'voucher_discount',
        'ship_province',
        'ship_municipality',
        'ship_barangay',
        'ship_street',
        'ship_contact_no',
        'logistics_profile_id',
        'origin_hub_id',
        'destination_hub_id',
        'current_hub_id',
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
        'delivered_at',
        'cod_collected_amount',
        'cod_collected_at',

        // Cancellation details
        'cancelled_by',
        'cancel_reason',
        'cancel_note',
        'cancelled_at',
        'cancel_request_status',
        'cancel_request_reason',
        'cancel_request_note',
        'cancel_requested_at',
        'cancel_request_response',
        'cancel_request_resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'shipping_fee' => 'decimal:2',
            'rider_pay' => 'decimal:2',
            'delivered_shipping_fee' => 'decimal:2',
            'earnings_recorded_at' => 'datetime',
            'voucher_discount' => 'decimal:2',
            'pickup_date' => 'date',
            'waybill_printed_at' => 'datetime',
            'stock_restored_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cod_collected_at' => 'datetime',
            'cod_collected_amount' => 'decimal:2',

            // Cancellation timestamps
            'cancelled_at' => 'datetime',
            'cancel_requested_at' => 'datetime',
            'cancel_request_resolved_at' => 'datetime',
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

    const STATUS_AWAITING_TRANSFER = 'AWAITING_TRANSFER';

    const STATUS_ASSIGNED_TO_RIDER = 'ASSIGNED_TO_RIDER';

    const STATUS_OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';

    const STATUS_DELIVERED = 'DELIVERED';

    const STATUS_COMPLETED = 'COMPLETED';

    const STATUS_CANCELLED = 'CANCELLED';

    const STATUS_DELIVERY_FAILED = 'DELIVERY_FAILED';

    const STATUS_RETURNED = 'RETURNED';

    // Seller: statuses they may set by hand.
    // READY_FOR_PICKUP is only entered through the pickup request form.
    const SELLER_EDITABLE_STATUSES = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
    ];

    const SELLER_MODIFIABLE_FROM = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
    ];

    const SELLER_TRANSITIONS = [
        self::STATUS_PLACED => [
            self::STATUS_CONFIRMED,
        ],
        self::STATUS_CONFIRMED => [
            self::STATUS_PREPARING,
        ],
        self::STATUS_PREPARING => [],
    ];

    // Seller sidebar links
    const GROUP_NEW = [
        self::STATUS_PLACED,
    ];

    const GROUP_PREPARE = [
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
    ];

    // Buyer order tabs. DELIVERY_FAILED is recoverable (reschedule),
    // so it stays under "To Deliver".
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
        self::STATUS_AWAITING_TRANSFER,
        self::STATUS_ASSIGNED_TO_RIDER,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERY_FAILED,
    ];

    const GROUP_COMPLETED = [
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
    ];

    const GROUP_RETURN_REFUND = [
        self::STATUS_RETURNED,
    ];

    const GROUP_CANCELLED = [
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

    // Seller reports
    const GROUP_REALIZED = [
        self::STATUS_COMPLETED,
    ];

    const GROUP_IN_PROGRESS = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_PICKED_UP,
        self::STATUS_AT_SORTING_CENTER,
        self::STATUS_SORTED,
        self::STATUS_AWAITING_TRANSFER,
        self::STATUS_ASSIGNED_TO_RIDER,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_DELIVERY_FAILED,
    ];

    const GROUP_LOST = [
        self::STATUS_CANCELLED,
        self::STATUS_RETURNED,
    ];

    // Shipping fees are earned by logistics once the parcel is delivered
    // and the COD cash is collected. GMV and commission still count only at COMPLETED.
    const GROUP_SHIPPING_EARNED = [
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
    ];

    // A DELIVERED order completes by itself if the buyer never confirms.
    const AUTO_COMPLETE_DAYS = 7;

    // Return/refund requests are accepted for this many days after delivery.
    // The admin setting "return_window_days" overrides it.
    const RETURN_WINDOW_DAYS = 7;

    // Waybill can be (re)printed once the seller is preparing the order.
    const WAYBILL_STATUSES = [
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_PICKED_UP,
        self::STATUS_AT_SORTING_CENTER,
        self::STATUS_SORTED,
        self::STATUS_AWAITING_TRANSFER,
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

    // Buyer cancellation rules
    const BUYER_CANCEL_INSTANT_FROM = [
        self::STATUS_PLACED,
    ];

    const BUYER_CANCEL_REQUEST_FROM = [
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
    ];

    // Seller cancellation rules
    const SELLER_CANCEL_FROM = [
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_PICKUP,
    ];

    const CANCEL_REQUEST_AUTO_APPROVE_HOURS = 24;

    const CANCELLED_BY_LABELS = [
        'buyer' => 'the buyer',
        'seller' => 'the seller',
        'system' => 'Shopleap',
    ];

    const BUYER_CANCEL_REASONS = [
        'Changed my mind',
        'Want to change delivery address',
        'Want to change items or variation',
        'Found a better price elsewhere',
        'Ordered by mistake',
        'Seller is taking too long',
        'Other',
    ];

    const SELLER_CANCEL_REASONS = [
        'Out of stock',
        'Buyer asked to cancel',
        'Unable to fulfil the order',
        'Incorrect price or listing details',
        'Unable to reach the buyer',
        'Other',
    ];

    const STATUS_LABELS = [
        self::STATUS_PLACED => 'Placed',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_PREPARING => 'Preparing',
        self::STATUS_READY_FOR_PICKUP => 'Ready for Pickup',
        self::STATUS_PICKED_UP => 'Picked Up',
        self::STATUS_AT_SORTING_CENTER => 'At Sorting Center',
        self::STATUS_SORTED => 'Sorted',
        self::STATUS_AWAITING_TRANSFER => 'Awaiting Transfer',
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
        self::STATUS_AWAITING_TRANSFER => 'bg-orange-100 text-orange-600',
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
        self::STATUS_AWAITING_TRANSFER => 'Parcel awaiting transfer to its destination hub',
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

    public function shopVoucher()
    {
        return $this->belongsTo(ShopVoucher::class);
    }

    public function voucherRedemption()
    {
        return $this->hasOne(ShopVoucherRedemption::class);
    }

    /**
     * Total parcel weight for this order, in kilograms.
     * Old order items predate the weight snapshot and are treated as 0.
     */
    public function getTotalWeightKgAttribute(): float
    {
        return round((float) $this->items->sum(
            fn (OrderItem $item) => (float) ($item->weight_kg ?? 0)
        ), 3);
    }

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    /** The hub that owns the pickup leg. Set when the seller chooses a hub. */
    public function originHub()
    {
        return $this->belongsTo(LogisticsProfile::class, 'origin_hub_id');
    }

    /** The hub that serves the delivery address. Set when the route is resolved. */
    public function destinationHub()
    {
        return $this->belongsTo(LogisticsProfile::class, 'destination_hub_id');
    }

    /** The hub physically holding the parcel right now. */
    public function currentHub()
    {
        return $this->belongsTo(LogisticsProfile::class, 'current_hub_id');
    }

    /** Orders currently held by a hub (current hub or the hub that owns the order). */
    public function scopeHeldBy($query, int $hubId)
    {
        return $query->where(function ($q) use ($hubId) {
            $q->where('current_hub_id', $hubId)
                ->orWhere('logistics_profile_id', $hubId);
        });
    }

    /** Orders that are not yet final (still in motion). */
    public function scopeNonFinal($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PLACED,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_PICKED_UP,
            self::STATUS_AT_SORTING_CENTER,
            self::STATUS_SORTED,
            self::STATUS_AWAITING_TRANSFER,
            self::STATUS_ASSIGNED_TO_RIDER,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERY_FAILED,
        ]);
    }

    /** Orders whose pickup leg belongs to a hub. */
    public function scopeOriginatingFrom($query, int $hubId)
    {
        return $query->where('origin_hub_id', $hubId);
    }

    public function pickupRider()
    {
        return $this->belongsTo(CourierProfile::class, 'pickup_rider_id');
    }

    public function earningCourier(): BelongsTo
    {
        return $this->belongsTo(CourierProfile::class, 'earning_courier_id');
    }

    public function deliveryRider()
    {
        return $this->belongsTo(CourierProfile::class, 'delivery_rider_id');
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    public function parcelEvents()
    {
        return $this->hasMany(ParcelEvent::class)->orderBy('id');
    }

    public function shipmentBagItems()
    {
        return $this->hasMany(ShipmentBagItem::class);
    }

    // ── Accessors ──

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]
            ?? str_replace('_', ' ', $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status]
            ?? 'bg-light-gray text-charcoal';
    }

    /**
     * What the buyer actually pays (COD amount): items + shipping.
     * total_amount remains items-only.
     */
    public function getGrandTotalAttribute(): float
    {
        return (float) $this->total_amount + (float) $this->shipping_fee;
    }

    /**
     * Commission rate that applied to this order.
     * Calculated from the saved amounts, so later rate changes do not alter it.
     */
    public function getCommissionRateLabelAttribute(): string
    {
        if ((float) $this->total_amount <= 0) {
            return '0%';
        }

        $rate = round(
            (float) $this->commission_amount
                / (float) $this->total_amount
                * 100,
            2
        );

        return rtrim(rtrim(number_format($rate, 2), '0'), '.').'%';
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(BuyerAddress::class)->withTrashed();
    }

    /**
     * Delivery destination. Reads the checkout snapshot, falling back to the
     * buyer's live profile for orders placed before snapshots existed.
     */
    public function getShipToAttribute(): array
    {
        if ($this->shipping_address !== null) {
            return $this->shipping_address;
        }

        $p = $this->buyer?->buyerProfile;

        return [
            'name' => $this->buyer_name ?? $this->buyer?->name,
            'contact_no' => $this->ship_contact_no ?? $p?->contact_no,
            'province' => $this->ship_province ?? $p?->province,
            'municipality' => $this->ship_municipality ?? $p?->municipality,
            'barangay' => $this->ship_barangay ?? $p?->barangay,
            'street' => $this->ship_street
                ?? ($p
                    ? trim(
                        ($p->house_number ?? '')
                            .' '
                            .($p->street ?? '')
                    )
                    : null),
        ];
    }

    // ── Returns / refunds ──

    public function returns()
    {
        return $this->hasMany(OrderReturn::class);
    }

    public static function returnWindowDays(): int
    {
        return max(
            1,
            (int) PlatformSetting::get(
                'return_window_days',
                self::RETURN_WINDOW_DAYS
            )
        );
    }

    public function returnWindowEndsAt(): ?Carbon
    {
        return $this->delivered_at?->copy()->addDays(
            self::returnWindowDays()
        );
    }

    /**
     * The return/refund button exists only once the courier has delivered
     * the parcel, inside the window, and while no earlier request is open
     * or settled.
     */
    public function canRequestReturn(): bool
    {
        if (
            ! in_array(
                $this->status,
                [self::STATUS_DELIVERED, self::STATUS_COMPLETED],
                true
            )
            || ! $this->delivered_at
        ) {
            return false;
        }

        if (now()->greaterThan($this->returnWindowEndsAt())) {
            return false;
        }

        $returns = $this->relationLoaded('returns')
            ? $this->returns
            : $this->returns()->get();

        return $returns
            ->whereIn('status', OrderReturn::BLOCKING_STATUSES)
            ->isEmpty();
    }

    /**
     * The buyer's Return/Refund tab: parcels sent back after a failed
     * delivery, plus orders that have return/refund requests.
     */
    public function scopeReturnOrRefund($query)
    {
        return $query->where(
            fn ($q) => $q
                ->whereIn('status', self::GROUP_RETURN_REFUND)
                ->orWhereHas('returns')
        );
    }

    public function scopeWithoutReturnRequests($query)
    {
        return $query->whereDoesntHave(
            'returns',
            fn ($q) => $q->whereIn('status', OrderReturn::BLOCKING_STATUSES)
        );
    }

    // ── Refunds in reports ──

    public function scopeWithRefundTotals($query)
    {
        $counted = fn ($q) => $q->whereIn('status', OrderReturn::REFUND_COUNTED);

        return $query
            ->withSum(['returns as refunded_items' => $counted], 'items_amount')
            ->withSum(['returns as refunded_commission' => $counted], 'commission_reversal')
            ->withSum(['returns as refunded_shipping' => $counted], 'shipping_amount');
    }

    public function getNetItemsAmountAttribute(): float
    {
        return round((float) $this->total_amount - (float) ($this->refunded_items ?? 0), 2);
    }

    public function getNetCommissionAmountAttribute(): float
    {
        return round((float) $this->commission_amount - (float) ($this->refunded_commission ?? 0), 2);
    }

    public function getNetShippingFeeAttribute(): float
    {
        return round((float) $this->shipping_fee - (float) ($this->refunded_shipping ?? 0), 2);
    }

    public function getRefundedTotalAttribute(): float
    {
        return round((float) ($this->refunded_items ?? 0) + (float) ($this->refunded_shipping ?? 0), 2);
    }

    // ── Cancellation helpers ──

    public function buyerCancelMode(): ?string
    {
        if (
            in_array(
                $this->status,
                self::BUYER_CANCEL_INSTANT_FROM,
                true
            )
        ) {
            return 'instant';
        }

        if (
            in_array(
                $this->status,
                self::BUYER_CANCEL_REQUEST_FROM,
                true
            )
            && in_array(
                $this->cancel_request_status,
                [null, 'withdrawn'],
                true
            )
        ) {
            return 'request';
        }

        return null;
    }

    public function scopePendingCancelRequest($query)
    {
        return $query
            ->where('cancel_request_status', 'pending')
            ->whereIn('status', self::BUYER_CANCEL_REQUEST_FROM);
    }

    // ── Tracking timeline ──

    /**
     * Public tracking rows for the buyer and seller timelines.
     * Parcel events are the source of truth; orders created before they
     * existed fall back to the older order events.
     *
     * @return Collection<int, array{id: int, title: string, note: string|null, created_at: Carbon}>
     */
    public function trackingTimeline(): Collection
    {
        $parcelEvents = $this->relationLoaded('parcelEvents')
            ? $this->parcelEvents
            : $this->parcelEvents()->with('hub')->get();

        if ($parcelEvents->isNotEmpty()) {
            return $parcelEvents
                ->reject(fn (ParcelEvent $event) => $event->event_type === ParcelEvent::TYPE_STALE)
                ->map(fn (ParcelEvent $event) => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'note' => $event->note,
                    'created_at' => $event->created_at,
                ])
                ->sortByDesc('id')
                ->values();
        }

        $events = $this->relationLoaded('events')
            ? $this->events
            : $this->events()->get();

        return $events
            ->whereNotNull('status')
            ->map(fn (OrderEvent $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'note' => $event->note,
                'created_at' => $event->created_at,
            ])
            ->sortByDesc('id')
            ->values();
    }

    public function logStatusEvent(
        ?string $note = null,
        ?int $actorId = null
    ): OrderEvent {
        $actorId = $actorId ?? Auth::id();
        $status = $this->status;

        $event = $this->events()->create([
            'actor_id' => $actorId,
            'status' => $status,
            'title' => self::TIMELINE_TITLES[$status] ?? $this->status_label,
            'note' => $note,
        ]);

        $order = $this;

        DB::afterCommit(function () use ($order, $status, $note, $actorId) {
            try {
                app(Notifier::class)
                    ->orderStatusChanged($order->fresh() ?? $order, $status, $note, $actorId);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return $event;
    }

    /** Internal note — seller and logistics only, never shown to the buyer. */
    public function logInternalEvent(
        string $title,
        ?string $note = null,
        ?int $actorId = null
    ): OrderEvent {
        return $this->events()->create([
            'actor_id' => $actorId ?? Auth::id(),
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

        DB::transaction(function () {
            // Re-check under a row lock so two simultaneous cancels cannot both restore.
            $locked = static::whereKey($this->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->stock_restored_at) {
                return;
            }

            $items = $this->items()->get();

            // Same lock order as checkout and the seller editor:
            // products, then variants, ascending ID.
            $products = Product::whereIn(
                'id',
                $items->pluck('product_id')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
            )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            ProductVariant::whereIn(
                'id',
                $items->pluck('product_variant_id')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
            )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $touchedVariantProducts = [];

            foreach ($items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::whereKey($item->product_variant_id)
                        ->increment('stock_quantity', $item->quantity);

                    if ($products->has($item->product_id)) {
                        $touchedVariantProducts[$item->product_id] =
                            $products->get($item->product_id);
                    }
                } elseif (
                    $item->product_id
                    && empty($item->selected_options)
                ) {
                    // A line with options but no variant ID may reference a
                    // variant that was deleted. Do not add that stock to the
                    // base product.
                    Product::whereKey($item->product_id)
                        ->increment('stock_quantity', $item->quantity);
                }
            }

            // The product row holds the total across variants, so recompute it.
            foreach ($touchedVariantProducts as $product) {
                $product->syncFromVariants();
            }

            $this->update([
                'stock_restored_at' => now(),
            ]);
        });
    }
}
