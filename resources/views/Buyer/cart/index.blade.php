<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/buyer-cart.js'])

    <title>My Cart | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal pb-24">
    @php
        $headerCartCount = $cartCount ?? $cartItems->sum('quantity');
        $headerCartPreview = $cartPreviewItems ?? $cartItems->take(3);
    @endphp

    <x-Buyer.header :cart-count="$headerCartCount" :cart-preview-items="$headerCartPreview" />

    <main class="mx-auto max-w-5xl px-5 py-6 sm:px-8">

        @if ($cartItems->isEmpty())

            <div
                class="flex flex-col items-center justify-center rounded-lg bg-white p-16 text-center shadow-sm shadow-charcoal/5">
                <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="mt-4 text-sm font-semibold text-charcoal">Your cart is empty</p>
                <p class="mt-1 text-xs text-charcoal/50">Browse the shop and add something you like.</p>
                <a href="{{ route('buyer.home') }}"
                    class="mt-5 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                    Continue Shopping
                </a>
            </div>
        @else
            <div
                class="mb-2 hidden items-center gap-4 rounded-lg bg-white px-5 py-3 text-xs font-semibold uppercase tracking-wide text-charcoal/40 shadow-sm shadow-charcoal/5 sm:flex">
                <input type="checkbox" id="selectAllTop"
                    class="h-4 w-4 shrink-0 rounded border-light-gray text-primary focus:ring-primary">
                <span class="flex-1">Product</span>
                <span class="w-24 shrink-0 text-right">Unit Price</span>
                <span class="w-28 shrink-0 text-center">Quantity</span>
                <span class="w-24 shrink-0 text-right">Total Price</span>
                <span class="w-20 shrink-0 text-right">Actions</span>
            </div>

            <div class="space-y-2" id="cartItemsList">
                @foreach ($cartItems as $item)
                    @php $hasOptions = $item->product->options->isNotEmpty(); @endphp
                    <div data-cart-item-id="{{ $item->id }}" data-line-total="{{ $item->line_total }}"
                        class="flex items-center gap-4 rounded-lg bg-white p-4 shadow-sm shadow-charcoal/5">

                        <input type="checkbox"
                            class="item-checkbox h-4 w-4 shrink-0 rounded border-light-gray text-primary focus:ring-primary">

                        <a href="{{ route('buyer.products.show', $item->product) }}"
                            class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                            @if ($item->product->main_image_url)
                                <img src="{{ $item->product->main_image_url }}" alt="{{ $item->product->name }}"
                                    class="h-full w-full object-cover">
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('buyer.products.show', $item->product) }}"
                                class="line-clamp-2 text-sm font-medium text-charcoal hover:text-primary">
                                {{ $item->product->name }}
                            </a>

                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <p
                                    class="selected-options-label text-xs text-charcoal/45 {{ $item->selected_options ? '' : 'hidden' }}">
                                    {{ $item->selected_options ? collect($item->selected_options)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ') : '' }}
                                </p>

                                @if ($hasOptions)
                                    <button type="button" data-cart-item-id="{{ $item->id }}"
                                        class="change-variant-btn text-xs font-semibold text-primary hover:underline">
                                        Change
                                    </button>
                                @endif
                            </div>

                            <p class="unit-price-cell mt-1 text-sm font-bold text-primary sm:hidden">
                                ₱{{ number_format($item->unit_price, 2) }}</p>
                        </div>

                        <p class="unit-price-cell hidden w-24 shrink-0 text-right text-sm text-charcoal/70 sm:block">
                            ₱{{ number_format($item->unit_price, 2) }}
                        </p>

                        <div class="flex w-28 shrink-0 items-center justify-center rounded-lg border border-light-gray">
                            <button type="button"
                                class="qty-minus px-2.5 py-1.5 text-charcoal/60 hover:text-primary">−</button>
                            <input type="number"
                                class="qty-input w-10 border-x border-light-gray py-1.5 text-center text-sm outline-none"
                                value="{{ $item->quantity }}" min="1"
                                max="{{ $item->variant?->stock_quantity ?? $item->product->stock_quantity }}">
                            <button type="button"
                                class="qty-plus px-2.5 py-1.5 text-charcoal/60 hover:text-primary">+</button>
                        </div>

                        <p class="line-total w-24 shrink-0 text-right text-sm font-bold text-sale-red">
                            ₱{{ number_format($item->line_total, 2) }}
                        </p>

                        <div class="flex w-20 shrink-0 justify-end">
                            <button type="button"
                                class="remove-btn rounded-lg p-2 text-charcoal/40 transition hover:bg-sale-red/10 hover:text-sale-red">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>

                    </div>
                @endforeach
            </div>

        @endif

    </main>

    @if (!$cartItems->isEmpty())
        <div
            class="fixed inset-x-0 bottom-0 z-30 border-t border-light-gray bg-white px-5 py-3 shadow-[0_-4px_12px_rgba(0,0,0,0.06)] sm:px-8">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4">

                <div class="flex items-center gap-4 text-sm">
                    <label class="flex items-center gap-2 font-medium text-charcoal">
                        <input type="checkbox" id="selectAllBottom"
                            class="h-4 w-4 rounded border-light-gray text-primary focus:ring-primary">
                        Select All (<span id="totalItemCount">{{ $cartItems->count() }}</span>)
                    </label>
                    <button type="button" id="deleteSelectedBtn"
                        class="font-medium text-sale-red transition hover:underline disabled:cursor-not-allowed disabled:text-charcoal/30 disabled:hover:no-underline"
                        disabled>
                        Delete
                    </button>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs text-charcoal/50">
                            Total (<span id="selectedCount">0</span> item<span id="selectedCountPlural">s</span>):
                        </p>
                        <p id="selectedSubtotal" class="text-lg font-bold text-sale-red">₱0.00</p>
                    </div>
                    <button type="button" id="checkoutBtn"
                        class="rounded-xl bg-primary px-8 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:bg-charcoal/20 disabled:shadow-none"
                        disabled>
                        Check Out
                    </button>
                </div>

            </div>
        </div>
    @endif

    <div id="toast"
        class="fixed bottom-24 right-6 z-[80] hidden max-w-sm rounded-xl px-5 py-3.5 text-sm font-medium text-white shadow-xl transition">
    </div>


    <!-- CHANGE VARIANT MODAL -->
    <div id="cartVariantModalOverlay"
        class="fixed inset-0 z-[95] hidden items-end justify-center bg-charcoal/50 p-0 sm:items-center sm:p-4">
        <div
            class="max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-2xl sm:max-w-md sm:rounded-2xl">

            <div class="flex items-start justify-between">
                <div>
                    <p id="cartVariantModalPrice" class="text-xl font-bold text-primary">₱0.00</p>
                    <p id="cartVariantModalStock" class="mt-1 text-xs text-charcoal/50">0 in stock</p>
                </div>
                <button type="button" id="cartVariantModalCloseBtn"
                    class="shrink-0 rounded-full p-1.5 text-charcoal/40 transition hover:bg-ice-blue hover:text-charcoal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="my-5 h-px bg-light-gray"></div>

            <div id="cartVariantOptionsContainer"></div>

            <p id="cartVariantModalWarning" class="hidden text-xs font-semibold text-sale-red">
                Please select all options above.
            </p>

            <button type="button" id="cartVariantModalConfirmBtn" disabled
                class="mt-6 w-full rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:bg-charcoal/20 disabled:shadow-none">
                Confirm
            </button>

        </div>
    </div>


    <!-- Data bridge for external JS -->
    <div id="cartPageData" data-checkout-route="{{ route('buyer.checkout.index') }}"></div>

    @php
        $cartDataForJs = $cartItems->map(
            fn($item) => [
                'id' => $item->id,
                'productId' => $item->product_id,
                'unitPrice' => (float) $item->unit_price,
                'maxStock' => $item->variant?->stock_quantity ?? $item->product->stock_quantity,
                'selectedOptions' => $item->selected_options,
                'options' => $item->product->options->map(
                    fn($opt) => [
                        'name' => $opt->name,
                        'values' => $opt->values,
                    ],
                ),
                'variants' => $item->product->variants->map(
                    fn($v) => [
                        'id' => $v->id,
                        'options' => $v->options,
                        'price' => (float) $v->price,
                        'stock_quantity' => $v->stock_quantity,
                    ],
                ),
            ],
        );
    @endphp
    <script id="cartData" type="application/json">
        @json($cartDataForJs)
    </script>

    <x-Buyer.message-widget />
</body>

</html>
