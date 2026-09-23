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
                <span class="rounded bg-leaf-green/10 px-2 py-1 text-xs font-semibold text-leaf-green">New
                    Arrival</span>

                <h1 class="mt-3 text-2xl font-bold text-charcoal">{{ $product->name }}</h1>

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
                    <span>{{ $product->stock_quantity }} in stock</span>
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
                            Choose {{ $product->options->pluck('name')->implode(', ') }} when you add it to your cart.
                        </p>
                    </div>

                    <div class="my-5 h-px bg-light-gray"></div>
                @else
                    <!-- No options: quantity stays inline as before -->
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
                    <button type="button" id="addToCartBtn"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl border-2 border-primary bg-white px-6 py-3.5 text-sm font-bold text-primary transition hover:bg-primary/5">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ $product->options->isNotEmpty() ? 'Select Options' : 'Add to Cart' }}
                    </button>
                    <a href="{{ route('buyer.cart.index') }}"
                        class="flex flex-1 items-center justify-center rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue">
                        View Cart
                    </a>
                </div>
            </div>

        </div>

        <!-- SELLER PROFILE BAR -->
        @if ($product->seller?->sellerProfile)
            <div
                class="mt-4 flex flex-col items-center gap-4 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5 sm:flex-row sm:justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-lg font-bold text-primary">
                        {{ strtoupper(substr($product->seller->sellerProfile->shop_name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-base font-bold text-charcoal">
                            {{ $product->seller->sellerProfile->shop_name }}</p>
                        <p class="text-xs text-charcoal/50">
                            {{ $product->seller->sellerProfile->line_of_business }}</p>
                        @if ($product->seller->sellerProfile->location)
                            <p class="mt-0.5 text-xs text-charcoal/40">
                                {{ $product->seller->sellerProfile->location }}</p>
                        @endif
                    </div>
                </div>

                <a href="{{ route('buyer.shop.show', $product->seller) }}"
                    class="flex shrink-0 items-center justify-center gap-1.5 rounded-xl border-2 border-primary px-6 py-2.5 text-sm font-bold text-primary transition hover:bg-primary/5">
                    View Shop
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        @endif

        <!-- PRODUCT DESCRIPTION -->
        <div class="mt-4 rounded-lg bg-white p-6 shadow-sm shadow-charcoal/5">
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-charcoal/50">Product Description</h3>
            <p class="whitespace-pre-line text-sm leading-relaxed text-charcoal/70">
                {{ $product->description ?: 'No description provided.' }}</p>
        </div>

        <!-- BUYER REVIEWS -->
        <div class="mt-4 rounded-lg bg-white p-6 shadow-sm shadow-charcoal/5">
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-charcoal/50">Buyer Reviews</h3>
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <svg class="h-10 w-10 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                <p class="mt-3 text-sm text-charcoal/50">No reviews yet</p>
                <p class="mt-1 text-xs text-charcoal/35">Be the first to review this product after your purchase.</p>
            </div>
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
