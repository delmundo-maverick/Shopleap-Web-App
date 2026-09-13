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

    <x-navbars.buyer active="home" />

    <main class="mx-auto max-w-5xl px-5 py-6 sm:px-8">

        <h1 class="mb-5 text-2xl font-bold text-charcoal">Checkout</h1>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('buyer.checkout.store') }}">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <div class="space-y-5 lg:col-span-2">

                    <!-- DELIVERY ADDRESS -->
                    <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <h3 class="mb-3 text-sm font-bold text-charcoal">Delivery Address</h3>
                        @if (auth()->user()->buyerProfile)
                            <p class="text-sm text-charcoal/70">
                                {{ auth()->user()->name }}<br>
                                {{ auth()->user()->buyerProfile->contact_no }}<br>
                                {{ auth()->user()->buyerProfile->house_number }} {{ auth()->user()->buyerProfile->street }},
                                {{ auth()->user()->buyerProfile->barangay }},
                                {{ auth()->user()->buyerProfile->municipality }},
                                {{ auth()->user()->buyerProfile->province }}
                            </p>
                        @else
                            <p class="text-sm text-charcoal/50">No address on file.</p>
                        @endif
                    </div>

                    <!-- ITEMS GROUPED BY SELLER -->
                    @foreach ($groupedBySeller as $sellerId => $items)
                        @php $seller = $items->first()->product->seller; @endphp
                        <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                            <p class="mb-3 flex items-center gap-2 text-sm font-bold text-charcoal">
                                <svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5" />
                                </svg>
                                {{ $seller->sellerProfile->business_name ?? $seller->name }}
                            </p>

                            <div class="divide-y divide-light-gray">
                                @foreach ($items as $item)
                                    <div class="flex items-center gap-3 py-3">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                                            @if ($item->product->main_image_url)
                                                <img src="{{ $item->product->main_image_url }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-1 text-sm text-charcoal">{{ $item->product->name }}</p>
                                            <p class="text-xs text-charcoal/50">Qty: {{ $item->quantity }}</p>
                                        </div>
                                        <p class="shrink-0 text-sm font-bold text-charcoal">
                                            ₱{{ number_format($item->quantity * $item->product->display_price, 2) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <!-- PAYMENT METHOD -->
                    <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <h3 class="mb-3 text-sm font-bold text-charcoal">Payment Method</h3>
                        <label class="flex items-center gap-3 rounded-lg border border-primary bg-primary/5 px-4 py-3">
                            <input type="radio" name="payment_method" value="cod" checked class="accent-primary">
                            <span class="text-sm font-medium text-charcoal">Cash on Delivery (COD)</span>
                        </label>
                        <p class="mt-2 text-xs text-charcoal/40">More payment methods coming soon.</p>
                    </div>

                </div>

                <!-- SUMMARY -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <h3 class="mb-4 text-sm font-bold text-charcoal">Order Total</h3>
                        <div class="flex items-center justify-between text-sm text-charcoal/70">
                            <span>Merchandise Subtotal</span>
                            <span class="font-semibold text-charcoal">₱{{ number_format($total, 2) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-charcoal/40">Shipping fees are not yet calculated.</p>

                        <div class="my-4 h-px bg-light-gray"></div>

                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-sm font-bold text-charcoal">Total</span>
                            <span class="text-xl font-bold text-primary">₱{{ number_format($total, 2) }}</span>
                        </div>

                        <button type="submit"
                            class="w-full rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue">
                            Place Order
                        </button>
                    </div>
                </div>

            </div>

        </form>

    </main>

</body>

</html>
