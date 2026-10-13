<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/product-show.js'])

    <title>{{ $product->name }} | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    <main class="mx-auto max-w-7xl px-5 py-6 sm:px-8">

        <!-- BREADCRUMB -->
        <nav class="mb-4 flex items-center gap-2 text-xs text-charcoal/50">
            <a href="{{ route('buyer.products.index') }}" class="shrink-0 hover:text-primary">All Products</a>
            <span>/</span>
            <a href="{{ route('buyer.products.index', ['category' => $product->category]) }}"
                class="shrink-0 hover:text-primary">{{ $product->category }}</a>
            <span>/</span>
            <span class="truncate text-charcoal/70">{{ $product->name }}</span>
        </nav>

        <!-- MAIN PRODUCT CARD -->
        <div class="grid grid-cols-1 gap-8 rounded-lg bg-white p-6 shadow-sm shadow-charcoal/5 lg:grid-cols-2">

            <!-- IMAGE GALLERY -->
            <div>
                <div id="mainImageWrapper" class="aspect-square w-full overflow-hidden rounded-lg bg-ice-blue/60">
                    @if ($product->images->isNotEmpty())
                        <img id="mainImage" src="{{ $product->images->first()->url }}" alt="{{ $product->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-charcoal/25">
                            <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v14H4V6z" />
                            </svg>
                        </div>
                    @endif
                </div>

                @if ($product->images->count() > 1)
                    <div class="mt-3 grid grid-cols-6 gap-2">
                        @foreach ($product->images as $img)
                            <button type="button" data-thumb="{{ $img->url }}"
                                class="thumb-btn aspect-square overflow-hidden rounded-lg border-2 {{ $loop->first ? 'border-primary' : 'border-transparent' }}">
                                <img src="{{ $img->url }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- DETAILS -->
            <div>
                <!-- BADGES -->
                <div class="flex flex-wrap items-center gap-2">
                    @if ($product->created_at?->gt(now()->subDays(14)))
                        <span class="rounded bg-leaf-green/10 px-2 py-1 text-xs font-semibold text-leaf-green">New
                            Arrival</span>
                    @endif
                    @if ($product->was_price && $product->was_price > $product->display_price)
                        <span class="rounded bg-sale-red/10 px-2 py-1 text-xs font-semibold text-sale-red">
                            -{{ round((1 - $product->display_price / $product->was_price) * 100) }}% OFF
                        </span>
                    @endif
                </div>

                <h1 class="mt-3 text-2xl font-bold text-charcoal">{{ $product->name }}</h1>

                @if ($reviewStats['count'] > 0)
                    @php $avgFilled = (int) round($reviewStats['average']); @endphp
                    <a href="#reviews" class="mt-2 inline-flex items-center gap-2 text-sm">
                        <span class="font-bold text-charcoal">{{ number_format($reviewStats['average'], 1) }}</span>
                        <span class="leading-none">
                            <span class="text-amber-400">{{ str_repeat('★', $avgFilled) }}</span><span
                                class="text-charcoal/15">{{ str_repeat('★', 5 - $avgFilled) }}</span>
                        </span>
                        <span class="text-xs text-charcoal/50 underline-offset-2 hover:underline">
                            {{ number_format($reviewStats['count']) }}
                            {{ \Illuminate\Support\Str::plural('review', $reviewStats['count']) }}
                        </span>
                    </a>
                @endif

                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-primary">
                        @if ($product->options->isNotEmpty())
                            From
                        @endif
                        ₱{{ number_format($product->display_price, 2) }}
                    </span>
                    @if ($product->was_price)
                        <span
                            class="text-base text-charcoal/40 line-through">₱{{ number_format($product->was_price, 2) }}</span>
                    @endif
                </div>

                <div class="mt-4 flex items-center gap-4 text-xs text-charcoal/50">
                    @if ($product->stock_quantity < 1)
                        <span class="font-semibold text-sale-red">Out of stock</span>
                    @elseif ($product->stock_quantity <= 5)
                        <span class="font-semibold text-sale-red">Only {{ $product->stock_quantity }} left</span>
                    @else
                        <span>{{ $product->stock_quantity }} in stock</span>
                    @endif
                    <span>·</span>
                    <span>{{ $product->category }}{{ $product->subcategory ? ' — ' . $product->subcategory : '' }}</span>
                </div>

                @if ($product->seller_location)
                    <p class="mt-2 flex items-center gap-1 text-xs text-charcoal/50">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Ships from {{ $product->seller_location }}
                    </p>
                @endif

                <div class="my-5 h-px bg-light-gray"></div>

                @if ($product->options->isNotEmpty())
                    <!-- Options exist: selection happens inside the modal, not inline -->
                    <div class="rounded-xl border border-light-gray bg-ice-blue/40 p-4">
                        <p class="text-sm font-semibold text-charcoal">This product has options</p>
                        <p class="mt-1 text-xs text-charcoal/50">
                            Choose {{ $product->options->pluck('name')->implode(', ') }} when you add to cart or buy
                            now.
                        </p>
                    </div>

                    <div class="my-5 h-px bg-light-gray"></div>
                @else
                    <!-- No options: quantity stays inline -->
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-semibold text-charcoal">Quantity</span>
                        <div class="flex items-center rounded-lg border border-light-gray">
                            <button type="button" id="qtyMinus"
                                class="px-3 py-2 text-charcoal/60 hover:text-primary">−</button>
                            <input type="number" id="qtyInput" value="1" min="1"
                                max="{{ $product->stock_quantity }}"
                                class="w-14 border-x border-light-gray py-2 text-center text-sm outline-none">
                            <button type="button" id="qtyPlus"
                                class="px-3 py-2 text-charcoal/60 hover:text-primary">+</button>
                        </div>
                    </div>
                @endif

                <!-- ACTIONS -->
                <div class="mt-6 flex gap-3">
                    <button type="button" id="addToCartBtn" @disabled($product->stock_quantity < 1)
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl border-2 border-primary bg-white px-6 py-3.5 text-sm font-bold text-primary transition hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Add to Cart
                    </button>
                    <button type="button" id="buyNowBtn" @disabled($product->stock_quantity < 1)
                        class="flex flex-1 items-center justify-center rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50">
                        Buy Now
                    </button>
                </div>
            </div>

        </div>

        <!-- SELLER PROFILE BAR -->
        @if ($product->seller?->sellerProfile)
            @php $shop = $product->seller->sellerProfile; @endphp
            <div
                class="mt-4 flex flex-col gap-4 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5 sm:flex-row sm:items-center sm:justify-between">

                <!-- Seller info (clickable, goes to the shop) -->
                <a href="{{ route('buyer.shop.show', $product->seller) }}"
                    class="group flex min-w-0 items-center gap-4">
                    <x-avatar :user="$product->seller" size="lg" />
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold text-charcoal transition group-hover:text-primary">
                            {{ $shop->shop_name }}</p>
                        @if ($shop->line_of_business)
                            <p class="truncate text-xs text-charcoal/50">{{ $shop->line_of_business }}</p>
                        @endif
                        @if ($shop->location)
                            <p class="mt-0.5 flex items-center gap-1 truncate text-xs text-charcoal/40">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $shop->location }}
                            </p>
                        @endif
                    </div>
                </a>

                <!-- Buttons grouped together -->
                <div class="grid grid-cols-2 gap-3 sm:flex sm:shrink-0">
                    <a href="{{ route('messages.index') }}?contact={{ $product->seller_id }}&product={{ $product->id }}"
                        class="flex items-center justify-center gap-2 rounded-xl border-2 border-primary px-5 py-2.5 text-sm font-bold text-primary transition hover:bg-primary/5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h8M8 14h5m-9 6l2.4-3.2A8 8 0 1112 20H4z" />
                        </svg>
                        Message Seller
                    </a>
                    <a href="{{ route('buyer.shop.show', $product->seller) }}"
                        class="flex items-center justify-center gap-2 rounded-xl bg-ice-blue px-5 py-2.5 text-sm font-bold text-charcoal transition hover:bg-primary/10 hover:text-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9l1.5-5h15L21 9M3 9v11h18V9M3 9h18M9 20v-6h6v6" />
                        </svg>
                        View Shop
                    </a>
                </div>
            </div>
        @endif

        <!-- PRODUCT DESCRIPTION -->
        <div class="mt-4 rounded-lg bg-white p-6 shadow-sm shadow-charcoal/5">
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-charcoal/50">Product Description</h3>
            <p class="whitespace-pre-line text-sm leading-relaxed text-charcoal/70">
                {{ $product->description ?: 'No description provided.' }}</p>
        </div>

        <!-- BUYER REVIEWS -->
        <div id="reviews" class="mt-4 scroll-mt-24 rounded-lg bg-white p-6 shadow-sm shadow-charcoal/5">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-charcoal/50">Buyer Reviews</h3>

            @if ($reviewStats['count'] === 0)
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <svg class="h-10 w-10 text-charcoal/20" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <p class="mt-3 text-sm text-charcoal/50">No reviews yet</p>
                    <p class="mt-1 text-xs text-charcoal/35">Be the first to review this product after your purchase.
                    </p>
                </div>
            @else
                @php
                    $avg = $reviewStats['average'];
                    $filled = (int) round($avg);
                    $reviewUrl = fn(array $params) => request()->fullUrlWithQuery(
                        array_merge(['page' => null], $params),
                    ) . '#reviews';
                    $pillBase = 'rounded-full border px-4 py-1.5 text-xs font-semibold transition';
                    $pillOn = 'border-primary bg-primary text-white';
                    $pillOff = 'border-light-gray bg-white text-charcoal/70 hover:border-primary hover:text-primary';
                    $noFilter = !$ratingFilter && !$mediaFilter;
                @endphp

                <!-- SUMMARY + FILTERS -->
                <div class="flex flex-col gap-6 rounded-xl bg-ice-blue/40 p-5 md:flex-row md:items-center">

                    <div class="shrink-0 text-center md:w-40">
                        <p class="text-4xl font-bold text-primary">{{ number_format($avg, 1) }}<span
                                class="text-base font-semibold text-charcoal/40"> / 5</span></p>
                        <p class="mt-1 text-lg leading-none">
                            <span class="text-amber-400">{{ str_repeat('★', $filled) }}</span><span
                                class="text-charcoal/15">{{ str_repeat('★', 5 - $filled) }}</span>
                        </p>
                        <p class="mt-1 text-xs text-charcoal/50">
                            {{ number_format($reviewStats['count']) }}
                            {{ \Illuminate\Support\Str::plural('review', $reviewStats['count']) }}
                        </p>
                    </div>

                    <div class="flex-1 space-y-1.5">
                        @foreach ($ratingDistribution as $star => $count)
                            @php $percent = round(($count / $reviewStats['count']) * 100); @endphp
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-6 shrink-0 font-semibold text-charcoal/60">{{ $star }}★</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-charcoal/10">
                                    <div class="h-full rounded-full bg-amber-400"
                                        style="width: {{ $percent }}%">
                                    </div>
                                </div>
                                <span class="w-6 shrink-0 text-right text-charcoal/50">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ $reviewUrl(['rating' => null, 'media' => null]) }}"
                        class="{{ $pillBase }} {{ $noFilter ? $pillOn : $pillOff }}">
                        All ({{ $reviewStats['count'] }})
                    </a>
                    @foreach ($ratingDistribution as $star => $count)
                        @if ($count > 0)
                            <a href="{{ $reviewUrl(['rating' => $star, 'media' => null]) }}"
                                class="{{ $pillBase }} {{ $ratingFilter === $star && !$mediaFilter ? $pillOn : $pillOff }}">
                                {{ $star }} ★ ({{ $count }})
                            </a>
                        @endif
                    @endforeach
                    @if ($reviewStats['with_media'] > 0)
                        <a href="{{ $reviewUrl(['rating' => null, 'media' => 1]) }}"
                            class="{{ $pillBase }} {{ $mediaFilter ? $pillOn : $pillOff }}">
                            With Photos/Videos ({{ $reviewStats['with_media'] }})
                        </a>
                    @endif
                </div>

                <!-- REVIEW LIST -->
                @if ($reviews->isEmpty())
                    <p class="py-10 text-center text-sm text-charcoal/50">No reviews match this filter.</p>
                @else
                    <div class="mt-2 divide-y divide-light-gray">
                        @foreach ($reviews as $review)
                            <div class="flex gap-3 py-5">

                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold uppercase text-primary">
                                    {{ mb_substr($review['buyer'], 0, 1) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-charcoal">{{ $review['buyer'] }}</p>
                                    <p class="mt-0.5 text-sm leading-none">
                                        <span
                                            class="text-amber-400">{{ str_repeat('★', $review['rating']) }}</span><span
                                            class="text-charcoal/15">{{ str_repeat('★', 5 - $review['rating']) }}</span>
                                    </p>
                                    <p class="mt-1 text-xs text-charcoal/40">
                                        {{ $review['created_at']->format('Y-m-d H:i') }}
                                        @if ($review['variation'])
                                            | Variation: {{ $review['variation'] }}
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
                                                        <img src="{{ $media['url'] }}" alt="Review photo"
                                                            loading="lazy" class="h-full w-full object-cover">
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (filled($review['reply']))
                                        <div class="mt-3 rounded-lg bg-ice-blue/60 px-4 py-3">
                                            <p class="text-xs font-bold text-primary">
                                                Response from
                                                {{ $product->seller?->sellerProfile?->shop_name ?? 'the seller' }}
                                            </p>
                                            <p class="mt-1 whitespace-pre-line text-sm text-charcoal/70">
                                                {{ $review['reply'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pagination-frost mt-4 flex justify-center">
                        {{ $reviews->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- RECOMMENDED PRODUCTS -->
        @if ($related->isNotEmpty())
            <section class="mt-8">
                <h2 class="mb-3 text-base font-bold text-charcoal">You May Also Like</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ($related as $item)
                        <a href="{{ route('buyer.products.show', $item) }}"
                            class="group overflow-hidden rounded-lg bg-white shadow-sm shadow-charcoal/5 transition hover:shadow-md">
                            <div class="aspect-square overflow-hidden bg-ice-blue/60">
                                @if ($item->main_image_url)
                                    <img src="{{ $item->main_image_url }}" alt="{{ $item->name }}"
                                        class="h-full w-full object-cover transition group-hover:scale-105">
                                @endif
                            </div>
                            <div class="p-2.5">
                                <p class="line-clamp-2 text-xs text-charcoal">{{ $item->name }}</p>
                                <p class="mt-1 text-sm font-bold text-primary">
                                    ₱{{ number_format($item->display_price, 2) }}</p>
                                @if (($item->reviews_count ?? 0) > 0)
                                    <p class="mt-1 flex items-center gap-1 text-[11px] text-charcoal/50">
                                        <span class="text-amber-400">★</span>
                                        <span
                                            class="font-semibold text-charcoal/70">{{ number_format($item->reviews_avg_rating, 1) }}</span>
                                        <span>({{ $item->reviews_count }})</span>
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- MORE PRODUCTS TO EXPLORE -->
        @if ($explore->isNotEmpty())
            <section class="mt-8">
                <h2 class="mb-3 text-base font-bold text-charcoal">More Products to Explore</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ($explore as $item)
                        <a href="{{ route('buyer.products.show', $item) }}"
                            class="group overflow-hidden rounded-lg bg-white shadow-sm shadow-charcoal/5 transition hover:shadow-md">
                            <div class="aspect-square overflow-hidden bg-ice-blue/60">
                                @if ($item->main_image_url)
                                    <img src="{{ $item->main_image_url }}" alt="{{ $item->name }}"
                                        class="h-full w-full object-cover transition group-hover:scale-105">
                                @endif
                            </div>
                            <div class="p-2.5">
                                <p class="line-clamp-2 text-xs text-charcoal">{{ $item->name }}</p>
                                <p class="mt-1 text-sm font-bold text-primary">
                                    ₱{{ number_format($item->display_price, 2) }}</p>
                                @if (($item->reviews_count ?? 0) > 0)
                                    <p class="mt-1 flex items-center gap-1 text-[11px] text-charcoal/50">
                                        <span class="text-amber-400">★</span>
                                        <span
                                            class="font-semibold text-charcoal/70">{{ number_format($item->reviews_avg_rating, 1) }}</span>
                                        <span>({{ $item->reviews_count }})</span>
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

    </main>


    <!-- TOAST -->
    <div id="toast"
        class="fixed bottom-6 right-6 z-[80] hidden max-w-sm rounded-xl px-5 py-3.5 text-sm font-medium text-white shadow-xl transition">
    </div>


    <!-- SELECT VARIANT MODAL (Shopee-style) -->
    @if ($product->options->isNotEmpty())
        <div id="variantModalOverlay"
            class="fixed inset-0 z-[95] hidden items-end justify-center bg-charcoal/50 p-0 sm:items-center sm:p-4">
            <div
                class="max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-2xl sm:max-w-md sm:rounded-2xl">

                <div class="flex items-start gap-4">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url }}" alt="{{ $product->name }}"
                                class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p id="modalPrice" class="text-xl font-bold text-primary">
                            ₱{{ number_format($product->display_price, 2) }}</p>
                        <p id="modalStock" class="mt-1 text-xs text-charcoal/50">
                            {{ $product->stock_quantity }} in stock</p>
                    </div>
                    <button type="button" id="modalCloseBtn"
                        class="shrink-0 rounded-full p-1.5 text-charcoal/40 transition hover:bg-ice-blue hover:text-charcoal">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="my-5 h-px bg-light-gray"></div>

                <div class="space-y-4">
                    @foreach ($product->options as $option)
                        <div>
                            <p class="mb-2 text-sm font-semibold text-charcoal">{{ $option->name }}</p>
                            <div class="flex flex-wrap gap-2 variant-option-group"
                                data-option-name="{{ $option->name }}">
                                @foreach ($option->values as $value)
                                    <button type="button"
                                        class="variant-value-btn rounded-lg border border-light-gray px-4 py-2 text-xs font-semibold text-charcoal transition hover:border-primary"
                                        data-option-name="{{ $option->name }}"
                                        data-option-value="{{ $value }}">
                                        {{ $value }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <p id="modalVariantWarning" class="hidden text-xs font-semibold text-sale-red">
                        Please select all options above.
                    </p>
                </div>

                <div class="my-5 h-px bg-light-gray"></div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-charcoal">Quantity</span>
                    <div class="flex items-center rounded-lg border border-light-gray">
                        <button type="button" id="modalQtyMinus"
                            class="px-3 py-2 text-charcoal/60 hover:text-primary">−</button>
                        <input type="number" id="modalQtyInput" value="1" min="1"
                            class="w-14 border-x border-light-gray py-2 text-center text-sm outline-none">
                        <button type="button" id="modalQtyPlus"
                            class="px-3 py-2 text-charcoal/60 hover:text-primary">+</button>
                    </div>
                </div>

                <button type="button" id="modalConfirmBtn" disabled
                    class="mt-6 w-full rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:bg-charcoal/20 disabled:shadow-none">
                    Confirm
                </button>

            </div>
        </div>
    @endif


    <!-- ADDED TO CART — CONFIRMATION MODAL -->
    <div id="cartModalOverlay" class="fixed inset-0 z-[90] hidden items-center justify-center bg-charcoal/50 p-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">

            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <p class="mt-4 text-center text-base font-bold text-charcoal">Added to Cart</p>

            <div class="mt-4 flex items-center gap-3 rounded-xl border border-light-gray p-3">
                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                    @if ($product->images->isNotEmpty())
                        <img src="{{ $product->images->first()->url }}" alt="{{ $product->name }}"
                            class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="line-clamp-2 text-sm text-charcoal">{{ $product->name }}</p>
                    <p id="cartModalQty" class="mt-0.5 text-xs text-charcoal/50">Qty: 1</p>
                </div>
            </div>

            <div class="mt-5 flex gap-3">
                <button type="button" id="cartModalContinueBtn"
                    class="flex-1 rounded-xl border-2 border-primary px-4 py-2.5 text-sm font-bold text-primary transition hover:bg-primary/5">
                    Continue Shopping
                </button>
                <a href="{{ route('buyer.cart.index') }}"
                    class="flex-1 rounded-xl bg-primary px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-sky-blue">
                    View Cart
                </a>
            </div>

        </div>
    </div>

    <div id="productPageData" data-checkout-route="{{ route('buyer.checkout.index') }}"></div>

    @php
        $productDataForJs = [
            'productId' => $product->id,
            'basePrice' => (float) $product->display_price,
            'baseStock' => (int) $product->stock_quantity,
            'hasOptions' => $product->options->isNotEmpty(),
            'variants' => $product->variants->map(
                fn($v) => [
                    'id' => $v->id,
                    'options' => $v->options,
                    'price' => (float) $v->price,
                    'stock_quantity' => $v->stock_quantity,
                ],
            ),
        ];
    @endphp

    <!-- Data bridge: dynamic PHP data for the external JS module to read -->
    <script id="productData" type="application/json">
        @json($productDataForJs)
    </script>

</body>

</html>
