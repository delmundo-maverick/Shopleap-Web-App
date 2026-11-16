<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LogisticsProfile;
use App\Models\PlatformSetting;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CompanyFinance
{
    public const COLUMNS = ['shipping_earned' => 'Shipping fees earned', 'rider_pay_owed' => 'Rider pay owed', 'rider_pay_paid' => 'Rider pay paid',
        'net_margin' => 'Net margin', 'cod_to_remit' => 'COD to remit', 'overdue' => 'Overdue remittances', 'rider_cash' => 'Rider cash on hand'];

    public function __construct(private MoneyMovementQueries $movements, private SellerSettlements $settlements) {}

    public function dueDays(): int
    {
        return max(1, (int) PlatformSetting::get('remittance_due_days', 3));
    }

    public function hubs(Company $company, ?int $hubId = null): Builder
    {
        $scope = $company->hubs()->when($hubId !== null, fn ($hubs) => $hubs->whereKey($hubId))->select('id');
        $balances = DB::table('wallet_accounts as a')->join('ledger_entries as e', 'e.account_id', '=', 'a.id')
            ->where('a.owner_type', 'hub')->whereIn('a.owner_id', clone $scope)->select('a.owner_id')->groupBy('a.owner_id');
        foreach (['shipping_earned', 'rider_pay_owed', 'rider_pay_paid', 'rider_expense', 'cod_to_remit'] as $account) {
            $normal = in_array($account, LedgerService::DEBIT_ACCOUNTS, true) ? 'debit' : 'credit';
            $balances->selectRaw('SUM(CASE WHEN a.account_type = ? THEN CASE WHEN e.direction = ? THEN e.amount ELSE -e.amount END ELSE 0 END) AS '.$account, [$account, $normal]);
        }
        $overdue = $this->movements->deliveries()->whereIn('o.earning_hub_id', clone $scope)
            ->joinSub($this->settlements->orderBalances('hub', 'cod_to_remit'), 'due', 'due.order_id', '=', 'o.id')
            ->where('due.amount', '>', 0)->where('o.delivered_at', '<', now()->subDays($this->dueDays()))
            ->select('o.earning_hub_id')->selectRaw('SUM(due.amount) AS amount')->groupBy('o.earning_hub_id');
        $cash = DB::table('wallet_accounts as a')->join('ledger_entries as e', 'e.account_id', '=', 'a.id')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('orders as o', 'o.id', '=', 't.order_id')->where('a.owner_type', 'rider')->where('a.account_type', 'cash_on_hand')->whereIn('o.earning_hub_id', clone $scope)
            ->select('o.earning_hub_id')->selectRaw("SUM(CASE WHEN e.direction = 'debit' THEN e.amount ELSE -e.amount END) AS amount")->groupBy('o.earning_hub_id');
        $unknown = $this->movements->deliveries()->whereIn('o.earning_hub_id', clone $scope)->whereNull('o.rider_pay')->select('o.earning_hub_id')->selectRaw('COUNT(*) AS total')->groupBy('o.earning_hub_id');

        return DB::table((new LogisticsProfile)->getTable().' as h')->whereIn('h.id', $scope)
            ->leftJoinSub($balances, 'b', 'b.owner_id', '=', 'h.id')->leftJoinSub($overdue, 'due', 'due.earning_hub_id', '=', 'h.id')
            ->leftJoinSub($cash, 'cash', 'cash.earning_hub_id', '=', 'h.id')->leftJoinSub($unknown, 'unknown_pay', 'unknown_pay.earning_hub_id', '=', 'h.id')
            ->select('h.public_code as hub_code', 'h.business_name as hub_name')
            ->selectRaw('COALESCE(b.shipping_earned, 0) AS shipping_earned, COALESCE(b.rider_pay_owed, 0) AS rider_pay_owed, COALESCE(b.rider_pay_paid, 0) AS rider_pay_paid,
                COALESCE(b.cod_to_remit, 0) AS cod_to_remit, COALESCE(due.amount, 0) AS overdue, COALESCE(cash.amount, 0) AS rider_cash,
                COALESCE(unknown_pay.total, 0) AS unknown_pay, CASE WHEN COALESCE(unknown_pay.total, 0) > 0 THEN NULL ELSE COALESCE(b.shipping_earned, 0) - COALESCE(b.rider_expense, 0) END AS net_margin');
    }

    public function totals(Builder $hubs): object
    {
        return DB::query()->fromSub($hubs, 'finance')->selectRaw('SUM(shipping_earned) AS shipping_earned, SUM(rider_pay_owed) AS rider_pay_owed,
            SUM(rider_pay_paid) AS rider_pay_paid, SUM(cod_to_remit) AS cod_to_remit, SUM(overdue) AS overdue, SUM(rider_cash) AS rider_cash,
            CASE WHEN SUM(unknown_pay) > 0 THEN NULL ELSE COALESCE(SUM(net_margin), 0) END AS net_margin')->first();
    }
}
