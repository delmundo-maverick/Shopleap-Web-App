<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
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

        $query = Product::with(['images', 'liveDiscount'])->active();

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

        // Must come after the sort block: select('products.*') would wipe the rating columns.
        $query->withReviewStats();

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

        $product->load(['images', 'seller.sellerProfile', 'options', 'variants', 'liveDiscount']);

        $related = Product::with(['images', 'liveDiscount'])
            ->withReviewStats()
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->active()
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $explore = Product::with(['images', 'liveDiscount'])
            ->withReviewStats()
            ->where('id', '!=', $product->id)
            ->active()
            ->inRandomOrder()
            ->limit(12)
            ->get();

        // Reviews: summary, star breakdown, filtered + paginated list
        $ratingFilter = (int) $request->input('rating');
        $ratingFilter = ($ratingFilter >= 1 && $ratingFilter <= 5) ? $ratingFilter : null;
        $mediaFilter = $request->boolean('media');

        $reviewBase = Review::where('product_id', $product->id);

        $summary = (clone $reviewBase)
            ->toBase()
            ->selectRaw('COUNT(*) AS total, AVG(rating) AS average')
            ->first();

        $reviewCount = (int) ($summary->total ?? 0);

        $reviewStats = [
            'count'      => $reviewCount,
            'average'    => $reviewCount > 0 ? round((float) $summary->average, 1) : null,
            'with_media' => $reviewCount > 0 ? (clone $reviewBase)->whereHas('media')->count() : 0,
        ];

        $distributionRows = (clone $reviewBase)
            ->toBase()
            ->select('rating')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $ratingDistribution = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $ratingDistribution[$star] = (int) ($distributionRows[$star] ?? 0);
        }

        $reviews = (clone $reviewBase)
            ->with(['media', 'buyer', 'orderItem'])
            ->when($ratingFilter, fn($q) => $q->where('rating', $ratingFilter))
            ->when($mediaFilter, fn($q) => $q->whereHas('media'))
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->fragment('reviews')
            ->through(fn(Review $review) => [
                'id'         => $review->id,
                'rating'     => $review->rating,
                'comment'    => $review->comment,
                'buyer'      => $review->buyer_masked_name,
                'variation'  => collect($review->orderItem?->selected_options ?? [])
                    ->map(fn($v, $k) => (is_int($k) ? '' : "{$k}: ") . (is_array($v) ? implode('/', $v) : $v))
                    ->filter()
                    ->implode(', '),
                'created_at' => $review->created_at,
                'reply'      => $review->seller_reply,
                'replied_at' => $review->seller_replied_at,
                'media'      => $review->media->map(fn($m) => [
                    'url'      => $m->url,
                    'is_video' => $m->is_video,
                ])->values(),
            ]);

        return view('Buyer.products.show', array_merge(
            compact(
                'product',
                'related',
                'explore',
                'reviews',
                'reviewStats',
                'ratingDistribution',
                'ratingFilter',
                'mediaFilter'
            ),
            $this->cartSummary($request)
        ));
    }
}
