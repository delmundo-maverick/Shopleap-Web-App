<x-logistics.layout title="Outbound Transfer" subtitle="Bag parcels for another hub of your company, seal it, and dispatch it"
    active="outbound">

    @php
        $awaitingCount = $awaiting->flatten()->count();
        $openBags = $counts['open'];
        $inTransit = $counts['in_transit'];
    @endphp

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="frost-panel rounded-2xl p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Awaiting Transfer</p>
            <p class="mt-2 text-3xl font-bold text-charcoal">{{ $awaitingCount }}</p>
            <p class="mt-1 text-xs text-charcoal/50">Parcels sorted for another hub</p>
        </div>
        <div class="frost-panel rounded-2xl p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Open Bags</p>
            <p class="mt-2 text-3xl font-bold text-charcoal">{{ $openBags }}</p>
            <p class="mt-1 text-xs text-charcoal/50">Bags still being packed</p>
        </div>
        <div class="frost-panel rounded-2xl p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">In Transit</p>
            <p class="mt-2 text-3xl font-bold text-primary">{{ $inTransit }}</p>
            <p class="mt-1 text-xs text-charcoal/50">Bags on the road</p>
        </div>
    </div>

    {{-- AWAITING TRANSFER, GROUPED BY DESTINATION HUB --}}
    <div class="space-y-4">
        <h2 class="text-section-label">Parcels Awaiting Transfer</h2>

        @if ($awaiting->isEmpty())
            <div class="frost-panel-solid flex h-40 flex-col items-center justify-center rounded-2xl text-center">
                <p class="text-sm font-semibold text-charcoal">Nothing to transfer</p>
                <p class="mt-1 text-xs text-charcoal/50">Parcels sorted for another hub will appear here.</p>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($awaiting as $hubId => $orders)
                    @php $destination = $orders->first()->destinationHub; @endphp

                    <div class="frost-panel-solid rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-charcoal">{{ $destination?->business_name }}</p>
                                <p class="mt-0.5 text-xs text-charcoal/60">
                                    {{ $destination?->municipality }}, {{ $destination?->province }}
                                    @if ($destination?->company_name)
                                        · {{ $destination->company_name }}
                                    @endif
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full bg-orange-100 px-2.5 py-1 text-xs font-bold text-orange-600">
                                {{ $orders->count() }} parcel(s)
                            </span>
                        </div>

                        <ul class="mt-3 space-y-1 text-xs text-charcoal/70">
                            @foreach ($orders->take(4) as $order)
                                <li>#{{ $order->id }} · {{ $order->items->sum('quantity') }} item(s)</li>
                            @endforeach
                            @if ($orders->count() > 4)
                                <li class="text-charcoal/50">+ {{ $orders->count() - 4 }} more</li>
                            @endif
                        </ul>

                        <form method="POST" action="{{ route('logistics.outbound.bags.store') }}" class="mt-4">
                            @csrf
                            <input type="hidden" name="to_hub_id" value="{{ $destination?->id }}">
                            <button type="submit"
                                class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
                                Open Bag for {{ $destination?->business_name }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- BAGS SENT BY THIS HUB --}}
    <div class="space-y-4">
        <h2 class="text-section-label">Bags Sent by {{ $hub->business_name }}</h2>

        <div class="frost-panel-solid overflow-hidden rounded-2xl">
            @if ($bags->isEmpty())
                <div class="flex h-40 flex-col items-center justify-center text-center">
                    <p class="text-sm font-semibold text-charcoal">No bags yet</p>
                    <p class="mt-1 text-xs text-charcoal/50">Open a bag above to start grouping parcels.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-white/50 bg-primary/5">
                            <tr>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Bag</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Destination</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Parcels</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Dispatched</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/50">
                            @foreach ($bags as $bag)
                                <tr class="transition hover:bg-white/40">
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-charcoal">{{ $bag->code }}</p>
                                        @if ($bag->flagged_at)
                                            <p class="text-[11px] font-semibold text-sale-red">Flagged:
                                                {{ $bag->flag_note }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-charcoal/70">{{ $bag->toHub?->business_name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-charcoal/70">
                                        {{ $bag->items_count }} @if ($bag->status === \App\Models\ShipmentBag::STATUS_RECEIVED || $bag->status === \App\Models\ShipmentBag::STATUS_DISCREPANCY)
                                            ({{ $bag->received_items_count }} received)
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-bold {{ $bag->status_color }}">{{ $bag->status_label }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-charcoal/60">
                                        {{ $bag->dispatched_at?->format('M d, g:i A') ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('logistics.outbound.show', $bag) }}"
                                            class="rounded-lg border border-primary/30 px-3.5 py-2 text-xs font-semibold text-primary transition hover:bg-primary/5">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div>{{ $bags->links() }}</div>
    </div>

</x-logistics.layout>
