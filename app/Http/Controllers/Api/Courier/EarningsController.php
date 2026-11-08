<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RiderEarnings;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EarningsController extends Controller
{
    public function __construct(private RiderEarnings $earnings) {}

    public function rate(Request $request): JsonResponse
    {
        $rate = $this->earnings->currentRate($request->attributes->get('courier')->logistics_profile_id);

        return response()->json(['amount' => $rate?->amount, 'currency' => 'PHP', 'effective_from' => $rate?->effective_from->toIso8601String()]);
    }

    public function index(Request $request): JsonResponse
    {
        [$from, $to, $period] = $this->range($request);
        $courier = $request->attributes->get('courier');
        $query = $this->earnings->deliveries(null, $courier->id, $from, $to);
        $totals = $this->earnings->totals($query);
        $daily = (clone $query)->toBase()->selectRaw('DATE(delivered_at) AS day, COUNT(*) AS deliveries, SUM(rider_pay) AS earned, COALESCE(SUM(cod_collected_amount), 0) AS cod')
            ->groupByRaw('DATE(delivered_at)')->orderByDesc('day')->get()->map(fn ($row) => [
                'date' => $row->day, 'deliveries' => (int) $row->deliveries,
                'earned' => RiderEarnings::money($row->earned ?? 0), 'cod_collected' => RiderEarnings::money($row->cod), 'currency' => 'PHP',
            ]);

        return response()->json(['period' => $period, 'currency' => 'PHP',
            'fee_per_delivery' => $this->earnings->currentRate($courier->logistics_profile_id)?->amount,
            'deliveries' => $totals['deliveries'], 'total_earned' => $totals['rider_pay'] ?? '0.00',
            'recorded_earnings' => $totals['paid_snapshots'], 'cod_collected' => $totals['cod_collected'], 'daily' => $daily]);
    }

    public function history(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);
        $orders = $this->earnings->deliveries(null, $request->attributes->get('courier')->id, $from, $to)
            ->orderByDesc('delivered_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return response()->json(['data' => $orders->getCollection()->map(fn (Order $order) => [
            'order_id' => $order->id, 'amount' => $order->rider_pay, 'currency' => 'PHP',
            'recorded_at' => $order->earnings_recorded_at?->toIso8601String(), 'delivered_at' => $order->delivered_at->toIso8601String(),
        ]), 'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()]]);
    }

    /** @return array{0: Carbon|null, 1: Carbon, 2: string} */
    private function range(Request $request): array
    {
        $data = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'week', 'month', 'all', '7d', '30d', 'custom'])],
            'preset' => ['nullable', Rule::in(['today', '7d', '30d', 'custom'])],
            'from' => ['required_if:period,custom', 'required_if:preset,custom', 'nullable', 'date_format:Y-m-d'],
            'to' => ['required_with:from', 'required_if:period,custom', 'required_if:preset,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $period = $data['preset'] ?? $data['period'] ?? (isset($data['from']) ? 'custom' : 'month');
        $to = now()->endOfDay();
        $from = match ($period) {
            'today' => now()->startOfDay(), 'week' => now()->startOfWeek(), '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(), 'all' => null,
            'custom' => Carbon::parse($data['from'])->startOfDay(), default => now()->startOfMonth(),
        };
        if ($period === 'custom') {
            $to = Carbon::parse($data['to'])->endOfDay();
            if ($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) > 365) {
                throw ValidationException::withMessages(['to' => 'Choose a date range of no more than 366 calendar days.']);
            }
        }

        return [$from, $to, $period];
    }
}
