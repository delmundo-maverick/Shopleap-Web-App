<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Dashboard | Shopleap Logistics</title>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    <div class="flex min-h-screen">
        <x-navbars.logistics active="dashboard" />

        <div class="flex flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" @click="$store.sidebar.open = true"
                        class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Dashboard</h2>
                        <p class="text-sm text-charcoal/55">Sorting center operations overview</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6">

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Pending Rider Applications
                        </p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">{{ $stats['pending_riders'] }}</p>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Active Riders</p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">{{ $stats['active_riders'] }}</p>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Parcels Awaiting Sort</p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">{{ $stats['parcels_awaiting_sort'] }}</p>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Out for Delivery</p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">{{ $stats['parcels_out_for_delivery'] }}</p>
                    </div>

                </div>

            </main>
        </div>
    </div>

</body>

</html>
