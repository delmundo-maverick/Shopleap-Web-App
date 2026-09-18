<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Checkout | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">
    <header class="sticky top-0 z-40 border-b border-light-gray bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-5 sm:px-8">

            <!-- LOGO -->
            <a href="{{ route('buyer.home') }}" class="flex shrink-0 items-center gap-2">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                    <!-- LOGO PLACEHOLDER — replace /public/images/shopleap-logo.png with the real logo -->
                    <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap"
                        class="h-full w-full rounded-lg object-contain">
                </div>
                <span class="text-xl font-extrabold tracking-tight text-primary">Shopleap</span>
            </a>

            <!-- SECURE CHECKOUT LABEL -->
            <div class="flex items-center gap-2 text-sm font-semibold text-charcoal/60">
                <svg class="h-4.5 w-4.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                        d="M16 8V6a4 4 0 00-8 0v2M5 8h14a1 1 0 011 1v10a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z" />
                </svg>
                <span class="hidden sm:inline">Secure Checkout</span>
            </div>

        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5 py-6 sm:px-8">

        {{-- STEP INDICATOR --}}
        <div class="mb-6 flex items-center justify-center gap-3 text-xs font-semibold text-charcoal/40 sm:text-sm">
            <span class="flex items-center gap-1.5 text-charcoal/40">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-charcoal/10 text-[10px]">✓</span>
                Cart
            </span>
            <span class="h-px w-8 bg-light-gray sm:w-16"></span>
            <span class="flex items-center gap-1.5 text-primary">
                <span
                    class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] text-white">2</span>
                Checkout
            </span>
            <span class="h-px w-8 bg-light-gray sm:w-16"></span>
            <span class="flex items-center gap-1.5 text-charcoal/40">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-charcoal/10 text-[10px]">3</span>
                Order Placed
            </span>
        </div>

        <h1 class="mb-5 text-2xl font-bold text-charcoal">Checkout</h1>

        @if ($errors->any())
            <div
                class="mb-5 flex items-start gap-2 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                <svg class="mt-0.5 h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('buyer.checkout.store') }}">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <div class="space-y-5 lg:col-span-2">

                    <!-- DELIVERY ADDRESS -->
                    <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-charcoal">
                                <svg class="h-4.5 w-4.5 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                        d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Delivery Address
                            </h3>
                            @if (auth()->user()->buyerProfile && Route::has('buyer.profile.edit'))
                                <a href="{{ route('buyer.profile.edit') }}"
                                    class="text-xs font-semibold text-primary hover:underline">Change</a>
                            @endif
                        </div>

                        @if (auth()->user()->buyerProfile)
                            <div class="rounded-lg border border-light-gray bg-ice-blue/40 p-4">
                                <p class="text-sm font-semibold text-charcoal">
                                    {{ auth()->user()->name }}
                                    <span
                                        class="ml-2 font-normal text-charcoal/50">{{ auth()->user()->buyerProfile->contact_no }}</span>
                                </p>
                                <p class="mt-1 text-sm text-charcoal/70">
                                    {{ auth()->user()->buyerProfile->house_number }}
                                    {{ auth()->user()->buyerProfile->street }},
                                    {{ auth()->user()->buyerProfile->barangay }},
                                    {{ auth()->user()->buyerProfile->municipality }},
                                    {{ auth()->user()->buyerProfile->province }}
                                </p>
                            </div>
                        @else
                            <div
                                class="flex items-center justify-between rounded-lg border border-dashed border-sale-red/30 bg-sale-red/5 p-4">
                                <p class="text-sm text-sale-red">No address on file. Add one before placing your order.
                                </p>
                                <a href="{{ Route::has('buyer.profile.edit') ? route('buyer.profile.edit') : '#' }}"
                                    class="shrink-0 rounded-lg bg-sale-red px-3 py-1.5 text-xs font-bold text-white transition hover:brightness-110">
                                    Add Address
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- ITEMS GROUPED BY SELLER -->
                    @foreach ($groupedBySeller as $sellerId => $items)
                        @php
                            $seller = $items->first()->product->seller;
                            $sellerSubtotal = $items->sum(fn($item) => $item->quantity * $item->product->display_price);
                        @endphp
                        <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                            <p class="mb-3 flex items-center gap-2 text-sm font-bold text-charcoal">
                                <svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5" />
                                </svg>
                                {{ $seller->sellerProfile->business_name ?? $seller->name }}
                            </p>

                            <div class="divide-y divide-light-gray">
                                @foreach ($items as $item)
                                    <div class="flex items-center gap-3 py-3">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                                            @if ($item->product->main_image_url)
                                                <img src="{{ $item->product->main_image_url }}"
                                                    alt="{{ $item->product->name }}"
                                                    class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-1 text-sm text-charcoal">{{ $item->product->name }}
                                            </p>
                                            <p class="text-xs text-charcoal/50">Qty: {{ $item->quantity }} ×
                                                ₱{{ number_format($item->product->display_price, 2) }}</p>
                                        </div>
                                        <p class="shrink-0 text-sm font-bold text-charcoal">
                                            ₱{{ number_format($item->quantity * $item->product->display_price, 2) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 flex items-center justify-between border-t border-light-gray pt-3">
                                <span class="text-xs text-charcoal/50">{{ $items->count() }}
                                    item{{ $items->count() === 1 ? '' : 's' }} from this seller</span>
                                <span
                                    class="text-sm font-bold text-charcoal">₱{{ number_format($sellerSubtotal, 2) }}</span>
                            </div>
                        </div>
                    @endforeach

                    <!-- PAYMENT METHOD -->
                    <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-charcoal">
                            <svg class="h-4.5 w-4.5 text-primary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            Payment Method
                        </h3>
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-primary bg-primary/5 px-4 py-3">
                            <input type="radio" name="payment_method" value="cod" checked
                                class="accent-primary">
                            <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3 .672 3 1.5-1.343 1.5-3 1.5m0-6c1.11 0 2.08.402 2.599 1M12 8V6.5m0 8V17m0-9c-1.11 0-2.08.402-2.599 1M12 17c1.11 0 2.08-.402 2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium text-charcoal">Cash on Delivery (COD)</span>
                        </label>
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-charcoal/40">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            More payment methods coming soon.
                        </p>
                    </div>

                    <!-- ORDER NOTE (optional) -->
                    <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <label for="notes" class="mb-2 block text-sm font-bold text-charcoal">Note to Seller <span
                                class="font-normal text-charcoal/40">(optional)</span></label>
                        <textarea id="notes" name="notes" rows="2" maxlength="255" placeholder="e.g. Leave at the guard house"
                            class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm text-charcoal outline-none focus:border-primary"></textarea>
                    </div>

                </div>

                <!-- SUMMARY -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <h3 class="mb-4 text-sm font-bold text-charcoal">Order Total</h3>

                        <div class="flex items-center justify-between text-sm text-charcoal/70">
                            <span>Merchandise Subtotal ({{ $groupedBySeller->flatten(1)->sum('quantity') }}
                                item{{ $groupedBySeller->flatten(1)->sum('quantity') === 1 ? '' : 's' }})</span>
                            <span class="font-semibold text-charcoal">₱{{ number_format($total, 2) }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm text-charcoal/70">
                            <span>Shipping Fee</span>
                            <span class="font-semibold text-charcoal/40">To be calculated</span>
                        </div>

                        <div class="my-4 h-px bg-light-gray"></div>

                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-bold text-charcoal">Total</span>
                            <span class="text-xl font-bold text-primary">₱{{ number_format($total, 2) }}</span>
                        </div>
                        <p class="mb-4 text-right text-[11px] text-charcoal/40">VAT included, where applicable</p>

                        <button type="submit"
                            class="w-full rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue">
                            Place Order
                        </button>

                        <p class="mt-3 text-center text-[11px] leading-relaxed text-charcoal/40">
                            By placing your order, you agree to Shopleap's
                            <a href="#" class="text-primary hover:underline">Terms of Service</a> and
                            <a href="#" class="text-primary hover:underline">Privacy Policy</a>.
                        </p>
                    </div>
                </div>

            </div>

        </form>

    </main>

</body>

</html>
