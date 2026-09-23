<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use LoadsCartSummary;

    public function show(Request $request, Product $product)
    {
        if ($product->status !== 'active') {
            abort(404);
        }

        $product->load(['images', 'seller.sellerProfile', 'options', 'variants']);

        $related = Product::with('images')
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->active()
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $explore = Product::with('images')
            ->where('id', '!=', $product->id)
            ->active()
            ->inRandomOrder()
            ->limit(12)
            ->get();

        return view('Buyer.products.show', array_merge(
            compact('product', 'related', 'explore'),
            $this->cartSummary($request)
        ));
    }
}
