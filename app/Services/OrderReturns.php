<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ReviewMedia;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Every step of a return/refund goes through here (buyer, seller, admin, deadlines),
 * so the rules and the timeline are identical everywhere. All moves are conditional
 * updates: a double click or a deadline run at the same moment can't apply a step twice.
 */
class OrderReturns
{
    public function __construct(private Notifier $notifier) {}

    // ── Buyer ──

    /** @throws \DomainException when the order can't take a request right now */
    public function open(Order $order, int $buyerId, array $data, Collection $orderItems, array $files): OrderReturn
    {
        $return = $this->persist($order, $buyerId, $data, $orderItems, $files);
        $this->notifier->returnChanged($return, $buyerId);

        return $return;
    }

    private function persist(Order $order, int $buyerId, array $data, Collection $orderItems, array $files): OrderReturn
    {
        $meta = OrderReturn::REASONS[$data['reason']];
        $disputed = $data['reason'] === 'not_received';

        $itemsAmount = round($orderItems->sum(fn ($item) => (float) $item->subtotal
            - ($order->items_subtotal !== null ? (float) $item->voucher_discount_allocated : 0)), 2);
        $wholeOrder = $orderItems->count() === $order->items()->count();
        $shipping = $wholeOrder ? round((float) $order->shipping_fee, 2) : 0.0;
        $commission = (float) $order->total_amount > 0
            ? round((float) $order->commission_amount * $itemsAmount / (float) $order->total_amount, 2)
            : 0.0;

        $uploaded = [];

        try {
            return DB::transaction(function () use ($order, $buyerId, $data, $orderItems, $files, $meta, $disputed, $itemsAmount, $shipping, $commission, &$uploaded) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if (! $locked->canRequestReturn()) {
                    throw new \DomainException(
                        'A return/refund can\'t be requested for this order. It may already have a request, or the '
                            .Order::returnWindowDays().'-day window has passed.'
                    );
                }

                $return = OrderReturn::create([
                    'order_id' => $order->id,
                    'buyer_id' => $buyerId,
                    'seller_id' => $order->seller_id,
                    'type' => $meta['type'],
                    'reason' => $data['reason'],
                    'details' => $data['details'],
                    'status' => $disputed ? OrderReturn::STATUS_DISPUTED : OrderReturn::STATUS_REQUESTED,
                    'items_amount' => $itemsAmount,
                    'shipping_amount' => $shipping,
                    'refund_amount' => round($itemsAmount + $shipping, 2),
                    'commission_reversal' => $commission,
                    'refund_method' => $data['refund_method'],
                    'refund_account_name' => $data['refund_account_name'],
                    'refund_account_number' => $data['refund_account_number'],
                    'response_due_at' => $disputed ? null : now()->addDays(OrderReturn::RESPONSE_DAYS),
                    'escalated_by' => $disputed ? 'system' : null,
                    'escalation_reason' => $disputed ? 'The courier marked this parcel as delivered, but the buyer says it was not received.' : null,
                    'escalated_at' => $disputed ? now() : null,
                ]);

                foreach ($orderItems as $item) {
                    $return->items()->create([
                        'order_item_id' => $item->id,
                        'quantity' => $item->quantity,
                        'amount' => round((float) $item->subtotal
                            - ($order->items_subtotal !== null ? (float) $item->voucher_discount_allocated : 0), 2),
                    ]);
                }

                foreach ($files as $i => $file) {
                    $path = Storage::disk(ReviewMedia::DISK)
                        ->putFileAs("returns/{$return->id}", $file, Str::uuid().'.'.$file->extension());

                    if (! $path) {
                        throw new \RuntimeException('Evidence upload failed.');
                    }

                    $uploaded[] = $path;

                    $return->media()->create([
                        'path' => $path,
                        'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
                        'sort_order' => $i,
                    ]);
                }

                $this->log($return, 'Return/refund requested', $meta['label'], $buyerId);

                if ($disputed) {
                    $this->log($return, 'Sent to Shopleap for review', 'The courier recorded this parcel as delivered, so Shopleap will check the delivery.', null);
                }

                $order->logInternalEvent(
                    'Return/refund requested',
                    $meta['label'].' · ₱'.number_format($return->refund_amount, 2),
                    $buyerId
                );

                return $return;
            });
        } catch (\Throwable $e) {
            // Don't leave orphaned files in storage if the database write failed.
            if ($uploaded) {
                Storage::disk(ReviewMedia::DISK)->delete($uploaded);
            }

            throw $e;
        }
    }

    public function withdraw(OrderReturn $return, int $actorId): bool
    {
        return $this->move(
            $return,
            [OrderReturn::STATUS_REQUESTED, OrderReturn::STATUS_APPROVED],
            ['status' => OrderReturn::STATUS_CANCELLED],
            'Buyer withdrew the request',
            null,
            $actorId
        );
    }

    /** The buyer ships the item back and enters the tracking number. */
    public function ship(OrderReturn $return, string $courier, string $tracking, int $actorId): bool
    {
        return $this->move(
            $return,
            [OrderReturn::STATUS_APPROVED],
            [
                'status' => OrderReturn::STATUS_SHIPPED_BACK,
                'return_courier' => $courier,
                'return_tracking_no' => $tracking,
                'return_shipped_at' => now(),
                'receipt_due_at' => now()->addDays(OrderReturn::RECEIPT_DAYS),
            ],
            'Buyer shipped the item back',
            "{$courier} · {$tracking}",
            $actorId,
            fn ($q) => $q->where('type', OrderReturn::TYPE_RETURN_AND_REFUND)
        );
    }

    /** After a decline, within the escalation window. */
    public function escalateByBuyer(OrderReturn $return, string $reason, int $actorId): bool
    {
        return $this->move(
            $return,
            [OrderReturn::STATUS_DECLINED],
            [
                'status' => OrderReturn::STATUS_DISPUTED,
                'escalated_by' => 'buyer',
                'escalation_reason' => $reason,
                'escalated_at' => now(),
            ],
            'Buyer asked Shopleap to review',
            $reason,
            $actorId,
            fn ($q) => $q->where('escalate_until', '>', now())
        );
    }

    // ── Seller (and automatic) ──

    public function approve(OrderReturn $return, ?int $actorId, bool $auto = false): bool
    {
        $refundOnly = $return->type === OrderReturn::TYPE_REFUND_ONLY;

        return $this->move(
            $return,
            [OrderReturn::STATUS_REQUESTED],
            [
                'status' => $refundOnly ? OrderReturn::STATUS_REFUND_PENDING : OrderReturn::STATUS_APPROVED,
                'approved_at' => now(),
                'seller_responded_at' => $auto ? null : now(),
                'ship_by_at' => $refundOnly ? null : now()->addDays(OrderReturn::SHIP_BACK_DAYS),
            ],
            $auto ? 'Approved automatically' : 'Seller approved the request',
            $auto
                ? 'The seller did not respond within '.OrderReturn::RESPONSE_DAYS.' days.'
                : ($refundOnly ? 'Shopleap will send the refund.' : 'The buyer can now ship the item back.'),
            $actorId
        );
    }

    public function decline(OrderReturn $return, string $reason, int $actorId): bool
    {
        return $this->move(
            $return,
            [OrderReturn::STATUS_REQUESTED],
            [
                'status' => OrderReturn::STATUS_DECLINED,
                'seller_response' => $reason,
                'seller_responded_at' => now(),
                'escalate_until' => now()->addDays(OrderReturn::ESCALATE_DAYS),
            ],
            'Seller declined the request',
            $reason,
            $actorId
        );
    }

    public function confirmReceived(OrderReturn $return, ?int $actorId, bool $auto = false): bool
    {
        return $this->move(
            $return,
            [OrderReturn::STATUS_SHIPPED_BACK],
            [
                'status' => OrderReturn::STATUS_REFUND_PENDING,
                'return_received_at' => $auto ? null : now(),
            ],
            $auto ? 'Receipt confirmed automatically' : 'Seller received the item',
            $auto
                ? 'The seller did not respond within '.OrderReturn::RECEIPT_DAYS.' days of the shipment.'
                : 'Shopleap will send the refund.',
            $actorId
        );
    }

    /** The seller says the item never arrived or isn't in the condition described. */
    public function reportProblem(OrderReturn $return, string $reason, int $actorId): bool
    {
        return $this->move(
            $return,
            [OrderReturn::STATUS_SHIPPED_BACK],
            [
                'status' => OrderReturn::STATUS_DISPUTED,
                'escalated_by' => 'seller',
                'escalation_reason' => $reason,
                'escalated_at' => now(),
            ],
            'Seller reported a problem with the returned item',
            $reason,
            $actorId
        );
    }

    // ── Admin (the screens come in the next step; usable from tinker meanwhile) ──

    public function adminDecide(OrderReturn $return, bool $approve, string $note, int $adminId): bool
    {
        if (! $approve) {
            return $this->move(
                $return,
                [OrderReturn::STATUS_DISPUTED],
                ['status' => OrderReturn::STATUS_REJECTED, 'admin_note' => $note, 'resolved_by' => $adminId, 'resolved_at' => now()],
                'Shopleap rejected the request',
                $note,
                $adminId
            );
        }

        // Approved, but the item still has to come back first.
        $needsReturn = $return->type === OrderReturn::TYPE_RETURN_AND_REFUND && ! $return->return_shipped_at;

        return $this->move(
            $return,
            [OrderReturn::STATUS_DISPUTED],
            [
                'status' => $needsReturn ? OrderReturn::STATUS_APPROVED : OrderReturn::STATUS_REFUND_PENDING,
                'approved_at' => now(),
                'ship_by_at' => $needsReturn ? now()->addDays(OrderReturn::SHIP_BACK_DAYS) : null,
                'admin_note' => $note,
                'resolved_by' => $adminId,
                'resolved_at' => now(),
            ],
            'Shopleap approved the request',
            $note,
            $adminId
        );
    }

    public function markRefunded(OrderReturn $return, string $reference, int $adminId): bool
    {
        $done = $this->move(
            $return,
            [OrderReturn::STATUS_REFUND_PENDING],
            [
                'status' => OrderReturn::STATUS_REFUNDED,
                'refund_reference' => $reference,
                'refunded_by' => $adminId,
                'refunded_at' => now(),
            ],
            'Refund sent',
            '₱'.number_format($return->refund_amount, 2)." · Ref {$reference}",
            $adminId
        );

        if ($done) {
            $return->order->logInternalEvent('Refund sent to the buyer', '₱'.number_format($return->refund_amount, 2), $adminId);
        }

        return $done;
    }

    // ── Deadlines (run hourly by returns:process-deadlines) ──

    /** @return array{auto_approved:int, closed:int, expired:int, auto_confirmed:int} */
    public function processDeadlines(): array
    {
        $out = ['auto_approved' => 0, 'closed' => 0, 'expired' => 0, 'auto_confirmed' => 0];

        OrderReturn::where('status', OrderReturn::STATUS_REQUESTED)
            ->where('response_due_at', '<=', now())
            ->chunkById(100, function ($rows) use (&$out) {
                foreach ($rows as $r) {
                    $this->approve($r, null, true) && $out['auto_approved']++;
                }
            });

        OrderReturn::where('status', OrderReturn::STATUS_DECLINED)
            ->where('escalate_until', '<=', now())
            ->chunkById(100, function ($rows) use (&$out) {
                foreach ($rows as $r) {
                    $closed = $this->move(
                        $r,
                        [OrderReturn::STATUS_DECLINED],
                        ['status' => OrderReturn::STATUS_REJECTED],
                        'Request closed',
                        'The buyer did not ask Shopleap to review the seller\'s decision in time.',
                        null
                    );
                    $closed && $out['closed']++;
                }
            });

        OrderReturn::where('status', OrderReturn::STATUS_APPROVED)
            ->where('type', OrderReturn::TYPE_RETURN_AND_REFUND)
            ->where('ship_by_at', '<=', now())
            ->chunkById(100, function ($rows) use (&$out) {
                foreach ($rows as $r) {
                    $expired = $this->move(
                        $r,
                        [OrderReturn::STATUS_APPROVED],
                        ['status' => OrderReturn::STATUS_EXPIRED],
                        'Request expired',
                        'The item was not shipped back within '.OrderReturn::SHIP_BACK_DAYS.' days.',
                        null
                    );
                    $expired && $out['expired']++;
                }
            });

        OrderReturn::where('status', OrderReturn::STATUS_SHIPPED_BACK)
            ->where('receipt_due_at', '<=', now())
            ->chunkById(100, function ($rows) use (&$out) {
                foreach ($rows as $r) {
                    $this->confirmReceived($r, null, true) && $out['auto_confirmed']++;
                }
            });

        return $out;
    }

    // ── Internals ──

    private function move(
        OrderReturn $return,
        array $from,
        array $values,
        string $title,
        ?string $note,
        ?int $actorId,
        ?Closure $extraWhere = null,
    ): bool {
        $done = DB::transaction(function () use ($return, $from, $values, $title, $note, $actorId, $extraWhere) {
            $query = OrderReturn::whereKey($return->id)->whereIn('status', $from);

            if ($extraWhere) {
                $extraWhere($query);
            }

            if ($query->update($values + ['updated_at' => now()]) === 0) {
                return false;
            }

            $return->refresh();
            $this->log($return, $title, $note, $actorId);

            return true;
        });

        // After the commit, and never able to fail the step itself.
        if ($done) {
            $this->notifier->returnChanged($return, $actorId);
        }

        return $done;
    }

    private function log(OrderReturn $return, string $title, ?string $note, ?int $actorId): void
    {
        $return->events()->create(['actor_id' => $actorId, 'title' => $title, 'note' => $note]);
    }
}
