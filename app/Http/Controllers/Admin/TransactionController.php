<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AdminTransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    private const PRESETS = ['7d', '30d', 'this_month', 'last_month', 'this_year', 'custom'];

    public function index(Request $request, AdminTransactionService $service)
    {
        [$from, $to, $preset] = $this->resolveRange($request);
        $report = $service->build($from, $to);

        $query = $service->ledger($from, $to);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('buyer_name', 'ilike', "%{$search}%")
                    ->orWhereHas('seller.sellerProfile', fn($s) => $s->where('business_name', 'ilike', "%{$search}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.transactions.index', compact('report', 'orders', 'from', 'to', 'preset'));
    }

    public function exportCsv(Request $request, AdminTransactionService $service)
    {
        [$from, $to] = $this->resolveRange($request);
        $filename = 'shopleap-transactions_' . $from->format('Y-m-d') . '_to_' . $to->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($service, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Order #', 'Date', 'Buyer', 'Seller', 'Status', 'GMV (PHP)', 'Commission (PHP)', 'Seller Payout (PHP)']);

            $totals = ['gmv' => 0.0, 'commission' => 0.0];

            foreach ($service->ledger($from, $to)->cursor() as $order) {
                $gross = (float) $order->total_amount;
                $commission = (float) $order->commission_amount;

                if (in_array($order->status, Order::GROUP_REALIZED, true)) {
                    $totals['gmv'] += $gross;
                    $totals['commission'] += $commission;
                }

                fputcsv($out, [
                    $order->id,
                    $order->created_at->format('Y-m-d H:i'),
                    $this->csvSafe($order->buyer_name),
                    $this->csvSafe($order->seller->sellerProfile->business_name ?? $order->seller->name ?? ''),
                    $order->status_label,
                    number_format($gross, 2, '.', ''),
                    number_format($commission, 2, '.', ''),
                    number_format($gross - $commission, 2, '.', ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, [
                'TOTAL (Realized/Completed orders only)',
                '',
                '',
                '',
                '',
                number_format($totals['gmv'], 2, '.', ''),
                number_format($totals['commission'], 2, '.', ''),
                number_format($totals['gmv'] - $totals['commission'], 2, '.', ''),
            ]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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

    private function csvSafe(?string $value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
