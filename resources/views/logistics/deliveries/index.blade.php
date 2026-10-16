@php
    $tabLabels = [
        'assigned' => 'Assigned',
        'out' => 'Out for Delivery',
        'failed' => 'Failed',
        'delivered' => 'Delivered',
    ];
    $field = 'frost-input rounded-xl px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $max = \App\Models\Order::MAX_DELIVERY_ATTEMPTS;
@endphp

<x-logistics.layout title="Deliveries" :subtitle="$orders->total() . ' parcel(s) in this view'" active="deliveries">

    <div class="frost-panel rounded-2xl px-4 py-3 text-sm">
        <span class="text-charcoal/60">Shipping fees earned (delivered parcels):</span>
        <b class="text-primary">₱{{ number_format($shippingEarned, 2) }}</b>
    </div>

    <div class="frost-panel-solid flex overflow-x-auto rounded-2xl">
        @foreach ($tabLabels as $key => $label)
            <a href="{{ route('logistics.deliveries.index', ['tab' => $key]) }}"
                class="flex min-w-max flex-1 items-center justify-center gap-1.5 border-b-2 px-4 py-3.5 text-sm font-semibold transition
                    {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/50 hover:text-charcoal' }}">
                {{ $label }}
                @if ($counts[$key] > 0)
                    <span
                        class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $tab === $key ? 'bg-primary/10 text-primary' : 'bg-light-gray text-charcoal/50' }}">{{ $counts[$key] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <form method="GET" class="frost-panel flex gap-3 rounded-2xl p-4">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by order number…"
            class="frost-input flex-1 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-4 focus:ring-primary/10">
        <button type="submit"
            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">Search</button>
    </form>

    @if ($orders->isEmpty())
        <div class="frost-panel-solid flex h-56 flex-col items-center justify-center rounded-2xl text-center">
            <p class="text-sm font-semibold text-charcoal">No parcels in this view</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($orders as $order)
                @php $ship = $order->ship_to; @endphp

                <div class="frost-panel-solid rounded-2xl p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-charcoal">Order #{{ $order->id }} · {{ $ship['name'] }}
                            </p>
                            <p class="mt-0.5 text-xs text-charcoal/60">
                                {{ $ship['street'] }}, {{ $ship['barangay'] }}, {{ $ship['municipality'] }},
                                {{ $ship['province'] }}
                            </p>
                            <p class="text-xs text-charcoal/60">Contact: {{ $ship['contact_no'] ?? '—' }}</p>
                            <p class="mt-1 text-xs text-charcoal/50">
                                Rider: {{ $order->deliveryRider?->full_name ?? '—' }}
                                @if ($order->delivery_area)
                                    · Area: {{ $order->delivery_area }}
                                @endif
                                · Attempts: {{ $order->delivery_attempts }}/{{ $max }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-charcoal/50">Cash on delivery</p>
                            <p class="text-lg font-bold text-primary">₱{{ number_format($order->grand_total, 2) }}</p>
                            <p class="text-[11px] text-charcoal/50">
                                Items ₱{{ number_format($order->total_amount, 2) }} + shipping
                                ₱{{ number_format($order->shipping_fee, 2) }}
                            </p>
                        </div>
                    </div>

                    @if ($tab === 'failed')
                        @php $lastFailure = $order->events->where('status', 'DELIVERY_FAILED')->last(); @endphp
                    @endif

                    <div class="mt-4 border-t border-white/50 pt-4">
                        @if ($tab === 'assigned')
                            <form method="POST" action="{{ route('logistics.deliveries.out', $order) }}">
                                @csrf
                                <button type="submit"
                                    class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">Mark
                                    Out for Delivery</button>
                            </form>
                        @elseif ($tab === 'out')
                            <div class="flex flex-wrap items-start gap-3">
                                <form method="POST" action="{{ route('logistics.deliveries.delivered', $order) }}"
                                    onsubmit="return confirm('Confirm the parcel was handed over and ₱{{ number_format($order->grand_total, 2) }} was collected?')"
                                    @csrf <button type="submit"
                                    class="rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110">
                                    Mark
                                    Delivered</button>
                                </form>

                                <details class="min-w-[18rem] flex-1">
                                    <summary
                                        class="cursor-pointer rounded-xl border border-sale-red/30 px-4 py-2.5 text-sm font-semibold text-sale-red">
                                        Mark Failed…</summary>
                                    <form method="POST" action="{{ route('logistics.deliveries.failed', $order) }}"
                                        class="mt-3 flex flex-wrap gap-2">
                                        @csrf
                                        <select name="reason" required class="{{ $field }}">
                                            <option value="" disabled selected>Reason…</option>
                                            @foreach ($reasons as $reason)
                                                <option value="{{ $reason }}">{{ $reason }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="note" maxlength="200"
                                            placeholder="Note (optional)" class="{{ $field }} flex-1">
                                        <button type="submit"
                                            class="rounded-xl bg-sale-red px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Record</button>
                                    </form>
                                </details>
                            </div>
                        @elseif ($tab === 'failed')
                            @if ($order->delivery_attempts < $max)
                                <form method="POST" action="{{ route('logistics.deliveries.reschedule', $order) }}"
                                    class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <select name="rider_id" class="{{ $field }}">
                                        <option value="">Same rider
                                            ({{ $order->deliveryRider?->full_name ?? 'unassigned' }})
                                        </option>
                                        @foreach ($riders as $rider)
                                            <option value="{{ $rider->id }}">{{ $rider->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-blue">Reschedule
                                        delivery</button>
                                </form>
                            @else
                                <p class="text-xs font-semibold text-orange-600">Maximum attempts reached. The parcel
                                    must be returned.</p>
                            @endif

                            <form method="POST" action="{{ route('logistics.deliveries.return', $order) }}"
                                class="mt-2"
                                onsubmit="return confirm('Return this parcel to the seller? Stock will be restored.')">
                                @csrf
                                <button type="submit"
                                    class="text-xs font-semibold text-sale-red/80 transition hover:text-sale-red">Return
                                    parcel to seller</button>
                            </form>
                        @else
                            <p class="text-xs text-charcoal/50">
                                Delivered {{ $order->updated_at->diffForHumans() }}. Shipping fee earned:
                                <b>₱{{ number_format($order->shipping_fee, 2) }}</b>. Waiting for the buyer to confirm
                                receipt.
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $orders->links() }}</div>
    @endif

</x-logistics.layout>
