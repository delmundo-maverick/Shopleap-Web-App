<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Services\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, PublicCatalog $catalog): View
    {
        $categories = config('shopleap_categories.list');

        $bestSellers = $catalog->featured()->take(6);

        $recommended = $catalog->query()->paginate(24)->withQueryString()->fragment('daily-discover');

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
