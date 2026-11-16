@props(['active' => null])
@php
    $adminReturnQueue = \App\Models\OrderReturn::whereIn('status', ['disputed', 'refund_pending'])->count();
    $sections = [
        'Overview' => [['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', 'key' => 'dashboard']],
        'User Management' => [['route' => 'admin.registrations.buyers.index', 'label' => 'Buyers & Customers', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'key' => 'users'],
            ['route' => 'admin.registrations.sellers.index', 'label' => 'Sellers & Merchants', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h6m-6 0V11m6 0V5', 'key' => 'sellers'],
            ['route' => 'admin.registrations.logistics.index', 'label' => 'Logistics Partners', 'icon' => 'M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8', 'key' => 'logistics']],
        'Support' => [['route' => 'messages.index', 'label' => 'Messages', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'key' => 'messages', 'badge' => true]],
        'Financials & Logs' => [['route' => 'admin.transactions.index', 'label' => 'All Transactions', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'key' => 'transactions'],
            ['route' => 'admin.returns.index', 'label' => 'Returns & Refunds', 'icon' => 'M3 10h10a5 5 0 010 10H9M3 10l4-4M3 10l4 4', 'key' => 'returns', 'count' => $adminReturnQueue],
            ['route' => 'admin.logs.index', 'label' => 'System Activity Logs', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'key' => 'logs']],
        'Platform Settings' => [['route' => 'admin.settings.index', 'label' => 'General', 'icon' => 'M4 6h16M4 12h16M4 18h16M9 3v6m6 0v6m-3 0v6', 'key' => ''],
            ['route' => 'admin.settings.shipping.index', 'label' => 'Shipping', 'icon' => 'M3 7h11v10H3zM14 10h4l3 3v4h-7zM7 20a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z', 'key' => ''],
            ['route' => 'admin.settings.announcements.index', 'label' => 'Announcements', 'icon' => 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 00-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m2 0v1a2 2 0 004 0v-1m-4-11V4', 'key' => ''],
            ['route' => 'admin.settings.policies.index', 'label' => 'Policies', 'icon' => 'M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M9 13h6m-6 4h6', 'key' => '']],
    ];
    array_unshift($sections['Financials & Logs'], ['route' => 'admin.ledger.index', 'label' => 'Platform ledger', 'key' => 'ledger']);
    $sections['Money'] = [
        ['route' => 'admin.settlements.index', 'label' => 'Seller settlement oversight'],
        ['route' => 'admin.refunds.index', 'label' => 'Refunds'],
    ];
    foreach (['remittances' => 'Remittances', 'settings' => 'Remittance settings'] as $section => $label) {
        $sections['Money'][] = ['route' => 'admin.movements.index', 'params' => ['section' => $section], 'label' => $label, 'selected' => request()->routeIs('admin.movements.index') && request()->route('section') === $section];
    }
@endphp
<x-nav.workspace label="Admin Control Panel" home="admin.dashboard" :sections="$sections" :active="$active" class="workspace-navigation" />
