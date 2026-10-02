<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SellerReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    private const PRESETS = ['7d', '30d', 'this_month', 'last_month', 'this_year', 'custom'];

    public function index(Request $request, SellerReportService $service)
    {
        [$from, $to, $preset] = $this->resolveRange($request);
        $tab = $this->resolveTab($request);

        $report = $service->build($request->user()->id, $from, $to);

        return view('seller.reports.index', compact('report', 'from', 'to', 'preset', 'tab'));
    }

    public function exportCsv(Request $request, SellerReportService $service)
    {
        [$from, $to] = $this->resolveRange($request);
        $tab = $this->resolveTab($request);
        $sellerId = $request->user()->id;
        $filename = $this->filename($tab, $from, $to) . '.csv';

        return response()->streamDownload(function () use ($service, $tab, $sellerId, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads ₱ / ñ correctly

            if ($tab === 'sales') {
                fputcsv($out, ['Product', 'Orders', 'Units Sold', 'Revenue (PHP)']);

                foreach ($service->productPerformance($sellerId, $from, $to) as $row) {
                    fputcsv($out, [
                        $this->csvSafe($row->name),
                        (int) $row->orders,
                        (int) $row->units,
                        number_format((float) $row->revenue, 2, '.', ''),
                    ]);
                }
            } else {
                fputcsv($out, ['Order #', 'Order Date', 'Buyer', 'Order Status', 'Units', 'Gross (PHP)', 'Commission (PHP)', 'Net (PHP)', 'Classification']);

                $totals = ['gross' => 0.0, 'commission' => 0.0];

                foreach ($service->ledger($sellerId, $from, $to) as $order) {
                    $gross = (float) $order->total_amount;
                    $commission = (float) $order->commission_amount;

                    if (in_array($order->status, Order::GROUP_REALIZED, true)) {
                        $classification = 'Realized';
                        $totals['gross'] += $gross;
                        $totals['commission'] += $commission;
                    } elseif (in_array($order->status, Order::GROUP_IN_PROGRESS, true)) {
                        $classification = 'Pending';
                    } else {
                        $classification = 'Cancelled/Returned';
                    }

                    fputcsv($out, [
                        $order->id,
                        $order->created_at->format('Y-m-d H:i'),
                        $this->csvSafe($order->buyer_name),
                        $order->status_label,
                        (int) $order->items_sum_quantity,
                        number_format($gross, 2, '.', ''),
                        number_format($commission, 2, '.', ''),
                        number_format($gross - $commission, 2, '.', ''),
                        $classification,
                    ]);
                }

                fputcsv($out, []);
                fputcsv($out, [
                    'TOTAL (Realized only)',
                    '',
                    '',
                    '',
                    '',
                    number_format($totals['gross'], 2, '.', ''),
                    number_format($totals['commission'], 2, '.', ''),
                    number_format($totals['gross'] - $totals['commission'], 2, '.', ''),
                    '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request, SellerReportService $service)
    {
        [$from, $to] = $this->resolveRange($request);
        $tab = $this->resolveTab($request);
        $user = $request->user();

        $report = $service->build($user->id, $from, $to);
        $shopName = $user->sellerProfile->business_name ?? $user->name;

        return Pdf::loadView('seller.reports.pdf', compact('report', 'from', 'to', 'tab', 'shopName'))
            ->setPaper('a4')
            ->download($this->filename($tab, $from, $to) . '.pdf');
    }

    private function resolveTab(Request $request): string
    {
        return $request->get('tab') === 'sales' ? 'sales' : 'financial';
    }

    private function resolveRange(Request $request): array
    {
        $preset = in_array($request->get('preset'), self::PRESETS, true) ? $request->get('preset') : '30d';
        $today = now();

        if ($preset === 'custom') {
            $validated = $request->validate([
                'from' => ['required', 'date'],
                'to' => ['required', 'date', 'after_or_equal:from'],
            ]);

            $from = Carbon::parse($validated['from'])->startOfDay();
            $to = Carbon::parse($validated['to'])->endOfDay();

            if ((int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) > 365) {
                throw ValidationException::withMessages(['to' => 'Please choose a date range of one year or less.']);
            }

            return [$from, $to, 'custom'];
        }

        $lastMonth = $today->copy()->subMonthNoOverflow();

        [$from, $to] = match ($preset) {
            '7d' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'last_month' => [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay()],
            default => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
        };

        return [$from, $to, $preset];
    }

    private function filename(string $tab, Carbon $from, Carbon $to): string
    {
        return sprintf('shopleap-%s-report_%s_to_%s', $tab === 'sales' ? 'sales' : 'financial', $from->format('Y-m-d'), $to->format('Y-m-d'));
    }

    /** Neutralise spreadsheet formula injection from user-supplied text (e.g. a buyer named "=SUM(...)"). */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
