<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items', 'buyer'])
            ->where('seller_id', $request->user()->id)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('buyer_name', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('seller.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        $order->load(['items.product', 'buyer.buyerProfile']);

        return view('seller.orders.show', compact('order'));
    }

    /**
     * AJAX — update order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        $request->validate([
            'status' => ['required', 'in:pending,to_ship,shipped,delivered,cancelled'],
        ]);

        $order->update(['status' => $request->status]);

        // TODO: notify buyer of status change (email/notification) once that system exists

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->id} is now marked as " . str_replace('_', ' ', $request->status) . '.',
            'status' => $request->status,
        ]);
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        if ($order->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
