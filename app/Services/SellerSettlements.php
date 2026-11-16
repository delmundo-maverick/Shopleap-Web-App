<?php

namespace App\Services;

use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Support\MoneyReferences;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SellerSettlements
{
    public function __construct(private LedgerService $ledger, private MoneyReport $report) {}

    public function isDirect(int $orderId): bool
    {
        return DB::table('ledger_transactions')->where('idempotency_key', 'order:'.$orderId.':logistics-routing')->exists();
    }

    public function routeOrder(Order $order): void
    {
        $net = LedgerService::cents($order->total_amount) - LedgerService::cents($order->commission_amount);
        if ($net <= 0 || $this->isDirect($order->id)) {
            return;
        }
        DB::table('seller_settlement_orders')->insert([
            'order_id' => $order->id, 'hub_id' => $order->earning_hub_id, 'seller_id' => $order->seller_id,
            'seller_amount' => LedgerService::decimal($net), 'commission_amount' => $order->commission_amount,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->ledger->post('order:'.$order->id.':logistics-routing', 'logistics_routing', $order->id, 'Seller funds held by logistics - '.$order->order_number, [
            $this->line('platform', 0, 'cod_receivable', 'credit', $net),
            $this->line('seller', $order->seller_id, 'hub_receivable', 'debit', $net),
            $this->line('hub', $order->earning_hub_id, 'cod_to_remit', 'debit', $net),
            $this->line('hub', $order->earning_hub_id, 'seller_payable', 'credit', $net),
        ]);
    }

    public function applyRefund(OrderReturn $return, bool $reverse = false): void
    {
        if (! $this->isDirect($return->order_id)) {
            return;
        }
        $key = 'return:'.$return->id.':logistics-routing';
        $original = DB::table('ledger_transactions')->where('idempotency_key', $key)->value('id');
        if ($reverse) {
            if ($original !== null) {
                $this->ledger->reverse($original, $key.':reversed');
            }

            return;
        }
        if ($original !== null) {
            return;
        }
        $source = DB::table('seller_settlement_orders')->where('order_id', $return->order_id)->firstOrFail();
        $commission = LedgerService::cents($return->commission_reversal);
        $net = LedgerService::cents($return->items_amount) - $commission;
        $this->ledger->post($key, 'logistics_refund_liability', $return->order_id, 'Refund funding assigned to logistics', [
            $this->line('hub', $source->hub_id, 'seller_payable', 'debit', $net),
            $this->line('seller', $source->seller_id, 'hub_receivable', 'credit', $net),
            $this->line('hub', $source->hub_id, 'cod_to_remit', 'debit', $commission),
            $this->line('platform', 0, 'cod_receivable', 'credit', $commission),
        ]);
    }

    public function orderBalances(string $ownerType, string $account): Builder
    {
        $debitNormal = in_array($account, LedgerService::DEBIT_ACCOUNTS, true);

        return DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')
            ->where('a.owner_type', $ownerType)->where('a.account_type', $account)
            ->select('t.order_id')->groupBy('t.order_id')
            ->selectRaw('ROUND(SUM(CASE WHEN e.direction = ? THEN e.amount ELSE -e.amount END), 2) as amount', [$debitNormal ? 'debit' : 'credit']);
    }

    public function eligible(?int $hubId = null, ?int $sellerId = null): Builder
    {
        $received = DB::table('rider_cash_handover_items as i')->join('rider_cash_handovers as h', 'h.id', '=', 'i.handover_id')
            ->select('i.order_id')->selectRaw('SUM(i.amount) as amount, MAX(COALESCE(h.settled_at, h.confirmed_at)) as received_at')->groupBy('i.order_id');
        $completed = DB::table('ledger_transactions')->where('type', 'completion')->select('order_id')->selectRaw('MIN(posted_at) as completed_at')->groupBy('order_id');

        return DB::table('seller_settlement_orders as s')->join('orders as o', 'o.id', '=', 's.order_id')
            ->join('users as u', 'u.id', '=', 's.seller_id')->join('logistics_profiles as h', 'h.id', '=', 's.hub_id')
            ->joinSub($this->orderBalances('seller', 'hub_receivable'), 'claim', 'claim.order_id', '=', 'o.id')
            ->joinSub($received, 'cash', 'cash.order_id', '=', 'o.id')->joinSub($completed, 'completion', 'completion.order_id', '=', 'o.id')
            ->when($hubId !== null, fn (Builder $query) => $query->where('s.hub_id', $hubId))
            ->when($sellerId !== null, fn (Builder $query) => $query->where('s.seller_id', $sellerId))
            ->where('o.status', Order::STATUS_COMPLETED)->where('claim.amount', '>', 0)
            ->whereColumn('cash.amount', '>=', 'o.cod_collected_amount')
            ->whereRaw('o.cod_collected_amount >= o.total_amount + o.delivered_shipping_fee')
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('seller_settlement_items as i')->whereColumn('i.active_order_id', 'o.id'))
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('order_returns as r')->whereColumn('r.order_id', 'o.id')
                ->whereIn('r.status', [OrderReturn::STATUS_REQUESTED, OrderReturn::STATUS_DISPUTED, OrderReturn::STATUS_APPROVED, OrderReturn::STATUS_SHIPPED_BACK, OrderReturn::STATUS_REFUND_PENDING]))
            ->select('s.*', 'o.order_number', 'u.name as seller_name', 'h.business_name as hub_name', 'claim.amount', 'cash.received_at', 'completion.completed_at');
    }

    public function dueAt(object $order): CarbonImmutable
    {
        return CarbonImmutable::parse($order->completed_at)->max(CarbonImmutable::parse($order->received_at))
            ->addDays((int) config('money.seller_settlement_due_days', 3));
    }

    public function directAvailable(int $sellerId): int
    {
        $amount = DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')->where('a.owner_type', 'seller')
            ->where('a.owner_id', $sellerId)->where('a.account_type', 'available')
            ->whereIn('t.order_id', DB::table('ledger_transactions')->where('type', 'logistics_routing')->select('order_id'))
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN e.direction = 'credit' THEN e.amount ELSE -e.amount END), 0), 2) as amount")->value('amount');

        return max(0, LedgerService::cents((string) $amount));
    }

    public function hasLegacyPlatformPayouts(): bool
    {
        if (DB::table('seller_payouts')->exists()) {
            return true;
        }

        return DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')->where('a.owner_type', 'seller')
            ->whereIn('a.account_type', ['pending', 'available', 'payout_locked'])
            ->where(function (Builder $query): void {
                $query->whereNull('t.order_id')->orWhereNotIn('t.order_id', DB::table('ledger_transactions')->where('type', 'logistics_routing')->select('order_id'));
            })->select('a.id')->groupBy('a.id')
            ->havingRaw("ROUND(SUM(CASE WHEN e.direction = 'credit' THEN e.amount ELSE -e.amount END), 2) > 0")->exists();
    }

    /** @param array<int, string> $orderCodes */
    public function create(User $actor, int $sellerId, int $methodId, array $orderCodes, string $requestKey): object
    {
        $hubId = $this->hubId($actor);

        return DB::transaction(function () use ($actor, $hubId, $sellerId, $methodId, $orderCodes, $requestKey): object {
            LogisticsProfile::whereKey($hubId)->lockForUpdate()->firstOrFail();
            User::whereKey($sellerId)->lockForUpdate()->firstOrFail();
            $key = 'ss:'.$actor->id.':'.$requestKey;
            if ($existing = DB::table('seller_settlements')->where('request_key', $key)->first()) {
                abort_unless((int) $existing->hub_id === $hubId && (int) $existing->seller_id === $sellerId, 403);

                return $existing;
            }
            $method = DB::table('seller_payout_methods')->where('id', $methodId)->where('seller_id', $sellerId)->firstOrFail();
            Order::whereIn('order_number', $orderCodes)->orderBy('id')->lockForUpdate()->get();

            return $this->ledger->synchronized(function () use ($actor, $hubId, $sellerId, $orderCodes, $method, $key): object {
                $orders = $this->eligible($hubId, $sellerId)->whereIn('o.order_number', $orderCodes)->orderBy('o.id')->get();
                $this->ensure($orders->isNotEmpty() && $orders->count() === count(array_unique($orderCodes)), 'Select completed, fully handed-over, unpaid orders without open returns.');
                $amount = $orders->sum(fn (object $order): int => LedgerService::cents((string) $order->amount));
                $this->ensure($amount > 0 && $amount <= 999999999999, 'The selected total exceeds the supported payment range. Select fewer orders.');
                $this->ensure(LedgerService::cents($this->report->balances('seller', $sellerId)['available']) >= $amount, 'The seller has insufficient available earnings or an unresolved recovery balance.');
                $record = MoneyReferences::insert('seller_settlements', 'SS', [
                    'request_key' => $key, 'hub_id' => $hubId, 'seller_id' => $sellerId, 'created_by' => $actor->id,
                    'amount' => LedgerService::decimal($amount), 'status' => 'pending', 'method_type' => $method->type,
                    'account_name' => $method->account_name, 'account_number_masked' => $method->account_number_masked,
                    'due_at' => $orders->map(fn (object $order) => $this->dueAt($order))->min(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($orders as $order) {
                    DB::table('seller_settlement_items')->insert(['settlement_id' => $record->id, 'order_id' => $order->order_id,
                        'active_order_id' => $order->order_id, 'amount' => $order->amount]);
                    $cents = LedgerService::cents((string) $order->amount);
                    $this->ledger->post('settlement:'.$record->id.':reserve:'.$order->order_id, 'seller_settlement_reserved', $order->order_id, 'Logistics seller payment reserved - '.$record->reference_no, [
                        $this->line('seller', $sellerId, 'available', 'debit', $cents), $this->line('seller', $sellerId, 'payout_locked', 'credit', $cents),
                    ]);
                }
                $this->event($record, $actor, 'created');

                return $record;
            });
        }, 3);
    }

    public function send(User $actor, string $reference, string $transferReference, string $proofPath): object
    {
        $hubId = $this->hubId($actor);

        return $this->change($reference, function (object $record) use ($actor, $hubId, $transferReference, $proofPath): object {
            abort_unless((int) $record->hub_id === $hubId, 403);
            if ($record->status === 'sent' && $record->transfer_reference === $transferReference) {
                return $record;
            }
            $this->ensure($record->status === 'pending', 'Only a pending payment can be sent.');
            $this->ensure(trim($transferReference) !== '' && $proofPath !== '', 'A transfer reference and payment proof are required.');
            $this->assertTransferReferenceAvailable($transferReference);
            foreach ($this->items($record) as $item) {
                $this->ensure(! DB::table('order_returns')->where('order_id', $item->order_id)->whereIn('status', [OrderReturn::STATUS_REQUESTED, OrderReturn::STATUS_DISPUTED, OrderReturn::STATUS_APPROVED, OrderReturn::STATUS_SHIPPED_BACK, OrderReturn::STATUS_REFUND_PENDING])->exists(), 'An order has a return/refund request. Cancel this pending settlement and resolve the return first.');
                $cents = LedgerService::cents((string) $item->amount);
                $this->ensure($this->orderAmount($item->order_id, 'seller', 'hub_receivable') >= $cents, 'Seller entitlement changed. Cancel and recreate this pending payment.');
                $this->ensure($this->orderAmount($item->order_id, 'hub', 'cash_held') >= $cents, 'This order has insufficient collected cash for the transfer.');
                $this->ledger->post('settlement:'.$record->id.':sent:'.$item->order_id, 'seller_settlement_sent', $item->order_id, 'Seller transfer awaiting receipt - '.$record->reference_no, [
                    $this->line('hub', $record->hub_id, 'cash_held', 'credit', $cents), $this->line('hub', $record->hub_id, 'cash_in_transit', 'debit', $cents),
                ]);
            }
            DB::table('seller_settlements')->where('id', $record->id)->update(['status' => 'sent', 'transfer_reference' => $transferReference,
                'proof_path' => $proofPath, 'sent_at' => now(), 'updated_at' => now()]);
            $this->event($record, $actor, 'sent');

            return $this->record($record->id);
        });
    }

    public function confirm(User $actor, string $reference): object
    {
        return $this->change($reference, function (object $record) use ($actor): object {
            abort_unless($actor->role === 'seller' && (int) $record->seller_id === $actor->id, 403);
            if ($record->status === 'paid') {
                return $record;
            }
            $this->ensure(in_array($record->status, ['sent', 'disputed'], true), 'Confirm receipt only after logistics records a transfer.');

            return $this->finish($record, $actor, 'confirmed');
        });
    }

    public function dispute(User $actor, string $reference, string $reason): object
    {
        return $this->change($reference, function (object $record) use ($actor, $reason): object {
            abort_unless($actor->role === 'seller' && (int) $record->seller_id === $actor->id, 403);
            if ($record->status === 'disputed') {
                return $record;
            }
            $this->ensure($record->status === 'sent' && strlen(trim($reason)) >= 3, 'A sent transfer and dispute reason are required.');
            DB::table('seller_settlements')->where('id', $record->id)->update(['status' => 'disputed', 'disputed_at' => now(), 'updated_at' => now()]);
            $this->event($record, $actor, 'disputed', $reason);

            return $this->record($record->id);
        });
    }

    public function cancel(User $actor, string $reference, string $reason): object
    {
        $hubId = $this->hubId($actor);

        return $this->change($reference, function (object $record) use ($actor, $hubId, $reason): object {
            abort_unless((int) $record->hub_id === $hubId, 403);
            if ($record->status === 'cancelled') {
                return $record;
            }
            $this->ensure($record->status === 'pending' && strlen(trim($reason)) >= 3, 'Only an unsent payment can be cancelled, with a reason.');

            return $this->release($record, $actor, $reason);
        });
    }

    public function resolve(User $actor, string $reference, string $outcome, string $reason, string $proofPath): object
    {
        abort_unless($actor->role === 'super_admin', 403);

        return $this->change($reference, function (object $record) use ($actor, $outcome, $reason, $proofPath): object {
            $reviewable = $record->status === 'disputed' || ($record->status === 'sent' && CarbonImmutable::parse($record->due_at)->isPast());
            $this->ensure($reviewable && strlen(trim($reason)) >= 3 && $proofPath !== '', 'Resolve a disputed or overdue sent payment with a reason and verified payment/return evidence.');
            $this->ensure(in_array($outcome, ['paid', 'returned'], true), 'Choose received or returned to hub.');
            DB::table('seller_settlements')->where('id', $record->id)->update(['resolved_at' => now()]);
            $this->event($record, $actor, 'resolution', $reason, $proofPath);
            if ($outcome === 'paid') {
                return $this->finish($record, $actor, 'resolved_received');
            }
            foreach ($this->items($record) as $item) {
                $id = DB::table('ledger_transactions')->where('idempotency_key', 'settlement:'.$record->id.':sent:'.$item->order_id)->value('id');
                $this->ledger->reverse($id, 'settlement:'.$record->id.':returned:'.$item->order_id);
            }

            return $this->release($record, $actor, $reason);
        });
    }

    public function recover(User $actor, int $orderId, string $transferReference, string $proofPath): void
    {
        $hubId = $this->hubId($actor);
        DB::transaction(function () use ($actor, $hubId, $orderId, $transferReference, $proofPath): void {
            LogisticsProfile::whereKey($hubId)->lockForUpdate()->firstOrFail();
            $source = DB::table('seller_settlement_orders')->where('order_id', $orderId)->where('hub_id', $hubId)->firstOrFail();
            User::whereKey($source->seller_id)->lockForUpdate()->firstOrFail();
            Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            $this->ledger->synchronized(function () use ($actor, $source, $orderId, $transferReference, $proofPath): void {
                if (DB::table('seller_settlement_events')->where('transfer_reference', $transferReference)->where('order_id', $orderId)->where('action', 'recovery_received')->exists()) {
                    return;
                }
                $amount = -$this->orderAmount($orderId, 'seller', 'hub_receivable');
                $this->ensure($amount > 0, 'There is no seller recovery due for this order.');
                $this->ensure($proofPath !== '', 'Recovery evidence is required.');
                $this->assertTransferReferenceAvailable($transferReference);
                $this->ledger->post('seller-recovery:'.$source->hub_id.':'.$transferReference, 'seller_recovery', $orderId, 'Seller refund recovery received', [
                    $this->line('hub', $source->hub_id, 'cash_held', 'debit', $amount), $this->line('seller', $source->seller_id, 'available', 'credit', $amount),
                    $this->line('hub', $source->hub_id, 'seller_payable', 'credit', $amount), $this->line('seller', $source->seller_id, 'hub_receivable', 'debit', $amount),
                ]);
                DB::table('seller_settlement_events')->insert(['order_id' => $orderId, 'actor_id' => $actor->id, 'action' => 'recovery_received',
                    'transfer_reference' => $transferReference, 'proof_path' => $proofPath, 'amount' => LedgerService::decimal($amount), 'created_at' => now(), 'updated_at' => now()]);
            });
        }, 3);
    }

    public function returnCommission(User $actor, int $orderId, string $transferReference, string $proofPath): void
    {
        abort_unless($actor->role === 'super_admin', 403);
        DB::transaction(function () use ($actor, $orderId, $transferReference, $proofPath): void {
            $source = DB::table('seller_settlement_orders')->where('order_id', $orderId)->firstOrFail();
            LogisticsProfile::whereKey($source->hub_id)->lockForUpdate()->firstOrFail();
            Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            $this->ledger->synchronized(function () use ($actor, $source, $orderId, $transferReference, $proofPath): void {
                if (DB::table('seller_settlement_events')->where('transfer_reference', $transferReference)->where('order_id', $orderId)->where('action', 'commission_return')->exists()) {
                    return;
                }
                $amount = -$this->orderAmount($orderId, 'hub', 'cod_to_remit');
                $this->ensure($amount > 0, 'There is no commission refund credit due to this hub.');
                $this->ensure($proofPath !== '', 'Commission return evidence is required.');
                $this->assertTransferReferenceAvailable($transferReference);
                $this->ensure(LedgerService::cents($this->report->balances('platform', 0)['cod_held']) >= $amount, 'Platform funds are insufficient for the commission return.');
                $this->ledger->post('commission-return:'.$transferReference, 'commission_return', $orderId, 'Refund commission returned to logistics', [
                    $this->line('platform', 0, 'cod_held', 'credit', $amount), $this->line('hub', $source->hub_id, 'cash_held', 'debit', $amount),
                    $this->line('platform', 0, 'cod_receivable', 'debit', $amount), $this->line('hub', $source->hub_id, 'cod_to_remit', 'credit', $amount),
                ]);
                DB::table('seller_settlement_events')->insert(['order_id' => $orderId, 'actor_id' => $actor->id, 'action' => 'commission_return',
                    'transfer_reference' => $transferReference, 'proof_path' => $proofPath, 'amount' => LedgerService::decimal($amount), 'created_at' => now(), 'updated_at' => now()]);
            });
        }, 3);
    }

    public function orderAmount(int $orderId, string $ownerType, string $account): int
    {
        $amount = $this->orderBalances($ownerType, $account)->where('t.order_id', $orderId)->first()?->amount;

        return $amount === null ? 0 : LedgerService::cents((string) $amount);
    }

    public function assertTransferReferenceAvailable(string $reference): void
    {
        $this->ensure(trim($reference) !== '' && strlen($reference) <= 150, 'A valid external transfer reference is required.');
        foreach (['seller_settlements', 'seller_settlement_events', 'refund_payouts'] as $table) {
            $this->ensure(! DB::table($table)->where('transfer_reference', $reference)->exists(), 'That transfer reference is already recorded.');
        }
    }

    private function hubId(User $actor): int
    {
        abort_unless($actor->role === 'logistics' && $actor->logisticsProfile !== null, 403);

        return $actor->logisticsProfile->id;
    }

    private function change(string $reference, \Closure $callback): object
    {
        return DB::transaction(function () use ($reference, $callback): object {
            $initial = DB::table('seller_settlements')->where('reference_no', $reference)->firstOrFail();
            LogisticsProfile::whereKey($initial->hub_id)->lockForUpdate()->firstOrFail();
            User::whereKey($initial->seller_id)->lockForUpdate()->firstOrFail();
            $record = DB::table('seller_settlements')->where('id', $initial->id)->lockForUpdate()->firstOrFail();
            Order::whereIn('id', $this->items($record)->pluck('order_id'))->orderBy('id')->lockForUpdate()->get();

            return $this->ledger->synchronized(fn (): object => $callback($record));
        }, 3);
    }

    private function items(object $record): Collection
    {
        return DB::table('seller_settlement_items')->where('settlement_id', $record->id)->orderBy('order_id')->get();
    }

    private function finish(object $record, User $actor, string $action): object
    {
        foreach ($this->items($record) as $item) {
            $amount = LedgerService::cents((string) $item->amount);
            $this->ledger->post('settlement:'.$record->id.':paid:'.$item->order_id, 'seller_settlement_paid', $item->order_id, 'Seller received logistics payment - '.$record->reference_no, [
                $this->line('hub', $record->hub_id, 'seller_payable', 'debit', $amount), $this->line('hub', $record->hub_id, 'cash_in_transit', 'credit', $amount),
                $this->line('seller', $record->seller_id, 'hub_receivable', 'credit', $amount), $this->line('system', 0, 'EXTERNAL_BANK', 'debit', $amount),
                $this->line('seller', $record->seller_id, 'payout_locked', 'debit', $amount), $this->line('seller', $record->seller_id, 'withdrawn', 'credit', $amount),
            ]);
        }
        $values = ['status' => 'paid', 'confirmed_at' => now(), 'updated_at' => now()];
        if ($record->status === 'disputed') {
            $values['resolved_at'] = now();
        }
        DB::table('seller_settlements')->where('id', $record->id)->update($values);
        DB::table('seller_settlement_items')->where('settlement_id', $record->id)->update(['active_order_id' => null]);
        $this->event($record, $actor, $action);

        return $this->record($record->id);
    }

    private function release(object $record, User $actor, string $reason): object
    {
        foreach ($this->items($record) as $item) {
            $id = DB::table('ledger_transactions')->where('idempotency_key', 'settlement:'.$record->id.':reserve:'.$item->order_id)->value('id');
            $this->ledger->reverse($id, 'settlement:'.$record->id.':released:'.$item->order_id);
        }
        DB::table('seller_settlement_items')->where('settlement_id', $record->id)->update(['active_order_id' => null]);
        DB::table('seller_settlements')->where('id', $record->id)->update(['status' => 'cancelled', 'updated_at' => now()]);
        $this->event($record, $actor, 'cancelled', $reason);

        return $this->record($record->id);
    }

    private function record(int $id): object
    {
        return DB::table('seller_settlements')->where('id', $id)->firstOrFail();
    }

    private function event(object $record, User $actor, string $action, ?string $note = null, ?string $proofPath = null): void
    {
        DB::table('seller_settlement_events')->insert(['settlement_id' => $record->id, 'actor_id' => $actor->id,
            'action' => $action, 'note' => $note, 'proof_path' => $proofPath, 'created_at' => now(), 'updated_at' => now()]);
        DB::afterCommit(function () use ($record, $action): void {
            $userIds = [$record->seller_id, LogisticsProfile::find($record->hub_id)?->user_id];
            if ($action === 'disputed') {
                $userIds = array_merge($userIds, User::where('role', 'super_admin')->pluck('id')->all());
            }
            foreach (User::whereIn('id', array_filter($userIds))->get() as $user) {
                try {
                    $user->notify(new ActivityNotification('settlement.'.$action, 'Seller payment '.str_replace('_', ' ', $action),
                        $record->reference_no.' · PHP '.$record->amount, route($user->role === 'seller' ? 'seller.settlements.index' : ($user->role === 'logistics' ? 'logistics.settlements.index' : 'admin.settlements.index'), [], false)));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        });
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['settlement' => $message]);
        }
    }

    /** @return array{owner_type: string, owner_id: int, account_type: string, direction: string, cents: int} */
    private function line(string $ownerType, int $ownerId, string $accountType, string $direction, int $cents): array
    {
        return ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'account_type' => $accountType, 'direction' => $direction, 'cents' => $cents];
    }
}
