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

    <main class="mx-auto max-w-7xl px-5 py-6 sm:px-8">

        <!-- SHOP HEADER CARD -->
        <section class="overflow-hidden rounded-lg bg-white shadow-sm shadow-charcoal/5">

            <!-- Cover strip -->
            <div class="h-24 bg-gradient-to-r from-primary to-sky-blue sm:h-28"></div>

            <div class="px-5 pb-5 sm:px-6">
                <div class="-mt-10 flex flex-col gap-4 sm:-mt-12 sm:flex-row sm:items-end sm:justify-between">

                    <!-- Avatar + name -->
                    <div class="flex items-end gap-4">
                        <div class="shrink-0 rounded-full border-4 border-white bg-white shadow-sm shadow-charcoal/10">
                            <x-avatar :user="$seller" size="xl" bg="bg-ice-blue" />
                        </div>
                        <div class="min-w-0 pb-1">
                            <h1 class="truncate text-xl font-bold text-charcoal">{{ $shop->shop_name }}</h1>
                            @if ($shop->line_of_business)
                                <p class="mt-0.5 truncate text-xs text-charcoal/50">{{ $shop->line_of_business }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Action -->
                    <a href="{{ route('messages.index') }}?contact={{ $seller->id }}"
                        class="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h8M8 14h5m-9 6l2.4-3.2A8 8 0 1112 20H4z" />
                        </svg>
                        Message Seller
                    </a>
                </div>

                @if ($shop->shop_description)
                    <p class="mt-4 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-charcoal/70">
                        {{ $shop->shop_description }}</p>
                @endif

                <div class="my-5 h-px bg-light-gray"></div>

                <!-- Stats -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="flex items-center gap-3 rounded-xl bg-light-gray px-4 py-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-primary">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-charcoal/50">Products</p>
                            <p class="text-sm font-bold text-charcoal">{{ $products->total() }}</p>
                        </div>
                    </div>

                    <!-- Rating -->
                    <a href="#shop-reviews" class="flex items-center gap-3 rounded-xl bg-light-gray px-4 py-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-amber-400">
                            <span class="text-lg leading-none">★</span>
                        </div>
                        <div>
                            <p class="text-xs text-charcoal/50">Rating</p>
                            @if ($shopRating['count'] > 0)
                                <p class="text-sm font-bold text-charcoal">
                                    {{ number_format($shopRating['average'], 1) }} / 5
                                    <span
                                        class="text-xs font-normal text-charcoal/50">({{ number_format($shopRating['count']) }})</span>
                                </p>
                            @else
                                <p class="text-sm font-bold text-charcoal/40">No ratings yet</p>
                            @endif
                        </div>
                    </a>

                    @if ($shop->location)
                        <div class="flex items-center gap-3 rounded-xl bg-light-gray px-4 py-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-primary">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                        d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-charcoal/50">Location</p>
                                <p class="truncate text-sm font-bold text-charcoal">{{ $shop->location }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center gap-3 rounded-xl bg-light-gray px-4 py-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-primary">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-charcoal/50">Joined</p>
                            <p class="text-sm font-bold text-charcoal">
                                {{ \Carbon\Carbon::parse($seller->created_at)->diffForHumans(null, true) }} ago</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PRODUCTS GRID -->
        <section class="mt-4 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
            <div class="mb-4 flex items-center justify-between border-b border-light-gray pb-3">
                <h2 class="text-base font-bold text-charcoal">All Products</h2>
                <span class="text-xs text-charcoal/50">{{ $products->total() }} item(s)</span>
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
                            class="group overflow-hidden rounded-lg border border-light-gray bg-white transition hover:border-primary/30 hover:shadow-md">
                            <div class="aspect-square overflow-hidden bg-ice-blue/60">
                                @if ($product->main_image_url)
                                    <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition group-hover:scale-105">
                                @endif
                            </div>
                            <div class="p-2.5">
                                <p class="line-clamp-2 text-xs text-charcoal">{{ $product->name }}</p>
                                <p class="mt-1 text-sm font-bold text-primary">
                                    ₱{{ number_format($product->display_price, 2) }}</p>
                                @if (($product->reviews_count ?? 0) > 0)
                                    <p class="mt-1 flex items-center gap-1 text-[11px] text-charcoal/50">
                                        <span class="text-amber-400">★</span>
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

        <!-- SHOP REVIEWS -->
        <section id="shop-reviews" class="mt-4 scroll-mt-24 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
            <div class="mb-4 flex items-center justify-between border-b border-light-gray pb-3">
                <h2 class="text-base font-bold text-charcoal">Shop Reviews</h2>
                @if ($shopRating['count'] > 0)
                    <span class="text-xs text-charcoal/50">
                        <span class="text-amber-400">★</span>
                        {{ number_format($shopRating['average'], 1) }} · {{ number_format($shopRating['count']) }}
                        {{ \Illuminate\Support\Str::plural('review', $shopRating['count']) }}
                    </span>
                @endif
            </div>

            @if ($latestReviews->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <p class="text-sm text-charcoal/50">No reviews yet</p>
                    <p class="mt-1 text-xs text-charcoal/35">Reviews appear here after buyers receive their orders.</p>
                </div>
            @else
                <div class="divide-y divide-light-gray">
                    @foreach ($latestReviews as $review)
                        <div class="flex gap-3 py-5 first:pt-0 last:pb-0">

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold uppercase text-primary">
                                {{ mb_substr($review['buyer'], 0, 1) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5">
                                    <p class="text-sm font-semibold text-charcoal">{{ $review['buyer'] }}</p>
                                    <p class="text-sm leading-none">
                                        <span
                                            class="text-amber-400">{{ str_repeat('★', $review['rating']) }}</span><span
                                            class="text-charcoal/15">{{ str_repeat('★', 5 - $review['rating']) }}</span>
                                    </p>
                                </div>

                                <p class="mt-1 text-xs text-charcoal/40">
                                    {{ $review['created_at']->format('Y-m-d H:i') }} ·
                                    @if ($review['product_url'])
                                        <a href="{{ $review['product_url'] }}"
                                            class="font-semibold text-primary hover:underline">{{ $review['product'] }}</a>
                                    @else
                                        {{ $review['product'] }}
                                    @endif
                                </p>

                                @if (filled($review['comment']))
                                    <p class="mt-2 whitespace-pre-line text-sm text-charcoal/80">
                                        {{ $review['comment'] }}</p>
                                @endif

                                @if ($review['media']->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($review['media'] as $media)
                                            @if ($media['is_video'])
                                                <video src="{{ $media['url'] }}" controls preload="metadata"
                                                    class="h-24 w-32 rounded-lg bg-charcoal/10 object-cover"></video>
                                            @else
                                                <a href="{{ $media['url'] }}" target="_blank" rel="noopener"
                                                    class="block h-24 w-24 overflow-hidden rounded-lg bg-charcoal/10">
                                                    <img src="{{ $media['url'] }}" alt="Review photo" loading="lazy"
                                                        class="h-full w-full object-cover">
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                @if (filled($review['reply']))
                                    <div class="mt-3 rounded-lg bg-ice-blue/60 px-4 py-3">
                                        <p class="text-xs font-bold text-primary">Response from
                                            {{ $shop->shop_name }}</p>
                                        <p class="mt-1 whitespace-pre-line text-sm text-charcoal/70">
                                            {{ $review['reply'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </main>

    <x-Buyer.message-widget />
</body>

</html>
