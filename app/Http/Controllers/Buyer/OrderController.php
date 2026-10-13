<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use LoadsCartSummary;

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');

        $baseQuery = Order::where('buyer_id', $request->user()->id);

        $counts = ['all' => (clone $baseQuery)->count()];
        foreach (Order::TAB_GROUPS as $key => $statuses) {
            $counts[$key] = (clone $baseQuery)->whereIn('status', $statuses)->count();
        }

        $ordersQuery = Order::with([
            'items.product',
            'items.review',
            'seller.sellerProfile',
            'events',
        ])
            ->where('buyer_id', $request->user()->id)
            ->latest();

        if ($tab !== 'all' && isset(Order::TAB_GROUPS[$tab])) {
            $ordersQuery->whereIn('status', Order::TAB_GROUPS[$tab]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $ordersQuery->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('seller.sellerProfile', function ($sq) use ($search) {
                        $sq->where('business_name', 'ilike', "%{$search}%");
                    });
            });
        }

        $orders = $ordersQuery->paginate(10)->withQueryString();

        return view('Buyer.orders.index', array_merge(
            compact('orders', 'tab', 'counts'),
            $this->cartSummary($request)
        ));
    }

    /**
     * Buyer confirms the parcel arrived (DELIVERED -> COMPLETED).
     * The conditional update makes a double-click or a second tab a harmless no-op.
     */
    public function confirmReceived(Request $request, Order $order)
    {
        if ($order->buyer_id !== $request->user()->id) {
            abort(403);
        }

        $updated = Order::whereKey($order->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->update(['status' => Order::STATUS_COMPLETED]);

        if ($updated) {
            $order->status = Order::STATUS_COMPLETED;
            $order->logStatusEvent();
        } elseif ($order->status !== Order::STATUS_COMPLETED) {
            return back()->with('status', 'This order can only be confirmed once it has been delivered.');
        }

        return redirect()->route('buyer.orders.index', ['tab' => 'completed']);
    }

    /**
     * Put every still-available item from a past order back into the cart
     * (same product + variant, same quantity, capped at current stock),
     * then send the buyer to the cart. Follows the same rules as CartController@store.
     */
    public function buyAgain(Request $request, Order $order)
    {
        if ($order->buyer_id !== $request->user()->id) {
            abort(403);
        }

        $order->load('items.product');

        $added = 0;
        $skipped = 0;

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product || $product->status !== 'active') {
                $skipped++;
                continue;
            }

            $variant = null;
            if ($item->product_variant_id) {
                $variant = ProductVariant::where('product_id', $product->id)
                    ->where('is_active', true)
                    ->find($item->product_variant_id);

                if (! $variant) {
                    $skipped++;
                    continue;
                }
            } elseif ($product->hasVariants()) {
                // The product gained variants since this order; the buyer must pick one on its page.
                $skipped++;
                continue;
            }

            $stock = $variant ? $variant->stock_quantity : $product->stock_quantity;
            if ($stock < 1) {
                $skipped++;
                continue;
            }

            $cartItem = CartItem::firstOrNew([
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
            ]);

            $current = $cartItem->exists ? $cartItem->quantity : 0;
            $newQty = min($current + $item->quantity, $stock, 99);

            if ($newQty <= $current) {
                $skipped++;
                continue;
            }

            $cartItem->quantity = $newQty;
            $cartItem->selected_options = $variant ? $variant->options : null;
            $cartItem->save();
            $added++;
        }

        if ($added === 0) {
            return back()->with('status', 'None of the items in this order are available right now.');
        }

        $redirect = redirect()->route('buyer.cart.index');

        if ($skipped > 0) {
            $redirect->with('status', "Added {$added} item(s) to your cart. {$skipped} unavailable item(s) were skipped.");
        }

        return $redirect;
    }
}
