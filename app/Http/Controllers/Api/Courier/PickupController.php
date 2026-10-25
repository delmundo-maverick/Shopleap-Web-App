<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\Order;
use App\Services\OrderTransitions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    /**
     * My pickups: orders assigned to me that are waiting to be collected.
     */
    public function index(Request $request): JsonResponse
    {
        $courier = $request->attributes->get('courier');

        $orders = Order::with([
            'items',
            'seller.sellerProfile',
            'logisticsProfile',
        ])
            ->where('pickup_rider_id', $courier->id)
            ->where('status', Order::STATUS_READY_FOR_PICKUP)
            ->when(
                $request->query('filter') === 'awaiting_accept',
                fn($q) => $q->where('pickup_status', 'assigned')
            )
            ->orderBy('pickup_date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $orders->map(fn($o) => $this->payload($o)),
        ]);
    }

    /**
     * Accept the pickup assigned by the sorting center.
     */
    public function accept(Request $request, Order $order): JsonResponse
    {
        $courier = $this->owned($request, $order);

        if ($order->status !== Order::STATUS_READY_FOR_PICKUP) {
            return response()->json([
                'message' => 'This order is no longer awaiting pickup.',
            ], 422);
        }

        if ($order->pickup_status === 'accepted') {
            return response()->json([
                'message' => 'Pickup already accepted.',
                'data' => $this->payload($order),
            ]);
        }

        $moved = Order::whereKey($order->id)
            ->where('pickup_rider_id', $courier->id)
            ->where('status', Order::STATUS_READY_FOR_PICKUP)
            ->where('pickup_status', 'assigned')
            ->update([
                'pickup_status' => 'accepted',
            ]) > 0;

        if (! $moved) {
            return response()->json([
                'message' => 'This pickup was just updated. Please refresh.',
            ], 422);
        }

        $order->refresh()->logInternalEvent(
            'Pickup accepted',
            "{$courier->full_name} accepted the pickup.",
            $request->user()->id
        );

        return response()->json([
            'message' => 'Pickup accepted.',
            'data' => $this->payload($order),
        ]);
    }

    /**
     * Decline an assigned pickup before accepting it.
     */
    public function decline(Request $request, Order $order): JsonResponse
    {
        $courier = $this->owned($request, $order);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $moved = Order::whereKey($order->id)
            ->where('pickup_rider_id', $courier->id)
            ->where('status', Order::STATUS_READY_FOR_PICKUP)
            ->where('pickup_status', 'assigned')
            ->update([
                'pickup_rider_id' => null,
                'pickup_status' => 'requested',
            ]) > 0;

        if (! $moved) {
            return response()->json([
                'message' => 'Only a pickup you have not accepted yet can be declined. Please contact the sorting center.',
            ], 422);
        }

        $order->refresh()->logInternalEvent(
            'Pickup declined by rider',
            "{$courier->full_name}: {$data['reason']}",
            $request->user()->id
        );

        return response()->json([
            'message' => 'Pickup declined. The sorting center will reassign it.',
        ]);
    }

    /**
     * Confirm pickup by scanning the waybill QR code or barcode.
     */
    public function confirm(
        Request $request,
        Order $order,
        OrderTransitions $transitions
    ): JsonResponse {
        $this->owned($request, $order);

        $data = $request->validate([
            'scan' => ['required', 'string', 'max:100'],
        ]);

        $valid = [
            'SHOPLEAP:ORDER:' . $order->id,
            str_pad((string) $order->id, 10, '0', STR_PAD_LEFT),
        ];

        if (! in_array(trim($data['scan']), $valid, true)) {
            return response()->json([
                'message' => 'This is not the right parcel.',
            ], 422);
        }

        if ($order->pickup_status !== 'accepted') {
            return response()->json([
                'message' => 'Accept the pickup first.',
            ], 422);
        }

        if (! $transitions->confirmPickup($order, $request->user()->id)) {
            return response()->json([
                'message' => "The pickup can't be confirmed yet. The seller may not have printed the label, or the order was just updated.",
            ], 422);
        }

        return response()->json([
            'message' => 'Pickup confirmed. Please bring the parcel to the sorting center.',
            'data' => $this->payload(
                $order->fresh([
                    'items',
                    'seller.sellerProfile',
                    'logisticsProfile',
                ])
            ),
        ]);
    }

    /**
     * Verify ownership before revealing whether an order was cancelled.
     */
    private function owned(
        Request $request,
        Order $order
    ): CourierProfile {
        $courier = $request->attributes->get('courier');

        abort_unless(
            $order->pickup_rider_id === $courier->id,
            403,
            'This pickup is not assigned to you.'
        );

        abort_if(
            $order->status === Order::STATUS_CANCELLED,
            422,
            'This order was cancelled. Do not collect the parcel.'
        );

        return $courier;
    }

    /**
     * Format an order for the courier app.
     */
    private function payload(Order $o): array
    {
        $seller = $o->seller->sellerProfile;

        return [
            'order_id' => $o->id,
            'status' => $o->status,
            'pickup_status' => $o->pickup_status,
            'pickup_date' => $o->pickup_date?->toDateString(),
            'pickup_window' => Order::PICKUP_SLOTS[$o->pickup_time_slot] ?? null,

            'seller' => [
                'name' => $seller->business_name ?? $o->seller->name,
                'contact_no' => $seller?->contact_no,
                'address' => trim(implode(', ', array_filter([
                    $seller?->street_address,
                    $seller?->barangay,
                    $seller?->municipality,
                    $seller?->province,
                ]))),
            ],

            'deliver_to_hub' => $o->logisticsProfile?->business_name,
            'items_count' => $o->items->sum('quantity'),
            'waybill_printed' => (bool) $o->waybill_printed_at,
        ];
    }
}
