<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentBag extends Model
{
    protected $fillable = [
        'code',
        'from_hub_id',
        'to_hub_id',
        'status',
        'created_by',
        'sealed_by',
        'received_by',
        'manifest_printed_by',
        'sealed_at',
        'dispatched_at',
        'received_at',
        'manifest_printed_at',
        'flagged_at',
        'flag_note',
    ];

    protected function casts(): array
    {
        return [
            'sealed_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
            'manifest_printed_at' => 'datetime',
            'flagged_at' => 'datetime',
        ];
    }

    const STATUS_OPEN = 'open';

    const STATUS_SEALED = 'sealed';

    const STATUS_IN_TRANSIT = 'in_transit';

    const STATUS_RECEIVED = 'received';

    const STATUS_DISCREPANCY = 'discrepancy';

    const ACTIVE_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_SEALED,
        self::STATUS_IN_TRANSIT,
    ];

    const STATUS_LABELS = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_SEALED => 'Sealed',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_RECEIVED => 'Received',
        self::STATUS_DISCREPANCY => 'Discrepancy',
    ];

    const STATUS_COLORS = [
        self::STATUS_OPEN => 'bg-orange-100 text-orange-600',
        self::STATUS_SEALED => 'bg-indigo-100 text-indigo-600',
        self::STATUS_IN_TRANSIT => 'bg-primary/10 text-primary',
        self::STATUS_RECEIVED => 'bg-leaf-green/10 text-leaf-green',
        self::STATUS_DISCREPANCY => 'bg-sale-red/10 text-sale-red',
    ];

    public function fromHub()
    {
        return $this->belongsTo(LogisticsProfile::class, 'from_hub_id');
    }

    public function toHub()
    {
        return $this->belongsTo(LogisticsProfile::class, 'to_hub_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sealer()
    {
        return $this->belongsTo(User::class, 'sealed_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function manifestPrinter()
    {
        return $this->belongsTo(User::class, 'manifest_printed_by');
    }

    public function items()
    {
        return $this->hasMany(ShipmentBagItem::class, 'bag_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'shipment_bag_items', 'bag_id', 'order_id')
            ->withPivot('received_at')
            ->withTimestamps();
    }

    public function scopeSentBy($query, int $hubId)
    {
        return $query->where('from_hub_id', $hubId);
    }

    public function scopeAddressedTo($query, int $hubId)
    {
        return $query->where('to_hub_id', $hubId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isSealed(): bool
    {
        return $this->status === self::STATUS_SEALED;
    }

    public function isInTransit(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
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
