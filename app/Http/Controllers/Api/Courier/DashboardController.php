<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RiderEarnings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Counts for the dashboard tiles and unread notifications. */
    public function summary(Request $request, RiderEarnings $earnings): JsonResponse
    {
        $c = $request->attributes->get('courier');

        $pickups = Order::where('pickup_rider_id', $c->id)
            ->where('status', Order::STATUS_READY_FOR_PICKUP);

        $deliveries = Order::where('delivery_rider_id', $c->id);

        $deliveredToday = (clone $deliveries)
            ->whereIn('status', RiderEarnings::EARNED_STATUSES)
            ->whereDate('delivered_at', today())
            ->count();

        return response()->json([
            'pickups' => [
                'awaiting_accept' => (clone $pickups)
                    ->where('pickup_status', 'assigned')
                    ->count(),
                'accepted' => (clone $pickups)
                    ->where('pickup_status', 'accepted')
                    ->count(),
                'total' => (clone $pickups)->count(),
            ],
            'deliveries' => [
                'to_deliver' => (clone $deliveries)
                    ->where('status', Order::STATUS_ASSIGNED_TO_RIDER)
                    ->count(),
                'out_for_delivery' => (clone $deliveries)
                    ->where('status', Order::STATUS_OUT_FOR_DELIVERY)
                    ->count(),
                'waiting_hub_decision' => (clone $deliveries)
                    ->where('status', Order::STATUS_DELIVERY_FAILED)
                    ->count(),
            ],
            'delivered_today' => $deliveredToday,
            'earned_today' => $earnings->totals($earnings->deliveries(null, $c->id, now()->startOfDay(), now()->endOfDay()))['rider_pay'] ?? '0.00',
            'currency' => 'PHP',
            'unread_notifications' => $request->user()
                ->unreadNotifications()
                ->count(),
        ]);
    }
}
