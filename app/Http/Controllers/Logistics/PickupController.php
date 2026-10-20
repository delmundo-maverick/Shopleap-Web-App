<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\Order;
use App\Services\OrderTransitions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PickupController extends Controller
{
    public function index(Request $request)
    {
        $hub = $request->user()->logisticsProfile;
        $tab = $request->get('tab') === 'scheduled' ? 'scheduled' : 'to_assign';

        $base = Order::where('logistics_profile_id', $hub->id)
            ->where('status', Order::STATUS_READY_FOR_PICKUP);

        $counts = [
            'to_assign' => (clone $base)->whereNull('pickup_rider_id')->count(),
            'scheduled' => (clone $base)->whereNotNull('pickup_rider_id')->count(),
        ];

        $orders = (clone $base)
            ->with(['items', 'seller.sellerProfile', 'pickupRider'])
            ->when(
                $tab === 'scheduled',
                fn($q) => $q->whereNotNull('pickup_rider_id'),
                fn($q) => $q->whereNull('pickup_rider_id')
            )
            ->orderBy('pickup_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $riders = $this->activeRiders($hub->id);

        // Suggested pickup rider: the one mapped to the seller's barangay (then the municipality rider).
        $areas = $hub->serviceAreas()->with(['rider', 'barangays.rider'])->get()->keyBy(fn($a) => $a->area_key);

        $suggested = $orders->getCollection()->mapWithKeys(function ($o) use ($areas) {
            $seller = $o->seller->sellerProfile;
            $area = $areas->get(HubServiceArea::keyFor($seller?->province, $seller?->municipality));

            return [$o->id => $area?->riderFor($seller?->barangay)?->id];
        });

        return view('logistics.pickups.index', compact('orders', 'riders', 'tab', 'counts', 'suggested'));
    }

    /** Assign (or reassign) the pickup rider and confirm the date/window. The rider then accepts in the app. */
    public function assign(Request $request, Order $order)
    {
        $hub = $request->user()->logisticsProfile;
        $this->authorizeOwnership($order, $hub->id);

        if ($order->status !== Order::STATUS_READY_FOR_PICKUP) {
            return back()->withErrors(['action' => 'This order is no longer awaiting pickup.']);
        }

        $validated = $request->validate([
            'rider_id' => ['required', 'integer'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'pickup_time_slot' => ['required', Rule::in(array_keys(Order::PICKUP_SLOTS))],
        ]);

        $rider = $this->activeRiders($hub->id)->firstWhere('id', (int) $validated['rider_id']);

        if (! $rider) {
            return back()->withErrors(['rider_id' => 'Choose one of your active, approved riders.']);
        }

        $changed = $order->pickup_date?->toDateString() !== $validated['pickup_date']
            || $order->pickup_time_slot !== $validated['pickup_time_slot'];

        // Same rider, same schedule: keep their acceptance. Anything else needs a fresh accept.
        $pickupStatus = ($order->pickup_rider_id === $rider->id && ! $changed)
            ? ($order->pickup_status ?? 'assigned')
            : 'assigned';

        DB::transaction(function () use ($order, $rider, $validated, $changed, $pickupStatus) {
            $order->update([
                'pickup_rider_id' => $rider->id,
                'pickup_status' => $pickupStatus,
                'pickup_date' => $validated['pickup_date'],
                'pickup_time_slot' => $validated['pickup_time_slot'],
            ]);

            $order->logInternalEvent(
                'Pickup scheduled' . ($changed ? ' (schedule adjusted by sorting center)' : ''),
                "{$rider->full_name} · " . Carbon::parse($validated['pickup_date'])->format('M d, Y') . ' · ' . Order::PICKUP_SLOTS[$validated['pickup_time_slot']]
            );
        });

        return back()->with('status', "Order #{$order->id} assigned to {$rider->full_name} for pickup.");
    }

    /** Hub can't serve this pickup: send it back to the seller to choose another hub. */
    public function decline(Request $request, Order $order)
    {
        $hub = $request->user()->logisticsProfile;
        $this->authorizeOwnership($order, $hub->id);

        if ($order->status !== Order::STATUS_READY_FOR_PICKUP || $order->pickup_rider_id) {
            return back()->withErrors(['action' => 'Only requests without an assigned rider can be declined.']);
        }

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        DB::transaction(function () use ($order, $hub, $validated) {
            $order->update([
                'status' => Order::STATUS_PREPARING,
                'logistics_profile_id' => null,
                'pickup_status' => 'declined',
                'pickup_decline_reason' => $validated['reason'],
                'pickup_date' => null,
                'pickup_time_slot' => null,
            ]);

            $order->logStatusEvent('Seller is rearranging the pickup.');
            $order->logInternalEvent("Pickup declined by {$hub->business_name}", $validated['reason']);
        });

        return back()->with('status', "Order #{$order->id} returned to the seller.");
    }

    /**
     * STAND-IN for the rider's own "Scan/Confirm Parcel" action in the app.
     * Uses the same transition code as the courier API. Remove once the app is live.
     */
    public function confirmPickup(Request $request, Order $order, OrderTransitions $transitions)
    {
        $hub = $request->user()->logisticsProfile;
        $this->authorizeOwnership($order, $hub->id);

        if ($order->status !== Order::STATUS_READY_FOR_PICKUP || ! $order->pickup_rider_id) {
            return back()->withErrors(['action' => 'Assign a rider before confirming pickup.']);
        }

        if (! $order->waybill_printed_at) {
            return back()->withErrors(['action' => 'The seller has not printed the shipping label yet.']);
        }

        if (! $transitions->confirmPickup($order, $request->user()->id)) {
            return back()->withErrors(['action' => 'This order was just updated. Please refresh the page.']);
        }

        return back()->with('status', "Order #{$order->id} confirmed picked up.");
    }

    private function activeRiders(int $hubId)
    {
        return CourierProfile::where('logistics_profile_id', $hubId)
            ->where('status', 'approved')->where('is_active', true)
            ->orderBy('first_name')->get();
    }

    private function authorizeOwnership(Order $order, int $hubId): void
    {
        abort_unless($order->logistics_profile_id === $hubId, 403, 'This order is not assigned to your sorting center.');
    }
}
