<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RiderEarnings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EarningsController extends Controller
{
    /** Profit page: earnings, delivery count, cash collected, and a per-day breakdown. */
    public function index(Request $request): JsonResponse
    {
        $courier = $request->attributes->get('courier');

        $data = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'week', 'month', 'all'])],
        ]);

        $period = $data['period'] ?? 'month';

        $base = Order::where('delivery_rider_id', $courier->id)
            ->whereIn('status', RiderEarnings::EARNED_STATUSES)
            ->whereNotNull('delivered_at');

        match ($period) {
            'today' => $base->where('delivered_at', '>=', now()->startOfDay()),
            'week' => $base->where('delivered_at', '>=', now()->startOfWeek()),
            'month' => $base->where('delivered_at', '>=', now()->startOfMonth()),
            default => null,
        };

        $fee = RiderEarnings::feePerDelivery();

        $totals = (clone $base)
            ->selectRaw('COUNT(*) as deliveries, COALESCE(SUM(cod_collected_amount), 0) as cod')
            ->first();

        $daily = (clone $base)
            ->selectRaw('DATE(delivered_at) as day, COUNT(*) as deliveries, COALESCE(SUM(cod_collected_amount), 0) as cod')
            ->groupByRaw('DATE(delivered_at)')
            ->orderByRaw('DATE(delivered_at) DESC')
            ->limit(31)
            ->get()
            ->map(fn($r) => [
                'date' => $r->day,
                'deliveries' => (int) $r->deliveries,
                'earned' => round($r->deliveries * $fee, 2),
                'cod_collected' => round((float) $r->cod, 2),
            ]);

        return response()->json([
            'period' => $period,
            'fee_per_delivery' => $fee,
            'deliveries' => (int) $totals->deliveries,
            'total_earned' => round($totals->deliveries * $fee, 2),
            'cod_collected' => round((float) $totals->cod, 2),
            'daily' => $daily,
        ]);
    }
}
