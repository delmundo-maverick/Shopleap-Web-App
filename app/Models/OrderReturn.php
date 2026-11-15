<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'items_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'commission_reversal' => 'decimal:2',
            'response_due_at' => 'datetime',
            'seller_responded_at' => 'datetime',
            'escalate_until' => 'datetime',
            'approved_at' => 'datetime',
            'ship_by_at' => 'datetime',
            'return_shipped_at' => 'datetime',
            'receipt_due_at' => 'datetime',
            'return_received_at' => 'datetime',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    const TYPE_REFUND_ONLY = 'refund_only';

    const TYPE_RETURN_AND_REFUND = 'return_and_refund';

    const STATUS_REQUESTED = 'requested';

    const STATUS_APPROVED = 'approved';

    const STATUS_SHIPPED_BACK = 'shipped_back';

    const STATUS_REFUND_PENDING = 'refund_pending';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_DECLINED = 'declined';

    const STATUS_DISPUTED = 'disputed';

    const STATUS_REJECTED = 'rejected';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_EXPIRED = 'expired';

    /** Still in progress: pauses the order's auto-complete. */
    const OPEN_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_APPROVED,
        self::STATUS_SHIPPED_BACK,
        self::STATUS_REFUND_PENDING,
        self::STATUS_DECLINED,
        self::STATUS_DISPUTED,
    ];

    /** A new request is allowed only if every earlier one was cancelled or expired. */
    const BLOCKING_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_APPROVED,
        self::STATUS_SHIPPED_BACK,
        self::STATUS_REFUND_PENDING,
        self::STATUS_DECLINED,
        self::STATUS_DISPUTED,
        self::STATUS_REFUNDED,
        self::STATUS_REJECTED,
    ];

    // Days
    const RESPONSE_DAYS = 3;   // seller answers, or it is approved automatically

    const ESCALATE_DAYS = 3;   // buyer may ask Shopleap to review a declined request

    const SHIP_BACK_DAYS = 7;  // buyer ships the item back

    const RECEIPT_DAYS = 3;    // seller confirms receipt, or it is confirmed automatically

    const MAX_MEDIA = 5;

    /** Refunds that count against sales reports: approved (waiting to be paid) and paid. */
    const REFUND_COUNTED = [
        self::STATUS_REFUND_PENDING,
        self::STATUS_REFUNDED,
    ];

    /** The reason decides the type. "Change of mind" is deliberately not accepted. */
    const REASONS = [
        'not_received' => ['label' => 'I did not receive my parcel', 'type' => self::TYPE_REFUND_ONLY, 'evidence' => false],
        'missing_item' => ['label' => 'Missing item or part', 'type' => self::TYPE_REFUND_ONLY, 'evidence' => true],
        'damaged' => ['label' => 'Item arrived damaged', 'type' => self::TYPE_RETURN_AND_REFUND, 'evidence' => true],
        'wrong_item' => ['label' => 'Wrong item, size or color', 'type' => self::TYPE_RETURN_AND_REFUND, 'evidence' => true],
        'defective' => ['label' => "Item doesn't work as it should", 'type' => self::TYPE_RETURN_AND_REFUND, 'evidence' => true],
        'not_as_described' => ['label' => 'Item is different from the listing', 'type' => self::TYPE_RETURN_AND_REFUND, 'evidence' => true],
    ];

    const REFUND_METHODS = [
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'bank' => 'Bank transfer',
    ];

    const RETURN_COURIERS = ['J&T Express', 'LBC', 'Flash Express', 'JRS Express', 'Lalamove', 'Other'];

    const STATUS_LABELS = [
        self::STATUS_REQUESTED => 'Waiting for seller',
        self::STATUS_APPROVED => 'Approved: ship the item back',
        self::STATUS_SHIPPED_BACK => 'Item shipped back',
        self::STATUS_REFUND_PENDING => 'Refund being processed',
        self::STATUS_REFUNDED => 'Refunded',
        self::STATUS_DECLINED => 'Declined by seller',
        self::STATUS_DISPUTED => 'Under review by Shopleap',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_EXPIRED => 'Expired',
    ];

    const STATUS_COLORS = [
        self::STATUS_REQUESTED => 'bg-orange-100 text-orange-600',
        self::STATUS_APPROVED => 'bg-blue-100 text-blue-600',
        self::STATUS_SHIPPED_BACK => 'bg-indigo-100 text-indigo-600',
        self::STATUS_REFUND_PENDING => 'bg-primary/10 text-primary',
        self::STATUS_REFUNDED => 'bg-leaf-green/10 text-leaf-green',
        self::STATUS_DECLINED => 'bg-sale-red/10 text-sale-red',
        self::STATUS_DISPUTED => 'bg-orange-100 text-orange-600',
        self::STATUS_REJECTED => 'bg-sale-red/10 text-sale-red',
        self::STATUS_CANCELLED => 'bg-light-gray text-charcoal/60',
        self::STATUS_EXPIRED => 'bg-light-gray text-charcoal/60',
    ];

    // ── Relations ──

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    public function media()
    {
        return $this->hasMany(OrderReturnMedia::class)->orderBy('sort_order');
    }

    public function events()
    {
        return $this->hasMany(OrderReturnEvent::class)->orderBy('id');
    }

    // ── Accessors ──

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-light-gray text-charcoal/60';
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason]['label'] ?? $this->reason;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_REFUND_ONLY ? 'Refund only' : 'Return and refund';
    }

    public function getRefundMethodLabelAttribute(): string
    {
        return self::REFUND_METHODS[$this->refund_method] ?? $this->refund_method;
    }

    public function setRefundAccountNumberAttribute(string $value): void
    {
        $this->attributes['refund_account_number'] = substr(preg_replace('/\D/', '', $value), -4);
    }

    /** Only the last four digits are retained and displayed. */
    public function getMaskedAccountAttribute(): string
    {
        $number = preg_replace('/\D/', '', (string) $this->refund_account_number);

        return '•••• '.substr($number, -4);
    }

    /** What changes in the seller's earnings: the items refunded, minus the commission handed back. */
    public function getSellerDeductionAttribute(): float
    {
        return round((float) $this->items_amount - (float) $this->commission_reversal, 2);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
