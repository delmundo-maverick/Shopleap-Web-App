<x-logistics.layout title="Incoming Parcels" :subtitle="$orders->total() . ' parcel(s) en route to this hub'" active="incoming">

    <!-- SEARCH -->
    <form method="GET" class="frost-panel flex gap-3 rounded-2xl p-4">
        <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by order number..."
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
                <p class="mt-1 text-xs text-charcoal/50">Parcels picked up by your riders will appear here until
                    logged as arrived.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="app-table w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Order</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Seller</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Pickup Rider</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Items</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr class="transition hover:bg-white/40">
                                <td class="px-5 py-4 font-semibold text-charcoal">#{{ $order->id }}</td>
                                <td class="px-5 py-4 text-charcoal/70">
                                    {{ $order->seller->sellerProfile->business_name ?? $order->seller->name }}
                                </td>
                                <td class="px-5 py-4 text-charcoal/70">
                                    {{ $order->pickupRider?->full_name ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-charcoal/70">{{ $order->items->sum('quantity') }} item(s)</td>
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
            </div>
        @endif
    </div>

    <div>{{ $orders->links() }}</div>

</x-logistics.layout>
