<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'quantity',
        'weight_kg',
        'price_at_purchase',
        'subtotal',
        'selected_options',
        'original_price',
        'voucher_discount_allocated',
    ];

    protected function casts(): array
    {
        return [
            'selected_options' => 'array',
            'weight_kg' => 'decimal:3',
            'voucher_discount_allocated' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
