<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MoneyReport
{
    /** @param array<int, int> $orderIds */
    public function buyerRefunds(int $buyerId, array $orderIds): Collection
    {
        return DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')
            ->where('a.owner_type', 'buyer')->where('a.owner_id', $buyerId)->where('a.account_type', 'refunds_issued')
            ->whereIn('t.order_id', $orderIds)->select('t.order_id', 't.reference_no', 't.posted_at', 'e.amount', 'e.direction')
            ->orderBy('t.posted_at')->orderBy('t.id')->get()->groupBy('order_id');
    }

    /** @return array<string, string|null> */
    public function balances(string $ownerType, int $ownerId): array
    {
        $rows = DB::table('wallet_accounts as a')->leftJoin('ledger_entries as e', 'e.account_id', '=', 'a.id')
            ->where('a.owner_type', $ownerType)->where('a.owner_id', $ownerId)
            ->groupBy('a.account_type')->select('a.account_type')
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN e.direction = 'debit' THEN e.amount ELSE -e.amount END), 0), 2) as balance")->get();
        $balances = array_fill_keys(LedgerService::ACCOUNTS[$ownerType], '0.00');
        foreach ($rows as $row) {
            $cents = LedgerService::cents((string) $row->balance);
            $balances[$row->account_type] = LedgerService::decimal(in_array($row->account_type, LedgerService::DEBIT_ACCOUNTS, true) ? $cents : -$cents);
        }
        if ($ownerType === 'hub') {
            $balances['net_margin'] = LedgerService::decimal(LedgerService::cents($balances['shipping_earned']) - LedgerService::cents($balances['rider_expense']));
            $hasUnknownPay = DB::table('orders as o')->where('o.earning_hub_id', $ownerId)->whereNull('o.rider_pay')
                ->whereExists(fn (Builder $query) => $query->selectRaw('1')->from('ledger_transactions as t')->whereColumn('t.order_id', 'o.id')->where('t.type', 'delivery'))->exists();
            if ($hasUnknownPay) {
                $balances['net_margin'] = null;
            }
        }

        return $balances;
    }

    /** @return array<string, mixed> */
    public function page(Request $request, string $ownerType, int $ownerId, bool $all = false): array
    {
        $filters = $this->filters($request);
        $query = $this->history($ownerType, $ownerId, $all, $filters);
        $cards = match ($ownerType) {
            'seller' => ['pending' => 'Pending', 'available' => 'Available', 'payout_locked' => 'Locked for payout', 'withdrawn' => 'Withdrawn'],
            'hub' => ['cash_held' => 'Cash held', 'cod_to_remit' => 'COD to remit', 'shipping_earned' => 'Shipping fees earned', 'rider_pay_owed' => 'Rider pay owed', 'rider_pay_paid' => 'Rider pay paid', 'net_margin' => 'Net margin'],
            default => ['commission_earned' => 'Commission earned', 'commission_pending' => 'Commission pending', 'cod_receivable' => 'COD receivable', 'cod_held' => 'COD held'],
        };

        return ['moneyBalances' => $this->balances($ownerType, $ownerId), 'moneyCards' => $cards,
            'moneyRows' => $query->paginate(25, ['*'], 'money_page')->withQueryString(),
            'moneyFilters' => $filters, 'moneyOwnerType' => $ownerType,
            'moneyRoute' => match ($ownerType) {
                'seller' => 'seller.earnings.index', 'hub' => 'logistics.money.index', default => 'admin.ledger.index'
            },
            'reconciliation' => $all ? $this->reconciliation() : null];
    }

    /** @return array{money_from: string|null, money_to: string|null, money_status: string} */
    public function filters(Request $request): array
    {
        $data = $request->validate([
            'money_from' => ['nullable', 'date_format:Y-m-d'],
            'money_to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('money_from'), 'after_or_equal:money_from')],
            'money_status' => ['nullable', Rule::in(['all', 'posted', 'reversed'])],
            'money_page' => ['nullable', 'integer', 'min:1'],
            'reconcile_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return ['money_from' => $data['money_from'] ?? null, 'money_to' => $data['money_to'] ?? null, 'money_status' => $data['money_status'] ?? 'all'];
    }

    /** @param array{money_from: string|null, money_to: string|null, money_status?: string} $filters */
    public function history(string $ownerType, int $ownerId, bool $all, array $filters): Builder
    {
        $debitTypes = "'".implode("','", LedgerService::DEBIT_ACCOUNTS)."'";
        $movement = "CASE WHEN (a.account_type IN ({$debitTypes}) AND e.direction = 'debit') OR (a.account_type NOT IN ({$debitTypes}) AND e.direction = 'credit') THEN e.amount ELSE -e.amount END";
        $base = DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')
            ->leftJoin('orders as o', 'o.id', '=', 't.order_id')
            ->when(! $all, fn (Builder $query) => $query->where('a.owner_type', $ownerType)->where('a.owner_id', $ownerId))
            ->select('e.id', 'e.amount', 'e.direction', 'a.account_type', 'a.owner_type', 'a.owner_id', 't.reference_no', 't.posted_at', 't.description', 'o.order_number', 'o.items_subtotal', 'o.voucher_discount', 'o.commission_amount', 'o.total_amount')
            ->selectRaw("CASE WHEN t.reversal_of IS NOT NULL OR EXISTS (SELECT 1 FROM ledger_transactions AS reversal WHERE reversal.reversal_of = t.id) THEN 'reversed' ELSE t.status END as status")
            ->selectRaw("{$movement} as movement, ROUND(SUM({$movement}) OVER (PARTITION BY e.account_id ORDER BY t.posted_at, t.id, e.id ROWS UNBOUNDED PRECEDING), 2) as running_balance");

        return DB::query()->fromSub($base, 'history')
            ->when(($filters['money_status'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('status', $filters['money_status']))
            ->when($filters['money_from'], fn (Builder $query, string $date) => $query->where('posted_at', '>=', Carbon::parse($date, config('money.timezone'))->startOfDay()->setTimezone(config('app.timezone'))))
            ->when($filters['money_to'], fn (Builder $query, string $date) => $query->where('posted_at', '<', Carbon::parse($date, config('money.timezone'))->startOfDay()->addDay()->setTimezone(config('app.timezone'))))
            ->orderByDesc('posted_at')->orderByDesc('id');
    }

    /** @return array<string, mixed> */
    public function reconciliation(): array
    {
        $totals = DB::table('ledger_entries')->selectRaw("ROUND(COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END),0), 2) as debits, ROUND(COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END),0), 2) as credits")->first();
        $unbalanced = DB::table('ledger_transactions as t')->leftJoin('ledger_entries as e', 'e.transaction_id', '=', 't.id')
            ->select('t.id')->groupBy('t.id')->havingRaw("COUNT(e.id) = 0 OR ROUND(SUM(CASE WHEN e.direction = 'debit' THEN e.amount ELSE -e.amount END), 2) <> 0");
        $delivery = DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')->where('t.type', 'delivery')->groupBy('t.order_id')->select('t.order_id')
            ->selectRaw("SUM(CASE WHEN a.owner_type = 'rider' AND a.account_type = 'cash_on_hand' THEN e.amount ELSE 0 END) as cash,
                SUM(CASE WHEN a.owner_type = 'seller' AND a.account_type = 'pending' THEN e.amount ELSE 0 END) as seller_net,
                SUM(CASE WHEN a.owner_type = 'platform' AND a.account_type = 'commission_pending' THEN e.amount ELSE 0 END) as commission,
                SUM(CASE WHEN a.owner_type = 'hub' AND a.account_type = 'shipping_earned' THEN e.amount ELSE 0 END) as shipping,
                SUM(CASE WHEN a.owner_type = 'hub' AND a.account_type = 'rider_expense' THEN e.amount ELSE 0 END) as rider_pay");
        $flags = DB::table('orders as o')->joinSub($delivery, 'd', 'd.order_id', '=', 'o.id')
            ->where(function (Builder $query): void {
                $query->whereRaw('ROUND(d.cash - d.seller_net - d.commission - d.shipping, 2) <> 0')
                    ->orWhereRaw('ROUND(d.cash - o.cod_collected_amount, 2) <> 0')->orWhereRaw('ROUND(d.seller_net + d.commission - o.total_amount, 2) <> 0')
                    ->orWhereRaw('ROUND(d.commission - o.commission_amount, 2) <> 0')->orWhereRaw('ROUND(d.shipping - o.delivered_shipping_fee, 2) <> 0')
                    ->orWhereRaw('ROUND(d.rider_pay - o.rider_pay, 2) <> 0')->orWhereNull('o.rider_pay');
            })->select('o.order_number', 'd.cash', 'd.seller_net', 'd.commission', 'd.shipping', 'o.rider_pay');

        return ['debits' => $totals->debits, 'credits' => $totals->credits, 'unremitted_hubs' => app(MoneyMovementQueries::class)->unremittedHubs(),
            'balanced' => $totals->debits === $totals->credits || LedgerService::cents((string) $totals->debits) === LedgerService::cents((string) $totals->credits),
            'unbalanced_transactions' => DB::query()->fromSub($unbalanced, 'unbalanced')->count(),
            'flags' => $flags->paginate(10, ['*'], 'reconcile_page')->withQueryString(),
            'unposted' => DB::table('orders as o')->whereNotNull('o.delivered_at')->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('ledger_transactions as t')->whereColumn('t.order_id', 'o.id')->where('t.type', 'delivery'))->count()];
    }
}
