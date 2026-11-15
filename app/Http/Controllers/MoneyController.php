<?php

namespace App\Http\Controllers;

use App\Services\HubContext;
use App\Services\LedgerService;
use App\Services\MoneyReport;
use App\Support\LedgerPresentation;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MoneyController extends Controller
{
    public function index(Request $request, MoneyReport $report): View|StreamedResponse
    {
        [$ownerType, $ownerId, $all] = match (true) {
            $request->routeIs('seller.*') => ['seller', $request->user()->id, false],
            $request->routeIs('logistics.*') => ['hub', HubContext::require($request)->id, false],
            $request->routeIs('admin.*') => ['platform', 0, true],
            default => abort(403),
        };
        if ($request->query('export') === 'csv') {
            $query = $report->history($ownerType, $ownerId, $all, $report->filters($request));

            return response()->streamDownload(function () use ($query, $ownerType): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                $columns = ['Reference', 'Date/time', 'Description', 'Order', 'Owner', 'Account', 'In (PHP)', 'Out (PHP)', 'Running account balance (PHP)', 'Status'];
                if ($ownerType === 'seller') {
                    $columns = array_merge($columns, ['Items subtotal (PHP)', 'Voucher (PHP)', 'Commission (PHP)', 'Seller net (PHP)']);
                }
                fputcsv($out, $columns, ',', '"', '');
                foreach ($query->cursor() as $row) {
                    $movement = LedgerService::cents((string) $row->movement);
                    $line = [LedgerPresentation::reference($row->reference_no), LedgerPresentation::dateTime($row->posted_at), LedgerPresentation::description($row->description), $row->order_number,
                        $row->owner_type.':'.$row->owner_id, $row->account_type, LedgerService::decimal(max(0, $movement)),
                        LedgerService::decimal(max(0, -$movement)), $row->running_balance, LedgerPresentation::status($row->status)];
                    if ($ownerType === 'seller') {
                        $line = array_merge($line, [$row->items_subtotal, $row->voucher_discount, $row->commission_amount,
                            $row->total_amount === null ? null : LedgerService::decimal(LedgerService::cents((string) $row->total_amount) - LedgerService::cents((string) $row->commission_amount))]);
                    }
                    $line = array_map(fn ($value): string => ! is_numeric($value) && preg_match('/^[\s]*[=+@\-\t\r\n]/', (string) $value) ? "'".$value : (string) $value, $line);
                    fputcsv($out, $line, ',', '"', '');
                }
                fclose($out);
            }, 'shopleap-'.$ownerType.'-ledger.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('money.index', $report->page($request, $ownerType, $ownerId, $all) + [
            'title' => match ($ownerType) {
                'seller' => 'Earnings', 'hub' => 'Hub ledger', default => 'Platform ledger'
            },
            'navigation' => match ($ownerType) {
                'seller' => 'navbars.seller', 'hub' => 'navbars.logistics', default => 'navbars.admin'
            },
        ]);
    }
}
