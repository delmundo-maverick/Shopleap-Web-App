<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Every cancellation goes through here (buyer, seller, auto-approval), so the rules,
 * the stock return, the timeline entry and the notifications are identical everywhere.
 * All moves are conditional updates: a double click, a second tab, or a courier's
 * pickup scan at the same moment can't both win.
 */
class OrderCancellation
{
    public function __construct(private Notifier $notifier) {}

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
        $pickupWasScheduled = $order->status === Order::STATUS_READY_FOR_PICKUP;
        $fromRequest = false;

        $done = DB::transaction(function () use ($order, $by, $reason, $note, $actorId, $fromStatuses, $eventNote, &$fromRequest) {
            $moved = Order::whereKey($order->id)
                ->whereIn('status', $fromStatuses)
                ->update([
                    'status' => Order::STATUS_CANCELLED,
                    'cancelled_by' => $by,
                    'cancel_reason' => $reason,
                    'cancel_note' => $note,
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]) > 0;

            if (! $moved) {
                return false;
            }

            $order->refresh();

            $fromRequest = $order->cancel_request_status === 'pending';
            $extra = [];

            if ($fromRequest) {
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

            $order->logStatusEvent($eventNote ?? $this->describe($by, $reason, $note), $actorId);

            return true;
        });

        if ($done) {
            $this->notifier->orderCancelled(
                $order,
                $by,
                $fromRequest,
                $actorId,
                $pickupWasScheduled && (bool) $order->logistics_profile_id
            );
        }

        return $done;
    }

    /** The buyer asks to cancel after the seller has started. One request per order. */
    public function request(Order $order, string $reason, ?string $note, int $actorId): bool
    {
        $saved = DB::transaction(function () use ($order, $reason, $note, $actorId) {
            $saved = Order::whereKey($order->id)
                ->whereIn('status', Order::BUYER_CANCEL_REQUEST_FROM)
                ->where(fn($q) => $q->whereNull('cancel_request_status')->orWhere('cancel_request_status', 'withdrawn'))
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
                $reason . ($note ? " — {$note}" : ''),
                $actorId
            );

            return true;
        });

        if ($saved) {
            $this->notifier->cancellationRequested($order, $actorId);
        }

        return $saved;
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
            $this->notifier->cancellationWithdrawn($order, $actorId);
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
            $eventNote ?? "Cancelled at the buyer's request. Reason: " . ($order->cancel_request_reason ?? 'Changed my mind') . '.'
        );
    }

    public function decline(Order $order, string $response, int $actorId): bool
    {
        $done = DB::transaction(function () use ($order, $response, $actorId) {
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

        if ($done) {
            $this->notifier->cancellationDeclined($order, $actorId);
        }

        return $done;
    }

    /** The order shipped before the request was answered, so the request no longer applies. */
    public function expire(Order $order): bool
    {
        $done = Order::whereKey($order->id)
            ->where('cancel_request_status', 'pending')
            ->update([
                'cancel_request_status' => 'expired',
                'cancel_request_resolved_at' => now(),
                'updated_at' => now(),
            ]) > 0;

        if ($done) {
            $this->notifier->cancellationExpired($order->refresh());
        }

        return $done;
    }

    private function describe(string $by, string $reason, ?string $note): string
    {
        $who = Order::CANCELLED_BY_LABELS[$by] ?? $by;

        return "Cancelled by {$who}. Reason: {$reason}." . ($note ? " {$note}" : '');
    }
}
