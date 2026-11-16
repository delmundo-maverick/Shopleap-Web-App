<?php

namespace App\Services;

use App\Support\MoneyReferences;
use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LedgerService
{
    /** @var array<string, array<int, string>> */
    public const ACCOUNTS = [
        'seller' => ['pending', 'available', 'payout_locked', 'withdrawn', 'hub_receivable'],
        'hub' => ['cash_receivable', 'cash_held', 'cod_to_remit', 'shipping_earned', 'rider_pay_owed', 'rider_pay_paid', 'rider_expense', 'collection_variance', 'seller_payable', 'cash_in_transit'],
        'rider' => ['cash_on_hand', 'cash_due_hub', 'earnings_accrued', 'earnings_paid', 'earnings_income'],
        'platform' => ['cod_receivable', 'cod_held', 'commission_pending', 'commission_earned'],
        'buyer' => ['refunds_issued'],
        'system' => ['EXTERNAL_CASH', 'EXTERNAL_BANK'],
    ];

    /** @var array<int, string> */
    public const DEBIT_ACCOUNTS = ['cash_on_hand', 'cash_receivable', 'cash_held', 'cod_receivable', 'cod_held', 'rider_expense', 'earnings_accrued', 'earnings_paid', 'EXTERNAL_BANK', 'collection_variance', 'hub_receivable', 'cash_in_transit'];

    public function synchronized(Closure $callback): mixed
    {
        return DB::transaction(function () use ($callback): mixed {
            $this->lockForPosting();

            return $callback();
        });
    }

    public static function cents(string|int $amount): int
    {
        if (! preg_match('/^(-?)(\d{1,16})(?:\.(\d{1,2}))?$/D', (string) $amount, $match)) {
            throw new InvalidArgumentException('Money must have at most two fractional digits.');
        }

        return ($match[1] === '-' ? -1 : 1) * ((int) $match[2] * 100 + (int) str_pad($match[3] ?? '', 2, '0'));
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    /** @param array<int, array{owner_type: string, owner_id: int, account_type: string, direction: string, cents: int}> $lines */
    public function post(string $key, string $type, ?int $orderId, string $description, array $lines, ?int $reversalOf = null): int
    {
        $debits = $credits = 0;
        foreach ($lines as $line) {
            if (! in_array($line['account_type'], self::ACCOUNTS[$line['owner_type']] ?? [], true)
                || ! in_array($line['direction'], ['debit', 'credit'], true)
                || ! is_int($line['cents']) || $line['cents'] < 0 || $line['cents'] > 999999999999
                || $line['owner_id'] < 0
                || (in_array($line['owner_type'], ['system', 'platform'], true) ? $line['owner_id'] !== 0 : $line['owner_id'] === 0)) {
                throw new InvalidArgumentException('Invalid ledger entry.');
            }
            if ($line['direction'] === 'debit') {
                $debits += $line['cents'];
            } else {
                $credits += $line['cents'];
            }
        }
        if ($debits !== $credits || $debits === 0) {
            throw new InvalidArgumentException('A ledger transaction must have equal, nonzero debits and credits.');
        }
        $hash = hash('sha256', json_encode([$type, $orderId, $lines, $reversalOf], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($key, $type, $orderId, $description, $lines, $reversalOf, $hash): int {
            $this->lockForPosting();
            $existing = DB::table('ledger_transactions')->where('idempotency_key', $key)->first();
            if ($existing) {
                if ($existing->payload_hash !== $hash) {
                    throw new InvalidArgumentException('An idempotency key cannot be reused for different entries.');
                }

                return $existing->id;
            }
            $transaction = MoneyReferences::insert('ledger_transactions', 'TX', [
                'type' => $type, 'order_id' => $orderId,
                'description' => $description, 'status' => 'posted', 'idempotency_key' => $key,
                'payload_hash' => $hash, 'posted_at' => now(), 'reversal_of' => $reversalOf,
            ]);
            $id = $transaction->id;
            foreach ($lines as $line) {
                if ($line['cents'] === 0) {
                    continue;
                }
                $account = ['owner_type' => $line['owner_type'], 'owner_id' => $line['owner_id'], 'account_type' => $line['account_type']];
                DB::table('wallet_accounts')->insertOrIgnore($account + ['currency' => 'PHP']);
                $accountId = DB::table('wallet_accounts')->where($account)->value('id');
                DB::table('ledger_entries')->insert(['transaction_id' => $id, 'account_id' => $accountId,
                    'direction' => $line['direction'], 'amount' => self::decimal($line['cents'])]);
            }

            return $id;
        });
    }

    public function reverse(int $transactionId, string $key): int
    {
        return DB::transaction(function () use ($transactionId, $key): int {
            $this->lockForPosting();
            $original = DB::table('ledger_transactions')->where('id', $transactionId)->firstOrFail();
            if ($original->reversal_of !== null) {
                throw new InvalidArgumentException('Reverse the original transaction only.');
            }
            $existing = DB::table('ledger_transactions')->where('reversal_of', $transactionId)->value('id');
            if ($existing !== null) {
                return $existing;
            }
            $lines = DB::table('ledger_entries as e')->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')
                ->where('e.transaction_id', $transactionId)->orderBy('e.id')->get()->map(fn (object $row): array => [
                    'owner_type' => $row->owner_type, 'owner_id' => $row->owner_id, 'account_type' => $row->account_type,
                    'direction' => $row->direction === 'debit' ? 'credit' : 'debit', 'cents' => self::cents((string) $row->amount),
                ])->all();

            return $this->post($key, 'reversal', $original->order_id, 'Reversal of '.$original->reference_no, $lines, $transactionId);
        });
    }

    private function lockForPosting(): void
    {
        foreach (['EXTERNAL_CASH', 'EXTERNAL_BANK'] as $account) {
            DB::table('wallet_accounts')->insertOrIgnore(['owner_type' => 'system', 'owner_id' => 0, 'account_type' => $account, 'currency' => 'PHP']);
        }
        DB::table('wallet_accounts')->where('owner_type', 'system')->where('account_type', 'EXTERNAL_CASH')->lockForUpdate()->firstOrFail();
    }
}
