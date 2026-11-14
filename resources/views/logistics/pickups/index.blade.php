@php
    $today = now()->startOfDay();
@endphp

<x-logistics.layout title="Pickup Requests" :subtitle="$orders->total() . ' request(s) in this view'" active="pickups">
    @if ($riders->isEmpty())
        <div class="frost-panel rounded-2xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
            You have no active approved riders yet. Approve and activate riders under Rider Applications first.
        </div>
    @endif

    {{-- TABS --}}
    <div class="frost-panel-solid flex rounded-2xl">
        @foreach (['to_assign' => 'To Assign', 'scheduled' => 'Scheduled'] as $key => $label)
            <a href="{{ route('logistics.pickups.index', ['tab' => $key]) }}"
                class="flex flex-1 items-center justify-center gap-1.5 border-b-2 px-3 py-3.5 text-sm font-semibold transition
                    {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/50 hover:text-charcoal' }}">
                {{ $label }}

                @if ($counts[$key] > 0)
                    <span
                        class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $tab === $key ? 'bg-primary/10 text-primary' : 'bg-light-gray text-charcoal/50' }}">
                        {{ $counts[$key] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- EMPTY STATE --}}
    @if ($orders->isEmpty())
        <div class="frost-panel-solid flex h-56 flex-col items-center justify-center rounded-2xl text-center">
            <p class="text-sm font-semibold text-charcoal">
                No pickup requests here
            </p>

            <p class="mt-1 text-xs text-charcoal/50">
                Sellers choose your hub when they mark an order ready for pickup.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($orders as $order)
                @php
                    $seller = $order->seller->sellerProfile;
                    $due = $order->pickup_date;

                    if (!$due) {
                        $badge = ['No date', 'bg-light-gray text-charcoal/60'];
                    } elseif ($due->lt($today)) {
                        $badge = ['Overdue', 'bg-sale-red/10 text-sale-red'];
                    } elseif ($due->isSameDay($today)) {
                        $badge = ['Today', 'bg-orange-100 text-orange-600'];
                    } elseif ($due->isSameDay($today->copy()->addDay())) {
                        $badge = ['Tomorrow', 'bg-blue-100 text-blue-600'];
                    } else {
                        $badge = [$due->format('M d'), 'bg-primary/10 text-primary'];
                    }
                @endphp

                <div class="frost-panel-solid rounded-2xl p-5">
                    {{-- PICKUP DETAILS --}}
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-charcoal">
                                Order #{{ $order->order_number }} ·
                                {{ $seller->business_name ?? $order->seller->name }}
                            </p>

                            <p class="mt-0.5 text-xs text-charcoal/60">
                                {{ $seller?->street_address }},
                                {{ $seller?->barangay }},
                                {{ $seller?->municipality }},
                                {{ $seller?->province }}
                            </p>

                            <p class="text-xs text-charcoal/60">
                                Contact: {{ $seller?->contact_no ?? '—' }}
                            </p>

                            <p class="mt-1 text-xs text-charcoal/50">
                                {{ $order->items->sum('quantity') }} item(s) ·
                                Waybill {{ $order->waybill_printed_at ? 'printed' : 'not printed yet' }}
                            </p>
                        </div>

                        <div class="text-right">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $badge[1] }}">
                                {{ $badge[0] }}
                            </span>

                            <p class="mt-1.5 text-xs text-charcoal/60">
                                {{ $order->pickup_date?->format('D, M d') }}
                                <br>
                                {{ \App\Models\Order::PICKUP_SLOTS[$order->pickup_time_slot] ?? '' }}
                            </p>
                        </div>
                    </div>

                    {{-- PICKUP ASSIGNMENT --}}
                    <div class="mt-4 border-t border-white/50 pt-4">
                        @if (!$order->pickup_rider_id)
                            {{-- ASSIGN RIDER --}}
                            @include('logistics.pickups._assign_form', [
                                'order' => $order,
                                'riders' => $riders,
                                'submitLabel' => 'Assign Rider',
                                'suggestedRiderId' => $suggested[$order->id] ?? null,
                            ])

                            <details class="mt-3">
                                <summary
                                    class="cursor-pointer text-xs font-semibold text-sale-red/80 hover:text-sale-red">
                                    Can't serve this pickup?
                                </summary>

                                <form method="POST" action="{{ route('logistics.pickups.decline', $order) }}"
                                    class="mt-2 flex flex-wrap gap-2">
                                    @csrf

                                    <input type="text" name="reason" required maxlength="255"
                                        placeholder="Reason (the seller will see this)"
                                        class="frost-input min-w-[16rem] flex-1 rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10">

                                    <button type="submit"
                                        class="rounded-xl bg-sale-red px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">
                                        Decline
                                    </button>
                                </form>
                            </details>
                        @else
                            {{-- SCHEDULED PICKUP --}}
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm text-charcoal/70">
                                    Rider:
                                    <span class="font-semibold text-charcoal">
                                        {{ $order->pickupRider->full_name }}
                                    </span>
                                    — {{ $order->pickupRider->vehicle_type }}
                                    ({{ $order->pickupRider->plate_number }})
                                    <span
                                        class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $order->pickup_status === 'accepted' ? 'bg-leaf-green/10 text-leaf-green' : 'bg-orange-100 text-orange-600' }}">
                                        {{ $order->pickup_status === 'accepted' ? 'Accepted by rider' : 'Waiting for rider to accept' }}
                                    </span>
                                </p>

                                <form method="POST" action="{{ route('logistics.pickups.confirm', $order) }}">
                                    @csrf

                                    <button type="submit"
                                        class="rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110">
                                        Confirm Picked Up
                                    </button>
                                </form>
                            </div>

                            {{-- CHANGE RIDER OR RESCHEDULE --}}
                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs font-semibold text-primary hover:underline">
                                    Change rider or reschedule
                                </summary>

                                <div class="mt-2">
                                    @include('logistics.pickups._assign_form', [
                                        'order' => $order,
                                        'riders' => $riders,
                                        'submitLabel' => 'Update',
                                        'suggestedRiderId' => $suggested[$order->id] ?? null,
                                    ])
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- PAGINATION --}}
        <div>
            {{ $orders->links() }}
        </div>
    @endif
</x-logistics.layout>
