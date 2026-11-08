<?php

namespace App\Services;

use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use Illuminate\Support\Facades\DB;

/**
 * The only place where a courier's pickup/delivery steps change an order.
 * The web stand-ins and the courier API both call this, so the rules stay identical.
 * Every move is a conditional update: a double tap or a second device can't apply it twice.
 */
class OrderTransitions
{
    public function __construct(private ParcelLifecycle $lifecycle, private RiderEarnings $earnings) {}

    /** READY_FOR_PICKUP → PICKED_UP (a rider is assigned and the label was printed). */
    public function confirmPickup(Order $order, ?int $actorId = null): bool
    {
        return $this->lifecycle->transition(
            $order,
            Order::STATUS_READY_FOR_PICKUP,
            Order::STATUS_PICKED_UP,
            [],
            'Collected from the seller.',
            $actorId,
            riderId: $order->pickup_rider_id,
            eventType: ParcelEvent::TYPE_PICKED_UP,
            where: fn ($q) => $q->whereNotNull('pickup_rider_id')->whereNotNull('waybill_printed_at'),
        );
    }

    /** ASSIGNED_TO_RIDER → OUT_FOR_DELIVERY */
    public function outForDelivery(Order $order, ?int $actorId = null): bool
    {
        return $this->lifecycle->transition(
            $order,
            Order::STATUS_ASSIGNED_TO_RIDER,
            Order::STATUS_OUT_FOR_DELIVERY,
            [],
            null,
            $actorId,
            riderId: $order->delivery_rider_id,
            eventType: ParcelEvent::TYPE_OUT_FOR_DELIVERY,
            where: fn ($q) => $q->whereNotNull('delivery_rider_id'),
        );
    }

    /** OUT_FOR_DELIVERY → DELIVERED, recording the COD cash (defaults to items + shipping). */
    public function delivered(Order $order, ?int $actorId = null, ?float $collected = null, ?int $expectedCourierId = null, ?int $expectedHubId = null): bool
    {
        return DB::transaction(function () use ($order, $actorId, $collected, $expectedCourierId, $expectedHubId): bool {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->setRawAttributes($locked->getAttributes(), true);
            $alreadyDelivered = in_array($order->status, RiderEarnings::EARNED_STATUSES, true);
            $courierId = $alreadyDelivered ? ($order->earning_courier_id ?? $order->delivery_rider_id) : $order->delivery_rider_id;
            $hubId = $alreadyDelivered ? ($order->earning_hub_id ?? $order->current_hub_id ?? $order->logistics_profile_id) : ($order->current_hub_id ?? $order->logistics_profile_id);
            if (($expectedCourierId !== null && (int) $courierId !== $expectedCourierId)
                || ($expectedHubId !== null && (int) $hubId !== $expectedHubId)) {
                return false;
            }
            if ($alreadyDelivered) {
                return true;
            }
            if ($order->status !== Order::STATUS_OUT_FOR_DELIVERY || $order->delivery_rider_id === null) {
                return false;
            }
            if ($hubId !== null) {
                LogisticsProfile::whereKey($hubId)->lockForUpdate()->firstOrFail();
            }
            $recordedAt = now();
            $rate = $this->earnings->currentRate($hubId);
            $expected = round((float) $order->total_amount + (float) $order->shipping_fee, 2);
            $amount = round($collected ?? $expected, 2);

            return $this->lifecycle->transition(
                $order,
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_DELIVERED,
                ['delivered_at' => $recordedAt, 'cod_collected_amount' => $amount, 'cod_collected_at' => $recordedAt,
                    'earning_hub_id' => $hubId, 'earning_courier_id' => $order->delivery_rider_id,
                    'rider_pay' => $rate?->amount, 'delivered_shipping_fee' => $order->shipping_fee,
                    'earnings_recorded_at' => $recordedAt],
                'Waiting for the buyer to confirm receipt.',
                $actorId,
                hubId: $hubId,
                riderId: $order->delivery_rider_id,
                eventType: ParcelEvent::TYPE_DELIVERED,
                after: function (Order $o) use ($amount, $expected, $actorId) {
                    $o->logInternalEvent('COD collected', '₱'.number_format($amount, 2).' collected on delivery.', $actorId);

                    if (abs($amount - $expected) > 0.009) {
                        $o->logInternalEvent(
                            'COD amount differs from the order total',
                            'Expected ₱'.number_format($expected, 2).', collected ₱'.number_format($amount, 2).'.',
                            $actorId
                        );
                    }
                },
            );
        });
    }

    /** OUT_FOR_DELIVERY → DELIVERY_FAILED. Returns the attempt number, or null if the move didn't apply. */
    public function failed(Order $order, string $reason, ?string $note = null, ?int $actorId = null): ?int
    {
        $moved = $this->lifecycle->transition(
            $order,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERY_FAILED,
            ['delivery_attempts' => DB::raw('delivery_attempts + 1')],
            fn (Order $o) => $reason.($note ? " — {$note}" : '')." (Attempt {$o->delivery_attempts} of ".Order::MAX_DELIVERY_ATTEMPTS.')',
            $actorId,
            riderId: $order->delivery_rider_id,
            eventType: ParcelEvent::TYPE_DELIVERY_ATTEMPT,
        );

        return $moved ? (int) $order->delivery_attempts : null;
    }
}
