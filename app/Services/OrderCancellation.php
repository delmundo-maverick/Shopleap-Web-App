<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ParcelEvent;
use Illuminate\Support\Facades\DB;

/**
 * Every cancellation goes through here (buyer, seller, auto-approval), so the rules,
 * the stock return and the timeline entry are identical everywhere.
 * All moves are conditional updates: a double click, a second tab, or a courier's
 * pickup scan at the same moment can't both win.
 */
class OrderCancellation
{
    public function __construct(private ParcelLifecycle $lifecycle) {}

    /** Cancels the order if it is still in one of $fromStatuses. Returns false if it moved on. */
    public function cancel(
        Order $order,
        string $by,
        string $reason,
        ?string $note,
        ?int $actorId,
        array $fromStatuses,
        ?string $eventNote = null,
    ): bool {
        return DB::transaction(function () use ($order, $by, $reason, $note, $actorId, $fromStatuses, $eventNote) {
            $moved = $this->lifecycle->transition(
                $order,
                $fromStatuses,
                Order::STATUS_CANCELLED,
                [
                    'cancelled_by' => $by,
                    'cancel_reason' => $reason,
                    'cancel_note' => $note,
                    'cancelled_at' => now(),
                ],
                $eventNote ?? $this->describe($by, $reason, $note),
                $actorId,
                eventType: ParcelEvent::TYPE_CANCELLED,
            );

            if (! $moved) {
                return false;
            }

            $extra = [];

            if ($order->cancel_request_status === 'pending') {
                $extra['cancel_request_status'] = 'approved';
                $extra['cancel_request_resolved_at'] = now();
            }

            if ($order->pickup_status) {
                $extra['pickup_status'] = 'cancelled';
            }

            if ($extra) {
                $order->update($extra);
            }

            $order->restoreStock();
            app(ShopVoucherService::class)->restoreForCancelledOrder($order);

            return true;
        });
    }

    /** The buyer asks to cancel after the seller has started. One request per order. */
    public function request(Order $order, string $reason, ?string $note, int $actorId): bool
    {
        return DB::transaction(function () use ($order, $reason, $note, $actorId) {
            $saved = Order::whereKey($order->id)
                ->whereIn('status', Order::BUYER_CANCEL_REQUEST_FROM)
                ->where(fn ($q) => $q->whereNull('cancel_request_status')->orWhere('cancel_request_status', 'withdrawn'))
                ->update([
                    'cancel_request_status' => 'pending',
                    'cancel_request_reason' => $reason,
                    'cancel_request_note' => $note,
                    'cancel_requested_at' => now(),
                    'cancel_request_response' => null,
                    'cancel_request_resolved_at' => null,
                    'updated_at' => now(),
                ]) > 0;

            if (! $saved) {
                return false;
            }

            $order->refresh()->logInternalEvent(
                'Buyer requested cancellation',
                $reason.($note ? " — {$note}" : ''),
                $actorId
            );

            return true;
        });
    }

    public function withdraw(Order $order, int $actorId): bool
    {
        $done = Order::whereKey($order->id)
            ->where('cancel_request_status', 'pending')
            ->update([
                'cancel_request_status' => 'withdrawn',
                'cancel_request_resolved_at' => now(),
                'updated_at' => now(),
            ]) > 0;

        if ($done) {
            $order->refresh()->logInternalEvent('Buyer withdrew the cancellation request', null, $actorId);
        }

        return $done;
    }

    /** The seller approves the buyer's request (or it is approved automatically when $actorId is null). */
    public function approveRequest(Order $order, ?int $actorId, ?string $eventNote = null): bool
    {
        return $this->cancel(
            $order,
            'buyer',
            $order->cancel_request_reason ?? 'Changed my mind',
            $order->cancel_request_note,
            $actorId,
            Order::BUYER_CANCEL_REQUEST_FROM,
            $eventNote ?? "Cancelled at the buyer's request. Reason: ".($order->cancel_request_reason ?? 'Changed my mind').'.'
        );
    }

    public function decline(Order $order, string $response, int $actorId): bool
    {
        return DB::transaction(function () use ($order, $response, $actorId) {
            $done = Order::whereKey($order->id)
                ->where('cancel_request_status', 'pending')
                ->whereIn('status', Order::BUYER_CANCEL_REQUEST_FROM)
                ->update([
                    'cancel_request_status' => 'declined',
                    'cancel_request_response' => $response,
                    'cancel_request_resolved_at' => now(),
                    'updated_at' => now(),
                ]) > 0;

            if ($done) {
                $order->refresh()->logInternalEvent('Cancellation request declined', $response, $actorId);
            }

            return $done;
        });
    }

    /** The order shipped before the request was answered, so the request no longer applies. */
    public function expire(Order $order): void
    {
        Order::whereKey($order->id)
            ->where('cancel_request_status', 'pending')
            ->update([
                'cancel_request_status' => 'expired',
                'cancel_request_resolved_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function describe(string $by, string $reason, ?string $note): string
    {
        $who = Order::CANCELLED_BY_LABELS[$by] ?? $by;

        return "Cancelled by {$who}. Reason: {$reason}.".($note ? " {$note}" : '');
    }
}
