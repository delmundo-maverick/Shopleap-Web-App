<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Logistics\DeliveryController as HubDeliveryController;
use App\Models\CourierProfile;
use App\Models\Order;
use App\Services\OrderTransitions;
use App\Services\RiderCoverage;
use App\Services\RiderEarnings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    /** My deliveries: assigned to me and not finished yet. */
    public function index(Request $request): JsonResponse
    {
        $courier = $request->attributes->get('courier');

        $orders = Order::with(['items', 'seller.sellerProfile', 'buyer.buyerProfile'])
            ->where('delivery_rider_id', $courier->id)
            ->whereIn('status', [Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY])
            ->whereRaw('COALESCE(current_hub_id, logistics_profile_id) = ?', [$courier->logistics_profile_id])
            ->orderBy('delivery_area')->orderBy('ship_municipality')->orderBy('ship_barangay')->orderBy('id')
            ->get();

        return response()->json([
            'data' => $orders->map(fn ($o) => $this->payload($o)),
            'failure_reasons' => HubDeliveryController::FAILURE_REASONS,
        ]);
    }

    public function out(Request $request, Order $order, OrderTransitions $transitions): JsonResponse
    {
        $this->owned($request, $order);

        if (! $transitions->outForDelivery($order, $request->user()->id)) {
            return $this->wrongStage();
        }

        return response()->json(['message' => 'Marked as out for delivery.', 'data' => $this->payload($order->fresh(['items', 'seller.sellerProfile', 'buyer.buyerProfile']))]);
    }

    /** Delivered: the rider sends the cash actually collected. A difference from the order total is flagged to the hub. */
    public function delivered(Request $request, Order $order, OrderTransitions $transitions, RiderEarnings $earnings): JsonResponse
    {
        $courier = $this->owned($request, $order);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0']]);

        if (! $transitions->delivered($order, $request->user()->id, (float) $data['amount'], expectedCourierId: $courier->id, expectedHubId: in_array($order->status, RiderEarnings::EARNED_STATUSES, true) ? null : $courier->logistics_profile_id)) {
            return $this->wrongStage();
        }

        return response()->json(['message' => 'Delivery recorded. Thank you.', 'earning' => $earnings->earning($order)]);
    }

    public function failed(Request $request, Order $order, OrderTransitions $transitions): JsonResponse
    {
        $this->owned($request, $order);

        $data = $request->validate([
            'reason' => ['required', Rule::in(HubDeliveryController::FAILURE_REASONS)],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $attempt = $transitions->failed($order, $data['reason'], $data['note'] ?? null, $request->user()->id);

        if ($attempt === null) {
            return $this->wrongStage();
        }

        return response()->json([
            'message' => "Failed delivery recorded (attempt {$attempt} of ".Order::MAX_DELIVERY_ATTEMPTS.'). The sorting center will decide the next step.',
        ]);
    }

    private function owned(Request $request, Order $order): CourierProfile
    {
        $courier = $request->attributes->get('courier');

        if (in_array($order->status, RiderEarnings::EARNED_STATUSES, true)) {
            abort_unless((int) ($order->earning_courier_id ?? $order->delivery_rider_id) === (int) $courier->id, 403, 'This delivery is not assigned to you.');

            return $courier;
        }

        abort_unless($order->delivery_rider_id === $courier->id, 403, 'This delivery is not assigned to you.');

        abort_unless(
            (int) ($order->current_hub_id ?? $order->logistics_profile_id) === (int) $courier->logistics_profile_id,
            403,
            'This delivery belongs to another hub.'
        );

        $dest = $order->ship_to;

        abort_unless(
            RiderCoverage::coversAddress(
                $courier,
                $dest['province'],
                $dest['municipality'],
                $dest['barangay'],
                $courier->logistics_profile_id
            ),
            422,
            'You do not cover this delivery address. Ask the sorting center to reassign it.'
        );

        return $courier;
    }

    private function wrongStage(): JsonResponse
    {
        return response()->json(['message' => 'This order is not at the right stage for that action.'], 422);
    }

    private function payload(Order $o): array
    {
        $ship = $o->ship_to;

        return [
            'order_id' => $o->order_number,
            'order_number' => $o->order_number,
            'waybill_code' => $o->waybill_code,
            'status' => $o->status,
            'hub_id' => $o->current_hub_id ?? $o->logistics_profile_id,
            'delivery_area' => $o->delivery_area,
            'buyer' => [
                'name' => $ship['name'],
                'contact_no' => $ship['contact_no'],
                'address' => trim(implode(', ', array_filter([
                    $ship['street'],
                    $ship['barangay'],
                    $ship['municipality'],
                    $ship['province'],
                ]))),
                'barangay' => $ship['barangay'],
                'municipality' => $ship['municipality'],
            ],
            'note_from_buyer' => $o->buyer_note,
            'cod_amount' => round($o->grand_total, 2),
            'attempts' => (int) $o->delivery_attempts,
            'max_attempts' => Order::MAX_DELIVERY_ATTEMPTS,
            'items_count' => $o->items->sum('quantity'),
            'weight_kg' => $o->total_weight_kg,
            'seller' => $o->seller->sellerProfile->business_name ?? $o->seller->name,
        ];
    }
}
