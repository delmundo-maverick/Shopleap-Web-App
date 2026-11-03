@php
    $field = 'frost-input rounded-xl px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $received = $bag->items->whereNotNull('received_at')->count();
    $canEdit = $bag->isOpen();
@endphp

<x-logistics.layout title="Bag {{ $bag->code }}" subtitle="{{ $bag->fromHub?->business_name }} → {{ $bag->toHub?->business_name }}"
    active="outbound">

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

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('logistics.outbound.manifest', $bag) }}" target="_blank"
                    class="rounded-xl border border-primary/30 px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary/5">
                    Print Manifest
                </a>
                <a href="{{ route('logistics.outbound.index') }}"
                    class="rounded-xl border border-charcoal/15 px-4 py-2.5 text-sm font-semibold text-charcoal/70 transition hover:bg-charcoal/5">
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

                <form method="POST" action="{{ route('logistics.outbound.scan', $bag) }}" class="mt-4 flex gap-2">
                    @csrf
                    <input type="text" name="code" required autofocus autocomplete="off" placeholder="Scan or type code…"
                        class="{{ $field }} flex-1 font-mono">
                    <button type="submit"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-blue">
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
                    onsubmit="return confirm('Seal bag {{ $bag->code }} with {{ $bag->items->count() }} parcel(s)?')">
                    @csrf
                    <button type="submit" @disabled($bag->items->isEmpty())
                        class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40">
                        Seal Bag
                    </button>
                </form>
            </div>
        </div>

        {{-- ADD ORDERS --}}
        <div class="frost-panel-solid rounded-2xl p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-charcoal">Parcels for {{ $bag->toHub?->business_name }}</h3>
                    <p class="mt-1 text-xs text-charcoal/55">Select the parcels to include in this bag.</p>
                </div>
                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                    {{ $available->count() }} available
                </span>
            </div>

            @if ($available->isEmpty())
                <div class="mt-4 flex h-32 flex-col items-center justify-center rounded-xl border-2 border-dashed border-charcoal/10 text-center">
                    <p class="text-sm font-semibold text-charcoal">No parcels waiting for this hub</p>
                    <p class="mt-1 text-xs text-charcoal/50">Sort more parcels to this destination first.</p>
                </div>
            @else
                <form method="POST" action="{{ route('logistics.outbound.orders.store', $bag) }}" class="mt-4 space-y-3">
                    @csrf

                    <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                        @foreach ($available as $order)
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/60 bg-white/40 p-3 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                <input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
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

                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
                        Add Selected Parcels
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
                <form method="POST" action="{{ route('logistics.outbound.dispatch', $bag) }}"
                    onsubmit="return confirm('Mark bag {{ $bag->code }} as in transit?')">
                    @csrf
                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
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
                                            onsubmit="return confirm('Remove order #{{ $item->order_id }} from this bag?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-lg border border-sale-red/30 px-3 py-1.5 text-xs font-semibold text-sale-red transition hover:bg-sale-red/5">
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
