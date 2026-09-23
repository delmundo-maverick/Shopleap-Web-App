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

        $ordersQuery = Order::with(['items.product', 'seller.sellerProfile'])
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

    public function show(Request $request, Order $order)
    {
        if ($order->buyer_id !== $request->user()->id) {
            abort(403);
        }

        $order->load(['items', 'seller.sellerProfile']);

        return view('Buyer.orders.show', compact('order'));
    }

    public function confirmReceived(Request $request, Order $order)
    {
        if ($order->buyer_id !== $request->user()->id) {
            abort(403);
        }

        if ($order->status !== Order::STATUS_DELIVERED) {
            return back()->with('status', 'This order can only be confirmed once it has been delivered.');
        }

        $order->update(['status' => Order::STATUS_COMPLETED]);

        return back()->with('status', 'Thanks for confirming! Your order is now marked as completed.');
    }
}
