<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = $request->user()->cartItems()
            ->with(['product.images', 'product.seller.sellerProfile'])
            ->latest()
            ->get();

        $total = $cartItems->sum('line_total');

        return view('Buyer.cart.index', compact('cartItems', 'total'));
    }

    /**
     * AJAX — add a product to cart, or increment quantity if already there.
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        if ($product->status !== 'active' || $product->stock_quantity < 1) {
            return response()->json(['success' => false, 'message' => 'This product is currently unavailable.'], 422);
        }

        $qtyToAdd = $request->input('quantity', 1);

        $cartItem = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);
        $cartItem->quantity = ($cartItem->exists ? $cartItem->quantity : 0) + $qtyToAdd;
        $cartItem->save();

        $cartCount = $request->user()->cartItems()->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => "{$product->name} added to cart.",
            'cart_count' => $cartCount,
        ]);
    }

    /**
     * AJAX — change quantity for an existing cart line.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeOwnership($request, $cartItem);

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cartItem->update(['quantity' => $request->quantity]);
        $cartItem->load('product');

        return response()->json([
            'success' => true,
            'line_total' => $cartItem->line_total,
            'cart_total' => $request->user()->cartItems()->with('product')->get()->sum('line_total'),
            'cart_count' => $request->user()->cartItems()->sum('quantity'),
        ]);
    }

    /**
     * AJAX — remove a cart line entirely.
     */
    public function destroy(Request $request, CartItem $cartItem)
    {
        $this->authorizeOwnership($request, $cartItem);

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'cart_total' => $request->user()->cartItems()->with('product')->get()->sum('line_total'),
            'cart_count' => $request->user()->cartItems()->sum('quantity'),
        ]);
    }

    private function authorizeOwnership(Request $request, CartItem $cartItem): void
    {
        if ($cartItem->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
