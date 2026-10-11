<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Maps the dashboard's 5 buckets to the real order statuses in the DB
     * (see the orders_status_check constraint).
     */
    private const STATUS_GROUPS = [
        'pending'   => ['PLACED'],
        'to_ship'   => ['CONFIRMED', 'PREPARING', 'READY_FOR_PICKUP'],
        'shipped'   => ['PICKED_UP', 'AT_SORTING_CENTER', 'SORTED', 'ASSIGNED_TO_RIDER', 'OUT_FOR_DELIVERY'],
        'delivered' => ['DELIVERED', 'COMPLETED'],
        'cancelled' => ['CANCELLED', 'DELIVERY_FAILED', 'RETURNED'],
    ];

    /**
     * Statuses that should NOT count toward revenue.
     */
    private const NON_REVENUE_STATUSES = ['CANCELLED', 'DELIVERY_FAILED', 'RETURNED'];

    public function index(Request $request)
    {
        $user = $request->user();
        $sellerProfile = $user->sellerProfile;

        /*
        |--------------------------------------------------------------------------
        | Sales Trend: Last 6 Months (single query, non-revenue orders excluded)
        |--------------------------------------------------------------------------
        */

        $months = collect(range(5, 0))
            ->map(fn($i) => now()->startOfMonth()->subMonths($i));

        $monthlyTotals = Order::where('seller_id', $user->id)
            ->whereNotIn('status', self::NON_REVENUE_STATUSES)
            ->where('created_at', '>=', $months->first())
            ->get(['created_at', 'total_amount'])
            ->groupBy(fn($order) => $order->created_at->format('Y-m'))
            ->map(fn($orders) => (float) $orders->sum('total_amount'));

        $salesTrend = [
            'labels' => $months
                ->map(fn($month) => $month->format('M'))
                ->values(),

            'totals' => $months
                ->map(fn($month) => (float) $monthlyTotals->get($month->format('Y-m'), 0))
                ->values(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Order Status Counts (14 DB statuses grouped into 5 dashboard buckets)
        |--------------------------------------------------------------------------
        | toBase() bypasses model casts (e.g. an enum cast on `status`).
        | Keys are uppercased + trimmed so casing differences don't matter.
        */

        $statusCounts = Order::where('seller_id', $user->id)
            ->toBase()
            ->select('status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn($total, $status) => [
                strtoupper(trim((string) $status)) => (int) $total,
            ]);

        $orderStatusCounts = [];

        foreach (self::STATUS_GROUPS as $bucket => $statuses) {
            $orderStatusCounts[$bucket] = (int) $statusCounts
                ->only($statuses)
                ->sum();
        }

        // Log any status that isn't part of a bucket (e.g. a newly added one).
        $knownStatuses = collect(self::STATUS_GROUPS)->flatten()->all();
        $unknownStatuses = $statusCounts->except($knownStatuses);

        if ($unknownStatuses->isNotEmpty()) {
            logger()->warning(
                'Unmapped order statuses for seller ' . $user->id,
                $unknownStatuses->all()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard Statistics
        |--------------------------------------------------------------------------
        */

        $stats = [
            'sales_this_month' => (float) Order::where('seller_id', $user->id)
                ->whereNotIn('status', self::NON_REVENUE_STATUSES)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount'),

            // Orders the seller still needs to act on.
            'pending_orders' => $orderStatusCounts['pending']
                + $orderStatusCounts['to_ship'],

            'products_listed' => Product::where('seller_id', $user->id)
                ->where('status', 'active')
                ->count(),

            // TODO: Calculate from reviews when the reviews table exists.
            'store_rating' => null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Recent Orders
        |--------------------------------------------------------------------------
        */

        $recentOrders = Order::with('items')
            ->where('seller_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('seller.dashboard', compact(
            'sellerProfile',
            'salesTrend',
            'orderStatusCounts',
            'stats',
            'recentOrders'
        ));
    }
}
