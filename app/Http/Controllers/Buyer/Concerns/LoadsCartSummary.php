<?php

namespace App\Http\Controllers\Buyer\Concerns;

use Illuminate\Http\Request;

trait LoadsCartSummary
{
    protected function cartSummary(Request $request): array
    {
        $cartCount = $request->user()->cartItems()->sum('quantity');

        $cartPreviewItems = $request->user()->cartItems()
            ->with('product.images')
            ->latest()
            ->take(3)
            ->get();

        return compact('cartCount', 'cartPreviewItems');
    }
}
