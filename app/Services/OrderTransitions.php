<?php

namespace App\Services;

use App\Models\Order;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * The only place where a courier's pickup/delivery steps change an order.
 * The web stand-ins and the courier API both call this, so the rules stay identical.
 * Every move is a conditional update: a double tap or a second device can't apply it twice.
 */
class OrderTransitions
{
    /** READY_FOR_PICKUP → PICKED_UP (a rider is assigned and the label was printed). */
    public function confirmPickup(Order $order, ?int $actorId = null): bool
    {
        return $this->move(
            $order,
            Order::STATUS_READY_FOR_PICKUP,
            Order::STATUS_PICKED_UP,
            [],
            'Collected from the seller.',
            $actorId,
            fn($q) => $q->whereNotNull('pickup_rider_id')->whereNotNull('waybill_printed_at'),
        );
    }

    /** ASSIGNED_TO_RIDER → OUT_FOR_DELIVERY */
    public function outForDelivery(Order $order, ?int $actorId = null): bool
    {
        return $this->move(
            $order,
            Order::STATUS_ASSIGNED_TO_RIDER,
            Order::STATUS_OUT_FOR_DELIVERY,
            [],
            null,
            $actorId,
            fn($q) => $q->whereNotNull('delivery_rider_id'),
        );
    }

    /** OUT_FOR_DELIVERY → DELIVERED, recording the COD cash (defaults to items + shipping). */
    public function delivered(Order $order, ?int $actorId = null, ?float $collected = null): bool
    {
        $expected = round((float) $order->total_amount + (float) $order->shipping_fee, 2);
        $amount = round($collected ?? $expected, 2);

        return $this->move(
            $order,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
            ['delivered_at' => now(), 'cod_collected_amount' => $amount, 'cod_collected_at' => now()],
            'Waiting for the buyer to confirm receipt.',
            $actorId,
            null,
            function (Order $o) use ($amount, $expected, $actorId) {
                $o->logInternalEvent('COD collected', '₱' . number_format($amount, 2) . ' collected on delivery.', $actorId);

                if (abs($amount - $expected) > 0.009) {
                    $o->logInternalEvent(
                        'COD amount differs from the order total',
                        'Expected ₱' . number_format($expected, 2) . ', collected ₱' . number_format($amount, 2) . '.',
                        $actorId
                    );
                }
            },
        );
    }

    /** OUT_FOR_DELIVERY → DELIVERY_FAILED. Returns the attempt number, or null if the move didn't apply. */
    public function failed(Order $order, string $reason, ?string $note = null, ?int $actorId = null): ?int
    {
        $moved = $this->move(
            $order,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERY_FAILED,
            ['delivery_attempts' => DB::raw('delivery_attempts + 1')],
            fn(Order $o) => $reason . ($note ? " — {$note}" : '') . " (Attempt {$o->delivery_attempts} of " . Order::MAX_DELIVERY_ATTEMPTS . ')',
            $actorId,
        );

        return $moved ? (int) $order->delivery_attempts : null;
    }

    private function move(
        Order $order,
        string $from,
        string $to,
        array $values,
        string|Closure|null $note,
        ?int $actorId,
        ?Closure $where = null,
        ?Closure $after = null,
    ): bool {
        return DB::transaction(function () use ($order, $from, $to, $values, $note, $actorId, $where, $after) {
            $query = Order::whereKey($order->id)->where('status', $from);

            if ($where) {
                $where($query);
            }

            if ($query->update(['status' => $to] + $values) === 0) {
                return false;
            }

            $order->refresh();
            $order->logStatusEvent($note instanceof Closure ? $note($order) : $note, $actorId);

            if ($after) {
                $after($order);
            }

            return true;
        });
    }
}
