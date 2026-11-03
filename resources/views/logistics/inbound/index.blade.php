<x-logistics.layout title="Inbound Bags" subtitle="Bags addressed to this hub that still need to be received" active="inbound">

    <div class="space-y-4">
        <h2 class="text-section-label">Incoming Bags</h2>

        @if ($incoming->isEmpty())
            <div class="frost-panel-solid flex h-40 flex-col items-center justify-center rounded-2xl text-center">
                <p class="text-sm font-semibold text-charcoal">No bags on the way</p>
                <p class="mt-1 text-xs text-charcoal/50">Bags dispatched to this hub will appear here.</p>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($incoming as $bag)
                    <div class="frost-panel-solid rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-base font-bold text-charcoal">{{ $bag->code }}</p>
                                <p class="mt-1 text-sm text-charcoal/70">
                                    From {{ $bag->fromHub?->business_name ?? '—' }}
                                </p>
                                <p class="mt-0.5 text-xs text-charcoal/50">
                                    Dispatched {{ $bag->dispatched_at?->format('M d, Y g:i A') ?? '—' }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $bag->status_color }}">{{ $bag->status_label }}</span>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-white/50 pt-4">
                            <p class="text-xs text-charcoal/60">
                                {{ $bag->items_count }} parcel(s)
                                @if ($bag->received_items_count > 0)
                                    · {{ $bag->received_items_count }} scanned
                                @endif
                            </p>
                            <a href="{{ route('logistics.inbound.show', $bag) }}"
                                class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-blue">
                                Receive Bag
                            </a>
                        </div>

                        @if ($bag->flagged_at)
                            <p class="mt-2 rounded-lg bg-sale-red/10 px-3 py-2 text-xs font-semibold text-sale-red">
                                {{ $bag->flag_note }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <h2 class="text-section-label">Recently Received</h2>

        <div class="frost-panel-solid overflow-hidden rounded-2xl">
            @if ($recent->isEmpty())
                <div class="flex h-32 flex-col items-center justify-center text-center">
                    <p class="text-sm font-semibold text-charcoal">No received bags yet</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-white/50 bg-primary/5">
                            <tr>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Bag</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">From</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Parcels</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Received</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/50">
                            @foreach ($recent as $bag)
                                <tr class="transition hover:bg-white/40">
                                    <td class="px-5 py-4 font-mono font-bold text-charcoal">{{ $bag->code }}</td>
                                    <td class="px-5 py-4 text-charcoal/70">{{ $bag->fromHub?->business_name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-charcoal/70">
                                        {{ $bag->received_items_count }} / {{ $bag->items_count }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-bold {{ $bag->status_color }}">{{ $bag->status_label }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-charcoal/60">
                                        {{ $bag->received_at?->format('M d, g:i A') ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('logistics.inbound.show', $bag) }}"
                                            class="rounded-lg border border-primary/30 px-3.5 py-2 text-xs font-semibold text-primary transition hover:bg-primary/5">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div>{{ $recent->links() }}</div>
    </div>

</x-logistics.layout>
