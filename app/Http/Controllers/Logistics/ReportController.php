<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Services\HubContext;
use App\Services\LogisticsReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    private const PRESETS = ['today', '7d', '30d', 'this_month', 'last_month', 'this_year', 'custom'];

    private const TYPES = [
        'shipment_summary' => 'Shipment Summary',
        'delivery_performance' => 'Delivery Performance',
        'failed_returns' => 'Failed Delivery / Returns',
        'rider_performance' => 'Rider/Driver Performance',
        'cod_remittance' => 'COD & Remittance',
        'shipping_earnings' => 'Shipping Fees & Earnings',
        'area_route' => 'Area/Route Report',
        'hub_throughput' => 'Hub/Warehouse Throughput',
        'damaged_lost' => 'Damaged/Lost Parcels',
    ];

    public function index(Request $request, LogisticsReportService $service)
    {
        [$from, $to, $preset] = $this->resolveRange($request);
        $type = $this->resolveType($request);
        $hub = HubContext::for($request);
        $filters = $this->resolveFilters($request);

        $report = $service->build($hub?->id, $from, $to, $type, $filters);
        $types = $service->types();

        return view('logistics.reports.index', compact('report', 'from', 'to', 'preset', 'type', 'hub', 'types'));
    }

    public function exportCsv(Request $request, LogisticsReportService $service)
    {
        [$from, $to] = $this->resolveRange($request);
        $type = $this->resolveType($request);
        $hub = HubContext::for($request);
        $filters = $this->resolveFilters($request);

        $report = $service->build($hub?->id, $from, $to, $type, $filters);
        $filename = $this->filename($type, $from, $to).'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_column($report['columns'], 'label'));

            foreach ($report['rows'] as $row) {
                $line = [];
                foreach ($report['columns'] as $col) {
                    $line[] = $row[$col['key']] ?? '';
                }
                fputcsv($out, $line);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request, LogisticsReportService $service)
    {
        [$from, $to] = $this->resolveRange($request);
        $type = $this->resolveType($request);
        $hub = HubContext::for($request);
        $filters = $this->resolveFilters($request);

        $report = $service->build($hub?->id, $from, $to, $type, $filters);
        $hubName = $hub?->business_name ?? 'Logistics Hub';

        return Pdf::loadView('logistics.reports.pdf', compact('report', 'from', 'to', 'type', 'hubName'))
            ->setPaper('a4')
            ->download($this->filename($type, $from, $to).'.pdf');
    }

    private function resolveType(Request $request): string
    {
        $type = $request->get('type');
        $types = array_keys(self::TYPES);

        return in_array($type, $types, true) ? $type : $types[0];
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'status' => $request->get('status'),
            'rider_id' => $request->get('rider_id'),
            'origin_area' => $request->get('origin_area'),
            'destination_area' => $request->get('destination_area'),
            'payment_type' => $request->get('payment_type'),
            'seller_id' => $request->get('seller_id'),
        ];
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
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            '7d' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'last_month' => [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay()],
            default => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
        };

        return [$from, $to, $preset];
    }

    private function filename(string $type, Carbon $from, Carbon $to): string
    {
        return sprintf('shopleap-logistics-%s_%s_to_%s', $type, $from->format('Y-m-d'), $to->format('Y-m-d'));
    }
}
