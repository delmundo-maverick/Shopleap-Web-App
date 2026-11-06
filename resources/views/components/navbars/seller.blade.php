@props(['active' => null])
@php
    $pendingReturns = \App\Models\OrderReturn::where('seller_id', auth()->id())->whereIn('status', ['requested', 'shipped_back'])->count();
    $sections = [
        'Overview' => [['route' => 'seller.dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'key' => 'dashboard']],
        'Order Management' => [['route' => 'seller.products.index', 'label' => 'Manage Inventory', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'key' => 'inventory'],
            ['route' => 'seller.orders.index', 'label' => 'Order Notifications', 'icon' => 'M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 0h6m-6 0H5a2 2 0 01-2-2V7a2 2 0 012-2h3.28a1 1 0 01.948.684l.5 1.5a1 1 0 00.949.685H15.28a1 1 0 01.948.684l.5 1.5a1 1 0 00.949.685H19a2 2 0 012 2v6a2 2 0 01-2 2h-4', 'key' => 'orders', 'params' => ['status' => 'new']],
            ['route' => 'seller.orders.index', 'label' => 'Prepare & Ship Orders', 'icon' => 'M20 12H4m16 0l-4-4m4 4l-4 4M4 12l4-4M4 12l4 4', 'key' => 'prepare', 'params' => ['status' => 'prepare']],
            ['route' => 'seller.returns.index', 'label' => 'Returns & Refunds', 'icon' => 'M3 10h10a5 5 0 010 10H9M3 10l4-4M3 10l4 4', 'key' => 'returns', 'count' => $pendingReturns],
            ['route' => 'seller.vouchers.index', 'label' => 'Shop Vouchers', 'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z', 'key' => 'vouchers']],
        'Insights' => [['route' => 'seller.reports.index', 'label' => 'Generate Report', 'icon' => 'M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a1 1 0 001-1V9.5a1 1 0 00-.4-.8l-6-4.5a1 1 0 00-1.2 0l-6 4.5a1 1 0 00-.4.8V18a1 1 0 001 1z', 'key' => 'reports'],
            ['route' => 'seller.reviews.index', 'label' => 'Reviews', 'icon' => 'M12 3l2.78 5.63L21 9.54l-4.5 4.39 1.06 6.2L12 17.2l-5.56 2.93 1.06-6.2L3 9.54l6.22-.91L12 3z', 'key' => 'reviews']],
        'General' => [['route' => 'messages.index', 'label' => 'Chat / Messaging', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'key' => 'messages', 'badge' => true],
            ['route' => 'seller.account.index', 'label' => 'Account Management', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'key' => 'account']],
    ];
@endphp
<x-nav.workspace label="Seller Center" home="seller.dashboard" :sections="$sections" :active="$active" class="workspace-navigation" />
