<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    use LoadsCartSummary;

    public function index(Request $request)
    {
        $user = $request->user();

        $products = Product::query()
            ->select('products.*')
            ->join('wishlist_items', 'wishlist_items.product_id', '=', 'products.id')
            ->where('wishlist_items.user_id', $user->id)
            ->with(['images', 'liveDiscount', 'variants:id,product_id'])
            ->withReviewStats() // after select(), same ordering rule as your product index
            ->orderByDesc('wishlist_items.created_at')
            ->paginate(24);

        return view('Buyer.wishlist.index', array_merge(
            compact('products'),
            $this->cartSummary($request)
        ));
    }

    /** AJAX: add to wishlist (idempotent). */
    public function store(Request $request, Product $product)
    {
        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This product is currently unavailable.',
            ], 422);
        }

        try {
            $request->user()->wishlistItems()->firstOrCreate(['product_id' => $product->id]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request already added it, which is the desired end state.
        }

        return $this->state($request, true, 'Added to your wishlist.');
    }

    /** AJAX: remove from wishlist (idempotent). */
    public function destroy(Request $request, Product $product)
    {
        $request->user()->wishlistItems()->where('product_id', $product->id)->delete();

        return $this->state($request, false, 'Removed from your wishlist.');
    }

    private function state(Request $request, bool $wishlisted, string $message)
    {
        return response()->json([
            'success' => true,
            'wishlisted' => $wishlisted,
            'wishlist_count' => $request->user()->wishlistItems()->count(),
            'message' => $message,
        ]);
    }
}
