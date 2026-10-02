<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    use LoadsCartSummary;

    public function show(Request $request, User $seller)
    {
        abort_unless($seller->sellerProfile, 404);

        $products = Product::with('images')
            ->where('seller_id', $seller->id)
            ->active()
            ->latest()
            ->paginate(18);

        return view('Buyer.shop.show', array_merge(
            compact('seller', 'products'),
            $this->cartSummary($request)
        ));
    }
}
