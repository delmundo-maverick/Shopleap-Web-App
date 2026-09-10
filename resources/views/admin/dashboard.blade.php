<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Admin Dashboard | Shopleap</title>
</head>

<body class="relative min-h-screen text-charcoal antialiased">

    <!-- AMBIENT BACKGROUND (frosted glass needs something soft to sit on top of) -->
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[#EEF2FA]">
        <div class="absolute -top-40 -left-32 h-[28rem] w-[28rem] rounded-full bg-primary/25 blur-[110px]"></div>
        <div class="absolute top-1/3 -right-24 h-[24rem] w-[24rem] rounded-full bg-sky-400/20 blur-[110px]"></div>
        <div class="absolute bottom-0 left-1/4 h-[22rem] w-[22rem] rounded-full bg-indigo-300/20 blur-[110px]"></div>
    </div>

    <div class="flex min-h-screen">

        <x-navbars.admin active="dashboard" />

        <div class="flex flex-1 flex-col">

            <!-- TOPBAR -->
            <header
                class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-white/40 bg-white/55 px-6 backdrop-blur-xl">

                <div class="flex items-center gap-3">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-charcoal">Dashboard overview</h2>
                        <p class="text-sm text-charcoal/55">Welcome back, {{ auth()->user()->name ?? 'Admin' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">

                    <button
                        class="relative rounded-full border border-white/50 bg-white/50 p-2.5 text-charcoal/60 backdrop-blur-md transition hover:bg-white/80 hover:text-primary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-sale-red ring-2 ring-white/70"></span>
                    </button>

                    <div
                        class="flex items-center gap-2.5 rounded-full border border-white/50 bg-white/50 py-1 pl-1 pr-3 backdrop-blur-md">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-primary to-sky-400 text-sm font-bold text-white shadow-inner">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-sm font-semibold leading-tight text-charcoal">
                                {{ auth()->user()->name ?? 'Admin' }}</p>
                            <p class="text-xs leading-tight text-charcoal/50">Super Admin</p>
                        </div>
                    </div>

                </div>

            </header>


            <!-- CONTENT -->
            <main class="flex-1 space-y-6 p-6">

                <!-- STAT CARDS -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    <div
                        class="group rounded-3xl border border-white/50 bg-white/45 p-5 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.15)] backdrop-blur-xl transition hover:bg-white/60">
                        <div class="flex items-start justify-between">
                            <p class="text-xs font-medium uppercase tracking-wide text-charcoal/45">Pending approvals
                            </p>
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-charcoal">--</p>
                        <p class="mt-1 text-xs text-charcoal/50">Buyer / Seller / Logistics</p>
                    </div>

                    <div
                        class="group rounded-3xl border border-white/50 bg-white/45 p-5 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.15)] backdrop-blur-xl transition hover:bg-white/60">
                        <div class="flex items-start justify-between">
                            <p class="text-xs font-medium uppercase tracking-wide text-charcoal/45">Active users</p>
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-sky-400/15 text-sky-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-charcoal">--</p>
                        <p class="mt-1 text-xs text-charcoal/50">Across all roles</p>
                    </div>

                    <div
                        class="group rounded-3xl border border-white/50 bg-white/45 p-5 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.15)] backdrop-blur-xl transition hover:bg-white/60">
                        <div class="flex items-start justify-between">
                            <p class="text-xs font-medium uppercase tracking-wide text-charcoal/45">Open complaints</p>
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-sale-red/10 text-sale-red">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 9v3.75m0 3.75h.008M10.29 3.86l-8.18 14.16A1.5 1.5 0 003.5 20h17a1.5 1.5 0 001.39-1.98L13.71 3.86a1.5 1.5 0 00-2.42 0z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-charcoal">--</p>
                        <p class="mt-1 text-xs text-charcoal/50">Awaiting resolution</p>
                    </div>

                    <div
                        class="group rounded-3xl border border-white/50 bg-white/45 p-5 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.15)] backdrop-blur-xl transition hover:bg-white/60">
                        <div class="flex items-start justify-between">
                            <p class="text-xs font-medium uppercase tracking-wide text-charcoal/45">Commission earned
                            </p>
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-400/15 text-emerald-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 8c-1.66 0-3 .9-3 2s1.34 2 3 2 3 .9 3 2-1.34 2-3 2m0-8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-charcoal">₱ --</p>
                        <p class="mt-1 text-xs text-charcoal/50">This month · 10%</p>
                    </div>

                </div>

                <!-- CHARTS -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                    <div
                        class="rounded-3xl border border-white/50 bg-white/45 p-5 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.15)] backdrop-blur-xl lg:col-span-2">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-charcoal">Platform registrations</h3>
                            <p class="text-xs text-charcoal/50">New accounts by role, last 6 months</p>
                        </div>
                        <canvas id="registrationsChart" height="140"></canvas>
                    </div>

                    <div
                        class="rounded-3xl border border-white/50 bg-white/45 p-5 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.15)] backdrop-blur-xl">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-charcoal">Commission revenue</h3>
                            <p class="text-xs text-charcoal/50">Last 6 months</p>
                        </div>
                        <canvas id="commissionChart" height="220"></canvas>
                    </div>

                </div>

            </main>

        </div>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        const PRIMARY_BLUE = '#2563EB'; // TODO: match your actual tailwind `primary` hex if different
        const SKY_BLUE = '#38BDF8';
        const CHARCOAL_MUTED = 'rgba(107, 114, 128, 0.55)';
        const GRID_LINE = 'rgba(15, 23, 42, 0.06)';

        Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
        Chart.defaults.color = 'rgba(30, 41, 59, 0.55)';

        // --- Platform Registrations (grouped bar chart) ---
        new Chart(document.getElementById('registrationsChart'), {
            type: 'bar',
            data: {
                labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                datasets: [{
                        label: 'Buyers',
                        data: [0, 0, 0, 0, 0, 0], // TODO: replace with real counts
                        backgroundColor: PRIMARY_BLUE,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 18,
                    },
                    {
                        label: 'Sellers',
                        data: [0, 0, 0, 0, 0, 0], // TODO: replace with real counts
                        backgroundColor: SKY_BLUE,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 18,
                    },
                    {
                        label: 'Logistics',
                        data: [0, 0, 0, 0, 0, 0], // TODO: replace with real counts
                        backgroundColor: CHARCOAL_MUTED,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 18,
                    },
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 16,
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: GRID_LINE
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                }
            }
        });

        // --- Commission Revenue (line chart) ---
        new Chart(document.getElementById('commissionChart'), {
            type: 'line',
            data: {
                labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                datasets: [{
                    label: 'Commission (₱)',
                    data: [0, 0, 0, 0, 0, 0], // TODO: replace with real commission totals
                    borderColor: PRIMARY_BLUE,
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: PRIMARY_BLUE,
                    pointBorderWidth: 2,
                    borderWidth: 2.5,
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
                            color: GRID_LINE
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                }
            }
        });
    </script>

</body>

</html>
