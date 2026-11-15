<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Services\HubContext;
use App\Services\LogisticsReportService;
use App\Services\MoneyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const PRESETS = ['today', '7d', '30d', 'this_month', 'last_month', 'this_year', 'custom'];

    public function index(Request $request, LogisticsReportService $service): View
    {
        [$hub, $from, $to, $params] = $this->parameters($request, $service);
        $report = $service->build($hub->id, $from, $to, $params['type'], $params, paginate: true);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rows = $report['paginator'] ?? new LengthAwarePaginator(array_slice($report['rows'], ($page - 1) * 25, 25), count($report['rows']), 25, $page,
            ['path' => $request->url(), 'query' => $params]);

        return view('logistics.reports.index', ['report' => $report, 'rows' => $rows, 'from' => $from, 'to' => $to,
            'preset' => $params['preset'], 'type' => $params['type'], 'hub' => $hub, 'types' => $service->types(),
            'params' => $params, 'options' => $service->filterOptions($hub->id), 'statuses' => $this->statuses()]
            + app(MoneyReport::class)->page($request, 'hub', $hub->id));
    }

    public function exportCsv(Request $request, LogisticsReportService $service): StreamedResponse
    {
        [$hub, $from, $to, $params] = $this->parameters($request, $service);
        $report = $service->build($hub->id, $from, $to, $params['type'], $params);

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_column($report['columns'], 'label'), ',', '"', '');
            foreach ($report['rows'] as $row) {
                $line = [];
                foreach ($report['columns'] as $column) {
                    $value = (string) ($row[$column['key']] ?? '');
                    $line[] = ! is_numeric($value) && preg_match('/^[\s]*[=+@\-\t\r\n]/', $value) ? "'".$value : $value;
                }
                fputcsv($out, $line, ',', '"', '');
            }
            fclose($out);
        }, $this->filename($params['type'], $from, $to).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request, LogisticsReportService $service): Response
    {
        [$hub, $from, $to, $params] = $this->parameters($request, $service);
        $report = $service->build($hub->id, $from, $to, $params['type'], $params);
        $hubName = $hub->business_name;

        return Pdf::loadView('logistics.reports.pdf', compact('report', 'from', 'to', 'params', 'hubName'))
            ->setPaper('a4', count($report['columns']) > 5 ? 'landscape' : 'portrait')
            ->download($this->filename($params['type'], $from, $to).'.pdf');
    }

    private function statuses(): array
    {
        return array_values(array_unique(array_merge(Order::GROUP_TO_SHIP, Order::GROUP_TO_DELIVER, Order::GROUP_COMPLETED, Order::GROUP_CANCELLED, Order::GROUP_RETURN_REFUND)));
    }

    /** @return array{0: LogisticsProfile, 1: Carbon, 2: Carbon, 3: array<string, mixed>} */
    private function parameters(Request $request, LogisticsReportService $service): array
    {
        $hub = HubContext::require($request);
        $data = $request->validate([
            'type' => ['nullable', Rule::in(array_keys($service->types()))],
            'preset' => ['nullable', Rule::in(self::PRESETS)], 'page' => ['nullable', 'integer', 'min:1'],
            'from' => ['required_if:preset,custom', 'nullable', 'date_format:Y-m-d'],
            'to' => ['required_if:preset,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in($this->statuses())],
            'rider_id' => ['nullable', 'integer', Rule::in($service->ridersForHub($hub->id)->pluck('id')->all())],
            'seller_id' => ['nullable', 'integer', Rule::in($service->scopedOrders($hub->id)->distinct()->pluck('seller_id')->all())],
            'origin_area' => ['nullable', 'string', 'max:255'], 'destination_area' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['nullable', Rule::in(['COD'])],
            'proof' => ['nullable', Rule::in(['with', 'missing'])],
        ]);
        $preset = $data['preset'] ?? '30d';
        $today = now();
        $lastMonth = $today->copy()->subMonthNoOverflow();
        [$from, $to] = match ($preset) {
            'custom' => [Carbon::parse($data['from'])->startOfDay(), Carbon::parse($data['to'])->endOfDay()],
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            '7d' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'last_month' => [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay()],
            default => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
        };
        if ($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) > 365) {
            throw ValidationException::withMessages(['to' => 'Choose a date range of no more than 366 calendar days.']);
        }
        unset($data['page']);
        $params = array_filter(array_merge($data, ['type' => $data['type'] ?? 'shipment_summary', 'preset' => $preset]), fn ($value) => $value !== null && $value !== '');
        if ($preset !== 'custom') {
            unset($params['from'], $params['to']);
        }

        return [$hub, $from, $to, $params];
    }

    private function filename(string $type, Carbon $from, Carbon $to): string
    {
        return sprintf('shopleap-logistics-%s_%s_to_%s', $type, $from->format('Y-m-d'), $to->format('Y-m-d'));
    }
}
