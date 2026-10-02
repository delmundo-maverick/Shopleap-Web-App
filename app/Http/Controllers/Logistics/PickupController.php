<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\Order;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    public function index(Request $request)
    {
        $logisticsProfile = $request->user()->logisticsProfile;

        // Unclaimed pool (any hub can pick these up) + anything this
        // hub has already claimed but not yet confirmed picked up.
        $orders = Order::with(['items', 'seller.sellerProfile', 'assignedRider'])
            ->where('status', Order::STATUS_READY_FOR_PICKUP)
            ->where(function ($q) use ($logisticsProfile) {
                $q->whereNull('logistics_profile_id')
                    ->orWhere('logistics_profile_id', $logisticsProfile->id);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $availableRiders = CourierProfile::where('logistics_profile_id', $logisticsProfile->id)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return view('logistics.pickups.index', compact('orders', 'availableRiders'));
    }

    /**
     * Claim this order for the current hub and assign a rider to
     * physically collect it from the seller.
     */
    public function assignRider(Request $request, Order $order)
    {
        $this->authorizeClaim($request, $order);

        $request->validate([
            'rider_id' => ['required', 'integer'],
        ]);

        $rider = CourierProfile::where('logistics_profile_id', $request->user()->logisticsProfile->id)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->findOrFail($request->rider_id);

        $order->update([
            'logistics_profile_id' => $request->user()->logisticsProfile->id,
            'assigned_rider_id' => $rider->id,
        ]);

        return back()->with('status', "Order #{$order->id} assigned to {$rider->full_name} for pickup.");
    }

    /**
     * Stand-in for the rider's own "Scan/Confirm Parcel" action from the
     * ERP flow, until the Courier mobile app exists. Moves the order to
     * PICKED_UP once the rider has physically collected it from the seller.
     */
    public function confirmPickup(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if (! $order->assigned_rider_id) {
            return back()->with('status', 'Assign a rider before confirming pickup.');
        }

        if ($order->status !== Order::STATUS_READY_FOR_PICKUP) {
            return back()->with('status', 'This order is no longer awaiting pickup.');
        }

        $order->update(['status' => Order::STATUS_PICKED_UP]);

        return back()->with('status', "Order #{$order->id} confirmed picked up.");
    }

    private function authorizeClaim(Request $request, Order $order): void
    {
        $logisticsProfileId = $request->user()->logisticsProfile->id;

        if ($order->logistics_profile_id !== null && $order->logistics_profile_id !== $logisticsProfileId) {
            abort(403, 'This order has already been claimed by another sorting center.');
        }
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        if ($order->logistics_profile_id !== $request->user()->logisticsProfile->id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
