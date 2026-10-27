<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['user_id', 'product_id', 'quantity', 'selected_options', 'product_variant_id'];

    protected function casts(): array
    {
        return ['selected_options' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * The price to actually charge: the variant's price after any live
     * discount when a variant is selected, otherwise the product's
     * normal display price.
     */
    public function getUnitPriceAttribute(): float
    {
        return (float) ($this->variant
            ? $this->product->priceAfterDiscount($this->variant->price)
            : $this->product->display_price);
    }

    public function getLineTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Weight of one unit, in kilograms: the variant's effective weight when a
     * variant is selected, otherwise the product's default weight.
     */
    public function getUnitWeightKgAttribute(): ?float
    {
        // Avoid loading the variant's product relation: the cart item already
        // holds the product, so use it as the fallback.
        $weight = $this->variant
            ? ($this->variant->weight_kg ?? $this->product?->weight_kg)
            : $this->product?->weight_kg;

        return $weight !== null ? (float) $weight : null;
    }

    /**
     * Total weight for this cart line (unit weight x quantity), or null when unknown.
     */
    public function getLineWeightKgAttribute(): ?float
    {
        $unitWeight = $this->unit_weight_kg;

        return $unitWeight === null
            ? null
            : round($unitWeight * $this->quantity, 3);
    }
}
