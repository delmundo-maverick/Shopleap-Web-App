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

    <main class="mx-auto w-full max-w-7xl flex-1 px-5 py-6 sm:px-8">
        <div class="mb-5">
            <h1 class="text-xl font-bold">My Wishlist</h1>
            <p class="mt-1 text-sm text-charcoal/50">
                <span x-data x-text="$store.wishlist.count">{{ $products->total() }}</span> saved item(s)
            </p>
        </div>

        {{-- Empty state (also appears when the last item is un-hearted) --}}
        <div x-data x-show="$store.wishlist.count === 0" x-cloak
            class="flex flex-col items-center justify-center rounded-lg bg-white p-16 text-center shadow-sm shadow-charcoal/5">
            <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <p class="mt-4 text-sm font-semibold">Your wishlist is empty</p>
            <p class="mt-1 text-xs text-charcoal/50">Tap the heart on any product to save it for later.</p>
            <a href="{{ route('buyer.products.index') }}"
                class="mt-5 rounded-sm bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
                Browse Products
            </a>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($products as $product)
                    @php
                        $available = $product->status === 'active';
                        $inStock = $product->stock_quantity > 0;
                        $hasVariants = $product->hasVariants();
                        $canAdd = $available && $inStock && !$hasVariants;
                    @endphp

                    <article x-data="{ adding: false }" x-show="$store.wishlist.has({{ $product->id }})"
                        x-transition.opacity
                        class="flex min-w-0 flex-col overflow-hidden rounded-lg bg-white shadow-sm shadow-charcoal/5">

                        <div class="relative aspect-square overflow-hidden bg-ice-blue/60">
                            @if ($available)
                                <a href="{{ route('buyer.products.show', $product) }}" class="block h-full w-full">
                            @endif
                            @if ($product->main_image_url)
                                <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy"
                                    class="h-full w-full object-cover {{ $available ? '' : 'opacity-50 grayscale' }}">
                            @endif
                            @if ($available)
                                </a>
                            @endif

                            @if (!$available)
                                <span
                                    class="absolute left-2 top-2 rounded bg-charcoal/70 px-2 py-0.5 text-[10px] font-bold uppercase text-white">
                                    Unavailable
                                </span>
                            @elseif (!$inStock)
                                <span
                                    class="absolute left-2 top-2 rounded bg-sale-red px-2 py-0.5 text-[10px] font-bold uppercase text-white">
                                    Sold out
                                </span>
                            @endif

                            <x-Buyer.wishlist-button :product="$product" class="absolute right-2 top-2 z-10" />
                        </div>

                        <div class="flex flex-1 flex-col p-2.5 sm:p-3">
                            <p class="line-clamp-2 break-words text-xs leading-snug sm:text-sm">{{ $product->name }}</p>

                            <p class="mt-1.5 text-sm font-bold text-primary sm:text-base">
                                ₱{{ number_format($product->display_price, 2) }}
                                @if ($product->was_price)
                                    <span class="ml-1 text-xs font-normal text-charcoal/40 line-through">
                                        ₱{{ number_format($product->was_price, 2) }}
                                    </span>
                                @endif
                            </p>

                            @if (($product->reviews_count ?? 0) > 0)
                                <p class="mt-1 flex items-center gap-1 text-[10px] text-charcoal/50 sm:text-[11px]">
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
                                        class="w-full rounded-sm border border-primary bg-primary/5 px-2 py-2 text-xs font-semibold text-primary transition hover:bg-primary/10 disabled:opacity-50">
                                        Add To Cart
                                    </button>
                                @elseif ($available && $inStock && $hasVariants)
                                @else
                                    <button type="button" disabled
                                        class="w-full cursor-not-allowed rounded-sm bg-charcoal/10 px-2 py-2 text-xs font-semibold text-charcoal/40">
                                        {{ $available ? 'Out of Stock' : 'Unavailable' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-frost mt-8 flex justify-center">
                {{ $products->onEachSide(1)->links() }}
            </div>
        @endif
    </main>

    <x-Buyer.message-widget />
    <x-footer />
</body>

</html>
