<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PublicCatalog
{
    public function query(): Builder
    {
        return Product::active()->with(['images', 'seller.sellerProfile', 'liveDiscount', 'variants'])->latest();
    }

    public function featured(): Collection
    {
        $productIds = Cache::remember('public-catalog.featured.v2', 60, fn (): array => $this->query()->take(8)->pluck('id')->all());

        return $this->query()->whereKey($productIds)->get();
    }

    public function discover(string $search, string $category): Collection
    {
        $key = 'public-catalog.discover.v3.'.hash('sha256', json_encode([$search, $category]));

        $productIds = Cache::remember($key, 60, fn (): array => $this->query()
            ->when($search !== '', fn (Builder $query): Builder => $query->where('name', 'like', '%'.$search.'%'))
            ->when($category !== '', fn (Builder $query): Builder => $query->where('category', $category))
            ->take(40)->pluck('id')->all());

        return $this->query()->whereKey($productIds)->get();
    }
}
