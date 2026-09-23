<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ $seller->sellerProfile->shop_name }} | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-Buyer.header />

    <!-- SHOP HEADER CARD -->
    <div class="bg-light-gray">
        <div class="mx-auto max-w-7xl px-5 py-6 sm:px-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-4">
                    <div
                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border-2 border-white/20 bg-white text-2xl font-bold text-primary">
                        {{ strtoupper(substr($seller->sellerProfile->shop_name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-lg font-bold text-charcoal">{{ $seller->sellerProfile->shop_name }}</p>
                        <p class="mt-0.5 text-xs text-charcoal/60">{{ $seller->sellerProfile->line_of_business }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-10 gap-y-3 text-sm text-charcoal/70">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Products: <span class="font-semibold text-primary">{{ $products->total() }}</span>
                    </div>

                    @if ($seller->sellerProfile->location)
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ $seller->sellerProfile->location }}
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Joined: <span class="font-semibold text-primary">
                            {{ \Carbon\Carbon::parse($seller->created_at)->diffForHumans(null, true) }} ago
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <main class="mx-auto max-w-7xl px-5 py-6 sm:px-8">

        <!-- PRODUCTS GRID -->
        <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
            <div class="mb-4 flex items-center justify-between border-b border-light-gray pb-3">
                <h2 class="text-base font-bold text-charcoal">All Products</h2>
                <span class="text-xs text-charcoal/45">{{ $products->total() }} items</span>
            </div>

            @if ($products->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="h-12 w-12 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="mt-3 text-sm font-semibold text-charcoal">No products yet</p>
                    <p class="mt-1 text-xs text-charcoal/50">This seller hasn't listed any active products.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ($products as $product)
                        <a href="{{ route('buyer.products.show', $product) }}"
                            class="group overflow-hidden rounded-lg border border-light-gray transition hover:shadow-md">
                            <div class="aspect-square overflow-hidden bg-ice-blue/60">
                                @if ($product->main_image_url)
                                    <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition group-hover:scale-105">
                                @endif
                            </div>
                            <div class="p-2.5">
                                <p class="line-clamp-2 text-xs text-charcoal">{{ $product->name }}</p>
                                <p class="mt-1 text-sm font-bold text-primary">
                                    ₱{{ number_format($product->display_price, 2) }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-8 flex justify-center">
                    {{ $products->onEachSide(1)->links() }}
                </div>
            @endif
        </section>

    </main>

</body>

</html>
