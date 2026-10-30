<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    use LoadsCartSummary;

    public function show(Request $request, User $seller)
    {
        abort_unless($seller->isEligibleSeller(), 404);

        $seller->loadCount('followers');

        $isFollowing = $request->user()->isFollowing($seller);
        $followingCount = $request->user()->followedSellers()->count();
        $followersCount = $seller->followers_count;

        $products = Product::with(['images', 'liveDiscount'])
            ->withReviewStats()
            ->where('seller_id', $seller->id)
            ->active()
            ->latest()
            ->paginate(18);

        $reviewBase = Review::where('seller_id', $seller->id);

        $summary = (clone $reviewBase)
            ->toBase()
            ->selectRaw('COUNT(*) AS total, AVG(rating) AS average')
            ->first();

        $reviewCount = (int) ($summary->total ?? 0);

        $shopRating = [
            'count' => $reviewCount,
            'average' => $reviewCount > 0 ? round((float) $summary->average, 1) : null,
        ];

        $latestReviews = (clone $reviewBase)
            ->with(['media', 'buyer', 'orderItem', 'product'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Review $review) => [
                'rating' => $review->rating,
                'comment' => $review->comment,
                'buyer' => $review->buyer_masked_name,
                'product' => $review->orderItem?->product_name ?? $review->product?->name ?? 'Product',
                'product_url' => $review->product && $review->product->status === 'active'
                    ? route('buyer.products.show', $review->product)
                    : null,
                'created_at' => $review->created_at,
                'reply' => $review->seller_reply,
                'media' => $review->media->map(fn ($m) => [
                    'url' => $m->url,
                    'is_video' => $m->is_video,
                ])->values(),
            ]);

        return view('Buyer.shop.show', array_merge(
            compact('seller', 'products', 'shopRating', 'latestReviews', 'isFollowing', 'followingCount', 'followersCount'),
            $this->cartSummary($request)
        ));
    }
}
