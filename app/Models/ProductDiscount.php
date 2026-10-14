<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDiscount extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';

    /** Highest percentage a seller may set. */
    public const MAX_PERCENT = 90;

    protected $fillable = [
        'product_id',
        'seller_id',
        'type',
        'value',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /** Discounts buyers can benefit from right now. */
    public function scopeLive($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    /** live | scheduled | ended | paused — for the seller UI. */
    public function getStateAttribute(): string
    {
        if (!$this->is_active) {
            return 'paused';
        }

        if ($this->starts_at?->isFuture()) {
            return 'scheduled';
        }

        if ($this->ends_at && $this->ends_at->lte(now())) {
            return 'ended';
        }

        return 'live';
    }

    /**
     * The single place the discount maths lives. Never returns a price that is
     * zero, negative, or higher than the original: in those cases there is
     * simply no discount (e.g. a fixed ₱50 off on a product the seller later
     * repriced to ₱40).
     */
    public function apply(float $price): float
    {
        $value = (float) $this->value;

        $discounted = $this->type === self::TYPE_PERCENT
            ? round($price * (1 - $value / 100), 2)
            : round($price - $value, 2);

        return ($discounted > 0 && $discounted < $price) ? $discounted : $price;
    }

    /** "20% OFF" / "₱50 OFF" */
    public function getLabelAttribute(): string
    {
        $value = (float) $this->value;

        return $this->type === self::TYPE_PERCENT
            ? rtrim(rtrim(number_format($value, 2), '0'), '.') . '% OFF'
            : '₱' . number_format($value, 2) . ' OFF';
    }
}
