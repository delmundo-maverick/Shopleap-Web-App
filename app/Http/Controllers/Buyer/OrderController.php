<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\OrderCancellation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use LoadsCartSummary;

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');

        $baseQuery = Order::where('buyer_id', $request->user()->id);

        // Tab badges. The Completed count uses the same condition as the list below,
        // so the number always matches the orders shown.
        $counts = ['all' => (clone $baseQuery)->count()];
        foreach (Order::TAB_GROUPS as $key => $statuses) {
            $counts[$key] = match ($key) {
                'return_refund' => (clone $baseQuery)->returnOrRefund()->count(),
                'completed' => (clone $baseQuery)
                    ->whereIn('status', $statuses)
                    ->withoutReturnRequests()
                    ->count(),
                default => (clone $baseQuery)->whereIn('status', $statuses)->count(),
            };
        }

        $ordersQuery = Order::with([
            'items.product',
            'items.review',
            'seller.sellerProfile',
            'events',
            'returns',
        ])
            ->where('buyer_id', $request->user()->id)
            ->latest();

        if ($tab === 'return_refund') {
            $ordersQuery->returnOrRefund();
        } elseif ($tab === 'completed') {
            // Orders with an active or settled return/refund live under Return/Refund instead.
            $ordersQuery
                ->whereIn('status', Order::TAB_GROUPS['completed'])
                ->withoutReturnRequests();
        } elseif ($tab !== 'all' && isset(Order::TAB_GROUPS[$tab])) {
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
     * Cancel an order that has not shipped yet.
     * PLACED: cancelled right away. CONFIRMED / PREPARING / READY_FOR_PICKUP: a request the seller must approve.
     */
    public function cancel(Request $request, Order $order, OrderCancellation $cancellation)
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $data = $request->validate([
            'reason' => ['required', Rule::in(Order::BUYER_CANCEL_REASONS)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $note = filled($data['note'] ?? null) ? trim($data['note']) : null;
        $mode = $order->buyerCancelMode();

        if ($mode === 'instant') {
            $done = $cancellation->cancel(
                $order,
                'buyer',
                $data['reason'],
                $note,
                $request->user()->id,
                Order::BUYER_CANCEL_INSTANT_FROM
            );

            if ($done) {
                return redirect()
                    ->route('buyer.orders.index', ['tab' => 'cancelled'])
                    ->with('status', "Order #{$order->id} was cancelled.");
            }

            return back()->withErrors(['cancel' => 'The seller just confirmed your order, so it can no longer be cancelled right away. Please send a cancellation request instead.']);
        }

        if ($mode === 'request') {
            if ($cancellation->request($order, $data['reason'], $note, $request->user()->id)) {
                return back()->with('status', 'Cancellation request sent. The seller has ' . Order::CANCEL_REQUEST_AUTO_APPROVE_HOURS . ' hours to respond. If they don\'t, your order is cancelled automatically.');
            }

            return back()->withErrors(['cancel' => 'This order was just updated. Please refresh and try again.']);
        }

        $message = match (true) {
            $order->status === Order::STATUS_CANCELLED => 'This order is already cancelled.',
            $order->cancel_request_status === 'pending' => 'You already sent a cancellation request for this order.',
            $order->cancel_request_status === 'declined' => 'The seller declined your cancellation request. Please contact the seller.',
            default => "This order has already shipped, so it can't be cancelled. You can ask for a return or refund once it is delivered.",
        };

        return back()->withErrors(['cancel' => $message]);
    }

    public function withdrawCancelRequest(Request $request, Order $order, OrderCancellation $cancellation)
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        if ($cancellation->withdraw($order, $request->user()->id)) {
            return back()->with('status', 'Cancellation request withdrawn.');
        }

        return back()->withErrors(['cancel' => 'There is no pending cancellation request for this order.']);
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
