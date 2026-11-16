<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\FinancialReportRequest;
use App\Models\LogisticsProfile;
use App\Services\CompanyFinance;
use App\Services\MoneyHistory;
use App\Services\MoneyReport;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(FinancialReportRequest $request, CompanyFinance $finance, MoneyReport $money, MoneyHistory $csv): View|StreamedResponse
    {
        $company = $request->user()->company;
        $hub = $request->selectedHub();
        $scope = $company->hubs()->when($hub, fn ($hubs) => $hubs->whereKey($hub->id))->select('id')->getQuery()->toBase();
        $hubQuery = $finance->hubs($company, $hub?->id);
        $history = $money->history('hub', 0, true, ['money_from' => null, 'money_to' => null], $scope)
            ->join((new LogisticsProfile)->getTable().' as h', 'h.id', '=', 'history.owner_id')->select('history.*', 'h.business_name as hub_name')->reorder()->orderByDesc('history.posted_at')->orderByDesc('history.id');
        if ($request->validated('export') === 'csv') {
            if ($request->validated('table') === 'transactions') {
                return $csv->csv($history, ['Hub', 'Reference', 'Date', 'Order', 'Account', 'Movement', 'Status'],
                    fn (object $row): array => [$row->hub_name, $row->reference_no, $row->posted_at, $row->order_number ?? '', $row->account_type, $row->movement, $row->status], 'company-finance-transactions.csv');
            }

            return $csv->csv($hubQuery->orderBy('hub_name'), array_merge(['Hub'], array_values(CompanyFinance::COLUMNS)),
                fn (object $row): array => array_merge([$row->hub_name], array_map(fn (string $column) => $row->$column ?? 'Not recorded', array_keys(CompanyFinance::COLUMNS))), 'company-finance-hubs.csv');
        }
        $rows = (clone $hubQuery)->orderBy('hub_name')->paginate(20, ['*'], 'hubs_page')->withQueryString();

        return view('company.finance', ['totals' => $finance->totals($hubQuery), 'hubRows' => $rows, 'transactions' => $history->paginate(25, ['*'], 'transactions_page')->withQueryString(),
            'columns' => CompanyFinance::COLUMNS, 'dueDays' => $finance->dueDays(), 'hubs' => $company->hubs()->select(['public_code', 'business_name'])->orderBy('business_name')->get(),
            'chartData' => $rows->getCollection()->map(fn (object $row): array => ['name' => $row->hub_name, 'shipping' => $row->shipping_earned, 'owed' => $row->rider_pay_owed, 'paid' => $row->rider_pay_paid, 'margin' => $row->net_margin])->values()->all()]);
    }
}
