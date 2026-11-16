<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\FinancialReportRequest;
use App\Services\CompanyFinancialReports;
use App\Services\MoneyHistory;
use App\Services\RiderEarnings;
use Carbon\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(FinancialReportRequest $request, CompanyFinancialReports $reports, RiderEarnings $earnings, MoneyHistory $csv): View|StreamedResponse
    {
        $company = $request->user()->company;
        $hub = $request->selectedHub();
        $toDate = $request->validated('to') ?? now('Asia/Manila')->toDateString();
        $fromDate = $request->validated('from') ?? Carbon::parse($toDate, 'Asia/Manila')->subDays(29)->toDateString();
        abort_if($fromDate > $toDate, 422, 'The start date must not be after the end date.');
        $from = Carbon::parse($fromDate, 'Asia/Manila')->startOfDay()->setTimezone(config('app.timezone'));
        $to = Carbon::parse($toDate, 'Asia/Manila')->endOfDay()->setTimezone(config('app.timezone'));
        $deliveries = $reports->deliveries($company, $hub?->id, $from, $to);
        $hubRows = $reports->grouped($deliveries)->orderBy('hub_name');
        $riderRows = $reports->grouped($deliveries, true)->orderBy('hub_name')->orderBy('rider_name');
        if ($request->validated('export') === 'csv') {
            $byRider = $request->validated('table') === 'riders';

            return $csv->csv($byRider ? $riderRows : $hubRows,
                ['Hub', 'Rider', 'Parcels delivered', 'Shipping fees collected', 'Rider payouts owed', 'Net margin', 'Shipping snapshots', 'Pay snapshots'],
                fn (object $row): array => [$row->hub_name, $row->rider_name ?? '', $row->delivered, $row->shipping_fees ?? 'Not recorded', $row->rider_pay ?? 'Not recorded', $row->margin ?? 'Not recorded', $row->snapshots, $row->pay_snapshots],
                $byRider ? 'company-report-riders.csv' : 'company-report-hubs.csv');
        }

        return view('company.reports', ['totals' => $earnings->totals($deliveries), 'hubRows' => $hubRows->paginate(20, ['*'], 'hubs_page')->withQueryString(),
            'riderRows' => $riderRows->paginate(20, ['*'], 'riders_page')->withQueryString(), 'hubs' => $company->hubs()->select(['public_code', 'business_name'])->orderBy('business_name')->get(),
            'fromDate' => $fromDate, 'toDate' => $toDate]);
    }
}
