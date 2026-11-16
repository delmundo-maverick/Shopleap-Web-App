<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\Order;
use App\Services\OrderTransitions;
use App\Services\RiderCoverage;
use App\Services\RiderRealtime;
use App\Support\ScanCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'buyer.buyerProfile',
            'seller.sellerProfile',
            'logisticsProfile',
        ])
            ->where('pickup_rider_id', $courier->id)
            ->where('status', Order::STATUS_READY_FOR_PICKUP)
            ->whereRaw('COALESCE(origin_hub_id, logistics_profile_id) = ?', [$courier->logistics_profile_id])
            ->when(
                $request->query('filter') === 'awaiting_accept',
                fn ($q) => $q->where('pickup_status', 'assigned')
            )
            ->orderBy('pickup_date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $orders->map(fn ($o) => $this->payload($o, $courier)),
        ]);
    }

    /**
     * Accept the pickup assigned by the sorting center.
     */
    public function accept(Request $request, Order $order): JsonResponse
    {
        $courier = $this->owned($request, $order);

        if (! $this->coversSeller($courier, $order)) {
            return response()->json([
                'message' => 'You do not cover this pickup address. Ask the sorting center to reassign it.',
            ], 422);
        }

        if ($order->status !== Order::STATUS_READY_FOR_PICKUP) {
            return response()->json([
                'message' => 'This order is no longer awaiting pickup.',
            ], 422);
        }

        if ($order->pickup_status === 'accepted') {
            return response()->json([
                'message' => 'Pickup already accepted.',
                'data' => $this->payload($order, $courier),
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

        DB::afterCommit(fn () => RiderRealtime::notify($courier->id));

        return response()->json([
            'message' => 'Pickup accepted.',
            'data' => $this->payload($order, $courier),
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

        DB::afterCommit(fn () => RiderRealtime::notify($courier->id));

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
        $courier = $this->owned($request, $order);

        if (! $this->coversSeller($courier, $order)) {
            return response()->json([
                'message' => 'You do not cover this pickup address. Ask the sorting center to reassign it.',
            ], 422);
        }

        $data = $request->validate([
            'scan' => ['required', 'string', 'max:100'],
        ]);

        if (ScanCode::orderId($data['scan']) !== $order->id) {
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
                    'buyer.buyerProfile',
                    'seller.sellerProfile',
                    'logisticsProfile',
                ]),
                $courier
            ),
        ]);
    }

    /** Pickup riders cover the seller's address inside their own hub. */
    private function coversSeller(CourierProfile $courier, Order $order): bool
    {
        $order->loadMissing('seller.sellerProfile');
        $seller = $order->seller?->sellerProfile;

        return RiderCoverage::coversAddress(
            $courier,
            $seller?->province,
            $seller?->municipality,
            $seller?->barangay,
            $order->origin_hub_id ?? $order->logistics_profile_id
        );
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

        abort_unless(
            (int) ($order->origin_hub_id ?? $order->logistics_profile_id) === (int) $courier->logistics_profile_id,
            403,
            'This pickup belongs to another hub.'
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
    private function payload(Order $o, CourierProfile $courier): array
    {
        $payload = $o->courierPayload();
        $o->loadMissing(['items', 'seller.sellerProfile', 'logisticsProfile']);
        $seller = $o->seller?->sellerProfile;
        $sellerPhone = null;

        if ($o->status === Order::STATUS_READY_FOR_PICKUP
            && (int) $o->pickup_rider_id === (int) $courier->id
            && (int) ($o->origin_hub_id ?? $o->logistics_profile_id) === (int) $courier->logistics_profile_id) {
            $sellerPhone = trim((string) $seller?->contact_no);
            $sellerPhone = $sellerPhone === '' ? null : $sellerPhone;
        }

        return [
            ...$payload,
            'order_id' => $o->order_number,
            'order_number' => $o->order_number,
            'waybill_code' => $o->waybill_code,
            'status' => $o->status,
            'pickup_status' => $o->pickup_status,
            'hub_id' => $o->origin_hub_id ?? $o->logistics_profile_id,
            'hub_name' => $o->logisticsProfile?->business_name,
            'pickup_date' => $o->pickup_date?->toDateString(),
            'pickup_window' => Order::PICKUP_SLOTS[$o->pickup_time_slot] ?? null,

            'seller' => [
                'name' => $seller->business_name ?? $o->seller->name,
                'contact_no' => $sellerPhone,
                'seller_phone' => $sellerPhone,
                'address' => trim(implode(', ', array_filter([
                    $seller?->street_address,
                    $seller?->barangay,
                    $seller?->municipality,
                    $seller?->province,
                ]))),
            ],

            'deliver_to_hub' => $o->logisticsProfile?->business_name,
            'items_count' => $o->items->sum('quantity'),
            'weight_kg' => $o->total_weight_kg,
            'waybill_printed' => (bool) $o->waybill_printed_at,
        ];
    }
}
