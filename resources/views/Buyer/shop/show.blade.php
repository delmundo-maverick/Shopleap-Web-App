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

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    @php $shop = $seller->sellerProfile; @endphp

    <main class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8">

        <!-- SHOP HEADER: compact profile + inline statistics -->
        <section class="overflow-hidden bg-white shadow-sm ring-1 ring-charcoal/5">
            <div
                class="grid gap-5 bg-white px-4 py-5 sm:px-6 lg:grid-cols-[390px_1fr] lg:items-center lg:gap-10 lg:px-8">
                <!-- Shop profile card -->
                <div class="rounded-md bg-gradient-to-br from-primary to-sky-blue p-4 text-white">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-20 w-20 shrink-0 overflow-hidden rounded-full border-4 border-white/80 bg-white shadow">
                            @if (filled($seller->avatar_url))
                                <img src="{{ $seller->avatar_url }}" alt="{{ $shop->shop_name }} profile photo"
                                    class="h-full w-full object-cover" />
                            @else
                                <x-avatar :user="$seller" size="xl" bg="bg-ice-blue"
                                    class="h-full w-full object-cover" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h1 class="truncate text-lg font-extrabold tracking-tight sm:text-xl">
                                {{ $shop->shop_name }}
                            </h1>
                            <p class="mt-1 text-xs text-white/80">Seller on Shopleap</p>
                            @if ($shop->line_of_business)
                                <p class="mt-1 truncate text-xs text-white/70">{{ $shop->line_of_business }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <!-- Existing Follow button -->
                        <button type="button"
                            class="flex items-center justify-center gap-2 border border-white/80 bg-transparent px-3 py-2 text-xs font-bold text-white transition hover:bg-white/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Follow
                        </button>

                        <!-- Existing Chat link -->
                        <a href="{{ route('messages.index') }}?contact={{ $seller->id }}"
                            class="flex items-center justify-center gap-2 border border-white/80 bg-transparent px-3 py-2 text-xs font-bold text-white transition hover:bg-white/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 10h8M8 14h5m-9 6l2.4-3.2A8 8 0 1112 20H4z" />
                            </svg>
                            Chat
                        </a>
                    </div>
                </div>

                <!-- Inline shop statistics -->
                <div class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                    <div class="flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0 text-charcoal" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="text-sm text-charcoal">Products: <span
                                class="font-medium text-primary">{{ $products->total() }}</span></p>
                    </div>

                    <a href="#shop-reviews" class="flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0 text-charcoal" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3z" />
                        </svg>
                        <p class="text-sm text-charcoal">Rating:
                            @if ($shopRating['count'] > 0)
                                <span class="font-medium text-primary">{{ number_format($shopRating['average'], 1) }}
                                    ({{ number_format($shopRating['count']) }} reviews)</span>
                            @else
                                <span class="font-medium text-primary">No ratings</span>
                            @endif
                        </p>
                    </a>

                    @if ($shop->location)
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-charcoal" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1116 0z" />
                                <circle cx="12" cy="10" r="2.5" stroke-width="1.7" />
                            </svg>
                            <p class="text-sm text-charcoal">Location: <span
                                    class="font-medium text-primary">{{ $shop->location }}</span></p>
                        </div>
                    @endif

                    <div class="flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0 text-charcoal" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                        </svg>
                        <p class="text-sm text-charcoal">Joined: <span
                                class="font-medium text-primary">{{ \Carbon\Carbon::parse($seller->created_at)->diffForHumans(null, true) }}
                                ago</span></p>
                    </div>
                </div>
            </div>

            @if ($shop->shop_description)
                <div
                    class="border-t border-light-gray px-4 py-3 text-sm leading-relaxed text-charcoal/70 sm:px-6 lg:px-8">
                    {{ $shop->shop_description }}
                </div>
            @endif

            <!-- Shop navigation: links only, no extra buttons -->
            <nav class="flex items-center gap-8 overflow-x-auto border-t border-light-gray px-4 sm:justify-center sm:px-6"
                aria-label="Shop navigation">
                <a href="#"
                    class="shrink-0 border-b-2 border-primary py-4 text-sm font-medium text-primary">Home</a>
                <a href="#all-products"
                    class="shrink-0 border-b-2 border-transparent py-4 text-sm font-medium text-charcoal hover:text-primary">All
                    Products</a>
            </nav>
        </section>

        <!-- PRODUCTS GRID -->
        <section id="all-products"
            class="mt-4 sm:mt-6 rounded-2xl bg-white p-4 sm:p-6 shadow-sm shadow-charcoal/5 ring-1 ring-charcoal/5">
            <div class="mb-4 sm:mb-5 flex items-center justify-between border-b border-light-gray pb-3 sm:pb-4">
                <h2 class="text-base sm:text-lg font-black tracking-tight text-charcoal">All Products</h2>
                <span class="text-xs font-medium text-charcoal/50">{{ $products->total() }} item(s)</span>
            </div>

            @if ($products->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="h-12 w-12 text-charcoal/20" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="mt-3 text-sm font-semibold text-charcoal">No products yet</p>
                    <p class="mt-1 text-xs text-charcoal/50">This seller hasn't listed any active products.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($products as $product)
                        <a href="{{ route('buyer.products.show', $product) }}"
                            class="group overflow-hidden rounded-xl border border-light-gray bg-white transition duration-200 hover:border-primary/40 hover:shadow-lg">
                            <div class="aspect-square overflow-hidden bg-ice-blue/60">
                                @if ($product->main_image_url)
                                    <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @endif
                            </div>
                            <div class="p-2.5 sm:p-3">
                                <p class="line-clamp-2 text-xs font-medium text-charcoal">{{ $product->name }}</p>
                                <p class="mt-2 text-xs sm:text-sm font-black text-primary">
                                    ₱{{ number_format($product->display_price, 2) }}
                                </p>
                                @if (($product->reviews_count ?? 0) > 0)
                                    <p class="mt-1.5 flex items-center gap-1 text-[11px] text-charcoal/50">
                                        <span class="text-amber-400 font-bold">★</span>
                                        <span
                                            class="font-semibold text-charcoal/70">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                                        <span>({{ $product->reviews_count }})</span>
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="pagination-frost mt-8 flex justify-center">
                    {{ $products->onEachSide(1)->links() }}
                </div>
            @endif
        </section>

    </main>

    <x-Buyer.message-widget />
</body>

</html>
