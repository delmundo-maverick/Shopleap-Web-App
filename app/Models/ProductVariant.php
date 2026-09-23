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
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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
