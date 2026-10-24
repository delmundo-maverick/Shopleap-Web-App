<?php

namespace App\Support;

use App\Models\WishlistItem;

class WishlistIds
{
    private ?array $ids = null;

    /** @return int[] */
    public function all(): array
    {
        return $this->ids ??= auth()->check()
            ? WishlistItem::where('user_id', auth()->id())
            ->pluck('product_id')
            ->map(fn($id) => (int) $id)
            ->all()
            : [];
    }

    public function count(): int
    {
        return count($this->all());
    }
}
