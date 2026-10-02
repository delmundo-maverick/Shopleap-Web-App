<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\LogisticsProfile;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SortingController extends Controller
{
    public function index(Request $request)
    {
        $hub = $request->user()->logisticsProfile;
        $tab = $request->get('tab') === 'to_assign' ? 'to_assign' : 'to_sort';

        $base = Order::where('logistics_profile_id', $hub->id);

        $counts = [
            'to_sort' => (clone $base)->where('status', Order::STATUS_AT_SORTING_CENTER)->count(),
            'to_assign' => (clone $base)->where('status', Order::STATUS_SORTED)->count(),
        ];

        $orders = (clone $base)
            ->where('status', $tab === 'to_sort' ? Order::STATUS_AT_SORTING_CENTER : Order::STATUS_SORTED)
            ->with(['items', 'seller.sellerProfile', 'buyer.buyerProfile'])
            ->orderBy('id')
            ->get();

        $areas = $this->areasFor($hub);
        $riders = $this->activeRiders($hub->id);

        // Parcels grouped by destination, with the hub's mapped zone/rider for each.
        $groups = $orders
            ->groupBy(fn($o) => HubServiceArea::keyFor($o->ship_to['province'], $o->ship_to['municipality']))
            ->map(fn($group, $key) => [
                'area' => $areas->get($key),
                'dest' => $group->first()->ship_to,
                'orders' => $group,
            ]);

        return view('logistics.sorting.index', compact('tab', 'counts', 'orders', 'groups', 'areas', 'riders'));
    }

    public function sort(Request $request, Order $order)
    {
        $hub = $request->user()->logisticsProfile;
        abort_unless($order->logistics_profile_id === $hub->id, 403);

        if ($order->status !== Order::STATUS_AT_SORTING_CENTER) {
            return back()->withErrors(['action' => 'This parcel is not waiting to be sorted.']);
        }

        $order->load('buyer.buyerProfile');
        $this->sortOrder($order, $this->areasFor($hub));

        return back()->with('status', "Order #{$order->id} sorted for {$order->delivery_area}.");
    }

    public function sortAll(Request $request)
    {
        $hub = $request->user()->logisticsProfile;

        $orders = Order::where('logistics_profile_id', $hub->id)
            ->where('status', Order::STATUS_AT_SORTING_CENTER)
            ->with('buyer.buyerProfile')
            ->get();

        if ($orders->isEmpty()) {
            return back()->withErrors(['action' => 'There are no parcels waiting to be sorted.']);
        }

        $areas = $this->areasFor($hub);
        DB::transaction(fn() => $orders->each(fn($o) => $this->sortOrder($o, $areas)));

        return back()->with('status', "{$orders->count()} parcel(s) sorted.");
    }

    public function assign(Request $request, Order $order)
    {
        $hub = $request->user()->logisticsProfile;
        abort_unless($order->logistics_profile_id === $hub->id, 403);

        if ($order->status !== Order::STATUS_SORTED) {
            return back()->withErrors(['action' => 'This parcel is not waiting for a delivery rider.']);
        }

        $validated = $request->validate(['rider_id' => ['required', 'integer']]);
        $rider = $this->activeRiders($hub->id)->firstWhere('id', (int) $validated['rider_id']);

        if (! $rider) {
            return back()->withErrors(['rider_id' => 'Choose one of your active, approved riders.']);
        }

        DB::transaction(fn() => $this->assignRider($order, $rider));

        return back()->with('status', "Order #{$order->id} assigned to {$rider->full_name}.");
    }

    /** Assign every sorted parcel heading to one municipality to a single rider. */
    public function assignArea(Request $request)
    {
        $hub = $request->user()->logisticsProfile;

        $validated = $request->validate([
            'province' => ['required', 'string'],
            'municipality' => ['required', 'string'],
            'rider_id' => ['required', 'integer'],
        ]);

        $rider = $this->activeRiders($hub->id)->firstWhere('id', (int) $validated['rider_id']);

        if (! $rider) {
            return back()->withErrors(['rider_id' => 'Choose one of your active, approved riders.']);
        }

        $key = HubServiceArea::keyFor($validated['province'], $validated['municipality']);

        $orders = Order::where('logistics_profile_id', $hub->id)
            ->where('status', Order::STATUS_SORTED)
            ->with('buyer.buyerProfile')
            ->get()
            ->filter(fn($o) => HubServiceArea::keyFor($o->ship_to['province'], $o->ship_to['municipality']) === $key);

        if ($orders->isEmpty()) {
            return back()->withErrors(['action' => 'No sorted parcels found for that area.']);
        }

        DB::transaction(fn() => $orders->each(fn($o) => $this->assignRider($o, $rider)));

        return back()->with('status', "{$orders->count()} parcel(s) assigned to {$rider->full_name}.");
    }

    private function sortOrder(Order $order, Collection $areas): void
    {
        $dest = $order->ship_to;
        $area = $areas->get(HubServiceArea::keyFor($dest['province'], $dest['municipality']));
        $label = $area?->zone_name ?: ($dest['municipality'] ?: 'Unassigned');

        $order->update(['status' => Order::STATUS_SORTED, 'delivery_area' => $label]);
        $order->logStatusEvent('Sorted for delivery to ' . ($dest['municipality'] ?: 'your area') . '.');
    }

    private function assignRider(Order $order, CourierProfile $rider): void
    {
        $order->update([
            'delivery_rider_id' => $rider->id,
            'status' => Order::STATUS_ASSIGNED_TO_RIDER,
        ]);

        $order->logStatusEvent();
        $order->logInternalEvent('Delivery rider assigned', "{$rider->full_name} · {$order->delivery_area}");
    }

    private function areasFor(LogisticsProfile $hub): Collection
    {
        return $hub->serviceAreas()->with('rider')->get()->keyBy(fn($a) => $a->area_key);
    }

    private function activeRiders(int $hubId)
    {
        return CourierProfile::where('logistics_profile_id', $hubId)
            ->where('status', 'approved')->where('is_active', true)
            ->orderBy('first_name')->get();
    }
}
