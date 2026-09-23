<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function show(Request $request, User $seller)
    {
        abort_unless($seller->sellerProfile, 404);

        $products = Product::with('images')
            ->where('seller_id', $seller->id)
            ->active()
            ->latest()
            ->paginate(18);

        $cartCount = $request->user()->cartItems()->sum('quantity');
        $cartPreviewItems = $request->user()->cartItems()
            ->with('product.images')
            ->latest()
            ->take(3)
            ->get();

        return view('Buyer.shop.show', [
            'seller' => $seller,
            'products' => $products,
            'cartCount' => $cartCount,
            'cartPreviewItems' => $cartPreviewItems,
        ]);
    }
}
