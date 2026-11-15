<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Services\HubContext;
use App\Services\LedgerService;
use App\Services\MoneyHistory;
use App\Services\MoneyReport;
use App\Services\PayoutQueries;
use App\Services\Payouts;
use App\Support\LedgerPresentation;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutController extends Controller
{
    public function index(Request $request, PayoutQueries $queries, MoneyReport $report, MoneyHistory $historyTools): View|StreamedResponse
    {
        $role = $request->routeIs('seller.*') ? 'seller' : ($request->routeIs('logistics.*') ? 'logistics' : 'admin');
        $refunds = $request->routeIs('admin.refunds.*');
        $request->validate(['status' => ['nullable', Rule::in(['all', 'open', 'pending', 'processing', 'paid', 'rejected'])], 'page' => ['nullable', 'integer', 'min:1'], 'riders_page' => ['nullable', 'integer', 'min:1']]);
        $statusOptions = $role === 'logistics' ? ['all' => 'All statuses', 'paid' => 'Paid'] : ($refunds
            ? ['all' => 'All statuses', 'pending' => 'Pending', 'paid' => 'Paid']
            : ['all' => 'All statuses', 'open' => 'Pending and Processing', 'pending' => 'Pending', 'processing' => 'Processing', 'paid' => 'Paid', 'rejected' => 'Rejected']);
        $historyFilters = $historyTools->filters($request, array_keys($statusOptions), $role === 'admin' ? ($refunds ? 'pending' : 'open') : 'all');
        $status = $historyFilters['status'];
        $data = ['role' => $role, 'refunds' => $refunds, 'selectedStatus' => $status,
            'title' => $refunds ? 'Refunds' : ($role === 'logistics' ? 'Rider payouts' : ($role === 'admin' ? 'Seller payouts' : 'Payouts')),
            'riders' => null, 'riderOrders' => collect(), 'recordOrders' => collect(), 'methods' => collect(), 'hasActive' => false,
            'minimumPayout' => (string) PlatformSetting::get('minimum_payout', '100.00'), 'historyFilters' => $historyFilters, 'statusOptions' => $statusOptions];
        if ($role === 'logistics') {
            $hubId = HubContext::require($request)->id;
            $eligible = $queries->riderOrders($hubId);
            $data['balances'] = $report->balances('hub', $hubId);
            $data['riders'] = DB::query()->fromSub($eligible, 'eligible')->join('courier_profiles as c', 'c.id', '=', 'eligible.earning_courier_id')
                ->select('c.id', 'c.first_name', 'c.last_name')->selectRaw('ROUND(SUM(eligible.amount), 2) as amount')
                ->groupBy('c.id', 'c.first_name', 'c.last_name')->orderBy('c.last_name')->orderBy('c.id')->paginate(20, ['*'], 'riders_page')->withQueryString();
            $data['riderOrders'] = $eligible->whereIn('o.earning_courier_id', $data['riders']->getCollection()->pluck('id'))->get()->groupBy('earning_courier_id');
            $history = DB::table('rider_payouts as p')->join('courier_profiles as c', 'c.id', '=', 'p.rider_id')->where('p.hub_id', $hubId)
                ->select('p.*', 'c.first_name', 'c.last_name');
        } elseif ($refunds) {
            $data['balances'] = $report->balances('platform', 0);
            $history = $queries->refunds();
        } else {
            $sellerId = $role === 'seller' ? $request->user()->id : null;
            $data['balances'] = $report->balances($sellerId ? 'seller' : 'platform', $sellerId ?? 0);
            if ($sellerId) {
                $data['methods'] = DB::table('seller_payout_methods')->where('seller_id', $sellerId)->orderByDesc('is_default')->orderByDesc('id')->get();
                $data['hasActive'] = DB::table('seller_payouts')->where('active_seller_id', $sellerId)->exists();
            }
            $history = DB::table('seller_payouts as p')->join('users as u', 'u.id', '=', 'p.seller_id')->select('p.*', 'u.name as party_name')
                ->when($sellerId !== null, fn (Builder $query) => $query->where('p.seller_id', $sellerId));
        }
        $history = $historyTools->apply($history, $historyFilters, $role === 'logistics' ? 'p.paid_at' : 'p.requested_at');
        if ($request->query('export') === 'csv') {
            return $historyTools->csv($history->orderByDesc('p.id'),
                ['Reference', 'Party', 'Amount (PHP)', 'Status', 'Pending at', 'Processing at', 'Paid at', 'Rejected at', 'Method', 'Account name', 'Account ending', 'Rejection reason'],
                fn (object $row): array => [LedgerPresentation::reference($row->reference_no), $row->party_name ?? trim($row->first_name.' '.$row->last_name), LedgerService::decimal(LedgerService::cents((string) $row->amount)),
                    LedgerPresentation::status($row->status), ...array_map(fn (string $field): string => empty($row->$field) ? '' : LedgerPresentation::dateTime($row->$field), ['requested_at', 'processing_at', 'paid_at', 'rejected_at']),
                    $row->method_type ?? '', $row->account_name ?? '', isset($row->account_number_masked) ? '•••• '.substr($row->account_number_masked, -4) : '', $row->rejection_reason ?? ''],
                'shopleap-'.($refunds ? 'refunds' : $role.'-payouts').'.csv');
        }
        $data['rows'] = $history->orderByDesc('p.id')->paginate(25)->withQueryString();
        if ($role === 'logistics') {
            $data['recordOrders'] = DB::table('rider_payout_items as i')->join('orders as o', 'o.id', '=', 'i.order_id')
                ->whereIn('i.rider_payout_id', $data['rows']->getCollection()->pluck('id'))->select('i.rider_payout_id', 'o.order_number', 'i.amount')->get()->groupBy('rider_payout_id');
        }

        return view('money.payouts', $data);
    }

    public function payRider(Request $request, Payouts $payouts): RedirectResponse
    {
        $data = $request->validate(['rider_id' => ['required', 'integer'], 'all' => ['nullable', 'boolean'], 'orders' => ['required_unless:all,1', 'array', 'min:1', 'max:500'],
            'orders.*' => ['required', 'string', 'distinct', 'max:40'], 'notes' => ['nullable', 'string', 'max:1000'], 'request_key' => ['required', 'uuid']]);
        $record = $payouts->payRider(HubContext::require($request)->id, $data['rider_id'], $request->boolean('all') ? null : $data['orders'], $data['notes'] ?? null, $data['request_key'], $request->user()->id);

        return back()->with('status', 'Rider payout paid · '.$record->reference_no);
    }

    public function addMethod(Request $request, Payouts $payouts): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', Rule::in(['GCash', 'Maya', 'bank'])], 'account_name' => ['required', 'string', 'max:150'],
            'account_number_masked' => ['required', 'string', 'regex:/^\d{4}$/D'], 'is_default' => ['nullable', 'boolean']]);
        $data['is_default'] = $request->boolean('is_default');
        $payouts->addMethod($request->user()->id, $data);

        return back()->with('status', 'Payout method added.');
    }

    public function removeMethod(Request $request, int $method, Payouts $payouts): RedirectResponse
    {
        $payouts->removeMethod($request->user()->id, $method);

        return back()->with('status', 'Payout method removed.');
    }

    public function requestSeller(Request $request, Payouts $payouts): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'], 'method_id' => ['required', 'integer'], 'request_key' => ['required', 'uuid']]);
        $record = $payouts->requestSeller($request->user()->id, (string) $data['amount'], $data['method_id'], $data['request_key']);

        return back()->with('status', 'Payout requested · '.$record->reference_no);
    }

    public function decideSeller(Request $request, string $reference, string $decision, Payouts $payouts): RedirectResponse
    {
        $data = $request->validate(['reason' => [$decision === 'reject' ? 'required' : 'nullable', 'string', 'min:3', 'max:1000']]);
        $record = $payouts->decideSeller($reference, $decision, $data['reason'] ?? null, $request->user()->id);

        return back()->with('status', 'Payout '.ucfirst($record->status).' · '.$record->reference_no);
    }

    public function payRefund(Request $request, string $reference, Payouts $payouts): RedirectResponse
    {
        $record = $payouts->payRefund($reference, $request->user()->id);

        return back()->with('status', 'Refund paid · '.$record->reference_no);
    }
}
