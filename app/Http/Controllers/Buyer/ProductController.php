<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use LoadsCartSummary;

    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $sort = in_array($request->get('sort'), ['newest', 'price_low', 'price_high', 'bestseller'], true)
            ? $request->get('sort') : 'newest';

        $query = Product::with('images')->active();

        if ($search) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($sort === 'bestseller') {
            $query->select('products.*')
                ->leftJoinSub(
                    DB::table('order_items')
                        ->join('orders', 'orders.id', '=', 'order_items.order_id')
                        ->whereNotIn('orders.status', Order::GROUP_LOST)
                        ->groupBy('order_items.product_id')
                        ->selectRaw('order_items.product_id, SUM(order_items.quantity) as units_sold'),
                    'sales',
                    fn($join) => $join->on('sales.product_id', '=', 'products.id')
                )
                ->orderByDesc('sales.units_sold');
        } elseif ($sort === 'price_low') {
            $query->orderBy('price');
        } elseif ($sort === 'price_high') {
            $query->orderByDesc('price');
        } else {
            $query->latest();
        }

        $products = $query->paginate(24)->withQueryString();
        $categories = config('shopleap_categories.list');

        return view('Buyer.products.index', array_merge(
            compact('products', 'categories', 'search', 'category', 'sort'),
            $this->cartSummary($request)
        ));
    }

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
