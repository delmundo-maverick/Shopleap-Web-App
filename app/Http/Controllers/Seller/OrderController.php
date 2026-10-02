<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $order->load([
            'items.product.images',
            'buyer.buyerProfile',
            'logisticsProfile',
            'pickupRider',
            'events.actor',
        ]);

        // Hubs the seller can pick for pickup: those covering the seller's own municipality.
        $hubs = collect();
        $lastHubId = null;

        if ($order->status === Order::STATUS_PREPARING) {
            $sellerProfile = $request->user()->sellerProfile;

            $hubs = LogisticsProfile::available()
                ->covering($sellerProfile->province, $sellerProfile->municipality)
                ->withCount(['riders as active_riders_count' => fn($q) => $q
                    ->where('status', 'approved')->where('is_active', true)])
                ->orderBy('business_name')
                ->get();

            $lastHubId = Order::where('seller_id', $request->user()->id)
                ->whereNotNull('logistics_profile_id')
                ->latest()
                ->value('logistics_profile_id');
        }

        return view('seller.orders.show', compact('order', 'hubs', 'lastHubId'));
    }

    /** AJAX — manual status changes (Placed / Confirmed / Preparing / Cancelled). */
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if (! in_array($order->status, Order::SELLER_MODIFIABLE_FROM, true)) {
            $message = $order->status === Order::STATUS_READY_FOR_PICKUP
                ? 'Cancel the pickup request first if you need to change this order.'
                : 'This order has moved past the stage you can update. It is now being handled by delivery.';

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $request->validate([
            'status' => ['required', Rule::in(Order::SELLER_EDITABLE_STATUSES)],
        ]);

        if ($request->status !== $order->status) {
            DB::transaction(function () use ($order, $request) {
                $order->update(['status' => $request->status]);

                if ($order->status === Order::STATUS_CANCELLED) {
                    $order->restoreStock();
                }

                $order->logStatusEvent();
            });
        }

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->id} is now marked as {$order->status_label}.",
            'status' => $order->status,
        ]);
    }

    /** Choose a sorting center + pickup date/window and mark the order ready for pickup. */
    public function requestPickup(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_PREPARING) {
            return back()->withErrors(['pickup' => 'Only orders that are being prepared can be marked ready for pickup.']);
        }

        $validated = $request->validate([
            'logistics_profile_id' => ['required', 'integer'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(7)->toDateString()],
            'pickup_time_slot' => ['required', Rule::in(array_keys(Order::PICKUP_SLOTS))],
        ]);

        // Re-check server-side that this hub really serves the seller's area.
        $sellerProfile = $request->user()->sellerProfile;

        $hub = LogisticsProfile::available()
            ->covering($sellerProfile->province, $sellerProfile->municipality)
            ->find($validated['logistics_profile_id']);

        if (! $hub) {
            return back()->withErrors(['logistics_profile_id' => 'That sorting center does not serve your area.'])->withInput();
        }

        DB::transaction(function () use ($order, $hub, $validated) {
            $order->update([
                'status' => Order::STATUS_READY_FOR_PICKUP,
                'logistics_profile_id' => $hub->id,
                'pickup_status' => 'requested',
                'pickup_date' => $validated['pickup_date'],
                'pickup_time_slot' => $validated['pickup_time_slot'],
                'pickup_decline_reason' => null,
            ]);

            $order->logStatusEvent();
            $order->logInternalEvent(
                "Pickup requested from {$hub->business_name}",
                Carbon::parse($validated['pickup_date'])->format('M d, Y') . ' · ' . Order::PICKUP_SLOTS[$validated['pickup_time_slot']]
            );
        });

        return back()->with('status', "Pickup requested from {$hub->business_name}.");
    }

    /** Withdraw a pickup request — only possible before a rider has been assigned. */
    public function cancelPickup(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_READY_FOR_PICKUP || $order->pickup_rider_id) {
            return back()->withErrors(['pickup' => 'A rider is already assigned. Please message the sorting center to change this pickup.']);
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => Order::STATUS_PREPARING,
                'logistics_profile_id' => null,
                'pickup_status' => null,
                'pickup_date' => null,
                'pickup_time_slot' => null,
            ]);

            $order->logStatusEvent('Seller is rearranging the pickup.');
        });

        return back()->with('status', 'Pickup request cancelled. You can choose another sorting center.');
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        if ($order->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
