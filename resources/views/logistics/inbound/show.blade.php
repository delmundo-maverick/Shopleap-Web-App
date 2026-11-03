@php
    $field = 'frost-input rounded-xl px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $received = $bag->items->whereNotNull('received_at')->count();
    $pending = $bag->items->whereNull('received_at')->count();
    $canReceive = in_array($bag->status, [
        \App\Models\ShipmentBag::STATUS_SEALED,
        \App\Models\ShipmentBag::STATUS_IN_TRANSIT,
        \App\Models\ShipmentBag::STATUS_DISCREPANCY,
    ], true);
@endphp

<x-logistics.layout title="Receive {{ $bag->code }}" subtitle="{{ $bag->fromHub?->business_name }} → {{ $bag->toHub?->business_name }}"
    active="inbound">

    <div class="frost-panel-solid rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-mono text-2xl font-bold tracking-tight text-charcoal">{{ $bag->code }}</h2>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-bold {{ $bag->status_color }}">{{ $bag->status_label }}</span>
                </div>

                <p class="mt-2 text-sm text-charcoal/70">
                    From {{ $bag->fromHub?->business_name }} · dispatched
                    {{ $bag->dispatched_at?->format('M d, Y g:i A') ?? '—' }}
                </p>
                <p class="mt-1 text-xs text-charcoal/50">
                    {{ $received }} of {{ $bag->items->count() }} parcel(s) scanned
                    @if ($bag->received_at)
                        · Received {{ $bag->received_at->format('M d, g:i A') }}
                        by {{ $bag->receiver?->name ?? '—' }}
                    @endif
                </p>
            </div>

            <a href="{{ route('logistics.inbound.index') }}"
                class="rounded-xl border border-charcoal/15 px-4 py-2.5 text-sm font-semibold text-charcoal/70 transition hover:bg-charcoal/5">
                Back
            </a>
        </div>

        <div class="mt-5 border-t border-white/50 pt-4">
            <div class="h-3 overflow-hidden rounded-full bg-light-gray shadow-inner">
                <div class="h-full bg-gradient-to-r from-primary to-leaf-green transition-all duration-300"
                    style="width: {{ $bag->items->count() > 0 ? ($received / $bag->items->count()) * 100 : 0 }}%"></div>
            </div>
        </div>
    </div>

    @if (!$verified)
        <div class="frost-panel-solid rounded-2xl p-6">
            <h3 class="text-lg font-bold text-charcoal">Step 1 — Scan the bag</h3>
            <p class="mt-1 text-xs text-charcoal/55">
                Scan or type the bag code to confirm you are receiving the right bag.
            </p>

            <form method="POST" action="{{ route('logistics.inbound.verify', $bag) }}" class="mt-4 flex gap-2">
                @csrf
                <input type="text" name="code" required autofocus autocomplete="off" placeholder="Scan bag code…"
                    class="{{ $field }} flex-1 font-mono">
                <button type="submit"
                    class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-blue">
                    Verify
                </button>
            </form>
        </div>
    @else
        @if ($canReceive)
            <div class="grid gap-5 lg:grid-cols-2">
                <div class="frost-panel-solid rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-charcoal">Step 2 — Scan each parcel</h3>
                    <p class="mt-1 text-xs text-charcoal/55">
                        {{ $pending }} parcel(s) still waiting to be scanned.
                    </p>

                    <form method="POST" action="{{ route('logistics.inbound.scan', $bag) }}" class="mt-4 flex gap-2">
                        @csrf
                        <input type="text" name="code" required autofocus autocomplete="off"
                            placeholder="Scan parcel code…" class="{{ $field }} flex-1 font-mono">
                        <button type="submit"
                            class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-blue">
                            Scan
                        </button>
                    </form>
                </div>

                <div class="frost-panel-solid rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-charcoal">Step 3 — Close the bag</h3>
                    <p class="mt-1 text-xs text-charcoal/55">
                        Unscanned parcels will be flagged as missing and both hubs will be notified.
                    </p>

                    <form method="POST" action="{{ route('logistics.inbound.close', $bag) }}" class="mt-4"
                        onsubmit="return confirm('Close bag {{ $bag->code }} with {{ $pending }} unscanned parcel(s)? Unscanned parcels will be flagged as missing.')">
                        @csrf
                        <button type="submit"
                            class="w-full rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110">
                            Close Bag &amp; Confirm Receipt
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                This bag has already been received.
            </div>
        @endif
    @endif

    <div class="frost-panel-solid overflow-hidden rounded-2xl">
        <div class="flex items-center justify-between border-b border-white/50 px-5 py-4">
            <h3 class="text-lg font-bold text-charcoal">Parcels</h3>
            <p class="text-xs text-charcoal/50">{{ $received }} received · {{ $pending }} pending</p>
        </div>

        @if ($bag->items->isEmpty())
            <div class="flex h-32 flex-col items-center justify-center text-center">
                <p class="text-sm font-semibold text-charcoal">This bag is empty</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/50 bg-primary/5">
                        <tr>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Order</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Seller</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Destination</th>
                            <th class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                            @if ($verified && $canReceive)
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
                                <td class="px-5 py-4 text-charcoal/70">
                                    {{ $item->order?->ship_to['municipality'] ?? '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    @if ($item->received_at)
                                        <span
                                            class="rounded-full bg-leaf-green/10 px-2.5 py-1 text-xs font-bold text-leaf-green">Received</span>
                                    @elseif ($bag->status === \App\Models\ShipmentBag::STATUS_DISCREPANCY)
                                        <span
                                            class="rounded-full bg-sale-red/10 px-2.5 py-1 text-xs font-bold text-sale-red">Missing</span>
                                    @else
                                        <span
                                            class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-bold text-orange-600">Pending</span>
                                    @endif
                                </td>
                                @if ($verified && $canReceive)
                                    <td class="px-5 py-4 text-right">
                                        @unless ($item->received_at)
                                            <form method="POST"
                                                action="{{ route('logistics.inbound.parcels.confirm', [$bag, $item->order_id]) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-lg border border-primary/30 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary/5">
                                                    Confirm
                                                </button>
                                            </form>
                                        @endunless
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
