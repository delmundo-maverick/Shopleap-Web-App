<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $sellerProfile = $user->sellerProfile;

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $salesTrend = [
            'labels' => $months->map(fn ($m) => $m->format('M'))->values(),
            'totals' => $months->map(fn ($m) => Order::where('seller_id', $user->id)
                ->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)
                ->sum('total_amount'))->values(),
        ];

        $orderStatusCounts = [
            'pending' => Order::where('seller_id', $user->id)->where('status', 'pending')->count(),
            'to_ship' => Order::where('seller_id', $user->id)->where('status', 'to_ship')->count(),
            'shipped' => Order::where('seller_id', $user->id)->where('status', 'shipped')->count(),
            'delivered' => Order::where('seller_id', $user->id)->where('status', 'delivered')->count(),
            'cancelled' => Order::where('seller_id', $user->id)->where('status', 'cancelled')->count(),
        ];

        $stats = [
            'sales_this_month' => Order::where('seller_id', $user->id)
                ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
                ->sum('total_amount'),
            'pending_orders' => Order::where('seller_id', $user->id)
                ->whereIn('status', ['pending', 'to_ship'])->count(),
            'products_listed' => Product::where('seller_id', $user->id)->where('status', 'active')->count(),
            'store_rating' => null, // TODO: real value once a reviews table exists
        ];

        $recentOrders = Order::with('items')
            ->where('seller_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('seller.dashboard', compact(
            'sellerProfile', 'salesTrend', 'orderStatusCounts', 'stats', 'recentOrders'
        ));
    }
}
