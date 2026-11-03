<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\ShipmentBag;
use App\Services\HubContext;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $hub = HubContext::require($request);
        $orders = fn () => Order::heldBy($hub->id);
        $riders = fn () => CourierProfile::where('logistics_profile_id', $hub->id);

        $cards = [
            [
                'label' => 'Pending Rider Applications',
                'value' => $riders()->where('status', 'pending')->count(),
                'href' => route('logistics.riders.index'),
                'alert' => true,
            ],
            [
                'label' => 'Active Riders',
                'value' => $riders()->where('status', 'approved')->where('is_active', true)->count(),
                'href' => route('logistics.riders.index', ['status' => 'approved']),
                'alert' => false,
            ],
            [
                'label' => 'Pickups to Assign',
                'value' => $orders()->where('status', Order::STATUS_READY_FOR_PICKUP)->whereNull('pickup_rider_id')->count(),
                'href' => route('logistics.pickups.index'),
                'alert' => true,
            ],
            [
                'label' => 'Incoming Parcels',
                'value' => $orders()->where('status', Order::STATUS_PICKED_UP)->count(),
                'href' => route('logistics.incoming.index'),
                'alert' => true,
            ],
            [
                'label' => 'To Sort',
                'value' => $orders()->where('status', Order::STATUS_AT_SORTING_CENTER)->count(),
                'href' => route('logistics.sorting.index'),
                'alert' => true,
            ],
            [
                'label' => 'Awaiting Transfer (Outbound)',
                'value' => $orders()->where('status', Order::STATUS_AWAITING_TRANSFER)->count(),
                'href' => route('logistics.outbound.index'),
                'alert' => true,
            ],
            [
                'label' => 'Outbound Bags (Open/Sealed)',
                'value' => ShipmentBag::sentBy($hub->id)
                    ->whereIn('status', [ShipmentBag::STATUS_OPEN, ShipmentBag::STATUS_SEALED])
                    ->count(),
                'href' => route('logistics.outbound.index'),
                'alert' => false,
            ],
            [
                'label' => 'Inbound Bags',
                'value' => ShipmentBag::addressedTo($hub->id)
                    ->whereIn('status', [ShipmentBag::STATUS_SEALED, ShipmentBag::STATUS_IN_TRANSIT])
                    ->count(),
                'href' => route('logistics.inbound.index'),
                'alert' => true,
            ],
            [
                'label' => 'To Assign for Delivery',
                'value' => $orders()->where('status', Order::STATUS_SORTED)->count(),
                'href' => route('logistics.sorting.index', ['tab' => 'to_assign']),
                'alert' => true,
            ],
            [
                'label' => 'Out for Delivery',
                'value' => $orders()->whereIn('status', [Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY])->count(),
                'href' => route('logistics.deliveries.index'),
                'alert' => false,
            ],
            [
                'label' => 'Failed Deliveries',
                'value' => $orders()->where('status', Order::STATUS_DELIVERY_FAILED)->count(),
                'href' => route('logistics.deliveries.index', ['tab' => 'failed']),
                'alert' => true,
            ],
            [
                'label' => 'Stuck Parcels (>24h)',
                'value' => Order::heldBy($hub->id)
                    ->whereIn('status', [
                        Order::STATUS_PLACED, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING,
                        Order::STATUS_READY_FOR_PICKUP, Order::STATUS_PICKED_UP, Order::STATUS_AT_SORTING_CENTER,
                        Order::STATUS_SORTED, Order::STATUS_AWAITING_TRANSFER, Order::STATUS_ASSIGNED_TO_RIDER,
                        Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERY_FAILED,
                    ])
                    ->where('updated_at', '<=', now()->subHours(24))
                    ->whereDoesntHave('parcelEvents', fn ($q) => $q->where('created_at', '>', now()->subHours(24)))
                    ->count(),
                'href' => route('logistics.dashboard'),
                'alert' => true,
            ],
            [
                'label' => 'Riders & Coverage',
                'value' => $riders()->where('status', 'approved')->where('is_active', true)->count(),
                'href' => route('logistics.riders.index'),
                'alert' => false,
            ],
        ];

        $hasServiceAreas = $hub->serviceAreas()->exists();

        return view('logistics.dashboard', compact('cards', 'hub', 'hasServiceAreas'));
    }
}
