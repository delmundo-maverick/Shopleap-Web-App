<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $commissionByMonth = [
            'labels' => $months->map(fn($m) => $m->format('M'))->values(),
            'totals' => $months->map(fn($m) => Order::whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)->sum('commission_amount'))->values(),
        ];

        $stats = [
            'pending_approvals' => SellerProfile::where('status', 'pending')->count(),
            'active_users' => User::where('account_status', 'active')->count(),
            'open_complaints' => 0, // TODO: real value once complaints table exists
            'commission_this_month' => Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->sum('commission_amount'),
        ];

        return view('admin.dashboard', compact('registrationsByMonth', 'sellerStatusCounts', 'commissionByMonth', 'stats'));
    }
}
