<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Incoming Parcels | Shopleap Logistics</title>
</head>

<body class="min-h-screen text-charcoal">
    <div class="frost-surface"></div>

    <div class="flex min-h-screen">
        <x-navbars.logistics active="incoming" />

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
                        <h2 class="text-xl font-bold text-charcoal">Incoming Parcels</h2>
                        <p class="text-sm text-charcoal/55">{{ $orders->total() }} parcel(s) en route to this hub</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 space-y-5 p-6">

                @if (session('status'))
                    <div
                        class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- SEARCH -->
                <form method="GET" class="frost-panel flex gap-3 rounded-2xl p-4">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by order number..."
                            class="frost-input w-full rounded-xl py-2.5 pl-10 pr-4 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                    </div>
                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
                        Search
                    </button>
                </form>

                <!-- TABLE -->
                <div class="frost-panel-solid overflow-hidden rounded-2xl">
                    @if ($orders->isEmpty())
                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <p class="text-sm font-semibold text-charcoal">No incoming parcels</p>
                            <p class="mt-1 text-xs text-charcoal/50">Parcels picked up by your riders will appear here
                                until logged as arrived.</p>
                        </div>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-white/50 bg-primary/5">
                                <tr>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Order</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Seller</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Rider</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Items</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/50">
                                @foreach ($orders as $order)
                                    <tr class="transition hover:bg-white/40">
                                        <td class="px-5 py-4 font-semibold text-charcoal">#{{ $order->id }}</td>
                                        <td class="px-5 py-4 text-charcoal/70">
                                            {{ $order->seller->sellerProfile->business_name ?? $order->seller->name }}
                                        </td>
                                        <td class="px-5 py-4 text-charcoal/70">
                                            {{ $order->assignedRider?->full_name ?? '—' }}
                                        </td>
                                        <td class="px-5 py-4 text-charcoal/70">{{ $order->items->sum('quantity') }}
                                            item(s)</td>
                                        <td class="px-5 py-4 text-right">
                                            <form method="POST"
                                                action="{{ route('logistics.incoming.confirm-arrival', $order) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-blue">
                                                    Confirm Arrival
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div>{{ $orders->links() }}</div>

            </main>
        </div>
    </div>

</body>

</html>
