<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class IncomingParcelController extends Controller
{
    public function index(Request $request)
    {
        $logisticsProfileId = $request->user()->logisticsProfile->id;

        $query = Order::with(['items', 'seller.sellerProfile', 'assignedRider'])
            ->where('logistics_profile_id', $logisticsProfileId)
            ->where('status', Order::STATUS_PICKED_UP)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('id', $search);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('logistics.incoming.index', compact('orders'));
    }

    /**
     * Log the parcel's physical arrival at this sorting center.
     */
    public function confirmArrival(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_PICKED_UP) {
            return back()->with('status', 'This order is not awaiting arrival confirmation.');
        }

        $order->update(['status' => Order::STATUS_AT_SORTING_CENTER]);

        return back()->with('status', "Order #{$order->id} logged as arrived at the sorting center.");
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        if ($order->logistics_profile_id !== $request->user()->logisticsProfile->id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
