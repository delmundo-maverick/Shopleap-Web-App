<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Collection;

class Notifier
{
    // ── Cancellation ──

    public function orderCancelled(Order $order, string $by, bool $fromRequest, ?int $actorId, bool $pickupWasScheduled): void
    {
        $no = "#{$order->id}";
        $reason = $order->cancel_reason ? " Reason: {$order->cancel_reason}." : '';

        $buyerUrl = route('buyer.orders.index', ['tab' => 'cancelled'], false);
        $sellerUrl = route('seller.orders.show', $order, false);
        $meta = ['order_id' => $order->id];

        if ($by === 'seller') {
            $this->send(
                $order->buyer,
                $actorId,
                'cancel.by_seller',
                'Order cancelled by the seller',
                "Order {$no} was cancelled by the seller.{$reason}",
                $buyerUrl,
                $meta
            );
        } elseif ($fromRequest) {
            // The buyer's request was approved (by the seller, or automatically when $actorId is null).
            $this->send(
                $order->buyer,
                $actorId,
                'cancel.approved',
                'Cancellation approved',
                "Your request to cancel order {$no} was approved.",
                $buyerUrl,
                $meta
            );

            $this->send(
                $order->seller,
                $actorId,
                'cancel.auto_approved',
                'Order cancelled',
                "Order {$no} was cancelled at the buyer's request because you did not respond in time.",
                $sellerUrl,
                $meta
            );
        } else {
            $this->send(
                $order->seller,
                $actorId,
                'cancel.by_buyer',
                'Order cancelled by the buyer',
                "Order {$no} was cancelled by the buyer.{$reason}",
                $sellerUrl,
                $meta
            );
        }

        if ($pickupWasScheduled) {
            $this->send(
                $order->logisticsProfile?->user,
                $actorId,
                'cancel.pickup',
                'Pickup cancelled',
                "Order {$no} was cancelled, so no pickup is needed.",
                route('logistics.pickups.index', [], false),
                $meta
            );
        }
    }

    public function cancellationRequested(Order $order, ?int $actorId): void
    {
        $this->send(
            $order->seller,
            $actorId,
            'cancel.requested',
            'Cancellation request',
            "Order #{$order->id}: the buyer asked to cancel ({$order->cancel_request_reason}). Answer within "
                . Order::CANCEL_REQUEST_AUTO_APPROVE_HOURS . ' hours, or it is approved automatically.',
            route('seller.orders.show', $order, false),
            ['order_id' => $order->id]
        );
    }

    public function cancellationWithdrawn(Order $order, ?int $actorId): void
    {
        $this->send(
            $order->seller,
            $actorId,
            'cancel.withdrawn',
            'Cancellation request withdrawn',
            "The buyer withdrew the request to cancel order #{$order->id}.",
            route('seller.orders.show', $order, false),
            ['order_id' => $order->id]
        );
    }

    public function cancellationDeclined(Order $order, ?int $actorId): void
    {
        $this->send(
            $order->buyer,
            $actorId,
            'cancel.declined',
            'Cancellation declined',
            "Order #{$order->id}: the seller declined your cancellation request. {$order->cancel_request_response}",
            route('buyer.orders.index', [], false),
            ['order_id' => $order->id]
        );
    }

    public function cancellationExpired(Order $order): void
    {
        $this->send(
            $order->buyer,
            null,
            'cancel.expired',
            'Cancellation request expired',
            "Order #{$order->id} was already picked up by the courier, so it can no longer be cancelled.",
            route('buyer.orders.index', [], false),
            ['order_id' => $order->id]
        );
    }

    // ── Return / refund: one place decides who hears about each status ──

    public function returnChanged(OrderReturn $return, ?int $actorId): void
    {
        $return->loadMissing(['buyer', 'seller']);

        $no = '#' . $return->order_id;
        $money = '₱' . number_format((float) $return->refund_amount, 2);
        $meta = ['return_id' => $return->id, 'order_id' => $return->order_id];

        $buyerUrl = route('buyer.returns.show', $return, false);
        $sellerUrl = route('seller.returns.show', $return, false);
        $adminUrl = route('admin.returns.show', $return, false);

        $buyer = $return->buyer;
        $seller = $return->seller;

        $due = $return->response_due_at?->format('M d, g:i A');
        $shipBy = $return->ship_by_at?->format('M d');
        $receiptBy = $return->receipt_due_at?->format('M d, g:i A');
        $escalateBy = $return->escalate_until?->format('M d, g:i A');

        switch ($return->status) {
            case OrderReturn::STATUS_REQUESTED:
                $this->send(
                    $seller,
                    $actorId,
                    'return.requested',
                    'New return/refund request',
                    "Order {$no}: {$return->reason_label}. Please respond by {$due}.",
                    $sellerUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_APPROVED:
                $this->send(
                    $buyer,
                    $actorId,
                    'return.approved',
                    'Return approved',
                    "Order {$no}: please ship the item back by {$shipBy} and add the tracking number.",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $seller,
                    $actorId,
                    'return.approved',
                    'Return approved',
                    "Order {$no}: the return request was approved. The buyer will ship the item back.",
                    $sellerUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_DECLINED:
                $this->send(
                    $buyer,
                    $actorId,
                    'return.declined',
                    'Request declined',
                    "Order {$no}: the seller declined ({$return->seller_response}). You can ask Shopleap to review until {$escalateBy}.",
                    $buyerUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_SHIPPED_BACK:
                $this->send(
                    $seller,
                    $actorId,
                    'return.shipped',
                    'Item shipped back',
                    "Order {$no}: the buyer shipped the item via {$return->return_courier}. Confirm when it arrives, by {$receiptBy}.",
                    $sellerUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_REFUND_PENDING:
                $this->send(
                    $buyer,
                    $actorId,
                    'return.refund_approved',
                    'Refund approved',
                    "Order {$no}: {$money} will be sent to your {$return->refund_method_label}.",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $seller,
                    $actorId,
                    'return.refund_approved',
                    'Refund approved',
                    "Order {$no}: a refund of {$money} was approved.",
                    $sellerUrl,
                    $meta
                );
                $this->send(
                    $this->admins(),
                    $actorId,
                    'return.refund_due',
                    'Refund ready to pay',
                    "Case #{$return->id}: send {$money} to {$return->refund_method_label} ({$return->refund_account_name}).",
                    $adminUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_REFUNDED:
                $this->send(
                    $buyer,
                    $actorId,
                    'return.refunded',
                    'Refund sent',
                    "Order {$no}: {$money} was sent to your {$return->refund_method_label}. Reference: {$return->refund_reference}.",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $seller,
                    $actorId,
                    'return.refunded',
                    'Refund paid to the buyer',
                    "Order {$no}: the refund of {$money} was paid to the buyer.",
                    $sellerUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_DISPUTED:
                $this->send(
                    $this->admins(),
                    $actorId,
                    'return.disputed',
                    'Case needs review',
                    "Order {$no}: {$return->reason_label}. {$return->escalation_reason}",
                    $adminUrl,
                    $meta
                );
                $this->send(
                    $buyer,
                    $actorId,
                    'return.disputed',
                    'Shopleap is reviewing your case',
                    "Order {$no}: Shopleap is reviewing this case. You'll be told the decision.",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $seller,
                    $actorId,
                    'return.disputed',
                    'Shopleap is reviewing a case',
                    "Order {$no}: Shopleap is reviewing this return/refund case.",
                    $sellerUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_REJECTED:
                $note = $return->admin_note ? " {$return->admin_note}" : '';
                $this->send(
                    $buyer,
                    $actorId,
                    'return.rejected',
                    'Request closed',
                    "Order {$no}: the request was closed without a refund.{$note}",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $seller,
                    $actorId,
                    'return.rejected',
                    'Request closed',
                    "Order {$no}: the return/refund request was closed without a refund.{$note}",
                    $sellerUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_CANCELLED:
                $this->send(
                    $seller,
                    $actorId,
                    'return.withdrawn',
                    'Request withdrawn',
                    "Order {$no}: the buyer withdrew the return/refund request.",
                    $sellerUrl,
                    $meta
                );
                break;

            case OrderReturn::STATUS_EXPIRED:
                $this->send(
                    $buyer,
                    $actorId,
                    'return.expired',
                    'Request expired',
                    "Order {$no}: the item was not shipped back in time, so the request expired.",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $seller,
                    $actorId,
                    'return.expired',
                    'Request expired',
                    "Order {$no}: the return request expired because the item was not shipped back in time.",
                    $sellerUrl,
                    $meta
                );
                break;
        }
    }

    // ── Internals ──

    /** @return Collection<int, User> */
    private function admins(): Collection
    {
        return User::where('role', 'super_admin')->where('account_status', 'active')->get();
    }

    /**
     * Sends to one user or a collection, skipping whoever did the action.
     * A failure is reported and swallowed: notifications must never break an order action.
     */
    private function send(User|Collection|null $to, ?int $exceptUserId, string $kind, string $title, string $body, ?string $url, array $meta = []): void
    {
        $users = $to instanceof User ? collect([$to]) : collect($to ?? []);

        $users->filter()
            ->reject(fn($user) => $user->id === $exceptUserId)
            ->unique('id')
            ->each(function (User $user) use ($kind, $title, $body, $url, $meta) {
                try {
                    $user->notify(new ActivityNotification($kind, $title, $body, $url, $meta));
                } catch (\Throwable $e) {
                    report($e);
                }
            });
    }
}
