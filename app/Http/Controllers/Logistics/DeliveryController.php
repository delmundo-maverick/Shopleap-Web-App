<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * NOTE: the out / delivered / failed actions are STAND-INS for the rider's
 * own actions in the Courier mobile app. Remove them when the app ships.
 */
class DeliveryController extends Controller
{
    const FAILURE_REASONS = [
        'Recipient not available',
        'Wrong or incomplete address',
        'Unable to contact recipient',
        'Recipient refused the parcel',
        'Other',
    ];

    const TABS = [
        'assigned' => [Order::STATUS_ASSIGNED_TO_RIDER],
        'out' => [Order::STATUS_OUT_FOR_DELIVERY],
        'failed' => [Order::STATUS_DELIVERY_FAILED],
        'delivered' => [Order::STATUS_DELIVERED],
    ];

    public function index(Request $request)
    {
        $hub = $request->user()->logisticsProfile;

        $tab = $request->get('tab', 'assigned');
        if (! isset(self::TABS[$tab])) {
            $tab = 'assigned';
        }

        $base = Order::where('logistics_profile_id', $hub->id);

        $counts = collect(self::TABS)
            ->map(fn($statuses) => (clone $base)->whereIn('status', $statuses)->count())
            ->all();

        $query = (clone $base)
            ->whereIn('status', self::TABS[$tab])
            ->with(['items', 'seller.sellerProfile', 'buyer.buyerProfile', 'deliveryRider'])
            ->orderBy('id');

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $orders = $query->paginate(15)->withQueryString();
        $riders = $this->activeRiders($hub->id);

        return view('logistics.deliveries.index', [
            'tab' => $tab,
            'counts' => $counts,
            'orders' => $orders,
            'riders' => $riders,
            'reasons' => self::FAILURE_REASONS,
        ]);
    }

    public function outForDelivery(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_ASSIGNED_TO_RIDER) {
            return $this->wrongStage();
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => Order::STATUS_OUT_FOR_DELIVERY]);
            $order->logStatusEvent();
        });

        return back()->with('status', "Order #{$order->id} is out for delivery.");
    }

    public function delivered(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_OUT_FOR_DELIVERY) {
            return $this->wrongStage();
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => Order::STATUS_DELIVERED]);
            $order->logStatusEvent('Waiting for the buyer to confirm receipt.');
        });

        return back()->with('status', "Order #{$order->id} marked delivered. The buyer can now confirm receipt.");
    }

    public function failed(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_OUT_FOR_DELIVERY) {
            return $this->wrongStage();
        }

        $validated = $request->validate([
            'reason' => ['required', Rule::in(self::FAILURE_REASONS)],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $attempt = $order->delivery_attempts + 1;

        DB::transaction(function () use ($order, $validated, $attempt) {
            $order->update(['status' => Order::STATUS_DELIVERY_FAILED, 'delivery_attempts' => $attempt]);

            $order->logStatusEvent(
                $validated['reason']
                    . (! empty($validated['note']) ? " — {$validated['note']}" : '')
                    . " (Attempt {$attempt} of " . Order::MAX_DELIVERY_ATTEMPTS . ')'
            );
        });

        return back()->with('status', "Order #{$order->id} recorded as a failed delivery (attempt {$attempt}).");
    }

    public function reschedule(Request $request, Order $order)
    {
        $hub = $request->user()->logisticsProfile;
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_DELIVERY_FAILED) {
            return $this->wrongStage();
        }

        if ($order->delivery_attempts >= Order::MAX_DELIVERY_ATTEMPTS) {
            return back()->withErrors(['action' => 'Maximum delivery attempts reached. Return the parcel to the seller.']);
        }

        $validated = $request->validate(['rider_id' => ['nullable', 'integer']]);
        $riderId = $order->delivery_rider_id;

        if (! empty($validated['rider_id'])) {
            $rider = $this->activeRiders($hub->id)->firstWhere('id', (int) $validated['rider_id']);

            if (! $rider) {
                return back()->withErrors(['rider_id' => 'Choose one of your active, approved riders.']);
            }

            $riderId = $rider->id;
        }

        DB::transaction(function () use ($order, $riderId) {
            $order->update(['status' => Order::STATUS_ASSIGNED_TO_RIDER, 'delivery_rider_id' => $riderId]);
            $order->logStatusEvent('Delivery rescheduled. Attempt ' . ($order->delivery_attempts + 1) . ' of ' . Order::MAX_DELIVERY_ATTEMPTS . '.');
        });

        return back()->with('status', "Order #{$order->id} rescheduled for delivery.");
    }

    public function returnToSeller(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_DELIVERY_FAILED) {
            return $this->wrongStage();
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => Order::STATUS_RETURNED]);
            $order->restoreStock();
            $order->logStatusEvent('The parcel is being sent back to the seller.');
        });

        return back()->with('status', "Order #{$order->id} marked as returned to the seller.");
    }

    private function wrongStage()
    {
        return back()->withErrors(['action' => 'This order is not at the right stage for that action.']);
    }

    private function activeRiders(int $hubId)
    {
        return CourierProfile::where('logistics_profile_id', $hubId)
            ->where('status', 'approved')->where('is_active', true)
            ->orderBy('first_name')->get();
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        abort_unless($order->logistics_profile_id === $request->user()->logisticsProfile->id, 403);
    }
}
