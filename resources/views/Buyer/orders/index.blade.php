<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Orders | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-navbars.buyer active="orders" />

    <main class="mx-auto max-w-5xl px-5 py-6 sm:px-8">

        <h1 class="mb-5 text-2xl font-bold text-charcoal">My Orders</h1>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                {{ session('status') }}
            </div>
        @endif

        @if ($orders->isEmpty())

            <div class="flex flex-col items-center justify-center rounded-lg bg-white p-16 text-center shadow-sm shadow-charcoal/5">
                <p class="text-sm font-semibold text-charcoal">You haven't placed any orders yet</p>
                <a href="{{ route('buyer.home') }}" class="mt-5 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                    Start Shopping
                </a>
            </div>

        @else

            <div class="space-y-4">
                @foreach ($orders as $order)
                    <a href="{{ route('buyer.orders.show', $order) }}" class="block rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5 transition hover:shadow-md">
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-charcoal">
                                    {{ $order->seller->sellerProfile->business_name ?? $order->seller->name }}
                                </p>
                                <p class="text-xs text-charcoal/50">Order #{{ $order->id }} · {{ $order->created_at->format('M d, Y') }}</p>
                            </div>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-orange-100 text-orange-600',
                                    'to_ship' => 'bg-blue-100 text-blue-600',
                                    'shipped' => 'bg-primary/10 text-primary',
                                    'delivered' => 'bg-leaf-green/10 text-leaf-green',
                                    'cancelled' => 'bg-sale-red/10 text-sale-red',
                                ];
                            @endphp
                            <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $statusColors[$order->status] ?? 'bg-light-gray text-charcoal' }}">
                                {{ str_replace('_', ' ', $order->status) }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 overflow-x-auto">
                            @foreach ($order->items->take(4) as $item)
                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                                    @if ($item->product && $item->product->main_image_url)
                                        <img src="{{ $item->product->main_image_url }}" alt="{{ $item->product_name }}" class="h-full w-full object-cover">
                                    @endif
                                </div>
                            @endforeach
                            @if ($order->items->count() > 4)
                                <span class="text-xs text-charcoal/40">+{{ $order->items->count() - 4 }} more</span>
                            @endif
                        </div>

                        <div class="mt-3 flex items-center justify-between border-t border-light-gray pt-3">
                            <span class="text-xs text-charcoal/50">{{ $order->items->sum('quantity') }} item(s)</span>
                            <span class="text-sm font-bold text-charcoal">Total: ₱{{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $orders->links() }}</div>

        @endif

    </main>

</body>

</html>
