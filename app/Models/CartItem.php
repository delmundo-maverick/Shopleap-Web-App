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
     * The price to actually charge — the variant's price when one is
     * selected, otherwise the product's normal display price.
     */
    public function getUnitPriceAttribute(): float
    {
        return (float) ($this->variant?->price ?? $this->product->display_price);
    }

    public function getLineTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
