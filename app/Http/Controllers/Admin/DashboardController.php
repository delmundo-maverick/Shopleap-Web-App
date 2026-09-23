<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\SellerProfile;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i));

        $registrationsByMonth = [
            'labels' => $months->map(fn($m) => $m->format('M'))->values(),
            'buyers' => $months->map(fn($m) => User::where('role', 'buyer')
                ->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count())->values(),
            'sellers' => $months->map(fn($m) => User::where('role', 'seller')
                ->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count())->values(),
            'logistics' => $months->map(fn($m) => User::where('role', 'logistics')
                ->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count())->values(),
        ];

        $sellerStatusCounts = [
            'pending' => SellerProfile::where('status', 'pending')->count(),
            'approved' => SellerProfile::where('status', 'approved')->count(),
            'rejected' => SellerProfile::where('status', 'rejected')->count(),
        ];

        $logisticsStatusCounts = [
            'pending' => LogisticsProfile::where('status', 'pending')->count(),
            'approved' => LogisticsProfile::where('status', 'approved')->count(),
            'rejected' => LogisticsProfile::where('status', 'rejected')->count(),
        ];

        $buyerStatusCounts = [
            'pending' => BuyerProfile::where('status', 'pending')->count(),
            'approved' => BuyerProfile::where('status', 'approved')->count(),
            'rejected' => BuyerProfile::where('status', 'rejected')->count(),
        ];

        $commissionByMonth = [
            'labels' => $months->map(fn($m) => $m->format('M'))->values(),
            'totals' => $months->map(fn($m) => Order::whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)->sum('commission_amount'))->values(),
        ];

        // Real 14-value order status chain, using the labels already
        // defined on the Order model so this stays in sync automatically
        // if the chain changes again later.
        $orderStatusCounts = collect(Order::STATUS_LABELS)
            ->mapWithKeys(fn($label, $status) => [$label => Order::where('status', $status)->count()])
            ->toArray();

        $pendingApprovals = $sellerStatusCounts['pending']
            + $logisticsStatusCounts['pending']
            + $buyerStatusCounts['pending'];

        $stats = [
            'pending_approvals' => $pendingApprovals,
            'active_users' => User::where('account_status', 'active')->count(),
            'open_complaints' => 0, // TODO: real value once complaints table exists
            'commission_this_month' => Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->sum('commission_amount'),
        ];

        return view('admin.dashboard', compact(
            'registrationsByMonth',
            'sellerStatusCounts',
            'logisticsStatusCounts',
            'buyerStatusCounts',
            'orderStatusCounts',
            'commissionByMonth',
            'stats'
        ));
    }
}
