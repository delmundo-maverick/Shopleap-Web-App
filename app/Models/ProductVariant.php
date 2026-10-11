<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'options',
        'price',
        'stock_quantity',
        'sku',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isLowStock(?int $threshold = null): bool
    {
        return $this->stock_quantity <= ($threshold ?? (int) config('shop.low_stock_threshold', 5));
    }

    /**
     * Human-readable combo label, e.g. "Pack Size: Pack of 4, Color: Red"
     */
    public function getLabelAttribute(): string
    {
        return collect($this->options)
            ->map(fn($value, $name) => "{$name}: {$value}")
            ->implode(', ');
    }
}
