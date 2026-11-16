<?php

namespace App\Services;

use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Support\MoneyReferences;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Payouts
{
    public function __construct(private LedgerService $ledger, private PayoutQueries $queries, private MoneyReport $report, private Notifier $notifier) {}

    /** @param array<int, string>|null $orderCodes */
    public function payRider(int $hubId, int $riderId, ?array $orderCodes, ?string $notes, string $requestKey, int $actorId): object
    {
        return DB::transaction(function () use ($hubId, $riderId, $orderCodes, $notes, $requestKey, $actorId): object {
            LogisticsProfile::whereKey($hubId)->lockForUpdate()->firstOrFail();
            CourierProfile::whereKey($riderId)->lockForUpdate()->firstOrFail();
            $key = 'po:'.$actorId.':'.$requestKey;
            if ($existing = DB::table('rider_payouts')->where('request_key', $key)->first()) {
                $this->ensure((int) $existing->hub_id === $hubId && (int) $existing->rider_id === $riderId, 'This request belongs to another payout.');

                return $existing;
            }
            $query = $this->queries->riderOrders($hubId, $riderId)->when($orderCodes !== null, fn (Builder $query) => $query->whereIn('o.order_number', $orderCodes));
            $orderIds = (clone $query)->get()->pluck('id');
            Order::whereIn('id', $orderIds)->orderBy('id')->lockForUpdate()->get();
            $query->whereIn('o.id', $orderIds);

            return $this->ledger->synchronized(function () use ($query, $orderCodes, $hubId, $riderId, $notes, $key, $actorId): object {
                $orders = $query->get();
                $this->ensure($orders->isNotEmpty() && ($orderCodes === null || $orders->count() === count(array_unique($orderCodes))), 'Select unpaid earnings belonging to this rider and hub. Some parcels may already be paid.');
                $amount = $orders->sum(fn (object $order): int => LedgerService::cents((string) $order->amount));
                $this->ensure($amount > 0 && $amount <= 999999999999, 'The selected earnings exceed the supported payout range. Select fewer parcels.');
                $record = MoneyReferences::insert('rider_payouts', 'PO', [
                    'hub_id' => $hubId, 'rider_id' => $riderId, 'request_key' => $key, 'amount' => LedgerService::decimal($amount),
                    'status' => 'paid', 'paid_by' => $actorId, 'paid_at' => now(), 'notes' => $notes, 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($orders as $order) {
                    $cents = LedgerService::cents((string) $order->amount);
                    DB::table('rider_payout_items')->insert(['rider_payout_id' => $record->id, 'order_id' => $order->id, 'amount' => $order->amount]);
                    $this->ledger->post('rider-payout:'.$record->id.':'.$order->id, 'rider_payout', $order->id, 'Rider paid - '.$record->reference_no, [
                        $this->line('rider', $riderId, 'earnings_accrued', 'credit', $cents),
                        $this->line('rider', $riderId, 'earnings_paid', 'debit', $cents),
                        $this->line('hub', $hubId, 'rider_pay_owed', 'debit', $cents),
                        $this->line('hub', $hubId, 'cash_held', 'credit', $cents),
                    ]);
                }

                $this->notifier->moneyChanged('rider_payout', $record);
                DB::afterCommit(fn () => RiderRealtime::notify($riderId));

                return $record;
            });
        }, 3);
    }

    /** @param array{type: string, account_name: string, account_number_masked: string, is_default?: bool} $data */
    public function addMethod(int $sellerId, array $data): void
    {
        DB::transaction(function () use ($sellerId, $data): void {
            User::whereKey($sellerId)->lockForUpdate()->firstOrFail();
            $this->ensure(in_array($data['type'], ['GCash', 'Maya', 'bank'], true) && preg_match('/^\d{4}$/D', $data['account_number_masked']) === 1, 'Enter a supported method and only its last four digits.');
            $default = ($data['is_default'] ?? false) || ! DB::table('seller_payout_methods')->where('seller_id', $sellerId)->exists();
            if ($default) {
                DB::table('seller_payout_methods')->where('seller_id', $sellerId)->update(['is_default' => false, 'updated_at' => now()]);
            }
            DB::table('seller_payout_methods')->insert([
                'seller_id' => $sellerId, 'type' => $data['type'], 'account_name' => $data['account_name'],
                'account_number_masked' => $data['account_number_masked'], 'is_default' => $default, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    public function removeMethod(int $sellerId, int $methodId): void
    {
        DB::transaction(function () use ($sellerId, $methodId): void {
            User::whereKey($sellerId)->lockForUpdate()->firstOrFail();
            $method = DB::table('seller_payout_methods')->where('seller_id', $sellerId)->where('id', $methodId)->lockForUpdate()->firstOrFail();
            DB::table('seller_payout_methods')->where('id', $methodId)->delete();
            if ($method->is_default) {
                $replacement = DB::table('seller_payout_methods')->where('seller_id', $sellerId)->orderBy('id')->lockForUpdate()->first();
                if ($replacement) {
                    DB::table('seller_payout_methods')->where('id', $replacement->id)->update(['is_default' => true, 'updated_at' => now()]);
                }
            }
        }, 3);
    }

    public function requestSeller(int $sellerId, string $amount, int $methodId, string $requestKey): object
    {
        return DB::transaction(function () use ($sellerId, $amount, $methodId, $requestKey): object {
            User::whereKey($sellerId)->lockForUpdate()->firstOrFail();
            $key = 'sp:'.$sellerId.':'.$requestKey;
            if ($existing = DB::table('seller_payouts')->where('request_key', $key)->first()) {
                return $existing;
            }
            $this->ensure(! DB::table('seller_payouts')->where('active_seller_id', $sellerId)->exists(), 'Your pending or processing payout must be completed before requesting another.');
            $method = DB::table('seller_payout_methods')->where('seller_id', $sellerId)->where('id', $methodId)->lockForUpdate()->firstOrFail();

            return $this->ledger->synchronized(function () use ($sellerId, $amount, $method, $key): object {
                $cents = $this->amount($amount);
                $minimum = LedgerService::cents((string) (DB::table('platform_settings')->where('key', 'minimum_payout')->lockForUpdate()->value('value') ?? '100.00'));
                $available = LedgerService::cents($this->report->balances('seller', $sellerId)['available']);
                $available = max(0, $available - app(SellerSettlements::class)->directAvailable($sellerId));
                $this->ensure($cents >= $minimum && $cents <= $available, 'The payout must be at least ₱'.number_format($minimum / 100, 2).' and no more than your available balance.');
                $record = MoneyReferences::insert('seller_payouts', 'SP', [
                    'seller_id' => $sellerId, 'active_seller_id' => $sellerId, 'request_key' => $key, 'amount' => LedgerService::decimal($cents),
                    'method_type' => $method->type, 'account_name' => $method->account_name, 'account_number_masked' => $method->account_number_masked,
                    'status' => 'pending', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->ledger->post('seller-payout:'.$record->id.':requested', 'seller_payout_request', null, 'Seller payout requested - '.$record->reference_no, [
                    $this->line('seller', $sellerId, 'available', 'debit', $cents),
                    $this->line('seller', $sellerId, 'payout_locked', 'credit', $cents),
                ]);

                return $record;
            });
        }, 3);
    }

    public function decideSeller(string $reference, string $decision, ?string $reason, int $actorId): object
    {
        return DB::transaction(function () use ($reference, $decision, $reason, $actorId): object {
            $initial = DB::table('seller_payouts')->where('reference_no', $reference)->firstOrFail();
            User::whereKey($initial->seller_id)->lockForUpdate()->firstOrFail();
            $record = DB::table('seller_payouts')->where('id', $initial->id)->lockForUpdate()->firstOrFail();
            $target = match ($decision) {
                'approve' => 'processing', 'pay' => 'paid', 'reject' => 'rejected', default => abort(404)
            };
            if ($record->status === $target) {
                return $record;
            }
            $this->ensure($decision === 'reject' ? in_array($record->status, ['pending', 'processing'], true) : $record->status === ($decision === 'approve' ? 'pending' : 'processing'), 'This payout has already changed. Refresh the queue.');
            $this->ensure($decision !== 'reject' || trim((string) $reason) !== '', 'A rejection reason is required.');

            return $this->ledger->synchronized(function () use ($record, $decision, $target, $reason, $actorId): object {
                $cents = LedgerService::cents((string) $record->amount);
                if ($decision === 'reject') {
                    $transactionId = DB::table('ledger_transactions')->where('idempotency_key', 'seller-payout:'.$record->id.':requested')->value('id');
                    $this->ledger->reverse($transactionId, 'seller-payout:'.$record->id.':rejected');
                } elseif ($decision === 'pay') {
                    $this->ledger->post('seller-payout:'.$record->id.':paid', 'seller_payout_paid', null, 'Seller payout paid - '.$record->reference_no, [
                        $this->line('seller', $record->seller_id, 'payout_locked', 'debit', $cents),
                        $this->line('seller', $record->seller_id, 'withdrawn', 'credit', $cents),
                        $this->line('platform', 0, 'cod_held', 'credit', $cents),
                        $this->line('system', 0, 'EXTERNAL_BANK', 'debit', $cents),
                    ]);
                }
                $values = ['status' => $target, 'processed_by' => $actorId, 'processed_at' => now(), 'updated_at' => now()];
                if ($decision === 'approve') {
                    $values += ['processing_at' => now(), 'approved_by' => $actorId];
                } else {
                    $values += ['active_seller_id' => null, $target === 'paid' ? 'paid_at' : 'rejected_at' => now(), 'rejection_reason' => $decision === 'reject' ? trim($reason) : null];
                }
                DB::table('seller_payouts')->where('id', $record->id)->update($values);

                $record = DB::table('seller_payouts')->where('id', $record->id)->firstOrFail();
                $this->notifier->moneyChanged('seller_payout', $record);

                return $record;
            });
        }, 3);
    }

    public function prepareRefund(OrderReturn $return): ?object
    {
        return DB::transaction(function () use ($return): ?object {
            Order::whereKey($return->order_id)->lockForUpdate()->firstOrFail();
            $return = OrderReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();
            if ($return->status !== OrderReturn::STATUS_REFUND_PENDING) {
                return null;
            }

            return DB::table('refund_payouts')->where('order_return_id', $return->id)->first()
                ?? MoneyReferences::insert('refund_payouts', 'RF', [
                    'order_return_id' => $return->id, 'order_id' => $return->order_id, 'buyer_id' => $return->buyer_id,
                    'amount' => $return->refund_amount, 'status' => 'pending', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
        }, 3);
    }

    public function payRefund(string $reference, int $actorId, ?int $hubId = null, ?string $transferReference = null, ?string $proofPath = null): object
    {
        return DB::transaction(function () use ($reference, $actorId, $hubId, $transferReference, $proofPath): object {
            $initial = DB::table('refund_payouts')->where('reference_no', $reference)->firstOrFail();
            Order::whereKey($initial->order_id)->lockForUpdate()->firstOrFail();
            $return = OrderReturn::whereKey($initial->order_return_id)->lockForUpdate()->firstOrFail();
            $record = DB::table('refund_payouts')->where('id', $initial->id)->lockForUpdate()->firstOrFail();

            return $this->ledger->synchronized(function () use ($record, $return, $reference, $actorId, $hubId, $transferReference, $proofPath): object {
                $direct = app(SellerSettlements::class)->isDirect($record->order_id);
                $source = $direct ? DB::table('seller_settlement_orders')->where('order_id', $record->order_id)->firstOrFail() : null;
                if ($direct) {
                    abort_unless($hubId !== null && (int) $source->hub_id === $hubId && User::findOrFail($actorId)->role === 'logistics'
                        && (int) LogisticsProfile::where('user_id', $actorId)->value('id') === $hubId, 403);
                } else {
                    abort_unless($hubId === null && User::findOrFail($actorId)->role === 'super_admin', 403);
                }
                if ($record->status === 'paid') {
                    return $record;
                }
                $this->ensure($return->status === OrderReturn::STATUS_REFUND_PENDING && $record->status === 'pending', 'Only approved refunds ready for payment can be paid.');
                $cents = $this->amount((string) $record->amount);
                if ($direct) {
                    $this->ensure($transferReference !== null && trim($transferReference) !== '' && $proofPath !== null, 'A refund transfer reference and proof are required.');
                    app(SellerSettlements::class)->assertTransferReferenceAvailable($transferReference);
                    $this->ensure(app(SellerSettlements::class)->orderAmount($record->order_id, 'hub', 'cash_held') >= $cents, 'Recover seller funds and any remitted commission before funding this refund.');
                }
                $this->ledger->post('refund-payout:'.$record->id.':paid', 'refund_payout_paid', $record->order_id, 'Refund paid - '.$record->reference_no, [
                    $this->line($direct ? 'hub' : 'platform', $direct ? $source->hub_id : 0, $direct ? 'cash_held' : 'cod_held', 'credit', $cents),
                    $this->line('system', 0, 'EXTERNAL_BANK', 'debit', $cents),
                ]);
                $values = ['status' => 'paid', 'paid_by' => $actorId, 'paid_at' => now(), 'updated_at' => now()];
                if ($direct) {
                    $values += ['transfer_reference' => $transferReference, 'proof_path' => $proofPath];
                }
                DB::table('refund_payouts')->where('id', $record->id)->update($values);
                if ($direct) {
                    DB::table('seller_settlement_events')->insert(['order_id' => $record->order_id, 'actor_id' => $actorId, 'action' => 'buyer_refund',
                        'transfer_reference' => $transferReference, 'proof_path' => $proofPath, 'amount' => LedgerService::decimal($cents), 'created_at' => now(), 'updated_at' => now()]);
                }
                $return->forceFill(['status' => OrderReturn::STATUS_REFUNDED, 'refund_reference' => $reference, 'refunded_by' => $actorId, 'refunded_at' => now()])->save();
                $return->events()->create(['actor_id' => $actorId, 'title' => 'Refund paid', 'note' => '₱'.number_format($cents / 100, 2).' · '.$reference]);
                DB::afterCommit(fn () => app(Notifier::class)->returnChanged($return, $actorId));

                return DB::table('refund_payouts')->where('id', $record->id)->firstOrFail();
            });
        }, 3);
    }

    private function amount(string $amount): int
    {
        try {
            $cents = LedgerService::cents($amount);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount with at most two decimal places.']);
        }
        $this->ensure($cents > 0 && $cents <= 999999999999, 'Enter an amount within the supported payout range.');

        return $cents;
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['payout' => $message]);
        }
    }

    /** @return array{owner_type: string, owner_id: int, account_type: string, direction: string, cents: int} */
    private function line(string $ownerType, int $ownerId, string $accountType, string $direction, int $cents): array
    {
        return ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'account_type' => $accountType, 'direction' => $direction, 'cents' => $cents];
    }
}
