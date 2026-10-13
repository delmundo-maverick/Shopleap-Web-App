<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Seller Dashboard | Shopleap</title>
</head>

<body class="min-h-screen text-charcoal">

    <div class="frost-surface"></div>

    <div class="flex min-h-screen">

        <x-navbars.seller active="dashboard" />

        <div class="flex flex-1 flex-col min-w-0">

            {{-- TOPBAR --}}
            <header class="frost-panel-solid sticky top-0 z-10 flex h-20 items-center justify-between px-6">

                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="rounded-xl p-2 text-charcoal/60 transition hover:bg-white/70 hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div>
                        <h2 class="text-xl font-bold text-charcoal">
                            {{ $sellerProfile->business_name ?? 'Your Store' }}
                        </h2>

                        <p class="text-sm text-charcoal/55">
                            Welcome back,
                            {{ $sellerProfile->first_name ?? auth()->user()->name }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-3 frost-chip">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">
                            {{ strtoupper(substr($sellerProfile->first_name ?? (auth()->user()->name ?? 'S'), 0, 1)) }}
                        </div>

                        <div class="hidden sm:block">
                            <p class="text-sm font-semibold leading-tight text-charcoal">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs leading-tight text-charcoal/50">Seller</p>
                        </div>
                    </div>
                </div>

            </header>

            {{-- CONTENT --}}
            <main class="flex-1 space-y-6 p-6">

                <x-announcement-banner audience="seller" />

                {{-- STAT CARDS --}}
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    {{-- This Month's Sales --}}
                    <div class="frost-panel rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">
                                This Month's Sales
                            </p>

                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 2v8m0 0v2m0-2c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>

                        <p class="mt-2 text-3xl font-bold text-charcoal">
                            ₱{{ number_format($stats['sales_this_month'], 2) }}
                        </p>

                        <p class="mt-1 text-xs text-charcoal/50">This calendar month</p>
                    </div>

                    {{-- Pending Orders --}}
                    <div class="frost-panel rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">
                                Pending Orders
                            </p>

                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                        </div>

                        <p class="mt-2 text-3xl font-bold text-charcoal">
                            {{ $stats['pending_orders'] }}
                        </p>

                        <p class="mt-1 text-xs text-charcoal/50">To be prepared</p>
                    </div>

                    {{-- Products Listed --}}
                    <div class="frost-panel rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">
                                Products Listed
                            </p>

                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>

                        <p class="mt-2 text-3xl font-bold text-charcoal">
                            {{ $stats['products_listed'] }}
                        </p>

                        <p class="mt-1 text-xs text-charcoal/50">Active in your store</p>
                    </div>

                    {{-- Store Rating --}}
                    <div class="frost-panel rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">
                                Store Rating
                            </p>

                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-400/15 text-amber-500">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                        </div>

                        <p class="mt-2 text-3xl font-bold text-charcoal">
                            {{ $stats['store_rating'] !== null ? number_format($stats['store_rating'], 1) : '--' }}
                        </p>

                        <p class="mt-1 text-xs text-charcoal/50">
                            @if ($stats['review_count'] > 0)
                                From {{ number_format($stats['review_count']) }}
                                {{ \Illuminate\Support\Str::plural('review', $stats['review_count']) }}
                            @else
                                No reviews yet
                            @endif
                        </p>
                    </div>

                </div>

                {{-- CHARTS --}}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                    {{-- Sales Trend --}}
                    <div class="frost-panel rounded-2xl p-5 lg:col-span-2">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-charcoal">Sales Trend</h3>
                            <p class="text-xs text-charcoal/50">Last 6 months</p>
                        </div>

                        <div class="relative h-64 w-full">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>

                    {{-- Order Status --}}
                    <div class="frost-panel rounded-2xl p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-charcoal">Order Status</h3>
                            <p class="text-xs text-charcoal/50">Current breakdown</p>
                        </div>

                        <div class="relative h-64 w-full">
                            <canvas id="orderStatusChart"></canvas>

                            {{-- Visibility is controlled by JS via style.display --}}
                            <div id="orderStatusEmpty" style="display: none;"
                                class="absolute inset-0 flex-col items-center justify-center text-center">
                                <p class="text-sm font-semibold text-charcoal/60">
                                    No orders yet
                                </p>
                                <p class="mt-1 text-xs text-charcoal/40">
                                    Order status data will appear here.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- CUSTOMER REVIEWS --}}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                    {{-- Rating summary --}}
                    <div class="frost-panel rounded-2xl p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-charcoal">Customer Reviews</h3>
                            <p class="text-xs text-charcoal/50">All time</p>
                        </div>

                        @if ($stats['review_count'] > 0)
                            @php
                                $avg = $stats['store_rating'];
                                $filledStars = (int) round($avg);
                            @endphp

                            <div class="flex items-end gap-2">
                                <p class="text-4xl font-bold text-charcoal">{{ number_format($avg, 1) }}</p>
                                <p class="pb-1 text-sm text-charcoal/50">out of 5</p>
                            </div>

                            <p class="mt-1 text-lg leading-none">
                                <span class="text-amber-400">{{ str_repeat('★', $filledStars) }}</span><span
                                    class="text-charcoal/15">{{ str_repeat('★', 5 - $filledStars) }}</span>
                            </p>

                            <p class="mt-1 text-xs text-charcoal/50">
                                {{ number_format($stats['review_count']) }}
                                {{ \Illuminate\Support\Str::plural('review', $stats['review_count']) }}
                            </p>

                            <div class="mt-5 space-y-2">
                                @foreach ($ratingDistribution as $star => $count)
                                    @php $percent = $stats['review_count'] > 0 ? round(($count / $stats['review_count']) * 100) : 0; @endphp
                                    <div class="flex items-center gap-2 text-xs">
                                        <span
                                            class="w-6 shrink-0 font-semibold text-charcoal/60">{{ $star }}★</span>
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-charcoal/10">
                                            <div class="h-full rounded-full bg-amber-400"
                                                style="width: {{ $percent }}%"></div>
                                        </div>
                                        <span
                                            class="w-6 shrink-0 text-right text-charcoal/50">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div
                                class="flex h-40 items-center justify-center rounded-xl border border-dashed border-white/70 bg-white/30 px-4 text-center text-sm text-charcoal/40">
                                No reviews yet
                            </div>
                        @endif
                    </div>

                    {{-- Latest reviews --}}
                    <div class="frost-panel rounded-2xl p-5 lg:col-span-2">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-charcoal">Latest Reviews</h3>
                                <p class="text-xs text-charcoal/50">What buyers are saying about your products</p>
                            </div>

                            <a href="{{ route('seller.reviews.index') }}"
                                class="shrink-0 text-xs font-semibold text-primary hover:underline">
                                View all &amp; reply
                            </a>
                        </div>

                        @if ($recentReviews->isEmpty())
                            <div
                                class="flex h-40 items-center justify-center rounded-xl border border-dashed border-white/70 bg-white/30 px-4 text-center text-sm text-charcoal/40">
                                Reviews appear here once buyers confirm their orders and rate your products.
                            </div>
                        @else
                            <div class="divide-y divide-white/60">
                                @foreach ($recentReviews as $review)
                                    <div class="py-4 first:pt-0 last:pb-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-charcoal">{{ $review['buyer'] }}
                                                </p>
                                                <p class="mt-0.5 text-sm leading-none">
                                                    <span
                                                        class="text-amber-400">{{ str_repeat('★', $review['rating']) }}</span><span
                                                        class="text-charcoal/15">{{ str_repeat('★', 5 - $review['rating']) }}</span>
                                                </p>
                                            </div>

                                            <p class="shrink-0 text-xs text-charcoal/40">
                                                {{ $review['created_at']->diffForHumans() }}
                                            </p>
                                        </div>

                                        <p class="mt-2 text-xs text-charcoal/50">
                                            {{ $review['product'] }} ·
                                            <a href="{{ route('seller.orders.show', $review['order_id']) }}"
                                                class="font-semibold text-primary hover:underline">Order
                                                #{{ $review['order_id'] }}</a>
                                        </p>

                                        @if (filled($review['comment']))
                                            <p class="mt-2 whitespace-pre-line text-sm text-charcoal/80">
                                                {{ $review['comment'] }}</p>
                                        @else
                                            <p class="mt-2 text-sm italic text-charcoal/35">Rated without a written
                                                review.</p>
                                        @endif

                                        @if ($review['media']->isNotEmpty())
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($review['media'] as $media)
                                                    @if ($media['is_video'])
                                                        <video src="{{ $media['url'] }}" controls preload="metadata"
                                                            class="h-20 w-28 rounded-lg bg-charcoal/10 object-cover"></video>
                                                    @else
                                                        <a href="{{ $media['url'] }}" target="_blank" rel="noopener"
                                                            class="block h-20 w-20 overflow-hidden rounded-lg bg-charcoal/10">
                                                            <img src="{{ $media['url'] }}" alt="Review photo"
                                                                loading="lazy" class="h-full w-full object-cover">
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

                {{-- RECENT ORDERS --}}
                <div class="frost-panel rounded-2xl p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-charcoal">Recent Orders</h3>

                        <a href="{{ route('seller.orders.index') }}"
                            class="text-xs font-semibold text-primary hover:underline">
                            View all
                        </a>
                    </div>

                    @if ($recentOrders->isEmpty())
                        <div
                            class="flex h-40 items-center justify-center rounded-xl border border-dashed border-white/70 bg-white/30 text-sm text-charcoal/40">
                            No orders yet — new orders will appear here once buyers start purchasing.
                        </div>
                    @else
                        <div class="divide-y divide-white/60">
                            @foreach ($recentOrders as $order)
                                <a href="{{ route('seller.orders.show', $order) }}"
                                    class="flex items-center justify-between gap-4 rounded-xl px-2 py-3 transition hover:bg-white/50">

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-charcoal">
                                            Order #{{ $order->id }} — {{ $order->buyer_name }}
                                        </p>

                                        <p class="text-xs text-charcoal/50">
                                            {{ $order->items->sum('quantity') }} item(s)
                                            · {{ $order->created_at->diffForHumans() }}
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-3">
                                        <span class="text-sm font-bold text-charcoal">
                                            ₱{{ number_format($order->total_amount, 2) }}
                                        </span>

                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize text-primary frost-chip">
                                            {{ ucwords(strtolower(str_replace('_', ' ', $order->status))) }}
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

            </main>

        </div>

    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const SELLER_PRIMARY = '#1A5FB4';

            // Data from Seller\DashboardController@index
            const salesTrend = @json($salesTrend);
            const orderStatusCounts = @json($orderStatusCounts);

            // Order status values (computed first so they're logged even if Chart.js fails)
            const orderStatusLabels = ['Pending', 'To Ship', 'Shipped', 'Delivered', 'Cancelled'];

            const orderStatusData = [
                Number(orderStatusCounts.pending ?? 0),
                Number(orderStatusCounts.to_ship ?? 0),
                Number(orderStatusCounts.shipped ?? 0),
                Number(orderStatusCounts.delivered ?? 0),
                Number(orderStatusCounts.cancelled ?? 0)
            ];

            const hasOrders = orderStatusData.some(count => Number.isFinite(count) && count > 0);

            console.log('Seller dashboard chart data:', {
                salesTrend,
                orderStatusCounts,
                orderStatusData,
                hasOrders
            });

            // Check Chart.js
            if (typeof Chart === 'undefined') {
                console.error('Chart.js failed to load. Check your internet connection or CDN availability.');
                return;
            }

            // ---------------- Sales Trend Chart ----------------
            const salesCanvas = document.getElementById('salesTrendChart');

            if (salesCanvas) {
                try {
                    new Chart(salesCanvas, {
                        type: 'line',

                        data: {
                            labels: salesTrend.labels,
                            datasets: [{
                                label: 'Revenue (₱)',
                                data: salesTrend.totals,
                                borderColor: SELLER_PRIMARY,
                                backgroundColor: 'rgba(26, 95, 180, 0.12)',
                                tension: 0.35,
                                fill: true,
                                pointRadius: 3,
                                pointBackgroundColor: SELLER_PRIMARY,
                            }]
                        },

                        options: {
                            responsive: true,
                            maintainAspectRatio: false,

                            plugins: {
                                legend: {
                                    display: false
                                },

                                tooltip: {
                                    callbacks: {
                                        label(context) {
                                            return 'Revenue: ₱' +
                                                Number(context.raw).toLocaleString('en-PH', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                });
                                        }
                                    }
                                }
                            },

                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(20, 40, 70, 0.08)'
                                    }
                                },

                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Sales trend chart failed:', error);
                }
            }

            // ---------------- Order Status Doughnut Chart ----------------
            const orderStatusCanvas = document.getElementById('orderStatusChart');
            const orderStatusEmpty = document.getElementById('orderStatusEmpty');

            const showEmptyState = (show) => {
                if (orderStatusEmpty) {
                    orderStatusEmpty.style.display = show ? 'flex' : 'none';
                }
            };

            if (orderStatusCanvas && hasOrders) {
                showEmptyState(false);

                try {
                    new Chart(orderStatusCanvas, {
                        type: 'doughnut',

                        data: {
                            labels: orderStatusLabels,

                            datasets: [{
                                label: 'Orders',
                                data: orderStatusData,

                                backgroundColor: [
                                    '#F59E0B',
                                    '#1A5FB4',
                                    '#4A90D9',
                                    '#93C5FD',
                                    '#DC2626'
                                ],

                                borderWidth: 2,
                                borderColor: '#FFFFFF',
                                hoverOffset: 6
                            }]
                        },

                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',

                            plugins: {
                                legend: {
                                    position: 'bottom',

                                    labels: {
                                        boxWidth: 10,
                                        boxHeight: 10,
                                        padding: 12,

                                        font: {
                                            size: 11
                                        }
                                    }
                                },

                                tooltip: {
                                    callbacks: {
                                        label(context) {
                                            return `${context.label}: ${context.raw} orders`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Order status chart failed:', error);
                    showEmptyState(true);
                }
            } else {
                showEmptyState(true);
            }
        });
    </script>

</body>

</html>
