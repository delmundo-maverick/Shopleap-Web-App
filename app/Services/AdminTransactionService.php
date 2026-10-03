<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminTransactionService
{
    public function build(Carbon $from, Carbon $to): array
    {
        $orders = Order::whereBetween('created_at', [$from, $to])
            ->get(['id', 'seller_id', 'buyer_id', 'status', 'total_amount', 'commission_amount', 'created_at']);

        $realized = $orders->whereIn('status', Order::GROUP_REALIZED);
        $inProgress = $orders->whereIn('status', Order::GROUP_IN_PROGRESS);
        $lost = $orders->whereIn('status', Order::GROUP_LOST);

        $money = fn(Collection $c) => [
            'gmv' => round((float) $c->sum('total_amount'), 2),
            'commission' => round((float) $c->sum('commission_amount'), 2),
            'payout' => round((float) $c->sum('total_amount') - (float) $c->sum('commission_amount'), 2),
            'count' => $c->count(),
        ];

        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $granularity = $days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month');
        $buckets = $this->emptyBuckets($from, $to, $granularity);

        foreach ($realized as $order) {
            $key = $this->bucketKey($order->created_at, $granularity);
            if (!isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['orders']++;
            $buckets[$key]['gmv'] += (float) $order->total_amount;
            $buckets[$key]['commission'] += (float) $order->commission_amount;
        }
        foreach ($buckets as &$b) {
            $b['gmv'] = round($b['gmv'], 2);
            $b['commission'] = round($b['commission'], 2);
        }
        unset($b);

        $statusCounts = $orders->countBy('status');
        $statusBreakdown = collect(Order::STATUS_LABELS)
            ->map(fn($label, $status) => ['label' => $label, 'count' => $statusCounts->get($status, 0)])
            ->filter(fn($row) => $row['count'] > 0)
            ->values()->all();

        $topSellers = $this->topSellers($from, $to)->take(10)->map(fn($s) => [
            'name' => $s->shop_name,
            'orders' => (int) $s->orders,
            'gmv' => round((float) $s->gmv, 2),
            'commission' => round((float) $s->commission, 2),
        ])->values()->all();

        return [
            'range' => ['days' => $days, 'granularity' => $granularity],
            'realized' => $money($realized),
            'pending' => $money($inProgress),
            'lost' => $money($lost),
            'series' => array_values($buckets),
            'status_breakdown' => $statusBreakdown,
            'top_sellers' => $topSellers,
        ];
    }

    /** One row per seller shop, by realized commission (used by the Top Sellers table). */
    public function topSellers(Carbon $from, Carbon $to): Collection
    {
        return DB::table('orders')
            ->join('seller_profiles', 'seller_profiles.user_id', '=', 'orders.seller_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereIn('orders.status', Order::GROUP_REALIZED)
            ->groupBy('orders.seller_id', 'seller_profiles.business_name')
            ->selectRaw('seller_profiles.business_name as shop_name, COUNT(*) as orders, SUM(orders.total_amount) as gmv, SUM(orders.commission_amount) as commission')
            ->orderByDesc('gmv')
            ->get();
    }

    /** One row per order (used by the ledger table and CSV export). */
    public function ledger(Carbon $from, Carbon $to)
    {
        return Order::with(['seller.sellerProfile', 'buyer'])
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at');
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
                'orders' => 0,
                'gmv' => 0.0,
                'commission' => 0.0,
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
