<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Services\HubContext;
use App\Services\ParcelLifecycle;
use Illuminate\Http\Request;

class IncomingParcelController extends Controller
{
    public function index(Request $request)
    {
        $hub = HubContext::require($request);

        $query = Order::with(['items', 'seller.sellerProfile', 'pickupRider'])
            ->heldBy($hub->id)
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
    public function confirmArrival(Request $request, Order $order, ParcelLifecycle $lifecycle)
    {
        $hub = HubContext::require($request);
        $this->authorize('actAsHoldingHub', $order);

        if ($order->status !== Order::STATUS_PICKED_UP) {
            return back()->with('status', 'This order is not awaiting arrival confirmation.');
        }

        $moved = $lifecycle->transition(
            $order,
            Order::STATUS_PICKED_UP,
            Order::STATUS_AT_SORTING_CENTER,
            ['current_hub_id' => $hub->id, 'logistics_profile_id' => $hub->id],
            "Arrived at {$hub->business_name} hub.",
            $request->user()->id,
            $hub->id,
            eventType: ParcelEvent::TYPE_BAG_RECEIVED,
        );

        if (! $moved) {
            return back()->with('status', 'This order was just updated. Please refresh the page.');
        }

        return back()->with('status', "Order #{$order->id} logged as arrived at the sorting center.");
    }
}
