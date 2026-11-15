<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;

class OrderLedger
{
    public function __construct(private LedgerService $ledger) {}

    public function returnShippingPayer(OrderReturn $return): string
    {
        return config('money.return_shipping_payer.'.$return->reason, config('money.default_return_shipping_payer'));
    }

    public function deliverySkipReason(Order $order): ?string
    {
        if (! $order->delivered_at || ! $order->earnings_recorded_at || ! $order->earning_hub_id
            || ! $order->earning_courier_id || $order->delivered_shipping_fee === null || $order->cod_collected_amount === null) {
            return 'Missing frozen delivery/cash snapshot; no historical values inferred.';
        }
        if (LedgerService::cents($order->total_amount) < LedgerService::cents($order->commission_amount)) {
            return 'Commission exceeds the recorded items amount.';
        }

        return null;
    }

    public function delivered(Order $order): ?int
    {
        if ($this->deliverySkipReason($order) !== null) {
            return null;
        }
        $items = LedgerService::cents($order->total_amount);
        $commission = LedgerService::cents($order->commission_amount);
        $shipping = LedgerService::cents($order->delivered_shipping_fee);
        $cash = LedgerService::cents($order->cod_collected_amount);
        $lines = [
            $this->line('rider', $order->earning_courier_id, 'cash_on_hand', 'debit', $cash),
            $this->line('rider', $order->earning_courier_id, 'cash_due_hub', 'credit', $cash),
            $this->line('hub', $order->earning_hub_id, 'cash_receivable', 'debit', $cash),
            $this->line('hub', $order->earning_hub_id, 'cod_to_remit', 'credit', $items),
            $this->line('hub', $order->earning_hub_id, 'shipping_earned', 'credit', $shipping),
            $this->line('platform', 0, 'cod_receivable', 'debit', $items),
            $this->line('seller', $order->seller_id, 'pending', 'credit', $items - $commission),
            $this->line('platform', 0, 'commission_pending', 'credit', $commission),
        ];
        $variance = $items + $shipping - $cash;
        if ($variance !== 0) {
            $lines[] = $this->line('hub', $order->earning_hub_id, 'collection_variance', $variance > 0 ? 'debit' : 'credit', abs($variance));
        }
        if ($order->rider_pay !== null) {
            $pay = LedgerService::cents($order->rider_pay);
            $lines[] = $this->line('hub', $order->earning_hub_id, 'rider_expense', 'debit', $pay);
            $lines[] = $this->line('hub', $order->earning_hub_id, 'rider_pay_owed', 'credit', $pay);
            $lines[] = $this->line('rider', $order->earning_courier_id, 'earnings_accrued', 'debit', $pay);
            $lines[] = $this->line('rider', $order->earning_courier_id, 'earnings_income', 'credit', $pay);
        }

        return $this->ledger->post('order:'.$order->id.':delivered', 'delivery', $order->id, 'COD collected - Order '.$order->order_number, $lines);
    }

    public function completed(Order $order, ?string $releaseKey = null): ?int
    {
        $key = $releaseKey ?? 'order:'.$order->id.':completed';
        if (! $this->hasDelivery($order->id) || DB::table('ledger_transactions')->where('idempotency_key', $key)->exists()) {
            return null;
        }
        $pending = $this->orderBalance($order->id, 'seller', 'pending');
        $commission = $this->orderBalance($order->id, 'platform', 'commission_pending');
        if ($pending + $commission === 0) {
            return null;
        }

        return $this->ledger->post($key, 'completion', $order->id, 'Seller earnings released - Order '.$order->order_number, [
            $this->line('seller', $order->seller_id, 'pending', 'debit', $pending),
            $this->line('seller', $order->seller_id, 'available', 'credit', $pending),
            $this->line('platform', 0, 'commission_pending', 'debit', $commission),
            $this->line('platform', 0, 'commission_earned', 'credit', $commission),
        ]);
    }

    public function returnChanged(OrderReturn $return): void
    {
        $order = $return->order;
        if (! $this->hasDelivery($order->id)) {
            return;
        }
        $key = 'return:'.$return->id.':approved';
        $original = DB::table('ledger_transactions')->where('idempotency_key', $key)->value('id');
        if (in_array($return->status, [OrderReturn::STATUS_CANCELLED, OrderReturn::STATUS_EXPIRED, OrderReturn::STATUS_REJECTED], true)) {
            if ($original !== null) {
                $this->ledger->reverse($original, 'return:'.$return->id.':reversed');
                if ($order->status === Order::STATUS_COMPLETED) {
                    $this->completed($order, 'return:'.$return->id.':release-restored');
                }
            }

            return;
        }
        if (! in_array($return->status, [OrderReturn::STATUS_APPROVED, OrderReturn::STATUS_SHIPPED_BACK, OrderReturn::STATUS_REFUND_PENDING, OrderReturn::STATUS_REFUNDED], true) || $original !== null) {
            return;
        }
        $items = LedgerService::cents($return->items_amount);
        $commission = LedgerService::cents($return->commission_reversal);
        if ($items === 0) {
            return;
        }
        $confirmed = $order->status === Order::STATUS_COMPLETED;
        $this->ledger->post($key, 'refund', $order->id, 'Refund issued - Order '.$order->order_number, [
            $this->line('seller', $order->seller_id, $confirmed ? 'available' : 'pending', 'debit', $items - $commission),
            $this->line('platform', 0, $confirmed ? 'commission_earned' : 'commission_pending', 'debit', $commission),
            $this->line('buyer', $order->buyer_id, 'refunds_issued', 'credit', $items),
        ]);
    }

    public function hasDelivery(int $orderId): bool
    {
        return DB::table('ledger_transactions')->where('idempotency_key', 'order:'.$orderId.':delivered')->exists();
    }

    public function pendingItems(int $orderId): int
    {
        return $this->orderBalance($orderId, 'seller', 'pending') + $this->orderBalance($orderId, 'platform', 'commission_pending');
    }

    private function orderBalance(int $orderId, string $ownerType, string $accountType): int
    {
        $amount = DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')->where('t.order_id', $orderId)
            ->where('a.owner_type', $ownerType)->where('a.account_type', $accountType)
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN e.direction = 'credit' THEN e.amount ELSE -e.amount END), 0), 2) as balance")->value('balance');

        return LedgerService::cents((string) $amount);
    }

    /** @return array{owner_type: string, owner_id: int, account_type: string, direction: string, cents: int} */
    private function line(string $ownerType, int $ownerId, string $accountType, string $direction, int $cents): array
    {
        return ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'account_type' => $accountType, 'direction' => $direction, 'cents' => $cents];
    }
}
