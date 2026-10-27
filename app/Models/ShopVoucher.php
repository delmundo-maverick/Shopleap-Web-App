<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShopVoucher extends Model
{
    protected $fillable = [
        'seller_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_purchase_amount',
        'max_discount_amount',
        'total_quantity',
        'used_quantity',
        'per_buyer_limit',
        'starts_at',
        'expires_at',
        'status',
        'is_active',
        'combine_with_promotions',
        'eligible_product_ids',
        'eligible_category_ids',
        'min_quantity',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_purchase_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'total_quantity' => 'integer',
            'used_quantity' => 'integer',
            'per_buyer_limit' => 'integer',
            'min_quantity' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'combine_with_promotions' => 'boolean',
            'eligible_product_ids' => 'array',
            'eligible_category_ids' => 'array',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function redemptions()
    {
        return $this->hasMany(ShopVoucherRedemption::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public static function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function getRemainingQuantityAttribute(): ?int
    {
        if (is_null($this->total_quantity)) {
            return null;
        }

        return max(0, $this->total_quantity - $this->used_quantity);
    }

    public function isValidNow(): bool
    {
        $now = now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->status === 'disabled' || $this->status === 'expired' || $this->status === 'exhausted') {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if (! is_null($this->total_quantity) && $this->used_quantity >= $this->total_quantity) {
            return false;
        }

        return true;
    }

    public function canBeUsedByBuyer($buyerId): bool
    {
        if (! $this->isValidNow()) {
            return false;
        }

        if (! $this->per_buyer_limit) {
            return true;
        }

        $used = $this->redemptions()
            ->where('buyer_id', $buyerId)
            ->whereIn('status', ['applied', 'used'])
            ->count();

        return $used < $this->per_buyer_limit;
    }

    public function calculateDiscount(float $eligibleSubtotal, int $eligibleQuantity = 0): float
    {
        if ($eligibleSubtotal < (float) $this->min_purchase_amount) {
            return 0.0;
        }

        if ($this->min_quantity && $eligibleQuantity < $this->min_quantity) {
            return 0.0;
        }

        $discount = 0.0;

        if ($this->discount_type === 'percentage') {
            $discount = round($eligibleSubtotal * ((float) $this->discount_value / 100), 2);
            if (! is_null($this->max_discount_amount)) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }
        } else {
            $discount = round((float) $this->discount_value, 2);
        }

        if ($discount > $eligibleSubtotal) {
            $discount = $eligibleSubtotal;
        }

        if ($discount < 0) {
            $discount = 0.0;
        }

        return round($discount, 2);
    }
}
