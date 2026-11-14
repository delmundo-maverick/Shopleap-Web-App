<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Services\HubCoverage;
use App\Services\OrderCancellation;
use App\Services\ParcelLifecycle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private ParcelLifecycle $lifecycle) {}

    public function index(Request $request)
    {
        $query = Order::with(['items', 'buyer'])
            ->where('seller_id', $request->user()->id)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->matchingPublicCode($search)
                    ->orWhere('buyer_name', 'ilike', "%{$search}%");
            });
        }

        $status = $request->get('status', 'all');

        if ($status === 'new') {
            $query->whereIn('status', Order::GROUP_NEW);
        } elseif ($status === 'prepare') {
            $query->whereIn('status', Order::GROUP_PREPARE);
        } elseif ($status === 'cancel_requests') {
            $query->pendingCancelRequest();
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->paginate(15)->withQueryString();

        $pendingCancelRequests = Order::where('seller_id', $request->user()->id)
            ->pendingCancelRequest()
            ->count();

        return view('seller.orders.index', compact('orders', 'status', 'pendingCancelRequests'));
    }

    public function show(Request $request, Order $order, HubCoverage $coverage)
    {
        $this->authorizeOwnership($request, $order);

        $order->load([
            'items.product.images',
            'buyer.buyerProfile',
            'logisticsProfile',
            'originHub',
            'destinationHub',
            'pickupRider',
            'events.actor',
            'parcelEvents.hub',
            'returns',
        ]);

        // The pickup hub was already resolved from the seller's address at
        // checkout. Older orders fall back to resolving it again here.
        $hubs = collect();
        $lastHubId = null;

        if ($order->status === Order::STATUS_PREPARING) {
            $sellerProfile = $request->user()->sellerProfile;

            $originHub = $order->originHub
                ?? $coverage->hubForAddress(
                    $sellerProfile?->province,
                    $sellerProfile?->municipality,
                    $sellerProfile?->barangay
                );

            $hubs = $originHub
                ? LogisticsProfile::available()
                    ->whereKey($originHub->id)
                    ->withCount(['riders as active_riders_count' => fn ($q) => $q
                        ->where('status', 'approved')->where('is_active', true)])
                    ->get()
                : collect();

            $lastHubId = Order::where('seller_id', $request->user()->id)
                ->whereNotNull('logistics_profile_id')
                ->latest()
                ->value('logistics_profile_id');
        }

        return view('seller.orders.show', compact('order', 'hubs', 'lastHubId'));
    }

    /** AJAX: forward status changes, one step at a time (Placed → Confirmed → Preparing). Cancelling has its own action. */
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

        $from = $order->status;
        $to = $request->status;

        if ($to === $from) {
            return response()->json([
                'success' => true,
                'message' => "Order #{$order->order_number} is already marked as {$order->status_label}.",
                'status' => $order->status,
            ]);
        }

        if (! in_array($to, Order::SELLER_TRANSITIONS[$from] ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => "An order can't move from ".Order::STATUS_LABELS[$from].' to '.Order::STATUS_LABELS[$to].'.',
            ], 422);
        }

        $moved = false;

        DB::transaction(function () use ($order, $from, $to, $request, &$moved) {
            // Conditional update: a double-click or a second tab can't apply the move twice.
            $moved = $this->lifecycle->transition(
                $order,
                $from,
                $to,
                [],
                null,
                $request->user()->id,
            );
        });

        if (! $moved) {
            return response()->json([
                'success' => false,
                'message' => 'This order was just updated. Please refresh the page.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->order_number} is now marked as {$order->status_label}.",
            'status' => $order->status,
        ]);
    }

    /** Seller cancels the order with a reason (any time before the courier collects it). */
    public function cancel(Request $request, Order $order, OrderCancellation $cancellation)
    {
        $this->authorizeOwnership($request, $order);

        $data = $request->validate([
            'reason' => ['required', Rule::in(Order::SELLER_CANCEL_REASONS)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $note = filled($data['note'] ?? null) ? trim($data['note']) : null;

        $done = $cancellation->cancel(
            $order,
            'seller',
            $data['reason'],
            $note,
            $request->user()->id,
            Order::SELLER_CANCEL_FROM
        );

        if (! $done) {
            $message = $order->fresh()->status === Order::STATUS_CANCELLED
                ? 'This order is already cancelled.'
                : "This order has already been picked up by the courier, so it can't be cancelled anymore.";

            return back()->withErrors(['cancel' => $message]);
        }

        return redirect()
            ->route('seller.orders.show', $order)
            ->with('status', "Order #{$order->order_number} was cancelled and the stock was returned.");
    }

    public function approveCancel(Request $request, Order $order, OrderCancellation $cancellation)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->cancel_request_status !== 'pending') {
            return back()->withErrors(['cancel' => 'There is no pending cancellation request for this order.']);
        }

        if ($cancellation->approveRequest($order, $request->user()->id)) {
            return redirect()
                ->route('seller.orders.show', $order)
                ->with('status', 'Cancellation approved. The order was cancelled and the stock was returned.');
        }

        $cancellation->expire($order);

        return back()->withErrors(['cancel' => 'This order has already been picked up by the courier, so it can no longer be cancelled.']);
    }

    public function declineCancel(Request $request, Order $order, OrderCancellation $cancellation)
    {
        $this->authorizeOwnership($request, $order);

        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        if ($cancellation->decline($order, trim($data['reason']), $request->user()->id)) {
            return back()->with('status', 'Cancellation request declined. The buyer can see your reason.');
        }

        return back()->withErrors(['cancel' => 'There is no pending cancellation request for this order.']);
    }

    /** Confirm pickup from the hub assigned to the seller's address and mark the order ready. */
    public function requestPickup(Request $request, Order $order, HubCoverage $coverage)
    {
        $this->authorizeOwnership($request, $order);

        if ($order->status !== Order::STATUS_PREPARING) {
            return back()->withErrors(['pickup' => 'Only orders that are being prepared can be marked ready for pickup.']);
        }

        if ($order->cancel_request_status === 'pending') {
            return back()->withErrors(['pickup' => 'The buyer asked to cancel this order. Approve or decline the request first.']);
        }

        if (! $order->waybill_printed_at) {
            return back()->withErrors(['pickup' => 'Print the shipping label before requesting a pickup.']);
        }

        $validated = $request->validate([
            'logistics_profile_id' => ['required', 'integer'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(7)->toDateString()],
            'pickup_time_slot' => ['required', Rule::in(array_keys(Order::PICKUP_SLOTS))],
        ]);

        $sellerProfile = $request->user()->sellerProfile;
        $dest = $order->ship_to;

        // The pickup hub is fixed by the seller's address; the buyer's barangay
        // decides which hub delivers. Both were resolved at checkout.
        $hub = $order->originHub ?? $coverage->hubForAddress(
            $sellerProfile?->province,
            $sellerProfile?->municipality,
            $sellerProfile?->barangay
        );

        if (! $hub || ! $hub->isAvailable() || (int) $hub->id !== (int) $validated['logistics_profile_id']) {
            return back()->withErrors(['logistics_profile_id' => 'That sorting center is not the one assigned to pick up from your address.'])->withInput();
        }

        $destinationHub = $coverage->destinationHubFor(
            $hub,
            $dest['province'],
            $dest['municipality'],
            $dest['barangay']
        ) ?? $order->destinationHub;

        if (! $destinationHub) {
            return back()->withErrors(['pickup' => "This order can't be routed to the buyer's address yet. Contact support before requesting a pickup."])->withInput();
        }

        DB::transaction(function () use ($order, $hub, $destinationHub, $validated, $request) {
            $moved = $this->lifecycle->transition(
                $order,
                Order::STATUS_PREPARING,
                Order::STATUS_READY_FOR_PICKUP,
                [
                    'logistics_profile_id' => $hub->id,
                    'current_hub_id' => $hub->id,
                    'origin_hub_id' => $hub->id,
                    'destination_hub_id' => $destinationHub->id,
                    'pickup_status' => 'requested',
                    'pickup_date' => $validated['pickup_date'],
                    'pickup_time_slot' => $validated['pickup_time_slot'],
                    'pickup_decline_reason' => null,
                ],
                null,
                $request->user()->id,
                $hub->id,
            );

            if ($moved) {
                $order->logInternalEvent(
                    "Pickup requested from {$hub->business_name}",
                    Carbon::parse($validated['pickup_date'])->format('M d, Y').' · '.Order::PICKUP_SLOTS[$validated['pickup_time_slot']]
                );
            }
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

        DB::transaction(function () use ($order, $request) {
            // Keep origin/destination hubs: the route was set at checkout and
            // the next pickup request goes to the same hub.
            $this->lifecycle->transition(
                $order,
                Order::STATUS_READY_FOR_PICKUP,
                Order::STATUS_PREPARING,
                [
                    'logistics_profile_id' => null,
                    'current_hub_id' => null,
                    'pickup_status' => null,
                    'pickup_date' => null,
                    'pickup_time_slot' => null,
                ],
                'Seller is rearranging the pickup.',
                $request->user()->id,
            );
        });

        return back()->with('status', 'Pickup request cancelled. You can choose another pickup date.');
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        if ($order->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
