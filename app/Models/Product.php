<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending_review';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'active' => 'Active',
        'archived' => 'Inactive',
        'pending_review' => 'Pending review',
        'rejected' => 'Rejected',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'seller_id',
        'sku',
        'low_stock_threshold',
        'name',
        'description',
        'category',
        'subcategory',
        'price',
        'discount_price',
        'stock_quantity',
        'weight_kg',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'low_stock_threshold' => 'integer',
            'requires_review' => 'boolean',
            'discount_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'weight_kg' => 'decimal:3',
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

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * The price to actually display — the discounted price when one is
     * set, otherwise the regular price.
     */
    public function getDisplayPriceAttribute(): float
    {
        return $this->priceAfterDiscount($this->price);
    }

    /**
     * The original price to show struck-through, only when a discount
     * is actually active. Null (not shown) otherwise.
     */
    public function getWasPriceAttribute(): ?float
    {
        $price = (float) $this->price;

        return $this->display_price < $price ? $price : null;
    }

    /**
     * The main thumbnail: the first general image, otherwise the first
     * variant image (so variant-only galleries still render everywhere).
     */
    public function getMainImageUrlAttribute(): ?string
    {
        $first = $this->images->first();

        if ($first) {
            return $first->url;
        }

        $variant = $this->relationLoaded('variants')
            ? $this->variants->first(fn (ProductVariant $variant) => $variant->image_path !== null)
            : $this->variants()->whereNotNull('image_path')->first();

        return $variant?->image_url;
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

    public function options()
    {
        return $this->hasMany(ProductOption::class);
    }

    /** Every variant, including archived ones (seller side). */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** Only variants buyers may select. Use this on the buyer side. */
    public function activeVariants()
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->isNotEmpty()
            : $this->variants()->exists();
    }

    /** Active variants at or below the threshold (includes sold-out ones). */
    public function lowStockVariantCount(?int $threshold = null): int
    {
        $threshold ??= $this->low_stock_threshold ?? (int) config('shop.low_stock_threshold', 5);

        $this->loadMissing('variants');

        return $this->variants
            ->where('is_active', true)
            ->where('stock_quantity', '<=', $threshold)
            ->count();
    }

    /** Low when the total is low, or when any single active variant is. */
    public function isLowStock(?int $threshold = null): bool
    {
        $threshold ??= $this->low_stock_threshold ?? (int) config('shop.low_stock_threshold', 5);

        return $this->stock_quantity <= $threshold
            || $this->lowStockVariantCount($threshold) > 0;
    }

    /**
     * For products with variants, the product row holds the derived values:
     * price = cheapest active variant, stock = total across active variants.
     * Call this inside a transaction after any variant price/stock/status change.
     * No-op for products without variants.
     */
    public function syncFromVariants(): void
    {
        $all = $this->variants()->get(['price', 'stock_quantity', 'is_active']);

        if ($all->isEmpty()) {
            return;
        }

        $active = $all->where('is_active', true);

        $this->forceFill([
            'price' => ($active->isNotEmpty() ? $active : $all)->min('price'),
            'discount_price' => null,
            'stock_quantity' => $active->sum('stock_quantity'),
        ])->save();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Adds reviews_avg_rating and reviews_count to the query, for the
     * star rating shown on product cards.
     */
    public function scopeWithReviewStats($query)
    {
        return $query->withAvg('reviews', 'rating')->withCount('reviews');
    }

    /**
     * Seller inventory filter.
     *  'out' => nothing left to sell
     *  'low' => still sellable, but the total or any active variant is at/below the threshold
     */
    public function scopeStockLevel(Builder $query, ?string $level, ?int $threshold = null): Builder
    {
        $stockThreshold = $threshold !== null ? (string) $threshold : 'COALESCE('.$query->getModel()->qualifyColumn('low_stock_threshold').', '.(int) config('shop.low_stock_threshold', 5).')';

        return match ($level) {
            'out' => $query->where('stock_quantity', '<=', 0),
            'in' => $query->where('stock_quantity', '>', 0),
            'low' => $query->where('stock_quantity', '>', 0)->where(function ($q) use ($stockThreshold) {
                $q->whereRaw('stock_quantity <= '.$stockThreshold)
                    ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->whereRaw('stock_quantity <= '.$stockThreshold));
            }),
            default => $query,
        };
    }

    public function discount()
    {
        return $this->hasOne(ProductDiscount::class);
    }

    /** The discount only while it is active and inside its schedule. Eager-load this on buyer pages. */
    public function liveDiscount()
    {
        return $this->hasOne(ProductDiscount::class)->live();
    }

    /** Apply this product's live discount (if any) to any price, e.g. a variant's. */
    public function priceAfterDiscount(float|string|null $price): float
    {
        $price = (float) $price;

        return $this->liveDiscount?->apply($price) ?? $price;
    }
}
