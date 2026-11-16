<?php

namespace App\Services;

use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Support\MoneyReferences;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoneyMovements
{
    public function __construct(private LedgerService $ledger, private MoneyMovementQueries $queries, private Notifier $notifier) {}

    public function handover(int $hubId, int $riderId, string $received, ?string $notes, string $requestKey, int $actorId): object
    {
        return DB::transaction(function () use ($hubId, $riderId, $received, $notes, $requestKey, $actorId): object {
            LogisticsProfile::whereKey($hubId)->lockForUpdate()->firstOrFail();
            CourierProfile::whereKey($riderId)->lockForUpdate()->firstOrFail();
            $key = 'ho:'.$actorId.':'.$requestKey;
            if ($existing = DB::table('rider_cash_handovers')->where('request_key', $key)->first()) {
                $this->ensure((int) $existing->hub_id === $hubId && (int) $existing->rider_id === $riderId, 'This request belongs to another handover.');

                return $existing;
            }
            $this->ensure(! DB::table('rider_cash_handovers')->where('hub_id', $hubId)->where('rider_id', $riderId)->where('status', 'short')->exists(), 'Settle the existing shortage before confirming another handover.');
            $this->lockOrders($this->queries->cashOrders($hubId, $riderId));

            return $this->ledger->synchronized(function () use ($hubId, $riderId, $received, $notes, $key, $actorId): object {
                $orders = $this->queries->cashOrders($hubId, $riderId)->get();
                $expected = $this->sum($orders);
                $amount = $this->amount($received, true);
                $this->ensure($expected > 0 && $amount <= $expected, 'Received cash must be between zero and the outstanding cash amount.');
                $this->ensure($amount === $expected || trim((string) $notes) !== '', 'A note is required when received cash differs from expected cash.');
                $record = MoneyReferences::insert('rider_cash_handovers', 'HO', [
                    'hub_id' => $hubId, 'rider_id' => $riderId, 'request_key' => $key,
                    'amount_expected' => LedgerService::decimal($expected), 'amount_received' => LedgerService::decimal($amount),
                    'short_amount' => LedgerService::decimal($expected - $amount), 'settled_amount' => '0.00',
                    'status' => $amount === $expected ? 'confirmed' : 'short', 'confirmed_by' => $actorId,
                    'confirmed_at' => now(), 'notes' => $notes, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->allocateCash($record, $orders, $amount, 'confirmed');
                $this->notifier->moneyChanged('cash_handover', $record);
                DB::afterCommit(fn () => RiderRealtime::notify($riderId));

                return $record;
            });
        }, 3);
    }

    public function settleShortage(int $hubId, string $reference, string $note, int $actorId): object
    {
        return DB::transaction(function () use ($hubId, $reference, $note, $actorId): object {
            LogisticsProfile::whereKey($hubId)->lockForUpdate()->firstOrFail();
            $record = DB::table('rider_cash_handovers')->where('reference_no', $reference)->where('hub_id', $hubId)->lockForUpdate()->firstOrFail();
            if ($record->status === 'settled') {
                return $record;
            }
            $this->ensure($record->status === 'short' && trim($note) !== '', 'Only an open shortage can be settled, with a note.');
            CourierProfile::whereKey($record->rider_id)->lockForUpdate()->firstOrFail();
            $this->lockOrders($this->queries->cashOrders($hubId, $record->rider_id));

            return $this->ledger->synchronized(function () use ($record, $note, $actorId): object {
                $amount = LedgerService::cents((string) $record->short_amount) - LedgerService::cents((string) $record->settled_amount);
                $orders = $this->queries->cashOrders($record->hub_id, $record->rider_id)->get();
                $this->ensure($amount > 0 && $this->sum($orders) >= $amount, 'The shortage no longer matches outstanding rider cash.');
                $this->allocateCash($record, $orders, $amount, 'settled');
                DB::table('rider_cash_handovers')->where('id', $record->id)->update(['status' => 'settled', 'settled_amount' => $record->short_amount,
                    'settled_by' => $actorId, 'settled_at' => now(), 'settlement_note' => $note, 'updated_at' => now()]);

                DB::afterCommit(fn () => RiderRealtime::notify((int) $record->rider_id));

                return DB::table('rider_cash_handovers')->where('id', $record->id)->firstOrFail();
            });
        }, 3);
    }

    /** @param array<int, string>|null $orderCodes */
    public function submitRemittance(int $hubId, ?array $orderCodes, ?string $proofPath, string $requestKey, int $actorId): object
    {
        return DB::transaction(function () use ($hubId, $orderCodes, $proofPath, $requestKey, $actorId): object {
            LogisticsProfile::whereKey($hubId)->lockForUpdate()->firstOrFail();
            $key = 'rm:'.$actorId.':'.$requestKey;
            if ($existing = DB::table('remittances')->where('request_key', $key)->first()) {
                $this->ensure((int) $existing->hub_id === $hubId, 'This request belongs to another hub.');

                return $existing;
            }
            $query = $this->queries->remittable($hubId)->when($orderCodes !== null, fn (Builder $q) => $q->whereIn('o.order_number', $orderCodes));
            $this->lockOrders($query);
            $orders = $query->get();
            $this->ensure(($orderCodes === null || $orders->count() === count(array_unique($orderCodes))) && $orders->isNotEmpty(), 'Select eligible orders belonging to this hub. Some orders may already be submitted.');
            foreach ($orders as $order) {
                if (app(SellerSettlements::class)->isDirect($order->id)) {
                    $this->ensure(app(SellerSettlements::class)->orderAmount($order->id, 'hub', 'cash_held') >= LedgerService::cents((string) $order->amount), 'Collect the rider cash before remitting this order.');
                }
            }
            $record = MoneyReferences::insert('remittances', 'RM', ['hub_id' => $hubId, 'request_key' => $key,
                'total_amount' => LedgerService::decimal($this->sum($orders)), 'status' => 'submitted', 'proof_path' => $proofPath,
                'submitted_by' => $actorId, 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($orders as $order) {
                DB::table('remittance_items')->insert(['remittance_id' => $record->id, 'order_id' => $order->id, 'active_order_id' => $order->id, 'amount' => $order->amount]);
            }

            return $record;
        }, 3);
    }

    public function decideRemittance(string $reference, bool $verify, ?string $reason, int $actorId): object
    {
        return DB::transaction(function () use ($reference, $verify, $reason, $actorId): object {
            $initial = DB::table('remittances')->where('reference_no', $reference)->firstOrFail();
            LogisticsProfile::whereKey($initial->hub_id)->lockForUpdate()->firstOrFail();
            $record = DB::table('remittances')->where('id', $initial->id)->lockForUpdate()->firstOrFail();
            $target = $verify ? 'verified' : 'rejected';
            if ($record->status === $target) {
                return $record;
            }
            $this->ensure($record->status === 'submitted', 'This remittance has already been reviewed.');
            $this->ensure($verify || trim((string) $reason) !== '', 'A rejection reason is required.');
            $items = DB::table('remittance_items')->where('remittance_id', $record->id)->orderBy('order_id')->get();
            Order::whereIn('id', $items->pluck('order_id'))->orderBy('id')->lockForUpdate()->get();

            return $this->ledger->synchronized(function () use ($items, $record, $verify, $target, $reason, $actorId): object {
                if ($verify) {
                    foreach ($items as $item) {
                        $cents = LedgerService::cents((string) $item->amount);
                        if (app(SellerSettlements::class)->isDirect($item->order_id)) {
                            $this->ensure(app(SellerSettlements::class)->orderAmount($item->order_id, 'hub', 'cod_to_remit') >= $cents
                                && app(SellerSettlements::class)->orderAmount($item->order_id, 'hub', 'cash_held') >= $cents,
                                'Commission or available cash changed. Reject this remittance and submit the current amount.');
                        }
                        $this->ledger->post('remittance:'.$record->id.':'.$item->order_id, 'remittance', $item->order_id, 'Remittance verified - '.$record->reference_no, [
                            $this->line('hub', $record->hub_id, 'cod_to_remit', 'debit', $cents),
                            $this->line('platform', 0, 'cod_receivable', 'credit', $cents),
                            $this->line('platform', 0, 'cod_held', 'debit', $cents),
                            $this->line('hub', $record->hub_id, 'cash_held', 'credit', $cents),
                        ]);
                        if (app(SellerSettlements::class)->isDirect($item->order_id)) {
                            DB::table('remittance_items')->where('id', $item->id)->update(['active_order_id' => null]);
                        }
                    }
                } else {
                    DB::table('remittance_items')->where('remittance_id', $record->id)->update(['active_order_id' => null]);
                }
                DB::table('remittances')->where('id', $record->id)->update(['status' => $target, 'verified_by' => $actorId,
                    'verified_at' => $verify ? now() : null, 'rejected_at' => $verify ? null : now(),
                    'rejection_reason' => $verify ? null : $reason, 'updated_at' => now()]);

                $record = DB::table('remittances')->where('id', $record->id)->firstOrFail();
                $this->notifier->moneyChanged('remittance', $record);

                return $record;
            });
        }, 3);
    }

    /** @param Collection<int, object> $orders */
    private function allocateCash(object $record, Collection $orders, int $amount, string $phase): void
    {
        foreach ($orders as $order) {
            $part = min($amount, LedgerService::cents((string) $order->amount));
            if ($part === 0) {
                break;
            }
            $existing = DB::table('rider_cash_handover_items')->where('handover_id', $record->id)->where('order_id', $order->id)->first();
            if ($existing) {
                DB::table('rider_cash_handover_items')->where('id', $existing->id)->update(['amount' => LedgerService::decimal(LedgerService::cents((string) $existing->amount) + $part)]);
            } else {
                DB::table('rider_cash_handover_items')->insert(['handover_id' => $record->id, 'order_id' => $order->id, 'amount' => LedgerService::decimal($part)]);
            }
            $this->ledger->post('handover:'.$record->id.':'.$phase.':'.$order->id, 'cash_handover', $order->id, 'Cash handover '.$phase.' - '.$record->reference_no, [
                $this->line('rider', $record->rider_id, 'cash_on_hand', 'credit', $part),
                $this->line('hub', $record->hub_id, 'cash_held', 'debit', $part),
                $this->line('rider', $record->rider_id, 'cash_due_hub', 'debit', $part),
                $this->line('hub', $record->hub_id, 'cash_receivable', 'credit', $part),
            ]);
            $amount -= $part;
        }
        $this->ensure($amount === 0, 'Cash allocation could not be completed. Please refresh.');
    }

    private function lockOrders(Builder $query): void
    {
        $ids = (clone $query)->get()->pluck('id');
        Order::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
    }

    /** @param Collection<int, object> $rows */
    private function sum(Collection $rows): int
    {
        return $rows->sum(fn (object $row): int => LedgerService::cents((string) $row->amount));
    }

    private function amount(string $amount, bool $allowZero = false): int
    {
        try {
            $cents = LedgerService::cents($amount);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages(['amount' => 'Enter a valid amount with at most two decimal places.']);
        }
        $this->ensure($cents >= ($allowZero ? 0 : 1) && $cents <= 999999999999, 'Enter an amount within the supported range.');

        return $cents;
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['money' => $message]);
        }
    }

    /** @return array{owner_type: string, owner_id: int, account_type: string, direction: string, cents: int} */
    private function line(string $ownerType, int $ownerId, string $accountType, string $direction, int $cents): array
    {
        return ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'account_type' => $accountType, 'direction' => $direction, 'cents' => $cents];
    }
}
