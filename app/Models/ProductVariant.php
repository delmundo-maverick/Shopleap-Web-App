<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'options',
        'price',
        'stock_quantity',
        'weight_kg',
        'sku',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'weight_kg' => 'decimal:3',
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
            ->map(fn ($value, $name) => "{$name}: {$value}")
            ->implode(', ');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? Storage::disk('r2_public')->url($this->image_path)
            : null;
    }

    /**
     * Variant weight when set, otherwise the parent product's default weight.
     */
    public function getEffectiveWeightKgAttribute(): ?float
    {
        if ($this->weight_kg !== null) {
            return (float) $this->weight_kg;
        }

        return $this->product?->weight_kg !== null
            ? (float) $this->product->weight_kg
            : null;
    }
}
