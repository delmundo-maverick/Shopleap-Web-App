<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/css/pages/seller/order-show.css', 'resources/js/pages/seller/order-show.js', 'resources/js/app.js'])
    <title>Order #{{ $order->order_number }} | Shopleap Seller</title>
</head>

<body class="seller-order-show min-h-screen overflow-x-hidden text-charcoal" x-data="orderShowPage({
    status: @js($order->status),
    statusLabels: @js(\App\Models\Order::STATUS_LABELS),
    orderId: @js($order->order_number)
})">
    <div class="frost-surface"></div>

    @php
        $ship = $order->ship_to;
        $sellerProfile = auth()->user()->sellerProfile;
        $nextStatuses = \App\Models\Order::SELLER_TRANSITIONS[$order->status] ?? [];

        $canCancel = in_array($order->status, \App\Models\Order::SELLER_CANCEL_FROM, true);
        $cancelRequestPending =
            $order->cancel_request_status === 'pending' &&
            in_array($order->status, \App\Models\Order::BUYER_CANCEL_REQUEST_FROM, true);

        $defaultHubId = old('logistics_profile_id', $hubs->first()?->id ?? $lastHubId);
        $inputClass =
            'frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    @endphp

    <div class="flex min-h-screen w-full">
        <x-navbars.seller active="orders" />

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex min-h-20 flex-wrap items-center justify-between gap-3 border-b border-white/50 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ route('seller.orders.index') }}" aria-label="Back to orders"
                        class="frost-btn rounded-full p-2 text-charcoal/50 transition hover:text-primary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>

                    <div class="min-w-0">
                        <h1 class="break-words text-lg font-bold text-charcoal sm:text-xl">
                            Order #{{ $order->order_number }}
                        </h1>
                        <p class="text-sm text-charcoal/55">
                            Placed {{ $order->created_at->format('M d, Y \a\t g:i A') }}
                        </p>
                    </div>
                </div>

                <span :class="['status-badge', badgeVariant(status)]" class="seller-order-status-badge shrink-0"
                    x-text="statusLabel(status)">{{ $order->status_label }}</span>
            </header>

            <main class="w-full min-w-0 flex-1 p-3 sm:p-5 lg:p-7">
                <div class="w-full min-w-0 space-y-4">
                    @if (session('status'))
                        <div role="status"
                            class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div role="alert"
                            class="frost-panel rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="grid min-w-0 grid-cols-1 items-start gap-4 sm:gap-5 xl:grid-cols-12">
                        {{-- Buyer information and order items --}}
                        <div class="min-w-0 space-y-4 sm:space-y-5 xl:col-span-7 2xl:col-span-8">
                            <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                <h2 class="mb-3 text-section-label">Buyer Information</h2>

                                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                    <div class="min-w-0">
                                        <dt class="text-charcoal/50">Name</dt>
                                        <dd class="font-medium">{{ $ship['name'] ?? '—' }}</dd>
                                    </div>

                                    <div class="min-w-0">
                                        <dt class="text-charcoal/50">Contact No.</dt>
                                        <dd class="font-medium">{{ $ship['contact_no'] ?? '—' }}</dd>
                                    </div>

                                    <div class="min-w-0 sm:col-span-2">
                                        <dt class="text-charcoal/50">Delivery Address</dt>
                                        <dd class="font-medium">
                                            @if (!empty($ship['municipality']))
                                                {{ $ship['street'] ?? '' }},
                                                {{ $ship['barangay'] ?? '' }},
                                                {{ $ship['municipality'] }},
                                                {{ $ship['province'] ?? '' }}
                                            @else
                                                —
                                            @endif
                                        </dd>
                                    </div>

                                    @if ($order->buyer_note)
                                        <div class="min-w-0 sm:col-span-2">
                                            <dt class="text-charcoal/50">Note from buyer</dt>
                                            <dd class="font-medium">{{ $order->buyer_note }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </section>

                            <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                <h2 class="mb-3 text-section-label">Items</h2>

                                <div class="app-table-body">
                                    @foreach ($order->items as $item)
                                        <div
                                            class="flex min-w-0 flex-wrap items-center gap-3 py-3 sm:flex-nowrap sm:gap-4">
                                            <div
                                                class="seller-order-item-image h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-ice-blue">
                                                @if ($item->product && $item->product->main_image_url)
                                                    <img src="{{ $item->product->main_image_url }}"
                                                        alt="{{ $item->product_name }}" loading="lazy">
                                                @endif
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <p class="line-clamp-2 text-sm font-medium text-charcoal">
                                                    {{ $item->product_name }}
                                                </p>

                                                @if ($item->selected_options)
                                                    <p class="break-words text-xs font-semibold text-primary">
                                                        {{ collect($item->selected_options)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ') }}
                                                    </p>
                                                @endif

                                                <p class="text-xs text-charcoal/50">
                                                    ₱{{ number_format($item->price_at_purchase, 2) }}
                                                    × {{ $item->quantity }}
                                                    @if ($item->weight_kg !== null)
                                                        · {{ number_format((float) $item->weight_kg, 2) }} kg
                                                    @endif
                                                </p>
                                            </div>

                                            <p class="ml-auto shrink-0 text-sm font-bold text-charcoal">
                                                ₱{{ number_format($item->subtotal, 2) }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-4 space-y-2 border-t border-white/50 pt-4 text-sm">
                                    <div class="seller-order-summary-row flex justify-between text-charcoal/70">
                                        <span>Items subtotal</span>
                                        <span class="shrink-0 font-semibold text-charcoal">
                                            ₱{{ number_format($order->items_subtotal ?? $order->total_amount, 2) }}
                                        </span>
                                    </div>

                                    @if ($order->voucher_discount > 0)
                                        <div class="seller-order-summary-row flex justify-between gap-3 text-leaf-green">
                                            <span>Voucher discount ({{ $order->voucher_code }})</span>
                                            <span class="shrink-0">− ₱{{ number_format($order->voucher_discount, 2) }}</span>
                                        </div>
                                        <div class="seller-order-summary-row flex justify-between text-charcoal/70">
                                            <span>Items after voucher</span>
                                            <span class="shrink-0">₱{{ number_format($order->total_amount, 2) }}</span>
                                        </div>
                                    @endif

                                    @if (($order->total_weight_kg ?? 0) > 0)
                                        <div class="seller-order-summary-row flex justify-between text-charcoal/50">
                                            <span>Estimated Parcel Weight</span>
                                            <span class="shrink-0">
                                                {{ number_format($order->total_weight_kg, 2) }} kg
                                            </span>
                                        </div>
                                    @endif

                                    <div class="seller-order-summary-row flex justify-between text-charcoal/50">
                                        <span>Platform Commission ({{ $order->commission_rate_label }})</span>
                                        <span class="shrink-0">
                                            − ₱{{ number_format($order->commission_amount, 2) }}
                                        </span>
                                    </div>

                                    <div
                                        class="seller-order-summary-row flex justify-between border-t border-white/50 pt-2 font-bold text-charcoal">
                                        <span>Your Earnings</span>
                                        <span class="shrink-0">
                                            ₱{{ number_format($order->total_amount - $order->commission_amount, 2) }}
                                        </span>
                                    </div>

                                    <div class="mt-3 space-y-2 rounded-xl bg-ice-blue/60 p-3 text-xs text-charcoal/60">
                                        <div class="seller-order-summary-row flex justify-between">
                                            <span>Shipping fee paid by buyer</span>
                                            <span class="shrink-0 font-semibold text-charcoal">
                                                ₱{{ number_format($order->shipping_fee, 2) }}
                                            </span>
                                        </div>

                                        <p class="text-[11px] text-charcoal/45">
                                            Goes to delivery, not included in your earnings.
                                        </p>

                                        <div
                                            class="seller-order-summary-row flex justify-between border-t border-white/60 pt-2">
                                            <span>Buyer pays (COD)</span>
                                            <span class="shrink-0 font-bold text-charcoal">
                                                ₱{{ number_format($order->grand_total, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        {{-- Shipping, pickup, status, and timeline --}}
                        <div class="min-w-0 space-y-4 sm:space-y-5 xl:col-span-5 2xl:col-span-4">

                            {{-- Cancelled --}}
                            @if ($order->status === \App\Models\Order::STATUS_CANCELLED)
                                <section class="frost-panel min-w-0 rounded-2xl border border-sale-red/20 p-4 sm:p-5">
                                    <h2 class="mb-2 text-section-label">Order Cancelled</h2>
                                    <p class="text-sm font-semibold text-sale-red">
                                        Cancelled by
                                        {{ \App\Models\Order::CANCELLED_BY_LABELS[$order->cancelled_by] ?? 'the seller' }}
                                    </p>

                                    @if ($order->cancel_reason)
                                        <p class="mt-1 text-sm text-charcoal">{{ $order->cancel_reason }}</p>
                                    @endif

                                    @if ($order->cancel_note)
                                        <p class="mt-1 text-xs text-charcoal/60">{{ $order->cancel_note }}</p>
                                    @endif

                                    @if ($order->cancelled_at)
                                        <p class="mt-2 text-[11px] text-charcoal/40">
                                            {{ $order->cancelled_at->format('M d, Y · g:i A') }}
                                        </p>
                                    @endif
                                </section>
                            @endif

                            {{-- Buyer cancellation request --}}
                            @if ($cancelRequestPending)
                                <section class="frost-panel min-w-0 rounded-2xl border border-warning/30 p-4 sm:p-5">
                                    <h2 class="mb-2 text-section-label">Cancellation Request</h2>
                                    <p class="text-sm text-charcoal">The buyer asked to cancel this order.</p>

                                    <dl class="mt-3 space-y-2 text-sm">
                                        <div>
                                            <dt class="text-xs text-charcoal/50">Reason</dt>
                                            <dd class="font-medium">{{ $order->cancel_request_reason }}</dd>
                                        </div>

                                        @if ($order->cancel_request_note)
                                            <div>
                                                <dt class="text-xs text-charcoal/50">Note</dt>
                                                <dd class="font-medium">{{ $order->cancel_request_note }}</dd>
                                            </div>
                                        @endif

                                        <div>
                                            <dt class="text-xs text-charcoal/50">Answer before</dt>
                                            <dd class="font-medium">
                                                {{ $order->cancel_requested_at?->copy()->addHours(\App\Models\Order::CANCEL_REQUEST_AUTO_APPROVE_HOURS)->format('M d, g:i A') ?? '—' }}
                                                <span class="block text-xs font-normal text-charcoal/50">
                                                    No answer means it is approved automatically.
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>

                                    <form method="POST"
                                        action="{{ route('seller.orders.cancel-request.approve', $order) }}"
                                        onsubmit="return confirm('Approve this request? The order will be cancelled and the stock returned.')"
                                        class="mt-4">
                                        @csrf
                                        <button type="submit"
                                            class="w-full rounded-xl bg-sale-red px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-105">
                                            Approve &amp; cancel order
                                        </button>
                                    </form>

                                    <details class="mt-3">
                                        <summary
                                            class="cursor-pointer text-xs font-semibold text-primary hover:underline">
                                            Decline the request…
                                        </summary>

                                        <form method="POST"
                                            action="{{ route('seller.orders.cancel-request.decline', $order) }}"
                                            class="mt-2 space-y-2">
                                            @csrf
                                            <input type="text" name="reason" required maxlength="255"
                                                placeholder="Reason (the buyer will see this)"
                                                class="{{ $inputClass }}">

                                            <button type="submit"
                                                class="w-full rounded-xl border border-charcoal/20 px-4 py-2.5 text-sm font-bold text-charcoal transition hover:bg-ice-blue">
                                                Decline request
                                            </button>
                                        </form>
                                    </details>
                                </section>
                            @endif

                            {{-- Return / refund --}}
                            @php $returnRecord = $order->returns->sortByDesc('id')->first(); @endphp
                            @if ($returnRecord)
                                <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                    <h2 class="mb-2 text-section-label">Return / Refund</h2>
                                    <p class="text-sm text-charcoal">{{ $returnRecord->reason_label }}</p>
                                    <span
                                        class="mt-2 inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $returnRecord->status_color }}">
                                        {{ $returnRecord->status_label }}
                                    </span>
                                    <a href="{{ route('seller.returns.show', $returnRecord) }}"
                                        class="mt-3 block w-full rounded-xl border-2 border-primary px-4 py-2.5 text-center text-sm font-bold text-primary transition hover:bg-primary/5">
                                        Open request
                                    </a>
                                </section>
                            @endif

                            {{-- Shipping label --}}
                            @if ($order->canViewWaybill())
                                <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                    <h2 class="mb-3 text-section-label">Shipping Label</h2>

                                    <a href="{{ route('seller.orders.waybill.view', $order) }}" target="_blank" rel="noopener"
                                        class="mb-3 flex w-full items-center justify-center rounded-md bg-primary px-4 py-2.5 text-sm font-bold text-surface">
                                        View Waybill
                                    </a>
                                    <a href="{{ route('seller.orders.waybill', $order) }}" target="_blank"
                                        rel="noopener"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-primary px-4 py-2.5 text-sm font-bold text-primary transition hover:bg-primary/5">
                                        {{ $order->waybill_printed_at ? 'Reprint Waybill' : 'Print Waybill' }}
                                    </a>

                                    @if ($order->waybill_printed_at)
                                        <p class="mt-2 text-center text-xs text-charcoal/40">
                                            First printed {{ $order->waybill_printed_at->diffForHumans() }}
                                        </p>
                                    @elseif ($order->status === \App\Models\Order::STATUS_PREPARING)
                                        <div class="mt-3 rounded-xl bg-warning/10 p-3 text-xs text-charcoal">
                                            You haven't printed a shipping label yet. Print and attach it before the
                                            rider arrives.
                                        </div>
                                    @endif
                                </section>
                            @endif

                            {{-- Request pickup --}}
                            @if ($order->status === \App\Models\Order::STATUS_PREPARING)
                                <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                    <h2 class="mb-1 text-section-label">Request Pickup</h2>

                                    <p class="mb-4 text-xs text-charcoal/60">
                                        The sorting center assigned to your pickup address
                                        (<b>{{ $sellerProfile->municipality }}, {{ $sellerProfile->province }}</b>).
                                        The buyer's parcel is routed to the hub serving
                                        <b>Brgy. {{ $ship['barangay'] ?? '—' }}, {{ $ship['municipality'] ?? '—' }}</b>.
                                    </p>

                                    @if ($order->pickup_status === 'declined' && $order->pickup_decline_reason)
                                        <div class="mb-4 rounded-xl bg-warning/10 p-3 text-xs text-charcoal">
                                            <b>Your last pickup request was declined:</b>
                                            {{ $order->pickup_decline_reason }}.
                                            Please choose another sorting center or date.
                                        </div>
                                    @endif

                                    @if ($hubs->isEmpty())
                                        <div
                                            class="rounded-xl bg-ice-blue/60 p-4 text-xs leading-relaxed text-charcoal/60">
                                            No sorting center serves your pickup address yet.
                                            <a href="{{ route('messages.support') }}"
                                                class="font-semibold text-primary hover:underline">Contact support</a>
                                            to get connected, or cancel this order.
                                        </div>
                                    @else
                                        <form method="POST"
                                            action="{{ route('seller.orders.request-pickup', $order) }}"
                                            class="space-y-4">
                                            @csrf

                                            <fieldset class="space-y-2">
                                                <legend class="mb-2 text-xs font-semibold text-charcoal">
                                                    Pickup sorting center
                                                </legend>

                                                @foreach ($hubs as $hub)
                                                    <label
                                                        class="flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-white/60 bg-white/50 p-3 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                                        <input type="radio" name="logistics_profile_id"
                                                            value="{{ $hub->id }}" required
                                                            class="mt-1 shrink-0 accent-primary"
                                                            @checked((string) $defaultHubId === (string) $hub->id)>

                                                        <span class="min-w-0 flex-1">
                                                            <span
                                                                class="block break-words text-sm font-bold text-charcoal">
                                                                {{ $hub->business_name }}
                                                            </span>

                                                            @if ($hub->company_name)
                                                                <span class="block text-xs text-charcoal/50">
                                                                    {{ $hub->company_name }}
                                                                </span>
                                                            @endif

                                                            <span class="block text-xs text-charcoal/50">
                                                                Based in {{ $hub->municipality }},
                                                                {{ $hub->province }}
                                                            </span>

                                                            <span
                                                                class="mt-1 block text-[11px] {{ $hub->active_riders_count > 0 ? 'text-leaf-green' : 'text-warning' }}">
                                                                {{ $hub->active_riders_count }} active rider(s)
                                                                @if ($hub->id === $lastHubId)
                                                                    · Last used
                                                                @endif
                                                            </span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </fieldset>

                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <div class="min-w-0">
                                                    <label for="pickup_date"
                                                        class="mb-1 block text-xs font-semibold text-charcoal">
                                                        Pickup date
                                                    </label>
                                                    <input id="pickup_date" type="date" name="pickup_date"
                                                        required min="{{ now()->toDateString() }}"
                                                        max="{{ now()->addDays(7)->toDateString() }}"
                                                        value="{{ old('pickup_date', now()->addDay()->toDateString()) }}"
                                                        class="{{ $inputClass }}">
                                                </div>

                                                <div class="min-w-0">
                                                    <label for="pickup_time_slot"
                                                        class="mb-1 block text-xs font-semibold text-charcoal">
                                                        Time window
                                                    </label>
                                                    <select id="pickup_time_slot" name="pickup_time_slot" required
                                                        class="{{ $inputClass }}">
                                                        @foreach (\App\Models\Order::PICKUP_SLOTS as $key => $label)
                                                            <option value="{{ $key }}"
                                                                @selected(old('pickup_time_slot', 'morning') === $key)>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <button type="submit" @disabled(!$order->waybill_printed_at || $cancelRequestPending)
                                                class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50">
                                                Request Pickup &amp; Mark Ready
                                            </button>

                                            @if ($cancelRequestPending)
                                                <p class="text-[11px] text-warning">
                                                    Answer the buyer's cancellation request first.
                                                </p>
                                            @elseif (!$order->waybill_printed_at)
                                                <p class="text-[11px] text-warning">
                                                    Print the waybill first. It unlocks this button.
                                                </p>
                                            @endif

                                            <p class="text-[11px] text-charcoal/40">
                                                The sorting center may adjust the schedule. The confirmed date will show
                                                here.
                                            </p>
                                        </form>
                                    @endif
                                </section>
                            @endif

                            {{-- Pickup status --}}
                            @if ($order->status === \App\Models\Order::STATUS_READY_FOR_PICKUP)
                                <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                    <h2 class="mb-3 text-section-label">Pickup</h2>

                                    <dl class="space-y-3 text-sm">
                                        <div>
                                            <dt class="text-xs text-charcoal/50">Sorting center</dt>
                                            <dd class="font-semibold">
                                                {{ $order->logisticsProfile?->business_name ?? '—' }}
                                            </dd>

                                            @if ($order->logisticsProfile?->contact_no)
                                                <dd class="text-xs text-charcoal/50">
                                                    {{ $order->logisticsProfile->contact_no }}
                                                </dd>
                                            @endif
                                        </div>

                                        <div>
                                            <dt class="text-xs text-charcoal/50">Scheduled</dt>
                                            <dd class="font-semibold">
                                                {{ $order->pickup_date?->format('D, M d, Y') ?? '—' }}
                                                <span class="block text-xs font-normal text-charcoal/60">
                                                    {{ \App\Models\Order::PICKUP_SLOTS[$order->pickup_time_slot] ?? '' }}
                                                </span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="text-xs text-charcoal/50">Rider</dt>

                                            @if ($order->pickupRider)
                                                <dd class="font-semibold text-leaf-green">
                                                    {{ $order->pickupRider->full_name }}
                                                    <span class="block text-xs font-normal text-charcoal/60">
                                                        {{ $order->pickupRider->vehicle_type }}
                                                        · {{ $order->pickupRider->plate_number }}
                                                    </span>
                                                </dd>
                                            @else
                                                <dd class="text-warning">
                                                    Waiting for the sorting center to assign a rider
                                                </dd>
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
                                </section>
                            @endif

                            {{-- Update order status --}}
                            <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                <h2 class="mb-3 text-section-label">Update Order Status</h2>

                                @if (in_array($order->status, \App\Models\Order::SELLER_MODIFIABLE_FROM, true) && count($nextStatuses))
                                    <label for="next_order_status" class="sr-only">Select next order status</label>
                                    <select id="next_order_status" x-model="next"
                                        class="frost-input mb-3 w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                                        <option value="" disabled>Select next status…</option>
                                        @foreach ($nextStatuses as $value)
                                            <option value="{{ $value }}">
                                                {{ \App\Models\Order::STATUS_LABELS[$value] }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="button" @click="updateStatus()" :disabled="saving || !next"
                                        class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-60">
                                        <span x-text="saving ? 'Updating…' : 'Update Status'"></span>
                                    </button>

                                    <p class="mt-3 text-xs text-charcoal/50">
                                        Placed → Confirmed → Preparing, then request a pickup above.
                                    </p>
                                @elseif ($order->status === \App\Models\Order::STATUS_PREPARING)
                                    <div class="rounded-xl bg-ice-blue/60 p-4 text-xs text-charcoal/60">
                                        Pack the order and print the label, then request a pickup above.
                                    </div>
                                @elseif ($order->status === \App\Models\Order::STATUS_CANCELLED)
                                    <div class="rounded-xl bg-ice-blue/60 p-4 text-xs text-charcoal/60">
                                        This order was cancelled.
                                    </div>
                                @else
                                    <div class="rounded-xl bg-ice-blue/60 p-4 text-xs text-charcoal/60">
                                        This order is now with delivery. Follow its progress in the timeline below.
                                    </div>
                                @endif
                            </section>

                            {{-- Cancel order --}}
                            @if ($canCancel)
                                <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                    <h2 class="mb-3 text-section-label">Cancel Order</h2>

                                    <details>
                                        <summary
                                            class="cursor-pointer text-xs font-bold text-sale-red hover:underline">
                                            I can't fulfil this order…
                                        </summary>

                                        <form method="POST" action="{{ route('seller.orders.cancel', $order) }}"
                                            onsubmit="return confirm('Cancel this order? The stock will be returned and this cannot be undone.')"
                                            class="mt-3 space-y-3">
                                            @csrf

                                            <select name="reason" required class="{{ $inputClass }}">
                                                <option value="" disabled selected>Select a reason…</option>
                                                @foreach (\App\Models\Order::SELLER_CANCEL_REASONS as $reason)
                                                    <option value="{{ $reason }}">{{ $reason }}</option>
                                                @endforeach
                                            </select>

                                            <textarea name="note" rows="2" maxlength="255" placeholder="Note to the buyer (optional)"
                                                class="{{ $inputClass }} resize-none"></textarea>

                                            <button type="submit"
                                                class="w-full rounded-xl bg-sale-red px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-105">
                                                Cancel order
                                            </button>

                                            <p class="text-[11px] text-charcoal/50">
                                                The buyer will see the reason you choose.
                                                @if ($order->status === \App\Models\Order::STATUS_READY_FOR_PICKUP)
                                                    The sorting center's pickup for this order is cancelled too.
                                                @endif
                                            </p>
                                        </form>
                                    </details>
                                </section>
                            @endif

                            {{-- Order timeline --}}
                            @php $timeline = $order->trackingTimeline(); @endphp
                            @if ($timeline->isNotEmpty())
                                <section class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                                    <h2 class="mb-4 text-section-label">Order Timeline</h2>

                                    <ol class="space-y-4 border-l border-charcoal/10 pl-5">
                                        @foreach ($timeline as $event)
                                            <li class="relative min-w-0">
                                                <span aria-hidden="true"
                                                    class="absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-full {{ $loop->first ? 'bg-primary' : 'bg-charcoal/25' }}"></span>

                                                <p class="break-words text-sm font-semibold text-charcoal">
                                                    {{ $event['title'] }}
                                                </p>

                                                @if ($event['note'])
                                                    <p class="break-words text-xs text-charcoal/55">
                                                        {{ $event['note'] }}
                                                    </p>
                                                @endif

                                                <p class="text-[11px] text-charcoal/40">
                                                    {{ $event['created_at']->format('M d, g:i A') }}
                                                </p>
                                            </li>
                                        @endforeach
                                    </ol>
                                </section>
                            @endif
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    {{-- Toast notification --}}
    <div x-show="toast.show" x-cloak x-transition role="status" aria-live="polite"
        class="seller-order-toast fixed bottom-4 left-4 right-4 z-[80] rounded-2xl px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-md sm:bottom-6 sm:left-auto sm:right-6 sm:max-w-sm"
        :class="toast.error ? 'seller-order-toast--error' : 'seller-order-toast--success'" x-text="toast.message">
    </div>
</body>

</html>
