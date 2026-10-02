<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $status = $request->get('status', 'all');

        if ($status === 'new') {
            $query->whereIn('status', Order::GROUP_NEW);
        } elseif ($status === 'prepare') {
            $query->whereIn('status', Order::GROUP_PREPARE);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('seller.orders.index', compact('orders', 'status'));
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        $order->load(['items.product', 'buyer.buyerProfile']);

        return view('seller.orders.show', compact('order'));
    }

    /**
     * AJAX — update order status. Restricted to the stages a Seller
     * actually controls; anything past that belongs to Courier/Logistics.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if (!in_array($order->status, Order::SELLER_EDITABLE_STATUSES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This order has moved past the stage you can update — it is now being handled by delivery.',
            ], 422);
        }

        $request->validate([
            'status' => ['required', Rule::in(Order::SELLER_EDITABLE_STATUSES)],
        ]);

        $order->update(['status' => $request->status]);

        // TODO: notify buyer of status change (email/notification) once that system exists

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->id} is now marked as {$order->status_label}.",
            'status' => $order->status,
        ]);
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        if ($order->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
