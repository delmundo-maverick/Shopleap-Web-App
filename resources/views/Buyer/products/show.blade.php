<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/product-show.js'])

    <title>{{ $product->name }} | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    @php
        $hasDiscount = $product->was_price && $product->was_price > $product->display_price;
        $hasOptions = $product->options->isNotEmpty();
    @endphp

    <main class="mx-auto w-full max-w-7xl flex-1 px-0 py-0 pb-28 sm:px-5 sm:py-5 sm:pb-8 lg:px-8">

        {{-- BREADCRUMB --}}
        <nav aria-label="Breadcrumb"
            class="flex min-w-0 items-center gap-1.5 overflow-hidden px-3 py-2.5 text-[11px] text-charcoal/50 sm:mb-1 sm:gap-2 sm:px-0 sm:py-2 sm:text-sm">
            <a href="{{ route('buyer.products.index') }}"
                class="shrink-0 py-1 text-primary hover:underline">Products</a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('buyer.products.index', ['category' => $product->category]) }}"
                class="max-w-[30%] shrink truncate py-1 text-primary hover:underline sm:max-w-none">
                {{ $product->category }}
            </a>
            <span aria-hidden="true">›</span>
            <span class="min-w-0 truncate text-charcoal/70">{{ $product->name }}</span>
        </nav>

        {{-- PRODUCT CARD --}}
        <section class="overflow-hidden bg-white shadow-sm shadow-charcoal/5 sm:rounded-lg">

            <div
                class="grid grid-cols-1 gap-0 lg:grid-cols-[440px_minmax(0,1fr)] lg:gap-8 lg:p-5 xl:grid-cols-[470px_minmax(0,1fr)]">

                {{-- IMAGE GALLERY --}}
                <div class="min-w-0">
                    <div id="mainImageWrapper"
                        class="relative aspect-square w-full overflow-hidden bg-ice-blue/50 sm:max-h-[520px] lg:max-h-none">

                        @if ($product->main_image_url)
                            <img id="mainImage" src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                fetchpriority="high" class="h-full w-full object-contain">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-charcoal/25">
                                <svg class="h-12 w-12 sm:h-16 sm:w-16" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v14H4V6z" />
                                </svg>
                            </div>
                        @endif

                        @if ($product->images->count() > 1)
                            <span
                                class="absolute bottom-3 right-3 rounded-full bg-charcoal/70 px-2.5 py-1 text-[10px] font-semibold text-white sm:text-xs">
                                {{ $product->images->count() }} photos
                            </span>
                        @endif
                    </div>

                    {{-- THUMBNAILS --}}
                    @if ($product->images->count() > 1)
                        <div class="flex gap-2 overflow-x-auto px-3 py-3 sm:px-0" aria-label="Product images">
                            @foreach ($product->images as $img)
                                <button type="button" data-thumb="{{ $img->url }}"
                                    aria-label="View product image {{ $loop->iteration }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    class="thumb-btn h-14 w-14 shrink-0 overflow-hidden border-2 bg-ice-blue/50 transition sm:h-[72px] sm:w-[72px] {{ $loop->first ? 'border-primary' : 'border-transparent hover:border-primary/50' }}">
                                    <img src="{{ $img->url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- PRODUCT DETAILS --}}
                <div class="min-w-0 px-3 pb-5 pt-1 sm:px-0 lg:pb-0 lg:pt-0">

                    {{-- TITLE --}}
                    <h1
                        class="break-words text-base font-semibold leading-snug text-charcoal sm:text-xl lg:text-[22px]">
                        @if ($product->created_at?->gt(now()->subDays(14)))
                            <span
                                class="mr-1.5 inline-block -translate-y-0.5 rounded bg-leaf-green px-1.5 py-0.5 align-middle text-[10px] font-bold uppercase text-white">
                                New
                            </span>
                        @endif
                        {{ $product->name }}
                    </h1>

                    {{-- PRODUCT RATINGS --}}
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        @if ($reviewStats['count'] > 0)
                            @php
                                $avgFilled = (int) round($reviewStats['average']);
                            @endphp

                            <a href="#reviews" class="flex items-center gap-1.5">
                                <span class="font-semibold text-primary underline">
                                    {{ number_format($reviewStats['average'], 1) }}
                                </span>
                                <span class="leading-none">
                                    <span class="text-amber-400">{{ str_repeat('★', $avgFilled) }}</span><span
                                        class="text-charcoal/15">{{ str_repeat('★', 5 - $avgFilled) }}</span>
                                </span>
                            </a>

                            <span class="hidden h-4 w-px bg-light-gray sm:block"></span>

                            <a href="#reviews" class="text-charcoal/60 hover:text-primary">
                                <span class="font-semibold text-charcoal">
                                    {{ number_format($reviewStats['count']) }}
                                </span>
                                {{ \Illuminate\Support\Str::plural('Rating', $reviewStats['count']) }}
                            </a>
                        @else
                            <span class="text-xs text-charcoal/50 sm:text-sm">No ratings yet</span>
                        @endif
                    </div>

                    {{-- PRICE BANNER --}}
                    <div class="mt-3 rounded bg-ice-blue/70 px-4 py-3 sm:mt-4 sm:py-4">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="text-2xl font-bold leading-none text-primary sm:text-3xl">
                                @if ($hasOptions)
                                    <span class="text-sm font-semibold sm:text-base">From</span>
                                @endif
                                ₱{{ number_format($product->display_price, 2) }}
                            </span>

                            @if ($hasDiscount)
                                <span class="text-sm text-charcoal/40 line-through">
                                    ₱{{ number_format($product->was_price, 2) }}
                                </span>
                                <span class="rounded bg-sale-red/10 px-1.5 py-0.5 text-xs font-bold text-sale-red">
                                    -{{ round((1 - $product->display_price / $product->was_price) * 100) }}%
                                </span>
                            @endif
                        </div>

                        @if ($product->liveDiscount?->ends_at)
                            <p class="mt-1.5 text-[11px] font-semibold text-sale-red sm:text-xs">
                                Sale ends {{ $product->liveDiscount->ends_at->format('M d, Y g:i A') }}
                            </p>
                        @endif
                    </div>

                    {{-- PRODUCT DETAILS --}}
                    <div class="mt-4 space-y-4 text-sm sm:mt-5 sm:space-y-5">

                        @if ($product->seller_location)
                            <div
                                class="grid grid-cols-[90px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[110px_minmax(0,1fr)]">
                                <span class="text-charcoal/55">Shipping</span>
                                <span class="flex min-w-0 items-start gap-1.5 text-charcoal">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-leaf-green" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM17 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                                    </svg>
                                    <span>Ships from {{ $product->seller_location }}</span>
                                </span>
                            </div>
                        @endif

                        <div
                            class="grid grid-cols-[90px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[110px_minmax(0,1fr)]">
                            <span class="text-charcoal/55">Payment</span>
                            <span class="text-charcoal">Cash on Delivery (COD)</span>
                        </div>

                        {{-- PRODUCT VARIATIONS --}}
                        @if ($hasOptions)
                            @foreach ($product->options as $option)
                                <div
                                    class="grid grid-cols-[90px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[110px_minmax(0,1fr)]">
                                    <span class="pt-2 text-charcoal/55">{{ $option->name }}</span>
                                    <div class="flex flex-wrap gap-2 variant-option-group"
                                        data-option-name="{{ $option->name }}">
                                        @foreach ($option->values as $value)
                                            <button type="button" aria-pressed="false"
                                                class="variant-value-btn min-h-9 rounded-sm border border-light-gray bg-white px-3 py-1.5 text-sm text-charcoal transition hover:border-primary hover:text-primary active:bg-primary/5"
                                                data-option-name="{{ $option->name }}"
                                                data-option-value="{{ $value }}">
                                                {{ $value }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        {{-- HIDDEN JAVASCRIPT TRIGGER (desktop/mobile buttons proxy to it) --}}
                        <button type="button" id="addToCartBtn" class="hidden" aria-hidden="true" tabindex="-1"
                            @disabled($product->stock_quantity < 1)></button>

                        {{-- QUANTITY AND STOCK --}}
                        <div
                            class="grid grid-cols-[90px_minmax(0,1fr)] items-center gap-3 sm:grid-cols-[110px_minmax(0,1fr)]">
                            <span class="text-charcoal/55">Quantity</span>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                @if (!$hasOptions)
                                    <div class="flex items-center overflow-hidden rounded-sm border border-light-gray">
                                        <button type="button" id="qtyMinus" aria-label="Decrease quantity"
                                            @disabled($product->stock_quantity < 1)
                                            class="flex h-9 w-9 items-center justify-center text-lg text-charcoal/60 transition hover:bg-ice-blue hover:text-primary disabled:opacity-40">
                                            −
                                        </button>

                                        <input type="number" id="qtyInput"
                                            value="{{ $product->stock_quantity > 0 ? 1 : 0 }}"
                                            min="{{ $product->stock_quantity > 0 ? 1 : 0 }}"
                                            max="{{ $product->stock_quantity }}" aria-label="Product quantity"
                                            @disabled($product->stock_quantity < 1)
                                            class="h-9 w-12 border-x border-light-gray text-center text-sm font-semibold outline-none">

                                        <button type="button" id="qtyPlus" aria-label="Increase quantity"
                                            @disabled($product->stock_quantity < 1)
                                            class="flex h-9 w-9 items-center justify-center text-lg text-charcoal/60 transition hover:bg-ice-blue hover:text-primary disabled:opacity-40">
                                            +
                                        </button>
                                    </div>
                                @endif

                                @if ($product->stock_quantity < 1)
                                    <span class="text-xs font-bold uppercase text-sale-red">Out of stock</span>
                                @elseif ($product->stock_quantity <= 5)
                                    <span class="text-xs font-bold uppercase text-sale-red">
                                        Only {{ $product->stock_quantity }} left
                                    </span>
                                @else
                                    <span class="text-xs font-semibold uppercase text-charcoal/60">
                                        {{ $hasOptions ? 'In stock' : $product->stock_quantity . ' in stock' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- DESKTOP PURCHASE ACTIONS --}}
                    <div class="mt-6 hidden gap-3 sm:flex">
                        <button type="button" @if ($hasOptions) data-open-variant="true" @endif
                            @disabled($product->stock_quantity < 1)
                            class="desktop-add-to-cart flex min-h-12 min-w-[180px] flex-1 items-center justify-center gap-2 rounded-sm border border-primary bg-primary/5 px-4 py-3 text-sm font-semibold text-primary transition hover:bg-primary/10 disabled:cursor-not-allowed disabled:opacity-50 lg:max-w-[200px]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5m1.6 8l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17" />
                                <circle cx="10" cy="20" r="1.5" />
                                <circle cx="17" cy="20" r="1.5" />
                            </svg>
                            Add To Cart
                        </button>

                        <button type="button" id="buyNowBtn" @disabled($product->stock_quantity < 1)
                            class="flex min-h-12 min-w-[180px] flex-1 items-center justify-center rounded-sm bg-primary px-4 py-3 text-sm font-semibold text-white shadow-md shadow-primary/15 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50 lg:max-w-[200px]">
                            Buy Now
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- SELLER INFORMATION --}}
        @if ($product->seller?->sellerProfile && $sellerStats)
            @php
                $shop = $product->seller->sellerProfile;
                $seller = $product->seller;
            @endphp

            <section aria-label="Seller information"
                class="mt-2 bg-white p-4 shadow-sm shadow-charcoal/5 sm:mt-4 sm:rounded-lg sm:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:gap-8">

                    {{-- SHOP PROFILE --}}
                    <div
                        class="flex min-w-0 items-center gap-4 lg:w-[420px] lg:shrink-0 lg:border-r lg:border-light-gray lg:pr-8">
                        <a href="{{ route('buyer.shop.show', $seller) }}" class="shrink-0">
                            <x-avatar :user="$seller" size="xl" />
                        </a>

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('buyer.shop.show', $seller) }}" class="group block min-w-0">
                                <p class="truncate text-base font-semibold text-charcoal group-hover:text-primary">
                                    {{ $shop->shop_name }}
                                </p>
                            </a>

                            @if ($shop->location)
                                <p class="mt-0.5 flex items-center gap-1 text-xs text-charcoal/50">
                                    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1116 0z" />
                                        <circle cx="12" cy="10" r="2.5" stroke-width="2" />
                                    </svg>
                                    <span class="truncate">{{ $shop->location }}</span>
                                </p>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <a href="{{ route('messages.index') }}?contact={{ $product->seller_id }}&product={{ $product->id }}"
                                    class="inline-flex min-h-9 items-center gap-1.5 rounded-sm border border-primary bg-primary/5 px-3.5 py-1.5 text-sm font-medium text-primary transition hover:bg-primary/10">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h8M8 14h5m-9 6l2.4-3.2A8 8 0 1112 20H4z" />
                                    </svg>
                                    Chat Now
                                </a>

                                <a href="{{ route('buyer.shop.show', $seller) }}"
                                    class="inline-flex min-h-9 items-center gap-1.5 rounded-sm border border-light-gray bg-white px-3.5 py-1.5 text-sm font-medium text-charcoal transition hover:border-primary hover:text-primary">
                                    <svg class="h-4 w-4 shrink-0 text-charcoal/50" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9l1.5-5h15L21 9M3 9v11h18V9M3 9h18M9 20v-6h6v6" />
                                    </svg>
                                    View Shop
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- SELLER STATISTICS --}}
                    <dl class="grid flex-1 grid-cols-1 gap-x-12 gap-y-3 text-sm sm:grid-cols-2">

                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-charcoal/55">Ratings</dt>
                            <dd>
                                @if ($sellerStats['rating_count'] > 0)
                                    <a href="{{ route('buyer.shop.show', $seller) }}#shop-reviews"
                                        class="font-semibold text-primary hover:underline">
                                        <span class="text-amber-400">★</span>
                                        {{ number_format($sellerStats['rating_average'], 1) }}
                                        <span class="font-normal text-charcoal/50">
                                            ({{ number_format($sellerStats['rating_count']) }})
                                        </span>
                                    </a>
                                @else
                                    <span class="text-charcoal/40">None yet</span>
                                @endif
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-charcoal/55">Joined</dt>
                            <dd class="font-semibold text-primary">
                                {{ $seller->created_at->diffForHumans(null, true) }} ago
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-charcoal/55">Products</dt>
                            <dd>
                                <a href="{{ route('buyer.shop.show', $seller) }}#all-products"
                                    class="font-semibold text-primary hover:underline">
                                    {{ number_format($sellerStats['products']) }}
                                </a>
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-charcoal/55">Followers</dt>
                            <dd x-data x-text="$store.follow.followerCount({{ $seller->id }})"
                                class="font-semibold text-primary">{{ number_format($sellerStats['followers']) }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        @endif

        {{-- BELOW THE FOLD --}}
        <div class="space-y-2 sm:space-y-4 sm:pt-4">

            {{-- PRODUCT DESCRIPTION --}}
            <section class="bg-white p-4 shadow-sm shadow-charcoal/5 sm:rounded-lg sm:p-6">
                <h2
                    class="rounded bg-ice-blue/70 px-3 py-2.5 text-sm font-semibold uppercase text-charcoal/70 sm:text-base">
                    Product Description
                </h2>

                <p class="mt-4 whitespace-pre-line break-words px-1 text-sm leading-relaxed text-charcoal/75">
                    {{ $product->description ?: 'No description provided.' }}
                </p>
            </section>

            {{-- BUYER REVIEWS --}}
            <section id="reviews"
                class="scroll-mt-20 bg-white p-4 shadow-sm shadow-charcoal/5 sm:scroll-mt-24 sm:rounded-lg sm:p-6">
                <h2
                    class="rounded bg-ice-blue/70 px-3 py-2.5 text-sm font-semibold uppercase text-charcoal/70 sm:text-base">
                    Product Ratings
                </h2>

                @if ($reviewStats['count'] === 0)
                    <div class="flex flex-col items-center justify-center py-8 text-center sm:py-10">
                        <svg class="h-9 w-9 text-charcoal/20 sm:h-10 sm:w-10" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        <p class="mt-3 text-sm text-charcoal/55">No reviews yet</p>
                        <p class="mt-1 max-w-xs text-xs leading-relaxed text-charcoal/40">
                            Be the first to review this product after your purchase.
                        </p>
                    </div>
                @else
                    @php
                        $avg = $reviewStats['average'];
                        $filled = (int) round($avg);

                        $reviewUrl = fn(array $params) => request()->fullUrlWithQuery(
                            array_merge(['page' => null], $params),
                        ) . '#reviews';

                        $pillBase =
                            'inline-flex shrink-0 items-center rounded-sm border px-3 py-2 text-[11px] font-medium transition sm:px-4 sm:text-xs';
                        $pillOn = 'border-primary bg-primary text-white';
                        $pillOff =
                            'border-light-gray bg-white text-charcoal/70 hover:border-primary hover:text-primary';
                        $noFilter = !$ratingFilter && !$mediaFilter;
                    @endphp

                    {{-- RATING SUMMARY --}}
                    <div
                        class="mt-4 grid grid-cols-1 gap-4 rounded-lg bg-ice-blue/50 p-4 sm:grid-cols-[auto_minmax(0,1fr)] sm:gap-6 sm:p-5">
                        <div class="flex items-center gap-4 sm:block sm:w-32 sm:text-center">
                            <div>
                                <p class="text-3xl font-extrabold leading-none text-primary sm:text-4xl">
                                    {{ number_format($avg, 1) }}
                                    <span class="text-sm font-semibold text-charcoal/40">/ 5</span>
                                </p>

                                <p class="mt-1 text-base leading-none sm:text-lg">
                                    <span class="text-amber-400">{{ str_repeat('★', $filled) }}</span><span
                                        class="text-charcoal/15">{{ str_repeat('★', 5 - $filled) }}</span>
                                </p>
                            </div>

                            <p class="text-xs text-charcoal/50 sm:mt-2">
                                {{ number_format($reviewStats['count']) }}
                                {{ \Illuminate\Support\Str::plural('review', $reviewStats['count']) }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            @foreach ($ratingDistribution as $star => $count)
                                @php
                                    $percent = round(($count / $reviewStats['count']) * 100);
                                @endphp

                                <div class="flex items-center gap-2 text-[11px] sm:text-xs">
                                    <span class="w-6 shrink-0 font-semibold text-charcoal/60">
                                        {{ $star }}★
                                    </span>

                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-charcoal/10">
                                        <div class="h-full rounded-full bg-amber-400"
                                            style="width: {{ $percent }}%"></div>
                                    </div>

                                    <span class="w-6 shrink-0 text-right text-charcoal/50">
                                        {{ $count }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- REVIEW FILTERS --}}
                    <div class="mt-4 flex gap-2 overflow-x-auto pb-2">
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
                                Photos/Videos ({{ $reviewStats['with_media'] }})
                            </a>
                        @endif
                    </div>

                    @if ($reviews->isEmpty())
                        <p class="py-8 text-center text-sm text-charcoal/50">
                            No reviews match this filter.
                        </p>
                    @else
                        <div class="mt-2 divide-y divide-light-gray">
                            @foreach ($reviews as $review)
                                <article class="flex gap-3 py-4 sm:py-5">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold uppercase text-primary">
                                        {{ mb_substr($review['buyer'], 0, 1) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="break-words text-sm font-semibold text-charcoal">
                                            {{ $review['buyer'] }}
                                        </p>

                                        <p class="mt-1 text-sm leading-none">
                                            <span class="text-amber-400">
                                                {{ str_repeat('★', $review['rating']) }}
                                            </span><span class="text-charcoal/15">
                                                {{ str_repeat('★', 5 - $review['rating']) }}
                                            </span>
                                        </p>

                                        <p class="mt-1.5 break-words text-[10px] text-charcoal/40 sm:text-xs">
                                            {{ $review['created_at']->format('M d, Y') }}
                                            @if ($review['variation'])
                                                <span> · {{ $review['variation'] }}</span>
                                            @endif
                                        </p>

                                        @if (filled($review['comment']))
                                            <p
                                                class="mt-2 whitespace-pre-line break-words text-sm leading-relaxed text-charcoal/80">
                                                {{ $review['comment'] }}
                                            </p>
                                        @endif

                                        {{-- REVIEW MEDIA --}}
                                        @if ($review['media']->isNotEmpty())
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($review['media'] as $media)
                                                    @if ($media['is_video'])
                                                        <video src="{{ $media['url'] }}" controls preload="metadata"
                                                            class="h-24 w-32 rounded-lg bg-charcoal/10 object-cover sm:h-28 sm:w-36"></video>
                                                    @else
                                                        <a href="{{ $media['url'] }}" target="_blank" rel="noopener"
                                                            class="block h-20 w-20 overflow-hidden rounded-lg bg-charcoal/10 sm:h-24 sm:w-24">
                                                            <img src="{{ $media['url'] }}" alt="Review photo"
                                                                loading="lazy" class="h-full w-full object-cover">
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- SELLER REPLY --}}
                                        @if (filled($review['reply']))
                                            <div class="mt-3 rounded-lg bg-ice-blue/60 px-3 py-3 sm:px-4">
                                                <p class="text-xs font-bold text-primary">
                                                    Response from
                                                    {{ $product->seller?->sellerProfile?->shop_name ?? 'the seller' }}
                                                </p>

                                                <p
                                                    class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-charcoal/70">
                                                    {{ $review['reply'] }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="pagination-frost mt-4 flex justify-center overflow-x-auto">
                            {{ $reviews->onEachSide(1)->links() }}
                        </div>
                    @endif
                @endif
            </section>
        </div>

        {{-- RECOMMENDED PRODUCTS --}}
        @if ($related->isNotEmpty())
            <section class="mt-6 px-3 sm:mt-8 sm:px-0">
                <h2 class="mb-3 text-sm font-semibold uppercase text-charcoal/70 sm:text-base">
                    You May Also Like
                </h2>

                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-3 lg:grid-cols-6">
                    @foreach ($related as $item)
                        <a href="{{ route('buyer.products.show', $item) }}"
                            class="group min-w-0 overflow-hidden rounded-lg bg-white shadow-sm shadow-charcoal/5 transition hover:shadow-md">
                            <div class="aspect-square overflow-hidden bg-ice-blue/60">
                                @if ($item->main_image_url)
                                    <img src="{{ $item->main_image_url }}" alt="{{ $item->name }}" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @endif
                            </div>

                            <div class="p-2.5 sm:p-3">
                                <p class="line-clamp-2 break-words text-xs leading-snug text-charcoal sm:text-sm">
                                    {{ $item->name }}
                                </p>

                                <p class="mt-1.5 text-sm font-bold text-primary sm:text-base">
                                    ₱{{ number_format($item->display_price, 2) }}
                                </p>

                                @if (($item->reviews_count ?? 0) > 0)
                                    <p
                                        class="mt-1 flex flex-wrap items-center gap-1 text-[10px] text-charcoal/50 sm:text-[11px]">
                                        <span class="text-amber-400">★</span>
                                        <span class="font-semibold text-charcoal/70">
                                            {{ number_format($item->reviews_avg_rating, 1) }}
                                        </span>
                                        <span>({{ $item->reviews_count }})</span>
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- MORE PRODUCTS --}}
        @if ($explore->isNotEmpty())
            <section class="mt-6 px-3 sm:mt-8 sm:px-0">
                <h2 class="mb-3 text-sm font-semibold uppercase text-charcoal/70 sm:text-base">
                    More Products to Explore
                </h2>

                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-3 lg:grid-cols-6">
                    @foreach ($explore as $item)
                        <a href="{{ route('buyer.products.show', $item) }}"
                            class="group min-w-0 overflow-hidden rounded-lg bg-white shadow-sm shadow-charcoal/5 transition hover:shadow-md">
                            <div class="aspect-square overflow-hidden bg-ice-blue/60">
                                @if ($item->main_image_url)
                                    <img src="{{ $item->main_image_url }}" alt="{{ $item->name }}" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @endif
                            </div>

                            <div class="p-2.5 sm:p-3">
                                <p class="line-clamp-2 break-words text-xs leading-snug text-charcoal sm:text-sm">
                                    {{ $item->name }}
                                </p>

                                <p class="mt-1.5 text-sm font-bold text-primary sm:text-base">
                                    ₱{{ number_format($item->display_price, 2) }}
                                </p>

                                @if (($item->reviews_count ?? 0) > 0)
                                    <p
                                        class="mt-1 flex flex-wrap items-center gap-1 text-[10px] text-charcoal/50 sm:text-[11px]">
                                        <span class="text-amber-400">★</span>
                                        <span class="font-semibold text-charcoal/70">
                                            {{ number_format($item->reviews_avg_rating, 1) }}
                                        </span>
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

    {{-- MOBILE STICKY PURCHASE BAR --}}
    <div
        class="fixed inset-x-0 bottom-0 z-40 border-t border-light-gray bg-white/95 px-3 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2 shadow-[0_-4px_16px_rgba(46,58,70,0.08)] backdrop-blur sm:hidden">

        <div class="mx-auto flex w-full max-w-7xl items-center gap-2">
            <a href="{{ route('buyer.cart.index') }}" aria-label="View cart"
                class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-sm border border-light-gray text-primary transition active:bg-ice-blue">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5m1.6 8l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17" />
                    <circle cx="10" cy="20" r="1.5" />
                    <circle cx="17" cy="20" r="1.5" />
                </svg>
                <span class="mt-0.5 text-[9px] font-semibold">Cart</span>
            </a>

            <button type="button" data-mobile-action="add" @disabled($product->stock_quantity < 1)
                class="min-h-12 flex-1 rounded-sm border border-primary bg-primary/5 px-2 py-2.5 text-xs font-semibold text-primary transition active:bg-primary/10 disabled:cursor-not-allowed disabled:opacity-50">
                Add To Cart
            </button>

            <button type="button" data-mobile-action="buy" @disabled($product->stock_quantity < 1)
                class="min-h-12 flex-1 rounded-sm bg-primary px-2 py-2.5 text-xs font-semibold text-white shadow-md shadow-primary/15 transition active:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50">
                Buy Now
            </button>
        </div>
    </div>

    {{-- TOAST --}}
    <div id="toast" role="status" aria-live="polite"
        class="fixed bottom-20 right-3 z-[80] hidden max-w-[calc(100vw-1.5rem)] rounded-xl px-4 py-3 text-sm font-medium text-white shadow-xl transition sm:bottom-6 sm:right-6 sm:max-w-sm sm:px-5 sm:py-3.5">
    </div>

    {{-- SELECT VARIANT MODAL --}}
    @if ($hasOptions)
        <div id="variantModalOverlay"
            class="fixed inset-0 z-[95] hidden items-end justify-center bg-charcoal/50 p-0 sm:items-center sm:p-4">

            <div
                class="max-h-[88dvh] w-full overflow-y-auto rounded-t-2xl bg-white px-4 pb-5 pt-4 shadow-2xl sm:max-h-[90vh] sm:max-w-md sm:rounded-2xl sm:p-6">

                <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-light-gray sm:hidden"></div>

                <div class="flex items-start gap-3">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-ice-blue/60 sm:h-24 sm:w-24">
                        @if ($product->main_image_url)
                            <img id="modalVariantImage" src="{{ $product->main_image_url }}"
                                alt="{{ $product->name }}" class="h-full w-full object-cover">
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="line-clamp-2 text-xs font-semibold text-charcoal sm:text-sm">
                            {{ $product->name }}
                        </p>

                        <p id="modalPrice" class="mt-2 text-xl font-bold text-primary">
                            ₱{{ number_format($product->display_price, 2) }}
                        </p>

                        <p id="modalStock" class="mt-1 text-xs text-charcoal/50">
                            {{ $product->stock_quantity }} in stock
                        </p>
                    </div>

                    <button type="button" id="modalCloseBtn" aria-label="Close product options"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ice-blue text-charcoal/60 transition hover:text-charcoal">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="my-4 h-px bg-light-gray sm:my-5"></div>

                <div class="space-y-5">
                    @foreach ($product->options as $option)
                        <div>
                            <p class="mb-2.5 text-sm font-bold text-charcoal">
                                {{ $option->name }}
                            </p>

                            <div class="flex flex-wrap gap-2 variant-option-group"
                                data-option-name="{{ $option->name }}">
                                @foreach ($option->values as $value)
                                    <button type="button"
                                        class="variant-value-btn min-h-10 rounded-lg border border-light-gray px-3.5 py-2 text-xs font-semibold text-charcoal transition hover:border-primary active:bg-primary/5 sm:px-4"
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

                <div class="my-4 h-px bg-light-gray sm:my-5"></div>

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-semibold text-charcoal">Quantity</span>

                    <div class="flex items-center overflow-hidden rounded-xl border border-light-gray">
                        <button type="button" id="modalQtyMinus" aria-label="Decrease quantity"
                            class="flex h-10 w-10 items-center justify-center text-lg text-charcoal/60 hover:bg-ice-blue hover:text-primary">
                            −
                        </button>

                        <input type="number" id="modalQtyInput" value="1" min="1"
                            aria-label="Selected quantity"
                            class="h-10 w-12 border-x border-light-gray text-center text-sm outline-none">

                        <button type="button" id="modalQtyPlus" aria-label="Increase quantity"
                            class="flex h-10 w-10 items-center justify-center text-lg text-charcoal/60 hover:bg-ice-blue hover:text-primary">
                            +
                        </button>
                    </div>
                </div>

                <button type="button" id="modalConfirmBtn" disabled
                    class="mt-5 min-h-12 w-full rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-md shadow-primary/15 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:bg-charcoal/20 disabled:shadow-none">
                    Confirm Selection
                </button>
            </div>
        </div>
    @endif

    {{-- ADDED TO CART MODAL --}}
    <div id="cartModalOverlay"
        class="fixed inset-0 z-[90] hidden items-center justify-center bg-charcoal/50 p-3 sm:p-4">

        <div class="w-full max-w-sm rounded-2xl bg-white p-4 shadow-2xl sm:p-6">
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-leaf-green/10 text-leaf-green sm:h-14 sm:w-14">
                <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <p class="mt-3 text-center text-base font-bold text-charcoal sm:mt-4">
                Added to Cart
            </p>

            <div class="mt-4 flex items-center gap-3 rounded-xl border border-light-gray p-3">
                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                    @if ($product->main_image_url)
                        <img id="cartModalImage" src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                            class="h-full w-full object-cover">
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p class="line-clamp-2 text-sm text-charcoal">{{ $product->name }}</p>
                    <p id="cartModalQty" class="mt-1 text-xs text-charcoal/50">Qty: 1</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-2.5">
                <button type="button" id="cartModalContinueBtn"
                    class="min-h-11 rounded-xl border border-primary px-3 py-2.5 text-xs font-bold text-primary transition hover:bg-primary/5 sm:text-sm">
                    Continue Shopping
                </button>

                <a href="{{ route('buyer.cart.index') }}"
                    class="flex min-h-11 items-center justify-center rounded-xl bg-primary px-3 py-2.5 text-xs font-bold text-white transition hover:bg-sky-blue sm:text-sm">
                    View Cart
                </a>
            </div>
        </div>
    </div>

    {{-- PAGE DATA --}}
    <div id="productPageData" data-checkout-route="{{ route('buyer.checkout.index') }}"></div>

    @php
        $productDataForJs = [
            'productId' => $product->id,
            'basePrice' => (float) $product->display_price,
            'baseStock' => (int) $product->stock_quantity,
            'primaryImage' => $product->main_image_url,
            'selectedVariantId' => (int) request('variant') ?: null,
            'hasOptions' => $hasOptions,
            'variants' => $product->variants->map(
                fn($v) => [
                    'id' => $v->id,
                    'options' => $v->options,
                    'price' => (float) $product->priceAfterDiscount($v->price),
                    'original_price' => (float) $v->price,
                    'stock_quantity' => $v->stock_quantity,
                    'weight_kg' =>
                        $v->weight_kg !== null
                            ? (float) $v->weight_kg
                            : ($product->weight_kg !== null
                                ? (float) $product->weight_kg
                                : null),
                    'image_url' => $v->image_url,
                ],
            ),
        ];
    @endphp

    <script id="productData" type="application/json">
        @json($productDataForJs)
    </script>

    @if ($sellerStats)
        <x-Buyer.seller-follow-data :ids="$isFollowing ? [$seller->id] : []" :counts="[$seller->id => $sellerStats['followers']]" :total="$followingCount" />
    @endif

    {{-- SHARED FOOTER --}}
    <x-footer />

    <script>
        // Mobile purchase buttons trigger the existing JavaScript-bound controls.
        document.querySelectorAll('[data-mobile-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.mobileAction;

                if (action === 'add') {
                    const addButton = document.getElementById('addToCartBtn');

                    if (addButton && !addButton.disabled) {
                        addButton.click();
                    }
                }

                if (action === 'buy') {
                    const buyButton = document.getElementById('buyNowBtn');

                    if (buyButton && !buyButton.disabled) {
                        buyButton.click();
                    }
                }
            });
        });

        // Desktop Add to Cart reuses the existing handler.
        document.querySelectorAll('.desktop-add-to-cart').forEach((button) => {
            button.addEventListener('click', () => {
                const addButton = document.getElementById('addToCartBtn');

                if (addButton && !addButton.disabled) {
                    addButton.click();
                }
            });
        });
    </script>

</body>

</html>
