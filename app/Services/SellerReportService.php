<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SellerReportService
{
    public function build(int $sellerId, Carbon $from, Carbon $to): array
    {
        // Amounts are net of approved/paid refunds (Order::scopeWithRefundTotals).
        $orders = Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [$from, $to])
            ->select(['id', 'buyer_id', 'status', 'total_amount', 'commission_amount', 'voucher_discount', 'created_at'])
            ->withRefundTotals()
            ->get();

        $realized = $orders->whereIn('status', Order::GROUP_REALIZED);
        $inProgress = $orders->whereIn('status', Order::GROUP_IN_PROGRESS);
        $lost = $orders->whereIn('status', Order::GROUP_LOST);
        $valid = $orders->whereNotIn('status', Order::GROUP_LOST);

        $gross = fn(Collection $c) => (float) $c->sum(fn($o) => $o->net_items_amount);
        $commission = fn(Collection $c) => (float) $c->sum(fn($o) => $o->net_commission_amount);

        $money = fn(Collection $c) => [
            'gross' => round($gross($c), 2),
            'commission' => round($commission($c), 2),
            'net' => round($gross($c) - $commission($c), 2),
            'refunded' => round((float) $c->sum(fn($o) => $o->refunded_items ?? 0), 2),
            'voucher_discount' => round((float) $c->sum(fn($o) => $o->voucher_discount ?? 0), 2),
            'count' => $c->count(),
        ];

        $financial = [
            'realized' => $money($realized),
            'pending' => $money($inProgress),
            'lost' => $money($lost),
        ];

        $validCount = $valid->count();
        $totalCount = $orders->count();
        $validGross = $gross($valid);
        $buyerOrderCounts = $valid->whereNotNull('buyer_id')->groupBy('buyer_id')->map->count();

        $unitsSold = (int) $this->itemsQuery($sellerId, $from, $to)
            ->selectRaw('COALESCE(SUM(order_items.quantity - COALESCE(ri.quantity, 0)), 0) as units')
            ->value('units');

        $sales = [
            'total_orders' => $totalCount,
            'valid_orders' => $validCount,
            'completed_orders' => $realized->count(),
            'in_progress_orders' => $inProgress->count(),
            'lost_orders' => $lost->count(),
            'gross_sales' => round($validGross, 2),
            'units_sold' => $unitsSold,
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
            $buckets[$key]['sales_gross'] += $order->net_items_amount;

            if (in_array($order->status, Order::GROUP_REALIZED, true)) {
                $buckets[$key]['earned_orders']++;
                $buckets[$key]['earned_gross'] += $order->net_items_amount;
                $buckets[$key]['earned_commission'] += $order->net_commission_amount;
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

    /** Every product sold in the range, net of refunded items (used by the Sales CSV export). */
    public function productPerformance(int $sellerId, Carbon $from, Carbon $to): Collection
    {
        return $this->itemsQuery($sellerId, $from, $to)
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->selectRaw('order_items.product_name as name, COUNT(DISTINCT order_items.order_id) as orders, SUM(order_items.quantity - COALESCE(ri.quantity, 0)) as units, SUM(order_items.subtotal - COALESCE(ri.amount, 0)) as revenue')
            ->orderByDesc('units')
            ->orderByDesc('revenue')
            ->get();
    }

    /** One row per order (used by the Financial CSV export). Includes refund totals: see the net_* accessors on Order. */
    public function ledger(int $sellerId, Carbon $from, Carbon $to): Collection
    {
        return Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [$from, $to])
            ->withSum('items', 'quantity')
            ->withRefundTotals()
            ->orderBy('created_at')
            ->get();
    }

    private function itemsQuery(int $sellerId, Carbon $from, Carbon $to)
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoinSub($this->refundedLines(), 'ri', 'ri.order_item_id', '=', 'order_items.id')
            ->where('orders.seller_id', $sellerId)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.status', Order::GROUP_LOST);
    }

    /** Lines (and quantities) of approved or paid refunds, per order item. */
    private function refundedLines()
    {
        return DB::table('order_return_items')
            ->join('order_returns', 'order_returns.id', '=', 'order_return_items.order_return_id')
            ->whereIn('order_returns.status', OrderReturn::REFUND_COUNTED)
            ->groupBy('order_return_items.order_item_id')
            ->selectRaw('order_return_items.order_item_id as order_item_id, SUM(order_return_items.quantity) as quantity, SUM(order_return_items.amount) as amount');
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
