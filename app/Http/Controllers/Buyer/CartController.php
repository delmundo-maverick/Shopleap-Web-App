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
                'variant',
            ])
            ->latest()
            ->get();

        $total = $cartItems->sum('line_total');

        return view('Buyer.cart.index', compact('cartItems', 'total'));
    }

    /**
     * AJAX — add a product (optionally a specific variant) to cart,
     * or increment quantity if that exact product+variant is already there.
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'variant_id' => ['nullable', 'integer'],
            'selected_options' => ['nullable', 'array'],
        ]);

        if ($product->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'This product is currently unavailable.'], 422);
        }

        $variant = null;
        if ($request->filled('variant_id')) {
            $variant = ProductVariant::where('product_id', $product->id)->find($request->variant_id);

            if (!$variant) {
                return response()->json(['success' => false, 'message' => 'Selected option is no longer available.'], 422);
            }
        } elseif ($product->hasVariants()) {
            return response()->json(['success' => false, 'message' => 'Please select all product options.'], 422);
        }

        $availableStock = $variant ? $variant->stock_quantity : $product->stock_quantity;
        if ($availableStock < 1) {
            return response()->json(['success' => false, 'message' => 'This item is out of stock.'], 422);
        }

        $qtyToAdd = $request->input('quantity', 1);

        $cartItem = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);

        $newQty = ($cartItem->exists ? $cartItem->quantity : 0) + $qtyToAdd;

        if ($newQty > $availableStock) {
            return response()->json([
                'success' => false,
                'message' => "Only {$availableStock} in stock.",
            ], 422);
        }

        $cartItem->quantity = $newQty;
        $cartItem->selected_options = $variant ? $variant->options : null;
        $cartItem->save();

        $cartCount = $request->user()->cartItems()->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => "{$product->name} added to cart.",
            'cart_count' => $cartCount,
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

        $cartItem->load('product.variants', 'variant');

        $variant = $cartItem->variant;

        if ($request->filled('variant_id')) {
            $variant = ProductVariant::where('product_id', $cartItem->product_id)
                ->find($request->variant_id);

            if (!$variant) {
                return response()->json(['success' => false, 'message' => 'Selected option is no longer available.'], 422);
            }

            // Switching to a variant that's already its own cart line?
            // Merge into that line instead of creating a duplicate.
            $existingLine = CartItem::where('user_id', $request->user()->id)
                ->where('product_id', $cartItem->product_id)
                ->where('product_variant_id', $variant->id)
                ->where('id', '!=', $cartItem->id)
                ->first();

            if ($existingLine) {
                $mergedQty = $existingLine->quantity + $request->quantity;

                if ($mergedQty > $variant->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Only {$variant->stock_quantity} in stock.",
                    ], 422);
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
                    'cart_total' => $request->user()->cartItems()->with('product', 'variant')->get()->sum('line_total'),
                    'cart_count' => $request->user()->cartItems()->sum('quantity'),
                ]);
            }
        }

        $availableStock = $variant?->stock_quantity ?? $cartItem->product->stock_quantity;

        if ($request->quantity > $availableStock) {
            return response()->json([
                'success' => false,
                'message' => "Only {$availableStock} in stock.",
            ], 422);
        }

        $cartItem->quantity = $request->quantity;

        if ($request->filled('variant_id')) {
            $cartItem->product_variant_id = $variant->id;
            $cartItem->selected_options = $variant->options;
        }

        $cartItem->save();

        return response()->json([
            'success' => true,
            'merged' => false,
            'line_total' => $cartItem->line_total,
            'unit_price' => $cartItem->unit_price,
            'selected_options' => $cartItem->selected_options,
            'stock' => $availableStock,
            'cart_total' => $request->user()->cartItems()->with('product', 'variant')->get()->sum('line_total'),
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
            'cart_total' => $request->user()->cartItems()->with('product', 'variant')->get()->sum('line_total'),
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
