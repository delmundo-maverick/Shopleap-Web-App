<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Services\PublicCatalog;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, PublicCatalog $catalog)
    {
        $categories = config('shopleap_categories.list');

        $bestSellers = $catalog->featured();

        $recommended = $catalog->query()->paginate(16)->withQueryString();

        $cartItems = $request->user()->cartItems()
            ->with(['product.images', 'product.liveDiscount', 'variant'])
            ->latest()
            ->get();

        $cartCount = $cartItems->sum('quantity');
        $cartPreviewItems = $cartItems->take(3);

        return view('Buyer.home', compact(
            'categories',
            'bestSellers',
            'recommended',
            'cartCount',
            'cartPreviewItems'
        ));
    }
}
