<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Seller Dashboard | Shopleap</title>
</head>

<body class="min-h-screen bg-seller-bg text-charcoal">

    <div class="flex min-h-screen">

        <x-navbars.seller active="dashboard" />

        <div class="flex flex-1 flex-col">

            <!-- TOPBAR -->
            <header class="flex h-20 items-center justify-between border-b border-light-gray bg-white px-6">

                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="rounded-lg p-2 text-charcoal/60 transition hover:bg-seller-soft hover:text-seller lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">
                            {{ $sellerProfile->business_name ?? 'Your Store' }}
                        </h2>
                        <p class="text-sm text-charcoal/55">Welcome back,
                            {{ $sellerProfile->first_name ?? auth()->user()->name }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">

                    <button
                        class="relative rounded-full p-2 text-charcoal/60 transition hover:bg-seller-soft hover:text-seller">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-sale-red"></span>
                    </button>

                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-seller text-sm font-bold text-white">
                            {{ strtoupper(substr($sellerProfile->first_name ?? (auth()->user()->name ?? 'S'), 0, 1)) }}
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-sm font-semibold leading-tight text-charcoal">{{ auth()->user()->name }}</p>
                            <p class="text-xs leading-tight text-charcoal/50">Seller</p>
                        </div>
                    </div>

                </div>

            </header>

            <!-- CONTENT -->
            <main class="flex-1 space-y-6 p-6">

                <!-- STAT CARDS -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">This Month's Sales
                            </p>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-seller-soft text-seller">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.66 0-3 .9-3 2s1.34 2 3 2 3 .9 3 2-1.34 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 2v8m0 0v2m0-2c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-3xl font-bold text-charcoal">₱ --</p>
                        <p class="mt-1 text-xs text-charcoal/50">Awaiting order data</p>
                    </div>

                    <div class="rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Pending Orders</p>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-seller-soft text-seller">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 0h6" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-3xl font-bold text-charcoal">--</p>
                        <p class="mt-1 text-xs text-charcoal/50">To be prepared</p>
                    </div>

                    <div class="rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Products Listed
                            </p>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-seller-soft text-seller">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-3xl font-bold text-charcoal">--</p>
                        <p class="mt-1 text-xs text-charcoal/50">Active in your store</p>
                    </div>

                    <div class="rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Store Rating</p>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-seller-soft text-seller">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.914c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-3xl font-bold text-charcoal">--</p>
                        <p class="mt-1 text-xs text-charcoal/50">No reviews yet</p>
                    </div>

                </div>

                <!-- MAIN PANELS -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                    <div
                        class="rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5 lg:col-span-2">
                        <!-- CHARTS -->
                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                            <div
                                class="rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5 lg:col-span-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-bold text-charcoal">Sales Trend</h3>
                                        <p class="text-xs text-charcoal/50">Last 6 months</p>
                                    </div>
                                </div>
                                <canvas id="salesTrendChart" height="140"></canvas>
                            </div>

                            <div class="rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5">
                                <div class="mb-4">
                                    <h3 class="text-sm font-bold text-charcoal">Order Status</h3>
                                    <p class="text-xs text-charcoal/50">Current breakdown</p>
                                </div>
                                <canvas id="orderStatusChart" height="220"></canvas>
                            </div>

                        </div>

                        <!-- RECENT ORDERS -->
                        <div class="rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-charcoal">Recent Orders</h3>
                                <a href="#" class="text-xs font-semibold text-seller hover:underline">View
                                    all</a>
                            </div>

                            <div
                                class="flex h-40 items-center justify-center rounded-xl border border-dashed border-light-gray text-sm text-charcoal/40">
                                No orders yet — new orders will appear here once buyers start purchasing.
                            </div>
                        </div>

            </main>

        </div>

    </div>

    <!-- Chart.js via CDN — no npm install needed -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>

        const SELLER_TEAL = '#0D9488';
        const SELLER_TEAL_LIGHT = '#5EEAD4';

        // --- Sales Trend (line chart) ---
        new Chart(document.getElementById('salesTrendChart'), {
            type: 'line',
            data: {
                labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                datasets: [{
                    label: 'Revenue (₱)',
                    data: [0, 0, 0, 0, 0, 0], // TODO: replace with real monthly revenue
                    borderColor: SELLER_TEAL,
                    backgroundColor: 'rgba(13, 148, 136, 0.08)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: SELLER_TEAL,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#E5E7EB'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    },
                }
            }
        });

        // --- Order Status Breakdown (doughnut chart) ---
        new Chart(document.getElementById('orderStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'To Ship', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    data: [0, 0, 0, 0, 0], // TODO: replace with real order status counts
                    backgroundColor: ['#F59E0B', '#0D9488', '#14B8A6', '#5EEAD4', '#DC2626'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>
