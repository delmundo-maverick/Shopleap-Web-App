<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Admin Dashboard | Shopleap</title>
</head>

<body class="min-h-screen text-charcoal">

    <div class="frost-surface"></div>

    <div class="flex min-h-screen">

        <x-navbars.admin active="dashboard" />

        <div class="flex flex-1 flex-col">

            <!-- TOPBAR -->
            <header class="frost-panel-solid flex h-20 items-center justify-between border-b border-white/50 px-6">

                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Dashboard Overview</h2>
                        <p class="text-sm text-charcoal/55">Welcome back, {{ auth()->user()->name ?? 'Admin' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">

                    <button
                        class="frost-btn relative rounded-full p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-sale-red"></span>
                    </button>

                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-sm font-semibold text-charcoal leading-tight">
                                {{ auth()->user()->name ?? 'Admin' }}</p>
                            <p class="text-xs text-charcoal/50 leading-tight">Super Admin</p>
                        </div>
                    </div>

                </div>

            </header>


            <!-- CONTENT -->
            <main class="flex-1 space-y-6 p-6">

                <!-- STAT CARDS (real data) -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Pending Approvals</p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">{{ $stats['pending_approvals'] }}</p>
                        <p class="mt-1 text-xs text-charcoal/50">Seller applications</p>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Active Users</p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">{{ $stats['active_users'] }}</p>
                        <p class="mt-1 text-xs text-charcoal/50">Across all roles</p>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Open Complaints</p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">{{ $stats['open_complaints'] }}</p>
                        <p class="mt-1 text-xs text-charcoal/50">Complaints module not built yet</p>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Commission Earned</p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">₱
                            {{ number_format($stats['commission_this_month'], 2) }}</p>
                        <p class="mt-1 text-xs text-charcoal/50">This month · 10%</p>
                    </div>

                </div>

                <!-- CHARTS ROW 1 -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                    <div class="frost-panel rounded-2xl p-5 lg:col-span-2">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-charcoal">Platform Registrations</h3>
                            <p class="text-xs text-charcoal/50">New accounts by role, last 6 months</p>
                        </div>
                        <canvas id="registrationsChart" height="140"></canvas>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-charcoal">Seller Applications</h3>
                            <p class="text-xs text-charcoal/50">Current status breakdown</p>
                        </div>
                        <canvas id="sellerStatusChart" height="220"></canvas>
                    </div>

                </div>

                <!-- CHARTS ROW 2 -->
                <div class="frost-panel rounded-2xl p-5">
                    <div class="mb-4">
                        <h3 class="text-sm font-bold text-charcoal">Commission Revenue</h3>
                        <p class="text-xs text-charcoal/50">Platform earnings (10% per order), last 6 months</p>
                    </div>
                    <canvas id="commissionChart" height="90"></canvas>
                </div>

            </main>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        const PRIMARY_BLUE = '#1A5FB4'; // matches --color-primary in app.css
        const SKY_BLUE = '#4A90D9'; // matches --color-sky-blue in app.css
        const CHARCOAL_MUTED = '#2E3A46';

        const registrationsByMonth = @json($registrationsByMonth);
        const sellerStatusCounts = @json($sellerStatusCounts);
        const commissionByMonth = @json($commissionByMonth);

        new Chart(document.getElementById('registrationsChart'), {
            type: 'bar',
            data: {
                labels: registrationsByMonth.labels,
                datasets: [{
                        label: 'Buyers',
                        data: registrationsByMonth.buyers,
                        backgroundColor: PRIMARY_BLUE,
                        borderRadius: 4
                    },
                    {
                        label: 'Sellers',
                        data: registrationsByMonth.sellers,
                        backgroundColor: SKY_BLUE,
                        borderRadius: 4
                    },
                    {
                        label: 'Logistics',
                        data: registrationsByMonth.logistics,
                        backgroundColor: CHARCOAL_MUTED,
                        borderRadius: 4
                    },
                ]
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
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
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

        new Chart(document.getElementById('sellerStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Rejected'],
                datasets: [{
                    data: [sellerStatusCounts.pending, sellerStatusCounts.approved, sellerStatusCounts
                        .rejected
                    ],
                    backgroundColor: ['#F59E0B', '#16A34A', '#DC2626'],
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

        new Chart(document.getElementById('commissionChart'), {
            type: 'line',
            data: {
                labels: commissionByMonth.labels,
                datasets: [{
                    label: 'Commission (₱)',
                    data: commissionByMonth.totals,
                    borderColor: PRIMARY_BLUE,
                    backgroundColor: 'rgba(26, 95, 180, 0.08)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: PRIMARY_BLUE,
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
    </script>

</body>

</html>
