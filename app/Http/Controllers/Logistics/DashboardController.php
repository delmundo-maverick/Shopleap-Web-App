<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $hub = $request->user()->logisticsProfile;
        $orders = fn() => Order::where('logistics_profile_id', $hub->id);
        $riders = fn() => CourierProfile::where('logistics_profile_id', $hub->id);

        $cards = [
            [
                'label' => 'Pending Rider Applications',
                'value' => $riders()->where('status', 'pending')->count(),
                'href' => route('logistics.riders.index'),
                'alert' => true
            ],
            [
                'label' => 'Active Riders',
                'value' => $riders()->where('status', 'approved')->where('is_active', true)->count(),
                'href' => route('logistics.riders.index', ['status' => 'approved']),
                'alert' => false
            ],
            [
                'label' => 'Pickups to Assign',
                'value' => $orders()->where('status', Order::STATUS_READY_FOR_PICKUP)->whereNull('pickup_rider_id')->count(),
                'href' => route('logistics.pickups.index'),
                'alert' => true
            ],
            [
                'label' => 'Incoming Parcels',
                'value' => $orders()->where('status', Order::STATUS_PICKED_UP)->count(),
                'href' => route('logistics.incoming.index'),
                'alert' => true
            ],
            [
                'label' => 'To Sort',
                'value' => $orders()->where('status', Order::STATUS_AT_SORTING_CENTER)->count(),
                'href' => route('logistics.sorting.index'),
                'alert' => true
            ],
            [
                'label' => 'To Assign for Delivery',
                'value' => $orders()->where('status', Order::STATUS_SORTED)->count(),
                'href' => route('logistics.sorting.index', ['tab' => 'to_assign']),
                'alert' => true
            ],
            [
                'label' => 'Out for Delivery',
                'value' => $orders()->whereIn('status', [Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY])->count(),
                'href' => route('logistics.deliveries.index'),
                'alert' => false
            ],
            [
                'label' => 'Failed Deliveries',
                'value' => $orders()->where('status', Order::STATUS_DELIVERY_FAILED)->count(),
                'href' => route('logistics.deliveries.index', ['tab' => 'failed']),
                'alert' => true
            ],
        ];

        $hasServiceAreas = $hub->serviceAreas()->exists();

        return view('logistics.dashboard', compact('cards', 'hub', 'hasServiceAreas'));
    }
}
