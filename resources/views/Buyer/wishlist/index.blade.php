<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Wishlist | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    @php
        // Same button styles as the orders page.
        $btnBase =
            'inline-flex min-h-9 w-full items-center justify-center rounded-lg px-4 py-2 text-xs font-bold transition-colors';
        $btnPrimary = "$btnBase bg-primary text-white hover:bg-primary/90";
        $btnAccent = "$btnBase border border-primary bg-white text-primary hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-60";
        $btnDisabled = "$btnBase cursor-not-allowed border border-line bg-ice-blue text-charcoal/40";
    @endphp

    <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mb-4 sm:mb-6">
            <h1 class="text-xl font-bold text-charcoal md:text-2xl">My Wishlist</h1>
            <p class="mt-1 text-sm text-charcoal/60">
                <span x-data x-text="$store.wishlist.count">{{ $products->total() }}</span> saved item(s)
            </p>
        </div>

        {{-- Empty state (also appears when the last item is un-hearted) --}}
        <div x-data x-show="$store.wishlist.count === 0" x-cloak
            class="flex flex-col items-center justify-center rounded-xl border border-line bg-white p-8 text-center sm:p-16">
            <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <p class="mt-4 text-sm font-semibold text-charcoal">Your wishlist is empty</p>
            <p class="mt-1 text-xs text-charcoal/60">Tap the heart on any product to save it for later.</p>
            <a href="{{ route('buyer.products.index') }}" class="{{ $btnPrimary }} mt-5 !w-auto !min-h-10 !px-5">
                Browse Products
            </a>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 lg:grid-cols-6">
                @foreach ($products as $product)
                    @php
                        $available = $product->status === 'active';
                        $inStock = $product->stock_quantity > 0;
                        $hasVariants = $product->hasVariants();
                        $canAdd = $available && $inStock && !$hasVariants;
                    @endphp

                    <article x-data="{ adding: false }" x-show="$store.wishlist.has({{ $product->id }})"
                        x-transition.opacity
                        class="group flex min-w-0 flex-col overflow-hidden rounded-xl border border-line bg-white transition-colors hover:border-primary">

                        <div class="relative aspect-square overflow-hidden border-b border-line bg-ice-blue">
                            @if ($available)
                                <a href="{{ route('buyer.products.show', $product) }}" class="block h-full w-full">
                            @endif
                            @if ($product->main_image_url)
                                <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 {{ $available ? 'group-hover:scale-105' : 'opacity-50 grayscale' }}">
                            @endif
                            @if ($available)
                                </a>
                            @endif

                            @if (!$available)
                                <span
                                    class="absolute left-2 top-2 rounded-sm bg-charcoal/70 px-2 py-0.5 text-[10px] font-bold uppercase text-white">
                                    Unavailable
                                </span>
                            @elseif (!$inStock)
                                <span
                                    class="absolute left-2 top-2 rounded-sm bg-sale-red px-2 py-0.5 text-[10px] font-bold uppercase text-white">
                                    Sold out
                                </span>
                            @endif

                            <x-Buyer.wishlist-button :product="$product" class="absolute right-2 top-2 z-10" />
                        </div>

                        <div class="flex flex-1 flex-col p-2.5 sm:p-3">
                            <p
                                class="line-clamp-2 min-h-[2rem] break-words text-xs font-semibold leading-snug text-charcoal">
                                {{ $product->name }}
                            </p>

                            <p class="mt-1.5 text-sm font-bold tabular-nums text-primary sm:text-base">
                                ₱{{ number_format($product->display_price, 2) }}
                                @if ($product->was_price)
                                    <span class="ml-1 text-xs font-normal text-charcoal/40 line-through">
                                        ₱{{ number_format($product->was_price, 2) }}
                                    </span>
                                @endif
                            </p>

                            @if (($product->reviews_count ?? 0) > 0)
                                <p class="mt-1 flex items-center gap-1 text-[10px] text-charcoal/60 sm:text-[11px]">
                                    <span class="text-amber-400">★</span>
                                    <span
                                        class="font-semibold text-charcoal/70">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                                    <span>({{ $product->reviews_count }})</span>
                                </p>
                            @endif

                            <div class="mt-auto pt-3">
                                @if ($canAdd)
                                    <button type="button" :disabled="adding"
                                        @click="adding = true; $store.wishlist.addToCart('{{ route('buyer.cart.store', $product) }}').finally(() => adding = false)"
                                        class="{{ $btnAccent }}">
                                        Add To Cart
                                    </button>
                                @elseif ($available && $inStock && $hasVariants)
                                @else
                                    <button type="button" disabled class="{{ $btnDisabled }}">
                                        {{ $available ? 'Out of Stock' : 'Unavailable' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-frost mt-6 flex justify-center overflow-x-auto">
                {{ $products->onEachSide(1)->links() }}
            </div>
        @endif
    </main>

    <x-Buyer.message-widget />
    <x-footer />
</body>

</html>
