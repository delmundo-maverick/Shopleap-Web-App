<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categories = config('shopleap_categories.list');

        $bestSellers = Product::with(['images', 'seller.sellerProfile'])
            ->where('status', 'active')
            ->latest()
            ->take(8)
            ->get();

        $recommended = Product::with(['images', 'seller.sellerProfile'])
            ->where('status', 'active')
            ->latest()
            ->paginate(16)
            ->withQueryString();

        return view('buyer.home', compact('categories', 'bestSellers', 'recommended'));
    }
}
