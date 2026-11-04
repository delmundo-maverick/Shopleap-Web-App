@php
    $field = 'frost-input rounded-lg px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $received = $bag->items->whereNotNull('received_at')->count();
    $canEdit = $bag->isOpen();
    $destinationProvince = $bag->toHub?->province;
@endphp

<x-logistics.layout title="Bag {{ $bag->code }}" subtitle="{{ $bag->fromHub?->business_name }} → {{ $bag->toHub?->business_name }}"
    active="outbound" :scripts="['resources/js/pages/logistics/outbound-show.js']">

    <div class="frost-panel-solid rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold tracking-tight text-charcoal">{{ $bag->code }}</h2>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-bold {{ $bag->status_color }}">{{ $bag->status_label }}</span>
                    @if ($bag->flagged_at)
                        <span class="rounded-full bg-sale-red/10 px-3 py-1 text-xs font-bold text-sale-red">
                            {{ $bag->flag_note }}
                        </span>
                    @endif
                </div>

                <p class="mt-2 text-sm text-charcoal/70">
                    {{ $bag->fromHub?->business_name }} → {{ $bag->toHub?->business_name }}
                    @if ($destinationProvince)
                        · {{ $destinationProvince }}
                    @endif
                </p>
                <p class="mt-1 text-xs text-charcoal/50">
                    Created {{ $bag->created_at?->format('M d, Y g:i A') }}
                    @if ($bag->sealed_at)
                        · Sealed {{ $bag->sealed_at->format('M d, g:i A') }}
                    @endif
                    @if ($bag->dispatched_at)
                        · Dispatched {{ $bag->dispatched_at->format('M d, g:i A') }}
                    @endif
                    @if ($bag->received_at)
                        · Received {{ $bag->received_at->format('M d, g:i A') }}
                    @endif
                </p>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <a href="{{ route('logistics.outbound.manifest', $bag) }}" target="_blank" rel="noopener"
                    data-print-manifest @if ($bag->isOpen() && ! $bag->manifest_printed_at) data-manifest-reload @endif
                    class="btn btn-outline text-primary border-primary/30">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                        </path>
                    </svg>
                    Print Manifest
                </a>
                <a href="{{ route('logistics.outbound.index') }}" class="btn btn-outline">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                        </path>
                    </svg>
                    Back
                </a>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 border-t border-white/50 pt-4 text-sm sm:grid-cols-4">
            <div>
                <p class="text-xs text-charcoal/50">Parcels</p>
                <p class="font-bold text-charcoal">{{ $bag->items->count() }}</p>
            </div>
            <div>
                <p class="text-xs text-charcoal/50">Received</p>
                <p class="font-bold text-leaf-green">{{ $received }}</p>
            </div>
            <div>
                <p class="text-xs text-charcoal/50">Sealed by</p>
                <p class="font-semibold text-charcoal">{{ $bag->sealer?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-charcoal/50">Received by</p>
                <p class="font-semibold text-charcoal">{{ $bag->receiver?->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    @if ($canEdit)
        <div class="grid gap-5 lg:grid-cols-2">
            {{-- SCAN --}}
            <div class="frost-panel-solid rounded-2xl p-6">
                <h3 class="text-lg font-bold text-charcoal">Scan a Parcel</h3>
                <p class="mt-1 text-xs text-charcoal/55">Scan the waybill barcode or type the order number.</p>

                <form method="POST" action="{{ route('logistics.outbound.scan', $bag) }}"
                    class="mt-4 flex flex-col gap-2 sm:flex-row" data-loading>
                    @csrf
                    <input type="text" name="code" required autofocus autocomplete="off" placeholder="Scan or type code…"
                        class="{{ $field }} min-w-0 flex-1 font-mono">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add
                    </button>
                </form>
            </div>

            {{-- SEAL --}}
            <div class="frost-panel-solid rounded-2xl p-6">
                <h3 class="text-lg font-bold text-charcoal">Seal the Bag</h3>
                <p class="mt-1 text-xs text-charcoal/55">
                    Sealing locks the parcel list. Dispatch it afterwards to mark it in transit.
                </p>

                <form method="POST" action="{{ route('logistics.outbound.seal', $bag) }}" class="mt-4"
                    data-loading
                    data-confirm="Seal bag {{ $bag->code }} with {{ $bag->items->count() }} parcel(s)?">
                    @csrf
                    <button type="submit" @disabled($bag->items->isEmpty() || ! $bag->manifest_printed_at)
                        class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7"></path>
                        </svg>
                        Seal Bag
                    </button>

                    @if ($bag->items->isEmpty())
                        <p class="mt-2 text-xs text-charcoal/55">Add at least one parcel before sealing.</p>
                    @elseif (! $bag->manifest_printed_at)
                        <p class="mt-2 text-xs text-charcoal/55">Print the manifest first.</p>
                    @else
                        <p class="mt-2 flex items-center gap-1.5 text-xs font-medium text-leaf-green">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Manifest printed. Ready to seal.
                        </p>
                    @endif
                </form>
            </div>
        </div>

        {{-- ADD ORDERS --}}
        <div x-data="outboundShow()" class="frost-panel-solid rounded-2xl p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-charcoal">Parcels for {{ $bag->toHub?->business_name }}</h3>
                    <p class="mt-1 text-xs text-charcoal/55">
                        Destination province:
                        <span class="font-semibold text-charcoal">{{ $destinationProvince ?: 'Not set' }}</span>
                        · Only parcels headed to this province are shown.
                    </p>
                </div>
                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                    {{ $available->count() }} available
                </span>
            </div>

            @if ($available->isEmpty())
                <div
                    class="mt-4 flex h-32 flex-col items-center justify-center rounded-xl border-2 border-dashed border-charcoal/10 px-4 text-center">
                    <p class="text-sm font-semibold text-charcoal">No parcels headed to
                        {{ $destinationProvince ?: 'this province' }}</p>
                    <p class="mt-1 text-xs text-charcoal/50">Sort more parcels to this destination first.</p>
                </div>
            @else
                <form method="POST" action="{{ route('logistics.outbound.orders.store', $bag) }}"
                    class="mt-4 space-y-3" data-loading>
                    @csrf

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative min-w-0 flex-1">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-charcoal/40"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                            </svg>
                            <input type="search" x-model="search" @input="onSearchInput()"
                                placeholder="Search order, seller, or municipality"
                                aria-label="Search parcels to add"
                                class="{{ $field }} w-full pl-9">
                        </div>

                        <label
                            class="flex min-h-10 shrink-0 cursor-pointer items-center gap-2 rounded-lg border border-charcoal/15 bg-white px-3 py-2 text-xs font-semibold text-charcoal">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                                class="h-4 w-4 rounded border-charcoal/30 text-primary focus:ring-primary/30">
                            Select all visible
                        </label>

                        <span class="text-xs font-medium text-charcoal/60">
                            <span class="font-bold text-charcoal" x-text="selectedCount"></span> selected
                        </span>
                    </div>

                    <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                        @foreach ($available as $order)
                            <label data-parcel-row
                                data-search="#{{ $order->id }} {{ $order->seller?->sellerProfile?->business_name ?? $order->seller?->name }} {{ $order->ship_to['municipality'] ?? '' }} {{ $order->ship_to['barangay'] ?? '' }} {{ $order->ship_to['province'] ?? '' }}"
                                x-show="matches($el.dataset.search)"
                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-white/60 bg-white/40 p-3 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                <input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
                                    data-parcel-checkbox @change="onCheckboxChange()"
                                    class="mt-0.5 h-4 w-4 rounded border-charcoal/30 text-primary focus:ring-primary/30">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-bold text-charcoal">#{{ $order->id }} ·
                                        {{ $order->items->sum('quantity') }} item(s)</span>
                                    <span class="block text-xs text-charcoal/60">
                                        {{ $order->seller?->sellerProfile?->business_name ?? $order->seller?->name }}
                                        · {{ $order->ship_to['municipality'] ?? '' }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div x-show="search.trim() !== '' && visibleCount === 0" x-cloak
                        class="flex h-24 flex-col items-center justify-center rounded-lg border-2 border-dashed border-charcoal/10 px-4 text-center">
                        <p class="text-sm font-semibold text-charcoal">No parcels match your search</p>
                        <button type="button" @click="clearSearch()"
                            class="mt-1 text-xs font-semibold text-primary hover:underline">Clear search</button>
                    </div>

                    <button type="submit" :disabled="selectedCount === 0"
                        class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Selected Parcels (<span x-text="selectedCount"></span>)
                    </button>
                </form>
            @endif
        </div>
    @elseif ($bag->isSealed())
        <div class="frost-panel-solid rounded-2xl p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-charcoal">Ready to dispatch</h3>
                    <p class="mt-1 text-xs text-charcoal/55">
                        Bag is sealed with {{ $bag->items->count() }} parcel(s). Mark it in transit when it leaves the hub.
                    </p>
                </div>
                <form method="POST" action="{{ route('logistics.outbound.dispatch', $bag) }}" data-loading
                    data-confirm="Mark bag {{ $bag->code }} as in transit?">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Mark Dispatched
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- PARCEL LIST --}}
    <div class="frost-panel-solid overflow-hidden rounded-2xl">
        <div class="border-b border-white/50 px-5 py-4">
            <h3 class="text-lg font-bold text-charcoal">Parcels in this Bag</h3>
        </div>

        @if ($bag->items->isEmpty())
            <div class="flex h-32 flex-col items-center justify-center text-center">
                <p class="text-sm font-semibold text-charcoal">The bag is empty</p>
                <p class="mt-1 text-xs text-charcoal/50">Scan or select parcels above.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/50 bg-primary/5">
                        <tr>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Order</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Seller</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Items</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Received</th>
                            @if ($canEdit)
                                <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/50">
                        @foreach ($bag->items as $item)
                            <tr class="transition hover:bg-white/40">
                                <td class="px-5 py-4 font-bold text-charcoal">#{{ $item->order_id }}</td>
                                <td class="px-5 py-4 text-charcoal/70">
                                    {{ $item->order?->seller?->sellerProfile?->business_name ?? $item->order?->seller?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-charcoal/70">{{ $item->order?->items?->sum('quantity') ?? '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    @if ($item->received_at)
                                        <span
                                            class="rounded-full bg-leaf-green/10 px-2.5 py-1 text-xs font-bold text-leaf-green">Received</span>
                                    @else
                                        <span
                                            class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-bold text-orange-600">Pending</span>
                                    @endif
                                </td>
                                @if ($canEdit)
                                    <td class="px-5 py-4 text-right">
                                        <form method="POST"
                                            action="{{ route('logistics.outbound.orders.destroy', [$bag, $item->order_id]) }}"
                                            data-loading
                                            data-confirm="Remove order #{{ $item->order_id }} from this bag?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-logistics.layout>
