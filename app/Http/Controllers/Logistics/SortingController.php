<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\SortingSession;
use App\Services\HubContext;
use App\Services\HubCoverage;
use App\Services\ParcelLifecycle;
use App\Services\RiderCoverage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SortingController extends Controller
{
    public function __construct(private ParcelLifecycle $lifecycle) {}

    public function index(Request $request, HubCoverage $coverage)
    {
        $hub = HubContext::require($request);
        $user = $request->user();
        $tab = $request->get('tab') === 'to_assign' ? 'to_assign' : 'to_sort';

        $base = Order::heldBy($hub->id);

        $counts = [
            'to_sort' => (clone $base)->where('status', Order::STATUS_AT_SORTING_CENTER)->count(),
            'to_assign' => (clone $base)->where('status', Order::STATUS_SORTED)->count(),
        ];

        $orders = (clone $base)
            ->where('status', $tab === 'to_sort' ? Order::STATUS_AT_SORTING_CENTER : Order::STATUS_SORTED)
            ->with(['items', 'seller.sellerProfile', 'buyer.buyerProfile', 'destinationHub'])
            ->orderBy('id')
            ->get();

        $areas = $this->areasFor($hub);
        $riders = $this->activeRiders($hub->id);

        // Destination hub for every parcel on this page. Null means no hub in
        // this company serves the buyer's barangay.
        $destinations = $orders->mapWithKeys(function (Order $order) use ($coverage, $hub) {
            $dest = $order->ship_to;

            return [$order->id => $coverage->destinationHubFor(
                $hub,
                $dest['province'],
                $dest['municipality'],
                $dest['barangay']
            )];
        });

        // Get orders that need review (from active session scans)
        $needsReviewOrderIds = collect();
        $activeSession = SortingSession::where(fn ($q) => $q->where('user_id', $user->id)->orWhere('logistics_profile_id', $hub->id))
            ->active()
            ->first();

        if ($activeSession) {
            $needsReviewOrderIds = $activeSession->scans()
                ->where('result', 'needs_review')
                ->whereNotNull('order_id')
                ->pluck('order_id')
                ->unique();
        }

        // Suggested delivery rider per parcel: the barangay's rider, then the municipality rider.
        $suggested = $orders->mapWithKeys(fn ($o) => [$o->id => $this->suggestedRider($areas, $o)]);

        // Separate orders that need review
        $needsReviewOrders = $orders->whereIn('id', $needsReviewOrderIds);
        $regularOrders = $orders->whereNotIn('id', $needsReviewOrderIds);

        // Parcels grouped by destination hub first, then by barangay.
        $groups = $regularOrders
            ->groupBy(function ($o) {
                $dest = $o->ship_to;

                return ($o->destination_hub_id ?? 'none')
                    .'|'.HubServiceArea::barangayKey($dest['barangay']);
            })
            ->map(function ($group) use ($areas, $suggested) {
                $first = $group->first();
                $dest = $first->ship_to;

                return [
                    'hub' => $first->destinationHub,
                    'area' => $areas->get(HubServiceArea::keyFor($dest['province'], $dest['municipality'])),
                    'dest' => $dest,
                    'rider' => $suggested[$first->id],
                    'barangays' => $group->map(fn ($o) => $o->ship_to['barangay'])->filter()->unique()->values(),
                    'orders' => $group,
                ];
            });

        $serverTime = now();

        return view('logistics.sorting.index', compact('hub', 'tab', 'counts', 'orders', 'groups', 'areas', 'riders', 'suggested', 'destinations', 'activeSession', 'serverTime', 'needsReviewOrders'));
    }

    public function sort(Request $request, Order $order, HubCoverage $coverage)
    {
        $hub = HubContext::require($request);
        $user = $request->user();
        $this->authorize('actAsHoldingHub', $order);

        // Enforce active session requirement
        $activeSession = SortingSession::where(fn ($q) => $q->where('user_id', $user->id)->orWhere('logistics_profile_id', $hub->id))->active()->first();
        if (! $activeSession) {
            return back()->withErrors(['action' => 'Start a sorting session before sorting parcels.']);
        }

        if ($order->status !== Order::STATUS_AT_SORTING_CENTER) {
            return back()->withErrors(['action' => 'This parcel is not waiting to be sorted.']);
        }

        $order->load('buyer.buyerProfile');

        $result = $this->sortOrder($order, $hub, $coverage);

        if ($result === null) {
            return back()->withErrors(['action' => "Order #{$order->id} has no destination hub in your company for Brgy. {$order->ship_to['barangay']}, {$order->ship_to['municipality']}."]);
        }

        if ($result === 'transferred') {
            return back()->with('status', "Order #{$order->id} sorted and awaiting transfer to {$order->destinationHub?->business_name}.");
        }

        return back()->with('status', "Order #{$order->id} sorted for {$order->delivery_area}.");
    }

    public function sortAll(Request $request, HubCoverage $coverage)
    {
        $hub = HubContext::require($request);
        $user = $request->user();

        // Enforce active session requirement
        $activeSession = SortingSession::where(fn ($q) => $q->where('user_id', $user->id)->orWhere('logistics_profile_id', $hub->id))->active()->first();
        if (! $activeSession) {
            return back()->withErrors(['action' => 'Start a sorting session before sorting parcels.']);
        }

        $orders = Order::heldBy($hub->id)
            ->where('status', Order::STATUS_AT_SORTING_CENTER)
            ->with('buyer.buyerProfile')
            ->get();

        if ($orders->isEmpty()) {
            return back()->withErrors(['action' => 'There are no parcels waiting to be sorted.']);
        }

        $counts = ['sorted' => 0, 'transferred' => 0, 'skipped' => 0];

        DB::transaction(function () use ($orders, $hub, $coverage, &$counts) {
            foreach ($orders as $o) {
                $result = $this->sortOrder($o, $hub, $coverage);

                if ($result === null) {
                    $counts['skipped']++;
                } else {
                    $counts[$result]++;
                }
            }
        });

        if ($counts['sorted'] + $counts['transferred'] === 0) {
            return back()->withErrors(['action' => 'None of these parcels have a destination hub in your company. Check the buyers\' barangays and your service areas first.']);
        }

        $parts = [];

        if ($counts['sorted'] > 0) {
            $parts[] = "{$counts['sorted']} parcel(s) sorted.";
        }

        if ($counts['transferred'] > 0) {
            $parts[] = "{$counts['transferred']} parcel(s) awaiting transfer to another hub.";
        }

        if ($counts['skipped'] > 0) {
            $parts[] = "{$counts['skipped']} skipped: no destination hub in your company.";
        }

        return back()->with('status', implode(' ', $parts));
    }

    public function assign(Request $request, Order $order, HubCoverage $coverage)
    {
        $hub = HubContext::require($request);
        $this->authorize('actAsHoldingHub', $order);

        if ($order->status !== Order::STATUS_SORTED) {
            return back()->withErrors(['action' => 'This parcel is not waiting for a delivery rider.']);
        }

        $destination = $this->destinationFor($order, $hub, $coverage);

        if (! $destination) {
            return back()->withErrors(['action' => "Order #{$order->id} has no destination hub in your company."]);
        }

        if ((int) $destination->id !== (int) $hub->id) {
            $this->markAwaitingTransfer($order, $destination);

            return back()->withErrors(['action' => "Order #{$order->id} is awaiting transfer to {$destination->business_name} and cannot be assigned to a rider here."]);
        }

        $validated = $request->validate(['rider_id' => ['required', 'integer']]);
        $rider = $this->activeRiders($hub->id)->firstWhere('id', (int) $validated['rider_id']);

        if (! $rider) {
            return back()->withErrors(['rider_id' => 'Choose one of your active, approved riders.']);
        }

        $this->ensureCoversDelivery($rider, $order, $hub->id);

        DB::transaction(fn () => $this->assignRider($order, $rider));

        return back()->with('status', "Order #{$order->id} assigned to {$rider->full_name}.");
    }

    /** Assign a whole group of sorted parcels (the order ids sent by the page) to one rider. */
    public function assignArea(Request $request, HubCoverage $coverage)
    {
        $hub = HubContext::require($request);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer'],
            'rider_id' => ['required', 'integer'],
        ]);

        $rider = $this->activeRiders($hub->id)->firstWhere('id', (int) $validated['rider_id']);

        if (! $rider) {
            return back()->withErrors(['rider_id' => 'Choose one of your active, approved riders.']);
        }

        $orders = Order::heldBy($hub->id)
            ->where('status', Order::STATUS_SORTED)
            ->whereIn('id', $validated['order_ids'])
            ->with('buyer.buyerProfile')
            ->get();

        if ($orders->isEmpty()) {
            return back()->withErrors(['action' => 'No sorted parcels found to assign.']);
        }

        $assignable = collect();
        $transferred = 0;
        $unroutable = 0;

        DB::transaction(function () use ($orders, $hub, $coverage, $rider, &$assignable, &$transferred, &$unroutable) {
            foreach ($orders as $order) {
                $destination = $this->destinationFor($order, $hub, $coverage);

                if (! $destination) {
                    $unroutable++;

                    continue;
                }

                if ((int) $destination->id !== (int) $hub->id) {
                    $this->markAwaitingTransfer($order, $destination);
                    $transferred++;

                    continue;
                }

                $this->ensureCoversDelivery($rider, $order, $hub->id);
                $assignable->push($order);
            }

            $assignable->each(fn ($order) => $this->assignRider($order, $rider));
        });

        if ($assignable->isEmpty()) {
            return back()->withErrors([
                'action' => $transferred > 0
                    ? "{$transferred} parcel(s) are now awaiting transfer to another hub. None could be assigned here."
                    : 'None of these parcels have a destination hub in your company.',
            ]);
        }

        $message = "{$assignable->count()} parcel(s) assigned to {$rider->full_name}.";

        if ($transferred > 0) {
            $message .= " {$transferred} parcel(s) moved to awaiting transfer.";
        }

        if ($unroutable > 0) {
            $message .= " {$unroutable} skipped: no destination hub in your company.";
        }

        return back()->with('status', $message);
    }

    private function destinationFor(Order $order, LogisticsProfile $hub, HubCoverage $coverage): ?LogisticsProfile
    {
        $dest = $order->ship_to;

        return $coverage->destinationHubFor(
            $hub,
            $dest['province'],
            $dest['municipality'],
            $dest['barangay']
        );
    }

    private function ensureCoversDelivery(CourierProfile $rider, Order $order, int $hubId): void
    {
        $dest = $order->ship_to;

        if (RiderCoverage::coversAddress(
            $rider,
            $dest['province'],
            $dest['municipality'],
            $dest['barangay'],
            $hubId
        )) {
            return;
        }

        $place = $dest['barangay']
            ? " (Brgy. {$dest['barangay']}, {$dest['municipality']})"
            : '';

        throw ValidationException::withMessages([
            'rider_id' => "{$rider->full_name} does not cover the buyer's address{$place} for order #{$order->id}. Assign a rider who covers it, or update Service Areas.",
        ]);
    }

    private function suggestedRider(Collection $areas, Order $order): ?CourierProfile
    {
        $dest = $order->ship_to;

        return $areas->get(HubServiceArea::keyFor($dest['province'], $dest['municipality']))
            ?->riderFor($dest['barangay']);
    }

    /**
     * Sort the parcel at the hub that currently holds it.
     *
     * Returns "sorted" when this hub delivers the parcel, "transferred" when
     * it must move to another hub, or null when no destination hub exists.
     */
    private function sortOrder(Order $order, LogisticsProfile $hub, HubCoverage $coverage): ?string
    {
        $destination = $this->destinationFor($order, $hub, $coverage);

        if (! $destination) {
            return null;
        }

        $dest = $order->ship_to;
        $label = $coverage->deliveryAreaLabel($destination, $dest['province'], $dest['municipality']);

        $sameHub = (int) $destination->id === (int) $hub->id;

        $note = $sameHub
            ? 'Sorted for delivery to '.$dest['municipality'].'.'
            : 'Sorted and awaiting transfer to '.$destination->business_name.'.';

        $this->lifecycle->transition(
            $order,
            Order::STATUS_AT_SORTING_CENTER,
            $sameHub ? Order::STATUS_SORTED : Order::STATUS_AWAITING_TRANSFER,
            [
                'origin_hub_id' => $order->origin_hub_id ?? $hub->id,
                'destination_hub_id' => $destination->id,
                'current_hub_id' => $hub->id,
                'delivery_area' => $label,
            ],
            $note,
            eventType: ParcelEvent::TYPE_SORTED,
        );

        return $sameHub ? 'sorted' : 'transferred';
    }

    private function markAwaitingTransfer(Order $order, LogisticsProfile $destination): void
    {
        $this->lifecycle->transition(
            $order,
            Order::STATUS_SORTED,
            Order::STATUS_AWAITING_TRANSFER,
            ['destination_hub_id' => $destination->id],
            'Awaiting transfer to '.$destination->business_name.'.',
            eventType: ParcelEvent::TYPE_SORTED,
        );
    }

    private function assignRider(Order $order, CourierProfile $rider): void
    {
        $moved = $this->lifecycle->transition(
            $order,
            Order::STATUS_SORTED,
            Order::STATUS_ASSIGNED_TO_RIDER,
            ['delivery_rider_id' => $rider->id],
            null,
            riderId: $rider->id,
            eventType: ParcelEvent::TYPE_ASSIGNED,
        );

        if ($moved) {
            $order->logInternalEvent('Delivery rider assigned', "{$rider->full_name} · {$order->delivery_area}");
        }
    }

    private function areasFor(LogisticsProfile $hub): Collection
    {
        return $hub->serviceAreas()->with(['rider', 'barangays.rider'])->get()->keyBy(fn ($a) => $a->area_key);
    }

    private function activeRiders(int $hubId)
    {
        return CourierProfile::where('logistics_profile_id', $hubId)
            ->where('status', 'approved')->where('is_active', true)
            ->orderBy('first_name')->get();
    }
}
