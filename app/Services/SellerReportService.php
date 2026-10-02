<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SellerReportService
{
    public function build(int $sellerId, Carbon $from, Carbon $to): array
    {
        $orders = Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'buyer_id', 'status', 'total_amount', 'commission_amount', 'created_at']);

        $realized = $orders->whereIn('status', Order::GROUP_REALIZED);
        $inProgress = $orders->whereIn('status', Order::GROUP_IN_PROGRESS);
        $lost = $orders->whereIn('status', Order::GROUP_LOST);
        $valid = $orders->whereNotIn('status', Order::GROUP_LOST);

        $money = fn(Collection $c) => [
            'gross' => round((float) $c->sum('total_amount'), 2),
            'commission' => round((float) $c->sum('commission_amount'), 2),
            'net' => round((float) $c->sum('total_amount') - (float) $c->sum('commission_amount'), 2),
            'count' => $c->count(),
        ];

        $financial = [
            'realized' => $money($realized),
            'pending' => $money($inProgress),
            'lost' => $money($lost),
        ];

        $validCount = $valid->count();
        $totalCount = $orders->count();
        $validGross = (float) $valid->sum('total_amount');
        $buyerOrderCounts = $valid->whereNotNull('buyer_id')->groupBy('buyer_id')->map->count();

        $sales = [
            'total_orders' => $totalCount,
            'valid_orders' => $validCount,
            'completed_orders' => $realized->count(),
            'in_progress_orders' => $inProgress->count(),
            'lost_orders' => $lost->count(),
            'gross_sales' => round($validGross, 2),
            'units_sold' => (int) $this->itemsQuery($sellerId, $from, $to)->sum('order_items.quantity'),
            'average_order_value' => $validCount ? round($validGross / $validCount, 2) : 0.0,
            'cancellation_rate' => $totalCount ? round($lost->count() / $totalCount * 100, 1) : 0.0,
            'unique_buyers' => $buyerOrderCounts->count(),
            'repeat_buyers' => $buyerOrderCounts->filter(fn($n) => $n > 1)->count(),
        ];

        // Time series — daily up to 31 days, weekly up to 120, monthly beyond.
        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $granularity = $days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month');
        $buckets = $this->emptyBuckets($from, $to, $granularity);

        foreach ($valid as $order) {
            $key = $this->bucketKey($order->created_at, $granularity);
            if (!isset($buckets[$key])) {
                continue;
            }

            $buckets[$key]['sales_orders']++;
            $buckets[$key]['sales_gross'] += (float) $order->total_amount;

            if (in_array($order->status, Order::GROUP_REALIZED, true)) {
                $buckets[$key]['earned_orders']++;
                $buckets[$key]['earned_gross'] += (float) $order->total_amount;
                $buckets[$key]['earned_commission'] += (float) $order->commission_amount;
            }
        }

        foreach ($buckets as &$bucket) {
            $bucket['sales_gross'] = round($bucket['sales_gross'], 2);
            $bucket['earned_gross'] = round($bucket['earned_gross'], 2);
            $bucket['earned_commission'] = round($bucket['earned_commission'], 2);
            $bucket['earned_net'] = round($bucket['earned_gross'] - $bucket['earned_commission'], 2);
        }
        unset($bucket);

        $statusCounts = $orders->countBy('status');
        $statusBreakdown = collect(Order::STATUS_LABELS)
            ->map(fn($label, $status) => ['label' => $label, 'count' => $statusCounts->get($status, 0)])
            ->filter(fn($row) => $row['count'] > 0)
            ->values()
            ->all();

        $topProducts = $this->productPerformance($sellerId, $from, $to)
            ->take(10)
            ->map(fn($p) => [
                'name' => $p->name,
                'orders' => (int) $p->orders,
                'units' => (int) $p->units,
                'revenue' => round((float) $p->revenue, 2),
            ])
            ->values()
            ->all();

        return [
            'range' => ['days' => $days, 'granularity' => $granularity],
            'financial' => $financial,
            'sales' => $sales,
            'series' => array_values($buckets),
            'top_products' => $topProducts,
            'status_breakdown' => $statusBreakdown,
        ];
    }

    /** Every product sold in the range (used by the Sales CSV export). */
    public function productPerformance(int $sellerId, Carbon $from, Carbon $to): Collection
    {
        return $this->itemsQuery($sellerId, $from, $to)
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->selectRaw('order_items.product_name as name, COUNT(DISTINCT order_items.order_id) as orders, SUM(order_items.quantity) as units, SUM(order_items.subtotal) as revenue')
            ->orderByDesc('units')
            ->orderByDesc('revenue')
            ->get();
    }

    /** One row per order (used by the Financial CSV export). */
    public function ledger(int $sellerId, Carbon $from, Carbon $to): Collection
    {
        return Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [$from, $to])
            ->withSum('items', 'quantity')
            ->orderBy('created_at')
            ->get();
    }

    private function itemsQuery(int $sellerId, Carbon $from, Carbon $to)
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.seller_id', $sellerId)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.status', Order::GROUP_LOST);
    }

    private function bucketKey(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
            default => $date->format('Y-m'),
        };
    }

    private function emptyBuckets(Carbon $from, Carbon $to, string $granularity): array
    {
        $cursor = match ($granularity) {
            'day' => $from->copy()->startOfDay(),
            'week' => $from->copy()->startOfWeek(),
            default => $from->copy()->startOfMonth(),
        };

        $buckets = [];

        while ($cursor->lte($to)) {
            $buckets[$this->bucketKey($cursor, $granularity)] = [
                'label' => match ($granularity) {
                    'day' => $cursor->format('M d'),
                    'week' => 'Wk of ' . $cursor->format('M d'),
                    default => $cursor->format('M Y'),
                },
                'sales_orders' => 0,
                'sales_gross' => 0.0,
                'earned_orders' => 0,
                'earned_gross' => 0.0,
                'earned_commission' => 0.0,
                'earned_net' => 0.0,
            ];

            $cursor = match ($granularity) {
                'day' => $cursor->copy()->addDay(),
                'week' => $cursor->copy()->addWeek(),
                default => $cursor->copy()->addMonth(),
            };
        }

        return $buckets;
    }
}
