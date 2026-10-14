<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = $request->user()->cartItems()
            ->with([
                'product.images',
                'product.seller.sellerProfile',
                'product.options',
                'product.variants',
                'product.liveDiscount',
                'variant',
            ])
            ->latest()
            ->get();

        $total = $cartItems->sum('line_total');

        return view('Buyer.cart.index', compact('cartItems', 'total'));
    }

    /** AJAX: add a product (optionally a variant) or increment an existing line. */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'variant_id' => ['nullable', 'integer'],
            'selected_options' => ['nullable', 'array'],
        ]);

        if ($product->status !== 'active') {
            return $this->fail('This product is currently unavailable.');
        }

        $variant = null;
        if ($request->filled('variant_id')) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('is_active', true)
                ->find($request->variant_id);

            if (!$variant) {
                return $this->fail('Selected option is no longer available.');
            }
        } elseif ($product->hasVariants()) {
            return $this->fail('Please select all product options.');
        }

        $availableStock = $variant ? $variant->stock_quantity : $product->stock_quantity;
        if ($availableStock < 1) {
            return $this->fail('This item is out of stock.');
        }

        $cartItem = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);

        $newQty = ($cartItem->exists ? $cartItem->quantity : 0) + $request->input('quantity', 1);

        if ($newQty > $availableStock) {
            return $this->fail("Only {$availableStock} in stock.");
        }

        $cartItem->quantity = $newQty;
        $cartItem->selected_options = $variant ? $variant->options : null;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => "{$product->name} added to cart.",
            'cart_count' => $this->cartCount($request),
            'cart_item_id' => $cartItem->id,
        ]);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeOwnership($request, $cartItem);

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'variant_id' => ['nullable', 'integer'],
        ]);

        $cartItem->load('product.variants', 'product.liveDiscount', 'variant');

        if ($cartItem->product->status !== 'active') {
            return $this->fail('This product is no longer available.');
        }

        $variant = $cartItem->variant;
        $hasNewVariant = $request->filled('variant_id');

        if (!$hasNewVariant && $variant && !$variant->is_active) {
            return $this->fail('This option is no longer available. Please choose another or remove it.');
        }

        if (!$hasNewVariant && !$variant && $cartItem->product->hasVariants()) {
            return $this->fail('Please choose an option.');
        }

        if ($hasNewVariant) {
            $variant = ProductVariant::where('product_id', $cartItem->product_id)
                ->where('is_active', true)
                ->find($request->variant_id);

            if (!$variant) {
                return $this->fail('Selected option is no longer available.');
            }

            // Merge into an existing line for the same variant instead of duplicating.
            $existingLine = CartItem::with('product.liveDiscount', 'variant')
                ->where('user_id', $request->user()->id)
                ->where('product_id', $cartItem->product_id)
                ->where('product_variant_id', $variant->id)
                ->where('id', '!=', $cartItem->id)
                ->first();

            if ($existingLine) {
                $mergedQty = $existingLine->quantity + $request->quantity;

                if ($mergedQty > $variant->stock_quantity) {
                    return $this->fail("Only {$variant->stock_quantity} in stock.");
                }

                $existingLine->update([
                    'quantity' => $mergedQty,
                    'selected_options' => $variant->options,
                ]);
                $cartItem->delete();

                return response()->json([
                    'success' => true,
                    'merged' => true,
                    'removed_id' => $cartItem->id,
                    'target_id' => $existingLine->id,
                    'quantity' => $existingLine->quantity,
                    'line_total' => $existingLine->line_total,
                    'unit_price' => $existingLine->unit_price,
                    'selected_options' => $existingLine->selected_options,
                    'stock' => $variant->stock_quantity,
                    'cart_total' => $this->cartTotal($request),
                    'cart_count' => $this->cartCount($request),
                ]);
            }
        }

        $availableStock = $variant?->stock_quantity ?? $cartItem->product->stock_quantity;

        if ($request->quantity > $availableStock) {
            return $this->fail("Only {$availableStock} in stock.");
        }

        $cartItem->quantity = $request->quantity;

        if ($hasNewVariant) {
            $cartItem->product_variant_id = $variant->id;
            $cartItem->selected_options = $variant->options;
        }

        $cartItem->save();
        $cartItem->load('variant'); // refresh so prices reflect the new variant

        return response()->json([
            'success' => true,
            'merged' => false,
            'quantity' => $cartItem->quantity,
            'line_total' => $cartItem->line_total,
            'unit_price' => $cartItem->unit_price,
            'selected_options' => $cartItem->selected_options,
            'stock' => $availableStock,
            'cart_total' => $this->cartTotal($request),
            'cart_count' => $this->cartCount($request),
        ]);
    }

    /** AJAX: remove a cart line. */
    public function destroy(Request $request, CartItem $cartItem)
    {
        $this->authorizeOwnership($request, $cartItem);

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'cart_total' => $this->cartTotal($request),
            'cart_count' => $this->cartCount($request),
        ]);
    }

    /** AJAX: remove several of the buyer's cart lines. */
    public function destroyMany(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $removedIds = CartItem::where('user_id', $request->user()->id)
            ->whereIn('id', $data['ids'])
            ->pluck('id');

        CartItem::whereIn('id', $removedIds)->delete();

        return response()->json([
            'success' => true,
            'removed_ids' => $removedIds,
            'cart_count' => $this->cartCount($request),
        ]);
    }

    // ------------------------------------------------------------------

    private function fail(string $message)
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }

    private function cartCount(Request $request): int
    {
        return (int) $request->user()->cartItems()->sum('quantity');
    }

    private function cartTotal(Request $request): float
    {
        return (float) $request->user()->cartItems()
            ->with('product.liveDiscount', 'variant')
            ->get()
            ->sum('line_total');
    }

    private function authorizeOwnership(Request $request, CartItem $cartItem): void
    {
        if ($cartItem->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
