<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\HubContext;
use App\Services\LedgerService;
use App\Services\MoneyHistory;
use App\Services\MoneyMovementQueries;
use App\Services\MoneyMovements;
use App\Services\MoneyReport;
use App\Services\SellerSettlements;
use App\Support\LedgerPresentation;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MoneyMovementController extends Controller
{
    public function index(Request $request, MoneyMovementQueries $queries, MoneyReport $report, MoneyHistory $historyTools, string $section): View|StreamedResponse
    {
        $role = $request->routeIs('logistics.*') ? 'logistics' : 'admin';
        abort_unless(in_array($section, $role === 'logistics' ? ['cash', 'remittances'] : ['remittances', 'settings'], true), 404);
        $hubId = $role === 'logistics' ? HubContext::require($request)->id : null;
        $request->validate(['status' => ['nullable', Rule::in(['all', 'confirmed', 'short', 'settled', 'submitted', 'verified', 'rejected'])],
            'page' => ['nullable', 'integer', 'min:1'], 'people_page' => ['nullable', 'integer', 'min:1'], 'orders_page' => ['nullable', 'integer', 'min:1']]);
        $statusOptions = $section === 'cash' ? ['all' => 'All statuses', 'confirmed' => 'Confirmed', 'short' => 'Short', 'settled' => 'Settled']
            : ['all' => 'All statuses', 'submitted' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'];
        $historyFilters = $historyTools->filters($request, array_keys($statusOptions), $role === 'admin' ? 'submitted' : 'all');
        $status = $historyFilters['status'];
        $data = ['role' => $role, 'section' => $section, 'title' => match ($section) {
            'cash' => 'Rider cash', 'remittances' => 'Remittances', default => 'Remittance settings'
        },
            'selectedStatus' => $status, 'dueDays' => (int) PlatformSetting::get('remittance_due_days', 3),
            'minimumPayout' => PlatformSetting::get('minimum_payout', '100.00'),
            'historyFilters' => $historyFilters, 'statusOptions' => $statusOptions,
            'rows' => null, 'orders' => collect(), 'riders' => null, 'riderOrders' => collect(), 'recordOrders' => collect(), 'overdueHubs' => collect(), 'hubCash' => [],
            'balances' => $report->balances($role === 'logistics' ? 'hub' : 'platform', $hubId ?? 0)];
        if ($section === 'cash') {
            $eligible = $queries->cashOrders($hubId);
            $data['riders'] = DB::query()->fromSub($eligible, 'eligible')->join('courier_profiles as c', 'c.id', '=', 'eligible.earning_courier_id')
                ->select('c.id', 'c.first_name', 'c.last_name')->selectRaw('ROUND(SUM(eligible.amount), 2) as amount')
                ->groupBy('c.id', 'c.first_name', 'c.last_name')->orderBy('c.last_name')->orderBy('c.id')->paginate(20, ['*'], 'people_page')->withQueryString();
            $data['riderOrders'] = $eligible->whereIn('o.earning_courier_id', $data['riders']->getCollection()->pluck('id'))->get()->groupBy('earning_courier_id');
            $history = $queries->handovers($hubId)->join('courier_profiles as c', 'c.id', '=', 'p.rider_id')
                ->select('p.*', 'c.first_name', 'c.last_name')->orderByDesc('p.id');
            $historyTools->apply($history, $historyFilters, 'p.confirmed_at');
            if ($request->query('export') === 'csv') {
                return $this->export($historyTools, $history, $section);
            }
            $data['rows'] = $history->paginate(25)->withQueryString();
        } elseif ($section === 'remittances') {
            if ($hubId !== null) {
                $data['orders'] = $queries->remittable($hubId)->paginate(25, ['*'], 'orders_page')->withQueryString();
            }
            $history = DB::table('remittances as p')->join('logistics_profiles as h', 'h.id', '=', 'p.hub_id')->select('p.*', 'h.business_name')
                ->when($hubId !== null, fn (Builder $query) => $query->where('p.hub_id', $hubId))->orderByDesc('p.id');
            $historyTools->apply($history, $historyFilters, 'p.submitted_at');
            if ($request->query('export') === 'csv') {
                return $this->export($historyTools, $history, $section);
            }
            $data['rows'] = $history->paginate(25)->withQueryString();
            $data['recordOrders'] = DB::table('remittance_items as i')->join('orders as o', 'o.id', '=', 'i.order_id')
                ->whereIn('i.remittance_id', $data['rows']->getCollection()->pluck('id'))->select('i.remittance_id', 'o.order_number', 'i.amount')->get()->groupBy('remittance_id');
            $data['hubCash'] = DB::table('wallet_accounts as a')->join('ledger_entries as e', 'e.account_id', '=', 'a.id')
                ->where('a.owner_type', 'hub')->where('a.account_type', 'cash_held')->whereIn('a.owner_id', $data['rows']->getCollection()->pluck('hub_id'))
                ->groupBy('a.owner_id')->select('a.owner_id')->selectRaw("ROUND(SUM(CASE WHEN e.direction = 'debit' THEN e.amount ELSE -e.amount END), 2) as amount")->pluck('amount', 'owner_id');
            $data['overdueHubs'] = $queries->deliveries($hubId)->join('logistics_profiles as h', 'h.id', '=', 'o.earning_hub_id')
                ->joinSub(app(SellerSettlements::class)->orderBalances('hub', 'cod_to_remit'), 'due', 'due.order_id', '=', 'o.id')->where('due.amount', '>', 0)
                ->where('o.delivered_at', '<', now()->subDays($data['dueDays']))
                ->select('h.id', 'h.business_name')->selectRaw('COUNT(*) as orders, ROUND(SUM(due.amount), 2) as amount')->groupBy('h.id', 'h.business_name')->get();
        }

        return view('money.movements', $data);
    }

    public function handover(Request $request, MoneyMovements $service): RedirectResponse
    {
        $data = $request->validate(['rider_id' => ['required', 'integer'], 'amount' => ['required', 'string', 'regex:/^\d{1,10}(\.\d{1,2})?$/'], 'notes' => ['nullable', 'string', 'max:1000'], 'request_key' => ['required', 'uuid']]);
        $record = $service->handover(HubContext::require($request)->id, $data['rider_id'], $data['amount'], $data['notes'] ?? null, $data['request_key'], $request->user()->id);

        return back()->with('status', 'Cash handover recorded · '.$record->reference_no);
    }

    public function settle(Request $request, string $reference, MoneyMovements $service): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'min:3', 'max:1000']]);
        $record = $service->settleShortage(HubContext::require($request)->id, $reference, $data['notes'], $request->user()->id);

        return back()->with('status', 'Shortage settled · '.$record->reference_no);
    }

    public function submitRemittance(Request $request, MoneyMovements $service): RedirectResponse
    {
        $data = $request->validate(['all' => ['nullable', 'boolean'], 'orders' => ['required_unless:all,1', 'array', 'min:1', 'max:500'], 'orders.*' => ['required', 'string', 'distinct', 'max:40'],
            'proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'request_key' => ['required', 'uuid']]);
        $path = null;
        try {
            if ($request->hasFile('proof')) {
                $path = 'remittances/'.Str::uuid().'.'.$request->file('proof')->extension();
                if (! Storage::disk('r2')->putFileAs('remittances', $request->file('proof'), basename($path), ['visibility' => 'private'])) {
                    throw new \RuntimeException('The proof image could not be stored.');
                }
            }
            $record = $service->submitRemittance(HubContext::require($request)->id, $request->boolean('all') ? null : $data['orders'], $path, $data['request_key'], $request->user()->id);
        } catch (\Throwable $exception) {
            if ($path) {
                $this->deleteUnusedProof($path);
            }
            throw $exception;
        }
        if ($path && $path !== $record->proof_path) {
            $this->deleteUnusedProof($path);
        }

        return back()->with('status', 'Remittance submitted · '.$record->reference_no);
    }

    public function decideRemittance(Request $request, string $reference, string $decision, MoneyMovements $service): RedirectResponse
    {
        $data = $request->validate(['reason' => [$decision === 'reject' ? 'required' : 'nullable', 'string', 'min:3', 'max:1000']]);
        $record = $service->decideRemittance($reference, $decision === 'verify', $data['reason'] ?? null, $request->user()->id);
        $negative = LedgerService::cents(app(MoneyReport::class)->balances('hub', $record->hub_id)['cash_held']) < 0;

        return back()->with('status', 'Remittance '.$record->status.' · '.$record->reference_no)->with('money_warning', $negative ? 'The hub cash held balance is negative.' : null);
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate(['remittance_due_days' => ['required', 'integer', 'min:1', 'max:365'], 'minimum_payout' => ['required', 'decimal:0,2', 'min:0.01', 'max:9999999999.99']]);
        DB::transaction(function () use ($request, $data): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            PlatformSetting::whereIn('key', ['remittance_due_days', 'minimum_payout'])->orderBy('key')->lockForUpdate()->get();
            PlatformSetting::set('remittance_due_days', $data['remittance_due_days']);
            PlatformSetting::set('minimum_payout', LedgerService::decimal(LedgerService::cents((string) $data['minimum_payout'])));
        }, 3);

        return back()->with('status', 'Remittance settings saved.');
    }

    public function proof(Request $request, string $reference): StreamedResponse
    {
        $record = DB::table('remittances')->where('reference_no', $reference)->firstOrFail();
        abort_unless($request->routeIs('admin.*') || (int) $record->hub_id === HubContext::require($request)->id, 403);
        abort_unless($record->proof_path, 404);
        try {
            $stream = Storage::disk('r2')->readStream($record->proof_path);
        } catch (\Throwable $exception) {
            report($exception);
            abort(404, 'Proof image unavailable.');
        }
        abort_unless(is_resource($stream), 404);
        $mime = match (strtolower(pathinfo($record->proof_path, PATHINFO_EXTENSION))) {
            'png' => 'image/png', 'webp' => 'image/webp', default => 'image/jpeg'
        };

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function deleteUnusedProof(string $path): void
    {
        try {
            Storage::disk('r2')->delete($path);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function export(MoneyHistory $historyTools, Builder $history, string $section): StreamedResponse
    {
        return $historyTools->csv($history, ['Reference', 'Party', 'Amount (PHP)', 'Status', 'Recorded at', 'Verified at', 'Rejected at', 'Settled at', 'Rejection reason'],
            fn (object $row): array => [LedgerPresentation::reference($row->reference_no), $row->business_name ?? trim($row->first_name.' '.$row->last_name),
                LedgerService::decimal($section === 'cash' ? LedgerService::cents((string) $row->amount_received) + LedgerService::cents((string) $row->settled_amount) : LedgerService::cents((string) $row->total_amount)),
                LedgerPresentation::status($row->status), LedgerPresentation::dateTime($row->confirmed_at ?? $row->submitted_at),
                ...array_map(fn (string $field): string => empty($row->$field) ? '' : LedgerPresentation::dateTime($row->$field), ['verified_at', 'rejected_at', 'settled_at']), $row->rejection_reason ?? ''],
            'shopleap-'.$section.'-history.csv');
    }
}
