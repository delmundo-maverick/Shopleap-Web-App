<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ $seller->sellerProfile->shop_name }} | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    @php
        $shop = $seller->sellerProfile;

        // Same button styles as the orders page.
        $btnBase =
            'inline-flex min-h-10 w-full items-center justify-center rounded-lg px-5 py-2 text-xs font-bold transition-colors sm:w-auto';
        $btnOutline = "$btnBase border border-line bg-white text-charcoal hover:border-primary hover:text-primary";
    @endphp

    <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-4 sm:px-6 sm:py-6 lg:px-8">

        <!-- SHOP HEADER: compact profile + inline statistics -->
        <section class="overflow-hidden rounded-xl border border-line bg-white">
            <div
                class="grid gap-5 bg-white px-4 py-5 sm:px-6 lg:grid-cols-[390px_1fr] lg:items-center lg:gap-10 lg:px-8">
                <!-- Shop profile card -->
                <div
                    class="rounded-xl border border-primary/30 bg-gradient-to-br from-primary to-sky-blue p-4 text-white">
                    <div class="flex items-center gap-3">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full border-4 border-white/80 bg-white">
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
                        <!-- Follow / unfollow -->
                        <x-Buyer.follow-button :seller="$seller" :followers-count="$followersCount" :is-following="$isFollowing" variant="inverse"
                            class="w-full" />

                        <!-- Chat link -->
                        <a href="{{ route('messages.index') }}?contact={{ $seller->id }}"
                            class="flex min-h-10 items-center justify-center gap-2 border border-white/80 bg-transparent px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
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
                <div class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line bg-ice-blue text-charcoal">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </span>
                        <p class="text-sm text-charcoal/70">Products: <span
                                class="font-semibold tabular-nums text-primary">{{ $products->total() }}</span></p>
                    </div>

                    <a href="#shop-reviews"
                        class="group flex items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line bg-ice-blue text-charcoal transition-colors group-hover:border-primary group-hover:text-primary">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3z" />
                            </svg>
                        </span>
                        <p class="text-sm text-charcoal/70">Rating:
                            @if ($shopRating['count'] > 0)
                                <span
                                    class="font-semibold tabular-nums text-primary">{{ number_format($shopRating['average'], 1) }}
                                    ({{ number_format($shopRating['count']) }} reviews)</span>
                            @else
                                <span class="font-semibold text-primary">No ratings</span>
                            @endif
                        </p>
                    </a>

                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line bg-ice-blue text-charcoal">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <p class="text-sm text-charcoal/70">Followers: <span x-data
                                x-text="$store.follow.followerCount({{ $seller->id }})"
                                class="font-semibold tabular-nums text-primary">{{ number_format($followersCount) }}</span>
                        </p>
                    </div>

                    @if ($shop->location)
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line bg-ice-blue text-charcoal">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                        d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1116 0z" />
                                    <circle cx="12" cy="10" r="2.5" stroke-width="1.7" />
                                </svg>
                            </span>
                            <p class="text-sm text-charcoal/70">Location: <span
                                    class="font-semibold text-primary">{{ $shop->location }}</span></p>
                        </div>
                    @endif

                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line bg-ice-blue text-charcoal">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                            </svg>
                        </span>
                        <p class="text-sm text-charcoal/70">Joined: <span
                                class="font-semibold text-primary">{{ \Carbon\Carbon::parse($seller->created_at)->diffForHumans(null, true) }}
                                ago</span></p>
                    </div>
                </div>
            </div>

            @if ($shop->shop_description)
                <div
                    class="border-t border-line bg-ice-blue px-4 py-3 text-xs leading-relaxed text-charcoal/70 sm:px-6 lg:px-8">
                    {{ $shop->shop_description }}
                </div>
            @endif

            <!-- Shop navigation: same tab style as the orders page -->
            <nav class="flex overflow-x-auto border-t border-line [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                aria-label="Shop navigation">
                <div class="flex min-w-max flex-1 sm:min-w-0">
                    <a href="#"
                        class="flex flex-1 items-center justify-center whitespace-nowrap border-b-2 border-primary px-4 py-3.5 text-center text-sm font-semibold text-primary transition-colors">Home</a>
                    <a href="#all-products"
                        class="flex flex-1 items-center justify-center whitespace-nowrap border-b-2 border-transparent px-4 py-3.5 text-center text-sm font-semibold text-charcoal/60 transition-colors hover:text-charcoal">All
                        Products</a>
                    <a href="#shop-reviews"
                        class="flex flex-1 items-center justify-center whitespace-nowrap border-b-2 border-transparent px-4 py-3.5 text-center text-sm font-semibold text-charcoal/60 transition-colors hover:text-charcoal">Reviews</a>
                </div>
            </nav>
        </section>

        <!-- PRODUCTS GRID -->
        <section id="all-products"
            class="mt-3 scroll-mt-24 overflow-hidden rounded-xl border border-line bg-white sm:mt-4">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <h2 class="text-sm font-bold text-charcoal sm:text-base">All Products</h2>
                <span class="rounded-sm bg-ice-blue px-1.5 py-0.5 text-[10px] font-bold text-charcoal/60">
                    {{ $products->total() }} item(s)
                </span>
            </div>

            <div class="p-4 sm:p-5">
                @if ($products->isEmpty())
                    <div class="flex flex-col items-center justify-center p-8 text-center sm:p-16">
                        <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="mt-4 text-sm font-semibold text-charcoal">No products yet</p>
                        <p class="mt-1 text-xs text-charcoal/60">This seller hasn't listed any active products.</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-6">
                        @foreach ($products as $product)
                            <a href="{{ route('buyer.products.show', $product) }}"
                                class="group flex flex-col overflow-hidden rounded-xl border border-line bg-white transition-colors hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                                <div class="aspect-square overflow-hidden border-b border-line bg-ice-blue">
                                    @if ($product->main_image_url)
                                        <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                            loading="lazy"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @endif
                                </div>
                                <div class="flex flex-1 flex-col p-2.5 sm:p-3">
                                    <p class="line-clamp-2 min-h-[2rem] text-xs font-semibold text-charcoal">
                                        {{ $product->name }}</p>
                                    <p class="mt-2 text-xs font-bold tabular-nums text-primary sm:text-sm">
                                        ₱{{ number_format($product->display_price, 2) }}
                                    </p>
                                    @if (($product->reviews_count ?? 0) > 0)
                                        <p class="mt-1.5 flex items-center gap-1 text-[11px] text-charcoal/60">
                                            <span class="font-bold text-amber-400">★</span>
                                            <span
                                                class="font-semibold text-charcoal/70">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                                            <span>({{ $product->reviews_count }})</span>
                                        </p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="pagination-frost mt-6 flex justify-center overflow-x-auto">
                        {{ $products->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </section>

        <!-- SHOP REVIEWS -->
        <section id="shop-reviews"
            class="mt-3 scroll-mt-24 overflow-hidden rounded-xl border border-line bg-white sm:mt-4">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <h2 class="text-sm font-bold text-charcoal sm:text-base">Shop Reviews</h2>
                @if ($shopRating['count'] > 0)
                    <span class="rounded-sm bg-ice-blue px-1.5 py-0.5 text-[10px] font-bold text-charcoal/60">
                        <span class="text-amber-400">★</span> {{ number_format($shopRating['average'], 1) }}
                        ({{ number_format($shopRating['count']) }})
                    </span>
                @endif
            </div>

            @if ($latestReviews->isEmpty())
                <div class="flex flex-col items-center justify-center p-8 text-center sm:p-16">
                    <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8 10h8M8 14h5m-9 6l2.4-3.2A8 8 0 1112 20H4z" />
                    </svg>
                    <p class="mt-4 text-sm font-semibold text-charcoal">No reviews yet</p>
                </div>
            @else
                <div class="divide-y divide-line">
                    @foreach ($latestReviews as $review)
                        <article class="px-4 py-4 sm:px-5">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-charcoal">{{ $review['buyer'] }}</p>
                                <p class="text-xs text-charcoal/60">{{ $review['created_at']->format('M d, Y') }}
                                </p>
                            </div>

                            <p class="mt-1 text-sm leading-none" aria-label="{{ $review['rating'] }} out of 5 stars">
                                <span class="text-amber-400">{{ str_repeat('★', $review['rating']) }}</span><span
                                    class="text-charcoal/15">{{ str_repeat('★', 5 - $review['rating']) }}</span>
                            </p>

                            <p class="mt-2 text-xs">
                                @if ($review['product_url'])
                                    <a href="{{ $review['product_url'] }}"
                                        class="inline-block max-w-full truncate rounded-sm border border-line bg-ice-blue px-2 py-0.5 font-semibold text-charcoal/70 transition-colors hover:border-primary hover:text-primary">{{ $review['product'] }}</a>
                                @else
                                    <span
                                        class="inline-block max-w-full truncate rounded-sm border border-line bg-ice-blue px-2 py-0.5 font-semibold text-charcoal/70">{{ $review['product'] }}</span>
                                @endif
                            </p>

                            @if (filled($review['comment']))
                                <p
                                    class="mt-2 whitespace-pre-line break-words text-sm leading-relaxed text-charcoal/80">
                                    {{ $review['comment'] }}
                                </p>
                            @endif

                            @if ($review['media']->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($review['media'] as $media)
                                        @if ($media['is_video'])
                                            <video src="{{ $media['url'] }}" controls preload="metadata"
                                                class="h-24 w-32 rounded-lg border border-line bg-ice-blue object-cover"></video>
                                        @else
                                            <a href="{{ $media['url'] }}" target="_blank" rel="noopener"
                                                class="block h-20 w-20 overflow-hidden rounded-lg border border-line bg-ice-blue transition-colors hover:border-primary">
                                                <img src="{{ $media['url'] }}" alt="Review photo" loading="lazy"
                                                    class="h-full w-full object-cover">
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @if (filled($review['reply']))
                                <div
                                    class="mt-3 rounded-lg border border-line border-l-4 border-l-primary bg-ice-blue px-3 py-3">
                                    <p class="text-xs font-bold text-primary">Response from {{ $shop->shop_name }}
                                    </p>
                                    <p
                                        class="mt-1 whitespace-pre-line break-words text-xs leading-relaxed text-charcoal/70">
                                        {{ $review['reply'] }}
                                    </p>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

    </main>

    <x-Buyer.message-widget />

    <x-Buyer.seller-follow-data :ids="$isFollowing ? [$seller->id] : []" :counts="[$seller->id => $followersCount]" :total="$followingCount" />
</body>

</html>
