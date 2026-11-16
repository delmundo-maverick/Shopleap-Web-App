<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ScanCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderScanController extends Controller
{
    public function __invoke(Request $request, string $code): JsonResponse
    {
        $courier = $request->attributes->get('courier');
        $orderId = ScanCode::orderId($code);
        abort_if($orderId === null, 404, 'Parcel not found.');

        $order = Order::with(['items', 'buyer.buyerProfile', 'originHub', 'currentHub', 'logisticsProfile'])
            ->whereKey($orderId)
            ->where(function (Builder $query) use ($courier): void {
                $query->where(function (Builder $pickup) use ($courier): void {
                    $pickup->where('pickup_rider_id', $courier->id)
                        ->where('status', Order::STATUS_READY_FOR_PICKUP)
                        ->whereRaw('COALESCE(origin_hub_id, logistics_profile_id) = ?', [$courier->logistics_profile_id]);
                })->orWhere(function (Builder $delivery) use ($courier): void {
                    $delivery->where('delivery_rider_id', $courier->id)
                        ->whereIn('status', [Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY])
                        ->whereRaw('COALESCE(current_hub_id, logistics_profile_id) = ?', [$courier->logistics_profile_id]);
                });
            })->firstOrFail();

        $isPickup = $order->status === Order::STATUS_READY_FOR_PICKUP;
        $hub = $isPickup ? ($order->originHub ?? $order->logisticsProfile) : ($order->currentHub ?? $order->logisticsProfile);

        return response()->json(['data' => [
            ...$order->courierPayload(),
            'order_id' => $order->order_number,
            'order_number' => $order->order_number,
            'waybill_code' => $order->waybill_code,
            'status' => $order->status,
            'pickup_status' => $order->pickup_status,
            'hub_id' => $hub?->id,
            'hub_name' => $hub?->business_name,
            'deliver_to_hub' => $isPickup ? $hub?->business_name : null,
        ]]);
    }
}
