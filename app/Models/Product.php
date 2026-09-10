<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'seller_id', 'name', 'description', 'category', 'subcategory',
        'price', 'discount_price', 'stock_quantity', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * The price to actually display — the discounted price when one is
     * set, otherwise the regular price.
     */
    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->discount_price ?? $this->price);
    }

    /**
     * The original price to show struck-through, only when a discount
     * is actually active. Null (not shown) otherwise.
     */
    public function getWasPriceAttribute(): ?float
    {
        return $this->discount_price ? (float) $this->price : null;
    }

    /**
     * First product image's public R2 URL, or null if none uploaded.
     */
    public function getMainImageUrlAttribute(): ?string
    {
        $first = $this->images->first();

        return $first ? $first->url : null;
    }

    /**
     * Seller's city/municipality, pulled from their real profile —
     * falls back to province, then null if neither is available.
     */
    public function getSellerLocationAttribute(): ?string
    {
        $profile = $this->seller?->sellerProfile;

        return $profile?->municipality ?? $profile?->province ?? null;
    }
}
