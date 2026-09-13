<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Order #{{ $order->id }} | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-navbars.buyer active="orders" />

    <main class="mx-auto max-w-3xl px-5 py-6 sm:px-8">

        <a href="{{ route('buyer.orders.index') }}" class="mb-4 inline-flex items-center gap-1 text-xs text-charcoal/50 hover:text-primary">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to My Orders
        </a>

        <div class="rounded-lg bg-white p-6 shadow-sm shadow-charcoal/5">

            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-charcoal">Order #{{ $order->id }}</h1>
                    <p class="text-xs text-charcoal/50">Placed {{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
                </div>
                <span class="rounded-full bg-orange-100 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-orange-600">
                    {{ str_replace('_', ' ', $order->status) }}
                </span>
            </div>

            <p class="mb-4 text-sm font-semibold text-charcoal">
                Sold by: {{ $order->seller->sellerProfile->business_name ?? $order->seller->name }}
            </p>

            <div class="divide-y divide-light-gray border-y border-light-gray">
                @foreach ($order->items as $item)
                    <div class="flex items-center gap-4 py-4">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                            @if ($item->product && $item->product->main_image_url)
                                <img src="{{ $item->product->main_image_url }}" alt="{{ $item->product_name }}" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-charcoal">{{ $item->product_name }}</p>
                            <p class="text-xs text-charcoal/50">₱{{ number_format($item->price_at_purchase, 2) }} × {{ $item->quantity }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold text-charcoal">₱{{ number_format($item->subtotal, 2) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex items-center justify-between">
                <span class="text-sm font-bold text-charcoal">Order Total</span>
                <span class="text-xl font-bold text-primary">₱{{ number_format($order->total_amount, 2) }}</span>
            </div>

        </div>

    </main>

</body>

</html>
