<?php

namespace App\Services;

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ShipmentBag;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Collection;

class Notifier
{
    // ── Cancellation ──

    public function orderCancelled(Order $order, string $by, bool $fromRequest, ?int $actorId, bool $pickupWasScheduled): void
    {
        $no = "#{$order->order_number}";
        $reason = $order->cancel_reason ? " Reason: {$order->cancel_reason}." : '';

        $buyerUrl = route('buyer.orders.index', ['tab' => 'cancelled'], false);
        $sellerUrl = route('seller.orders.show', $order, false);
        $meta = ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code];

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

            $this->send(
                $order->pickupRider?->user,
                $actorId,
                'cancel.pickup_rider',
                'Pickup cancelled',
                "Order {$no} was cancelled. Do not collect the parcel.",
                null,
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
            "Order #{$order->order_number}: the buyer asked to cancel ({$order->cancel_request_reason}). Answer within "
                .Order::CANCEL_REQUEST_AUTO_APPROVE_HOURS.' hours, or it is approved automatically.',
            route('seller.orders.show', $order, false),
            ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code]
        );
    }

    public function cancellationWithdrawn(Order $order, ?int $actorId): void
    {
        $this->send(
            $order->seller,
            $actorId,
            'cancel.withdrawn',
            'Cancellation request withdrawn',
            "The buyer withdrew the request to cancel order #{$order->order_number}.",
            route('seller.orders.show', $order, false),
            ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code]
        );
    }

    public function cancellationDeclined(Order $order, ?int $actorId): void
    {
        $this->send(
            $order->buyer,
            $actorId,
            'cancel.declined',
            'Cancellation declined',
            "Order #{$order->order_number}: the seller declined your cancellation request. {$order->cancel_request_response}",
            route('buyer.orders.index', [], false),
            ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code]
        );
    }

    public function cancellationExpired(Order $order): void
    {
        $this->send(
            $order->buyer,
            null,
            'cancel.expired',
            'Cancellation request expired',
            "Order #{$order->order_number} was already picked up by the courier, so it can no longer be cancelled.",
            route('buyer.orders.index', [], false),
            ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code]
        );
    }

    // ── Return / refund: one place decides who hears about each status ──

    public function returnChanged(OrderReturn $return, ?int $actorId): void
    {
        $return->loadMissing(['buyer', 'seller']);

        $no = '#'.$return->order->order_number;
        $money = '₱'.number_format((float) $return->refund_amount, 2);
        $meta = ['return_id' => $return->id, 'order_id' => $return->order->order_number];

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

    // ── Order progress (called from Order::logStatusEvent) ──

    public function orderStatusChanged(Order $order, string $status, ?string $note, ?int $actorId): void
    {
        // Cancellations carry their reason and are handled by orderCancelled().
        if ($status === Order::STATUS_CANCELLED) {
            return;
        }

        $order->loadMissing(['buyer', 'seller.sellerProfile', 'logisticsProfile.user', 'deliveryRider.user']);

        $no = "#{$order->order_number}";
        $meta = ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code, 'status' => $status];
        $buyerUrl = route('buyer.orders.index', ['tab' => $this->buyerTab($status)], false);
        $sellerUrl = route('seller.orders.show', $order, false);
        $cash = '₱'.number_format($order->grand_total, 2);

        switch ($status) {
            case Order::STATUS_PLACED:
                $items = (int) $order->items()->sum('quantity');
                $this->send(
                    $order->seller,
                    $actorId,
                    'order.placed',
                    'New order',
                    "Order {$no} from {$order->buyer_name}: {$items} item(s), ₱".number_format((float) $order->total_amount, 2).'. Confirm it to start preparing.',
                    $sellerUrl,
                    $meta
                );
                break;

            case Order::STATUS_CONFIRMED:
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.confirmed',
                    'Order confirmed',
                    "The seller confirmed order {$no}.",
                    $buyerUrl,
                    $meta
                );
                break;

            case Order::STATUS_PREPARING:
                // A note means the pickup was being re-arranged, not a new step.
                if ($note === null) {
                    $this->send(
                        $order->buyer,
                        $actorId,
                        'order.preparing',
                        'Seller is preparing your order',
                        "The seller is packing order {$no}.",
                        $buyerUrl,
                        $meta
                    );
                }
                break;

            case Order::STATUS_READY_FOR_PICKUP:
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.ready',
                    'Order packed',
                    "Order {$no} is packed and waiting for the courier.",
                    $buyerUrl,
                    $meta
                );

                $shop = $order->seller?->sellerProfile?->business_name ?? $order->seller?->name;
                $slot = Order::PICKUP_SLOTS[$order->pickup_time_slot] ?? '';
                $this->send(
                    $order->logisticsProfile?->user,
                    $actorId,
                    'pickup.requested',
                    'New pickup request',
                    "Order {$no} from {$shop}: pickup on {$order->pickup_date?->format('M d')} ({$slot}). Assign a rider.",
                    route('logistics.pickups.index', [], false),
                    $meta
                );
                break;

            case Order::STATUS_PICKED_UP:
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.picked_up',
                    'Parcel picked up',
                    "Order {$no} was picked up by the courier.",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $order->seller,
                    $actorId,
                    'order.picked_up',
                    'Parcel collected',
                    "Order {$no} was collected by the courier.",
                    $sellerUrl,
                    $meta
                );
                break;

            case Order::STATUS_AT_SORTING_CENTER:
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.at_hub',
                    'Arrived at the sorting center',
                    "Order {$no} arrived at the sorting center.",
                    $buyerUrl,
                    $meta
                );
                break;

            case Order::STATUS_ASSIGNED_TO_RIDER:
                $ship = $order->ship_to;
                $this->send(
                    $order->deliveryRider?->user,
                    $actorId,
                    'delivery.assigned',
                    'New delivery assigned',
                    "Order {$no} to Brgy. {$ship['barangay']}, {$ship['municipality']}. Cash to collect: {$cash}.",
                    null,
                    $meta
                );

                // The first assignment is internal; a note with failed attempts means a reschedule.
                if ($note !== null && $order->delivery_attempts > 0) {
                    $this->send(
                        $order->buyer,
                        $actorId,
                        'order.rescheduled',
                        'Delivery rescheduled',
                        "Order {$no}: we'll try delivering again (attempt ".($order->delivery_attempts + 1).' of '.Order::MAX_DELIVERY_ATTEMPTS.').',
                        $buyerUrl,
                        $meta
                    );
                }
                break;

            case Order::STATUS_OUT_FOR_DELIVERY:
                $rider = $order->deliveryRider?->full_name;
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.out_for_delivery',
                    'Out for delivery',
                    "Order {$no} is out for delivery.".($rider ? " Your rider is {$rider}." : '')." Please prepare {$cash} cash.",
                    $buyerUrl,
                    $meta
                );
                break;

            case Order::STATUS_DELIVERED:
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.delivered',
                    'Parcel delivered',
                    "Order {$no} was delivered. Please confirm you received it in good condition.",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $order->seller,
                    $actorId,
                    'order.delivered',
                    'Order delivered',
                    "Order {$no} was delivered. It completes when the buyer confirms, or automatically after ".Order::AUTO_COMPLETE_DAYS.' days.',
                    $sellerUrl,
                    $meta
                );
                break;

            case Order::STATUS_DELIVERY_FAILED:
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.delivery_failed',
                    'Delivery unsuccessful',
                    "We couldn't deliver order {$no}.".($note ? " {$note}" : ''),
                    $buyerUrl,
                    $meta
                );
                break;

            case Order::STATUS_RETURNED:
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.returned',
                    'Parcel returned to the seller',
                    "Order {$no} couldn't be delivered and is being returned to the seller.",
                    $buyerUrl,
                    $meta
                );
                $this->send(
                    $order->seller,
                    $actorId,
                    'order.returned',
                    'Parcel returned to you',
                    "Order {$no} couldn't be delivered after {$order->delivery_attempts} attempt(s) and is coming back to you.",
                    $sellerUrl,
                    $meta
                );
                break;

            case Order::STATUS_COMPLETED:
                $earned = '₱'.number_format((float) $order->total_amount - (float) $order->commission_amount, 2);
                $this->send(
                    $order->seller,
                    $actorId,
                    'order.completed',
                    'Order completed',
                    "Order {$no} is complete. Your earnings: {$earned}.",
                    $sellerUrl,
                    $meta
                );

                // When the buyer confirmed it themselves they are the actor and aren't notified.
                $until = $order->returnWindowEndsAt()?->format('M d');
                $this->send(
                    $order->buyer,
                    $actorId,
                    'order.completed',
                    'Order completed',
                    "Order {$no} was completed automatically.".($until ? " You can still rate it or request a return/refund until {$until}." : ''),
                    $buyerUrl,
                    $meta
                );
                break;
        }
    }

    public function pickupAssigned(Order $order, CourierProfile $rider, ?int $actorId): void
    {
        $slot = Order::PICKUP_SLOTS[$order->pickup_time_slot] ?? '';

        $this->send(
            $order->seller,
            $actorId,
            'pickup.assigned',
            'Pickup scheduled',
            "Order #{$order->order_number}: {$rider->full_name} will collect it on {$order->pickup_date?->format('M d')} ({$slot}).",
            route('seller.orders.show', $order, false),
            ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code]
        );

        $rider->loadMissing('user');

        $this->send(
            $rider->user,
            $actorId,
            'pickup.assigned_rider',
            'New pickup assigned',
            "Order #{$order->order_number}: collect from ".($order->seller?->sellerProfile?->business_name ?? $order->seller?->name)
                ." on {$order->pickup_date?->format('M d')} ({$slot}). Please accept it.",
            null,
            ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code]
        );
    }

    public function pickupDeclined(Order $order, string $hubName, string $reason, ?int $actorId): void
    {
        $this->send(
            $order->seller,
            $actorId,
            'pickup.declined',
            'Pickup request declined',
            "Order #{$order->order_number}: {$hubName} can't collect it ({$reason}). Please choose another sorting center.",
            route('seller.orders.show', $order, false),
            ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code]
        );
    }

    // ── Shipment bags ──

    public function bagDispatched(ShipmentBag $bag, ?int $actorId): void
    {
        $bag->loadMissing(['fromHub', 'toHub.user']);

        $this->send(
            $bag->toHub?->user,
            $actorId,
            'bag.dispatched',
            'Incoming bag',
            "Bag {$bag->code} from {$bag->fromHub?->business_name} is on its way with {$bag->items()->count()} parcel(s).",
            route('logistics.inbound.show', $bag, false),
            ['bag_id' => $bag->id]
        );
    }

    public function bagReceived(ShipmentBag $bag, ?int $actorId): void
    {
        $bag->loadMissing(['fromHub.user', 'toHub']);

        $this->send(
            $bag->fromHub?->user,
            $actorId,
            'bag.received',
            'Bag received',
            "Bag {$bag->code} was received by {$bag->toHub?->business_name}.",
            route('logistics.outbound.show', $bag, false),
            ['bag_id' => $bag->id]
        );
    }

    /**
     * A bag closed with parcels that never arrived.
     *
     * @param  Collection<int, Order>  $missingOrders
     */
    public function bagDiscrepancy(ShipmentBag $bag, Collection $missingOrders, ?int $actorId): void
    {
        $bag->loadMissing(['fromHub.user', 'toHub.user']);

        $codes = $missingOrders->map(fn (Order $order) => "#{$order->order_number}")->implode(', ');
        $count = $missingOrders->count();
        $body = "Bag {$bag->code} was received with {$count} missing parcel(s): {$codes}.";

        $this->send(
            $bag->fromHub?->user,
            $actorId,
            'bag.discrepancy',
            'Bag discrepancy',
            $body,
            route('logistics.outbound.show', $bag, false),
            ['bag_id' => $bag->id]
        );

        $this->send(
            $bag->toHub?->user,
            $actorId,
            'bag.discrepancy',
            'Bag discrepancy',
            $body,
            route('logistics.inbound.show', $bag, false),
            ['bag_id' => $bag->id]
        );
    }

    public function staleBag(ShipmentBag $bag): void
    {
        $bag->loadMissing(['fromHub.user', 'toHub.user']);

        $body = "Bag {$bag->code} has been in transit since "
            .$bag->dispatched_at?->format('M d, Y g:i A').'. Please confirm its status.';

        $this->send(
            $bag->fromHub?->user,
            null,
            'bag.stale',
            'Bag still in transit',
            $body,
            route('logistics.outbound.show', $bag, false),
            ['bag_id' => $bag->id]
        );

        $this->send(
            $bag->toHub?->user,
            null,
            'bag.stale',
            'Bag still in transit',
            $body,
            route('logistics.inbound.show', $bag, false),
            ['bag_id' => $bag->id]
        );
    }

    public function staleParcel(Order $order, int $hours): void
    {
        $order->loadMissing(['currentHub.user']);

        $this->send(
            $order->currentHub?->user,
            null,
            'parcel.stale',
            'Parcel needs attention',
            "Order #{$order->order_number} has had no tracking update for {$hours} hours.",
            route('logistics.incoming.index', [], false),
            ['order_id' => $order->order_number, 'waybill_code' => $order->waybill_code]
        );
    }

    private function buyerTab(string $status): string
    {
        foreach (Order::TAB_GROUPS as $tab => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $tab;
            }
        }

        return 'all';
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
            ->reject(fn ($user) => $user->id === $exceptUserId)
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
