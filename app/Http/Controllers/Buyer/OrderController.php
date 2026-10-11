<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\Order;
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
}
