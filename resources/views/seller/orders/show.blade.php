<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Order #{{ $order->id }} | Shopleap Seller</title>

    <style>
        [x-cloak] {

            display: none !important;

        }

        .status-badge--placed {

            background: rgb(255 237 213);

            color: rgb(234 88 12);

        }

        .status-badge--confirmed {

            background: rgb(219 234 254);

            color: rgb(37 99 235);

        }

        .status-badge--preparing,

        .status-badge--ready_for_pickup {

            background: rgb(224 231 255);

            color: rgb(67 56 202);

        }

        .status-badge--picked_up,

        .status-badge--at_sorting_center,

        .status-badge--sorted,

        .status-badge--assigned_to_rider,

        .status-badge--out_for_delivery {

            background: rgb(219 234 254);

            color: rgb(26 95 180);

        }

        .status-badge--delivered,

        .status-badge--completed {

            background: rgb(220 252 231);

            color: rgb(22 163 74);

        }

        .status-badge--cancelled,

        .status-badge--delivery_failed,

        .status-badge--returned {

            background: rgb(254 226 226);

            color: rgb(220 38 38);

        }
    </style>

</head>

<body class="min-h-screen text-charcoal" x-data="orderShowPage()">

    <div class="frost-surface"></div>

    @php

        $ship = $order->ship_to;

        $sellerProfile = auth()->user()->sellerProfile;

        $nextStatuses = \App\Models\Order::SELLER_TRANSITIONS[$order->status] ?? [];

        $defaultHubId = old('logistics_profile_id', $lastHubId ?? ($hubs->count() === 1 ? $hubs->first()->id : null));

        $inputClass =
            'frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';

    @endphp

    <div class="flex min-h-screen">

        <x-navbars.seller active="orders" />

        <div class="flex flex-1 flex-col">

            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">

                <div class="flex items-center gap-3">

                    <a href="{{ route('seller.orders.index') }}"
                        class="frost-btn rounded-full p-2 text-charcoal/50 transition hover:text-primary">

                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />

                        </svg>

                    </a>

                    <div>

                        <h2 class="text-xl font-bold text-charcoal">Order #{{ $order->id }}</h2>

                        <p class="text-sm text-charcoal/55">Placed {{ $order->created_at->format('M d, Y \a\t g:i A') }}

                        </p>

                    </div>

                </div>

                <span :class="\
                `status-badge status-badge--${status.toLowerCase()}\`"
                    class="rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide"
                    x-text="statusLabel(status)">{{ $order->status_label }}</span>

            </header>

            <main class="flex-1 p-6">

                <div class="mx-auto max-w-5xl space-y-4">

                    @if (session('status'))
                        <div
                            class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">

                            {{ session('status') }}

                        </div>
                    @endif

                    @if ($errors->any())
                        <div
                            class="frost-panel rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">

                            {{ $errors->first() }}

                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                        <!-- LEFT: buyer + items -->

                        <div class="space-y-5 lg:col-span-2">

                            <div class="frost-panel rounded-2xl p-5">

                                <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Buyer Information

                                </p>

                                <dl class="grid grid-cols-2 gap-3 text-sm">

                                    <div>

                                        <dt class="text-charcoal/50">Name</dt>

                                        <dd class="font-medium">{{ $ship['name'] ?? '—' }}</dd>

                                    </div>

                                    <div>

                                        <dt class="text-charcoal/50">Contact No.</dt>

                                        <dd class="font-medium">{{ $ship['contact_no'] ?? '—' }}</dd>

                                    </div>

                                    <div class="col-span-2">

                                        <dt class="text-charcoal/50">Delivery Address</dt>

                                        <dd class="font-medium">

                                            @if ($ship['municipality'])
                                                {{ $ship['street'] }}, {{ $ship['barangay'] }},

                                                {{ $ship['municipality'] }}, {{ $ship['province'] }}
                                            @else
                                                —
                                            @endif

                                        </dd>

                                    </div>
                                    @if ($order->buyer_note)
                                        <div class="col-span-2">
                                            <dt class="text-charcoal/50">Note from buyer</dt>
                                            <dd class="font-medium">{{ $order->buyer_note }}</dd>
                                        </div>
                                    @endif

                                </dl>

                            </div>

                            <div class="frost-panel rounded-2xl p-5">

                                <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Items</p>

                                <div class="divide-y divide-white/50">

                                    @foreach ($order->items as $item)
                                        <div class="flex items-center gap-4 py-3">

                                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue">

                                                @if ($item->product && $item->product->main_image_url)
                                                    <img src="{{ $item->product->main_image_url }}"
                                                        alt="{{ $item->product_name }}"
                                                        class="h-full w-full object-cover">
                                                @endif

                                            </div>

                                            <div class="min-w-0 flex-1">

                                                <p class="line-clamp-1 text-sm text-charcoal">{{ $item->product_name }}

                                                </p>

                                                @if ($item->selected_options)
                                                    <p class="text-xs font-semibold text-primary">

                                                        {{ collect($item->selected_options)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ') }}

                                                    </p>
                                                @endif

                                                <p class="text-xs text-charcoal/50">

                                                    ₱{{ number_format($item->price_at_purchase, 2) }} ×

                                                    {{ $item->quantity }}</p>

                                            </div>

                                            <p class="shrink-0 text-sm font-bold text-charcoal">

                                                ₱{{ number_format($item->subtotal, 2) }}</p>

                                        </div>
                                    @endforeach

                                </div>

                                <div class="mt-4 space-y-1.5 border-t border-white/50 pt-4 text-sm">

                                    <div class="flex justify-between text-charcoal/70">

                                        <span>Items Total</span>

                                        <span
                                            class="font-semibold text-charcoal">₱{{ number_format($order->total_amount, 2) }}</span>

                                    </div>

                                    <div class="flex justify-between text-charcoal/50">

                                        <span>Platform Commission ({{ $order->commission_rate_label }})</span>

                                        <span>− ₱{{ number_format($order->commission_amount, 2) }}</span>

                                    </div>

                                    <div
                                        class="flex justify-between border-t border-white/50 pt-1.5 font-bold text-charcoal">

                                        <span>Your Earnings</span>

                                        <span>₱{{ number_format($order->total_amount - $order->commission_amount, 2) }}</span>

                                    </div>

                                    <div class="mt-3 rounded-xl bg-ice-blue/60 p-3 text-xs text-charcoal/60">

                                        <div class="flex justify-between">

                                            <span>Shipping fee paid by buyer</span>

                                            <span
                                                class="font-semibold text-charcoal">₱{{ number_format($order->shipping_fee, 2) }}</span>

                                        </div>

                                        <p class="mt-1 text-[11px] text-charcoal/45">Goes to delivery, not included in

                                            your earnings.</p>

                                        <div class="mt-1.5 flex justify-between border-t border-white/60 pt-1.5">

                                            <span>Buyer pays (COD)</span>

                                            <span
                                                class="font-bold text-charcoal">₱{{ number_format($order->grand_total, 2) }}</span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- RIGHT: label, pickup, status, timeline -->

                        <div class="space-y-5 lg:col-span-1">

                            {{-- SHIPPING LABEL --}}

                            @if (in_array($order->status, \App\Models\Order::WAYBILL_STATUSES, true))

                                <div class="frost-panel rounded-2xl p-5">

                                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Shipping

                                        Label</p>

                                    <a href="{{ route('seller.orders.waybill', $order) }}" target="\_blank"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-primary px-4 py-2.5 text-sm font-bold text-primary transition hover:bg-primary/5">

                                        {{ $order->waybill_printed_at ? 'Reprint Waybill' : 'Print Waybill' }}

                                    </a>

                                    @if ($order->waybill_printed_at)
                                        <p class="mt-2 text-center text-xs text-charcoal/40">First printed

                                            {{ $order->waybill_printed_at->diffForHumans() }}</p>
                                    @elseif ($order->status === \App\Models\Order::STATUS_PREPARING)
                                        <div class="mt-3 rounded-xl bg-orange-50 p-3 text-xs text-orange-700">

                                            You haven't printed a shipping label yet. Print and attach it before the

                                            rider arrives.

                                        </div>
                                    @endif

                                </div>

                            @endif

                            {{-- REQUEST PICKUP --}}

                            @if ($order->status === \App\Models\Order::STATUS_PREPARING)

                                <div class="frost-panel rounded-2xl p-5">

                                    <p class="mb-1 text-xs font-bold uppercase tracking-wide text-primary">Request

                                        Pickup</p>

                                    <p class="mb-4 text-xs text-charcoal/50">
                                        Sorting centers that cover your area
                                        (<b>{{ $sellerProfile->municipality }}, {{ $sellerProfile->province }}</b>)
                                        and the buyer's
                                        (<b>{{ $ship['municipality'] }}, {{ $ship['province'] }}</b>).
                                    </p>

                                    @if ($order->pickup_status === 'declined' && $order->pickup_decline_reason)
                                        <div class="mb-4 rounded-xl bg-orange-50 p-3 text-xs text-orange-700">

                                            <b>Your last pickup request was declined:</b>

                                            {{ $order->pickup_decline_reason }}.

                                            Please choose another sorting center or date.

                                        </div>
                                    @endif

                                    @if ($hubs->isEmpty())

                                        <div
                                            class="rounded-xl bg-ice-blue/60 p-4 text-xs leading-relaxed text-charcoal/60">

                                            No sorting center covers both your area and the buyer's area.
                                            <a href="{{ route('messages.support') }}"
                                                class="font-semibold text-primary hover:underline">Contact support</a>
                                            to get connected, or cancel this order.

                                        </div>
                                    @else
                                        <form method="POST"
                                            action="{{ route('seller.orders.request-pickup', $order) }}"
                                            class="space-y-4">

                                            @csrf

                                            <div class="space-y-2">

                                                @foreach ($hubs as $hub)
                                                    <label
                                                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/60 bg-white/50 p-3 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5">

                                                        <input type="radio" name="logistics_profile_id"
                                                            value="{{ $hub->id }}" required
                                                            class="mt-1 accent-primary" @checked((string) $defaultHubId === (string) $hub->id)>

                                                        <span class="min-w-0 flex-1">

                                                            <span
                                                                class="block text-sm font-bold text-charcoal">{{ $hub->business_name }}</span>

                                                            @if ($hub->company_name)
                                                                <span
                                                                    class="block text-xs text-charcoal/50">{{ $hub->company_name }}</span>
                                                            @endif

                                                            <span class="block text-xs text-charcoal/50">Based in

                                                                {{ $hub->municipality }}, {{ $hub->province }}</span>

                                                            <span
                                                                class="mt-0.5 block text-[11px] {{ $hub->active_riders_count > 0 ? 'text-leaf-green' : 'text-orange-600' }}">

                                                                {{ $hub->active_riders_count }} active rider(s)

                                                                @if ($hub->id === $lastHubId)
                                                                    · Last used
                                                                @endif

                                                            </span>

                                                        </span>

                                                    </label>
                                                @endforeach

                                            </div>

                                            <div class="grid grid-cols-2 gap-3">

                                                <div>

                                                    <label
                                                        class="mb-1 block text-xs font-semibold text-charcoal">Pickup

                                                        date</label>

                                                    <input type="date" name="pickup_date" required
                                                        min="{{ now()->toDateString() }}"
                                                        max="{{ now()->addDays(7)->toDateString() }}"
                                                        value="{{ old('pickup_date', now()->addDay()->toDateString()) }}"
                                                        class="{{ $inputClass }}">

                                                </div>

                                                <div>

                                                    <label class="mb-1 block text-xs font-semibold text-charcoal">Time

                                                        window</label>

                                                    <select name="pickup_time_slot" required
                                                        class="{{ $inputClass }}">

                                                        @foreach (\App\Models\Order::PICKUP_SLOTS as $key => $label)
                                                            <option value="{{ $key }}"
                                                                @selected(old('pickup_time_slot', 'morning') === $key)>{{ $label }}

                                                            </option>
                                                        @endforeach

                                                    </select>

                                                </div>

                                            </div>

                                            <button type="submit" @disabled(!$order->waybill_printed_at)
                                                class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50">
                                                Request Pickup &amp; Mark Ready
                                            </button>
                                            @unless ($order->waybill_printed_at)
                                                <p class="text-[11px] text-orange-600">Print the waybill first. It unlocks
                                                    this button.</p>
                                            @endunless

                                            <p class="text-[11px] text-charcoal/40">The sorting center may adjust the

                                                schedule. The confirmed date will show here.</p>

                                        </form>

                                    @endif

                                </div>

                            @endif

                            {{-- PICKUP STATUS --}}

                            @if ($order->status === \App\Models\Order::STATUS_READY_FOR_PICKUP)

                                <div class="frost-panel rounded-2xl p-5">

                                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Pickup</p>

                                    <dl class="space-y-2.5 text-sm">

                                        <div>

                                            <dt class="text-xs text-charcoal/50">Sorting center</dt>

                                            <dd class="font-semibold">{{ $order->logisticsProfile?->business_name }}

                                            </dd>

                                            @if ($order->logisticsProfile?->contact_no)
                                                <dd class="text-xs text-charcoal/50">

                                                    {{ $order->logisticsProfile->contact_no }}</dd>
                                            @endif

                                        </div>

                                        <div>

                                            <dt class="text-xs text-charcoal/50">Scheduled</dt>

                                            <dd class="font-semibold">

                                                {{ $order->pickup_date?->format('D, M d, Y') }}

                                                <span
                                                    class="block text-xs font-normal text-charcoal/60">{{ \App\Models\Order::PICKUP_SLOTS[$order->pickup_time_slot] ?? '' }}</span>

                                            </dd>

                                        </div>

                                        <div>

                                            <dt class="text-xs text-charcoal/50">Rider</dt>

                                            @if ($order->pickupRider)
                                                <dd class="font-semibold text-leaf-green">

                                                    {{ $order->pickupRider->full_name }}

                                                    <span
                                                        class="block text-xs font-normal text-charcoal/60">{{ $order->pickupRider->vehicle_type }}

                                                        · {{ $order->pickupRider->plate_number }}</span>

                                                </dd>
                                            @else
                                                <dd class="text-orange-600">Waiting for the sorting center to assign a

                                                    rider</dd>
                                            @endif

                                        </div>

                                    </dl>

                                    @unless ($order->pickup_rider_id)
                                        <form method="POST" action="{{ route('seller.orders.cancel-pickup', $order) }}"
                                            onsubmit="return confirm('Cancel this pickup request?')" class="mt-4">

                                            @csrf

                                            <button type="submit"
                                                class="w-full rounded-xl border border-sale-red/30 px-4 py-2.5 text-xs font-bold text-sale-red transition hover:bg-sale-red/5">

                                                Cancel pickup request

                                            </button>

                                        </form>
                                    @endunless

                                </div>

                            @endif

                            {{-- STATUS UPDATE --}}

                            <div class="frost-panel rounded-2xl p-5">

                                <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Update Order

                                    Status</p>

                                @if (in_array($order->status, \App\Models\Order::SELLER_MODIFIABLE_FROM, true))
                                    <select x-model="next"
                                        class="frost-input mb-3 w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                                        <option value="" disabled>Select next status…</option>
                                        @foreach ($nextStatuses as $value)
                                            <option value="{{ $value }}">
                                                {{ \App\Models\Order::STATUS_LABELS[$value] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="updateStatus()" :disabled="saving || !next"
                                        class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-60">
                                        <span x-text="saving ? 'Updating…' : 'Update Status'"></span>
                                    </button>
                                    <p class="mt-3 text-xs text-charcoal/40">
                                        Placed → Confirmed → Preparing, then request a pickup above. Cancel only if
                                        the order cannot be fulfilled; it can't be undone.
                                    </p>
                                @else
                                    <div class="rounded-xl bg-ice-blue/60 p-4 text-xs text-charcoal/60">

                                        This order is now with delivery. Follow its progress in the timeline below.

                                    </div>

                                @endif

                            </div>

                            {{-- TIMELINE --}}

                            @if ($order->events->isNotEmpty())

                                <div class="frost-panel rounded-2xl p-5">

                                    <p class="mb-4 text-xs font-bold uppercase tracking-wide text-primary">Order

                                        Timeline</p>

                                    <ol class="space-y-4 border-l border-charcoal/10 pl-5">

                                        @foreach ($order->events->sortByDesc('id') as $event)
                                            <li class="relative">

                                                <span
                                                    class="absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-full {{ $event->status ? 'bg-primary' : 'bg-charcoal/25' }}"></span>

                                                <p class="text-sm font-semibold text-charcoal">{{ $event->title }}</p>

                                                @if ($event->note)
                                                    <p class="text-xs text-charcoal/55">{{ $event->note }}</p>
                                                @endif

                                                <p class="text-[11px] text-charcoal/40">

                                                    {{ $event->created_at->format('M d, g:i A') }}

                                                    @unless ($event->status)
                                                        · Seller &amp; logistics only
                                                    @endunless

                                                </p>

                                            </li>
                                        @endforeach

                                    </ol>

                                </div>

                            @endif

                        </div>

                    </div>

                </div>

            </main>

        </div>

    </div>

    <!-- TOAST -->

    <div x-show="toast.show" x-cloak x-transition
        class="fixed bottom-6 right-6 z-[80] max-w-sm rounded-2xl px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-md"
        :style="\
        `background: ${toast.error ? 'rgba(228,87,46,0.92)' : 'rgba(76,175,125,0.92)'}\`"
        x-text="toast.message">

    </div>

    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.data('orderShowPage', () => ({

                status: @json($order->status),

                saving: false,
                next: '',

                toast: {

                    show: false,

                    message: '',

                    error: false

                },

                statusLabels: @json(\App\Models\Order::STATUS_LABELS),

                statusLabel(value) {

                    return this.statusLabels[value] ?? value.replace(/\_/g, ' ');

                },

                csrfToken() {

                    return document.querySelector('meta[name="csrf-token"]').content;

                },

                showToast(message, isError = false) {

                    this.toast = {

                        show: true,

                        message,

                        error: isError

                    };

                    setTimeout(() => {

                        this.toast.show = false;

                    }, 3500);

                },

                async updateStatus() {
                    if (!this.next) return;
                    if (this.next === 'CANCELLED' &&
                        !confirm(
                            'Cancel this order? Stock will be restored and this cannot be undone.')
                    ) {
                        return;
                    }

                    this.saving = true;
                    try {
                        const res = await fetch(`/seller/orders/{{ $order->id }}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                status: this.next
                            }),
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) {

                            this.showToast(data.message || 'Could not update status.', true);

                            return;

                        }

                        this.showToast(data.message);

                        this.status = data.status;

                        // Reload so the pickup / label cards reflect the new stage.

                        setTimeout(() => window.location.reload(), 700);

                    } catch (err) {

                        this.showToast('Network error — please try again.', true);

                    } finally {

                        this.saving = false;

                    }

                },

            }));

        });
    </script>

</body>

</html>
