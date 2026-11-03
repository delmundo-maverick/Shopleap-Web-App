<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/admin/admin-dashboard.js'])

    <title>Admin Dashboard | Shopleap</title>
</head>

<body class="min-h-screen text-charcoal">

    <div class="frost-surface"></div>

    <div class="flex min-h-screen">

        <x-navbars.admin active="dashboard" />

        <div class="flex min-w-0 flex-1 flex-col">

            <!-- TOPBAR -->
            <header class="frost-panel-solid flex h-20 items-center justify-between border-0 border-b border-line px-6">

                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-lg p-2 text-charcoal/60 hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-page-title">Dashboard Overview</h2>
                        <p class="text-sm text-charcoal/60">Welcome back, {{ auth()->user()->name ?? 'Admin' }}</p>
                    </div>
                </div>
                <x-notification-bell />
            </header>


            <!-- CONTENT -->
            <main class="flex-1 space-y-5 p-4 sm:space-y-6 sm:p-6">

                <x-announcement-banner audience="super_admin" />

                <!-- STAT CARDS (real data) -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4">

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-card-label">Pending Approvals</p>
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-warning/10 text-warning">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-bold tabular-nums text-charcoal sm:text-3xl">
                            {{ $stats['pending_approvals'] }}</p>
                        <p class="mt-1 text-xs text-charcoal/60">Buyer, Seller & Logistics applications</p>
                    </div>

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-card-label">Active Users</p>
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-bold tabular-nums text-charcoal sm:text-3xl">
                            {{ $stats['active_users'] }}</p>
                        <p class="mt-1 text-xs text-charcoal/60">Across all roles</p>
                    </div>

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-card-label">Open Complaints</p>
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-danger/10 text-danger">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-bold tabular-nums text-charcoal sm:text-3xl">
                            {{ $stats['open_complaints'] }}</p>
                        <p class="mt-1 text-xs text-charcoal/60">Complaints module not built yet</p>
                    </div>

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-card-label">Commission Earned</p>
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-success/10 text-success">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-bold tabular-nums text-charcoal sm:text-3xl">
                            ₱ {{ number_format($stats['commission_this_month'], 2) }}</p>
                        <p class="mt-1 text-xs text-charcoal/60">This month · 10%</p>
                    </div>

                </div>

                <!-- REGISTRATIONS + COMMISSION (side by side on large screens) -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="mb-4">
                            <h3 class="text-card-title">Platform Registrations</h3>
                            <p class="text-card-hint">New accounts by role, last 6 months</p>
                        </div>
                        <div class="relative h-64 sm:h-72">
                            <canvas id="registrationsChart"></canvas>
                        </div>
                    </div>

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="mb-4">
                            <h3 class="text-card-title">Commission Revenue</h3>
                            <p class="text-card-hint">Platform earnings (10% per order), last 6 months</p>
                        </div>
                        <div class="relative h-64 sm:h-72">
                            <canvas id="commissionChart"></canvas>
                        </div>
                    </div>

                </div>

                <!-- APPLICATION STATUS ROW — seller / logistics / buyer, always one line on sm+ -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5">

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="mb-3 sm:mb-4">
                            <h3 class="text-card-title">Seller Applications</h3>
                            <p class="text-card-hint">Current status breakdown</p>
                        </div>
                        <div class="relative mx-auto h-48 w-full max-w-[220px] sm:h-56">
                            <canvas id="sellerStatusChart"></canvas>
                        </div>
                    </div>

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="mb-3 sm:mb-4">
                            <h3 class="text-card-title">Logistics Applications</h3>
                            <p class="text-card-hint">Current status breakdown</p>
                        </div>
                        <div class="relative mx-auto h-48 w-full max-w-[220px] sm:h-56">
                            <canvas id="logisticsStatusChart"></canvas>
                        </div>
                    </div>

                    <div class="frost-panel rounded-xl p-4 sm:p-5">
                        <div class="mb-3 sm:mb-4">
                            <h3 class="text-card-title">Buyer Applications</h3>
                            <p class="text-card-hint">Current status breakdown</p>
                        </div>
                        <div class="relative mx-auto h-48 w-full max-w-[220px] sm:h-56">
                            <canvas id="buyerStatusChart"></canvas>
                        </div>
                    </div>

                </div>

                <!-- ORDERS BY STATUS -->
                <div class="frost-panel rounded-xl p-4 sm:p-5">
                    <div class="mb-4">
                        <h3 class="text-card-title">Orders by Status</h3>
                        <p class="text-card-hint">Every order across the full fulfillment chain, right now</p>
                    </div>
                    <div class="relative h-72 sm:h-80">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                </div>

            </main>

        </div>

    </div>

    {{-- Data for resources/js/pages/admin/admin-dashboard.js --}}
    @php
        $dashboardData = [
            'registrationsByMonth' => $registrationsByMonth,
            'sellerStatusCounts' => $sellerStatusCounts,
            'logisticsStatusCounts' => $logisticsStatusCounts,
            'buyerStatusCounts' => $buyerStatusCounts,
            'orderStatusCounts' => $orderStatusCounts,
            'commissionByMonth' => $commissionByMonth,
        ];
    @endphp
    <script type="application/json" id="admin-dashboard-data">@json($dashboardData)</script>

</body>

</html>
