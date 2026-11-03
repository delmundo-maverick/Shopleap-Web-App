@php
    $cartItems = $cartItems->filter(fn($item) => $item->product)->values();
    $headerCartCount = $cartCount ?? $cartItems->sum('quantity');
    $headerCartPreview = $cartPreviewItems ?? $cartItems->take(3);

    $shops = $cartItems->groupBy(fn($item) => $item->product->seller_id);

    // NOTE: confirm the shop-name column on your seller profile (business_name is assumed).
    $shopName = fn($seller) => $seller?->sellerProfile?->business_name ?? ($seller?->name ?? 'Shopleap Seller');

    $lineState = function ($item) {
        $product = $item->product;
        $variant = $item->variant;
        $stock = (int) ($variant?->stock_quantity ?? $product->stock_quantity);

        $reason = match (true) {
            $product->status !== 'active' => 'This product is no longer available.',
            (bool) $item->product_variant_id && (!$variant || !$variant->is_active)
                => 'This option is no longer available. Please choose another.',
            !$item->product_variant_id && $product->hasVariants() => 'Please choose an option.',
            $stock < 1 => 'Out of stock.',
            default => null,
        };

        return ['reason' => $reason, 'stock' => $stock];
    };

    $gridCols = 'sm:grid-cols-[minmax(0,1fr)_6rem_7.5rem_6rem_2.5rem]';

    // Same button style as the orders page.
    $btnBase =
        'inline-flex min-h-10 w-full items-center justify-center rounded-lg px-5 py-2 text-xs font-bold transition-colors sm:w-auto';
    $btnPrimary = "$btnBase bg-primary text-white hover:bg-primary/90";
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/buyer-cart.js'])

    <title>My Cart | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    {{-- Header --}}
    <x-Buyer.header :cart-count="$headerCartCount" :cart-preview-items="$headerCartPreview" />

    {{-- Main Content --}}
    <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-4 pb-32 sm:px-6 sm:py-6 sm:pb-32 lg:px-8">

        @if ($cartItems->isEmpty())

            {{-- Empty Cart --}}
            <div
                class="flex flex-col items-center justify-center rounded-xl border border-line bg-white p-8 text-center sm:p-16">
                <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>

                <p class="mt-4 text-sm font-semibold text-charcoal">Your cart is empty</p>
                <p class="mt-1 text-xs text-charcoal/60">
                    Browse the shop and add something you like.
                </p>

                <a href="{{ route('buyer.home') }}" class="{{ $btnPrimary }} mt-5 !w-auto">
                    Continue Shopping
                </a>
            </div>
        @else
            {{-- Unavailable Items Notice --}}
            <div id="unavailableBanner" role="status"
                class="mb-3 hidden items-center justify-between gap-3 rounded-xl border border-sale-red/30 bg-sale-red/5 px-4 py-3 text-xs text-sale-red sm:text-sm">
                <p>
                    <span id="unavailableCount">0</span>
                    item(s) in your cart can't be purchased right now.
                </p>

                <button type="button" id="removeUnavailableBtn" class="shrink-0 font-bold underline">
                    Remove them
                </button>
            </div>

            {{-- Column Headings (Desktop) --}}
            <div
                class="mb-3 hidden items-center gap-x-4 rounded-xl border border-line bg-white px-4 py-3 text-xs font-semibold uppercase tracking-wide text-charcoal/60 sm:grid {{ $gridCols }}">

                <label class="flex items-center gap-3">
                    <input type="checkbox" id="selectAllTop" aria-label="Select all items"
                        class="h-4 w-4 shrink-0 rounded border-line text-primary focus:ring-primary">
                    <span>Product</span>
                </label>

                <span class="text-right">Unit Price</span>
                <span class="text-center">Quantity</span>
                <span class="text-right">Total Price</span>
                <span class="sr-only">Actions</span>
            </div>

            {{-- Cart Items --}}
            <div class="space-y-3 sm:space-y-4" id="cartItemsList">

                @foreach ($shops as $sellerId => $items)
                    @php
                        $seller = $items->first()->product->seller;
                        $name = $shopName($seller);
                    @endphp

                    <section class="shop-group overflow-hidden rounded-xl border border-line bg-white"
                        data-shop-id="{{ $sellerId }}" aria-label="Items from {{ $name }}">

                        {{-- Shop Header --}}
                        <div class="flex flex-wrap items-center gap-3 border-b border-line bg-ice-blue px-4 py-3">
                            <input type="checkbox"
                                class="shop-checkbox h-4 w-4 shrink-0 rounded border-line text-primary focus:ring-primary"
                                aria-label="Select all items from {{ $name }}">

                            <svg class="h-4.5 w-4.5 shrink-0 text-primary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M3 9l1-5h16l1 5M3 9v11h18V9M3 9h18M9 20v-6h6v6" />
                            </svg>

                            @if ($seller)
                                <a href="{{ route('buyer.shop.show', $seller) }}"
                                    class="min-w-0 truncate text-sm font-bold text-charcoal transition-colors hover:text-primary">
                                    {{ $name }}
                                </a>
                            @else
                                <span class="min-w-0 truncate text-sm font-bold text-charcoal">
                                    {{ $name }}
                                </span>
                            @endif

                            <span
                                class="shrink-0 rounded-sm border border-line bg-white px-1.5 py-0.5 text-[10px] font-bold text-charcoal/60">
                                {{ $items->count() }}
                                {{ $items->count() === 1 ? 'item' : 'items' }}
                            </span>

                            <span class="shop-subtotal ml-auto shrink-0 text-xs font-semibold text-primary"></span>
                        </div>

                        {{-- Shop Items --}}
                        <div class="divide-y divide-line">

                            @foreach ($items as $item)
                                @php
                                    $state = $lineState($item);
                                    $available = $state['reason'] === null;
                                    $overStock = $available && $item->quantity > $state['stock'];

                                    $notice =
                                        $state['reason'] ??
                                        ($overStock
                                            ? "Only {$state['stock']} left. Please reduce the quantity."
                                            : null);

                                    $itemName = $item->product->name;
                                @endphp

                                <div class="cart-row grid items-center gap-x-4 gap-y-3 px-4 py-4 {{ $gridCols }}"
                                    data-cart-item-id="{{ $item->id }}" data-shop-id="{{ $sellerId }}"
                                    data-name="{{ $itemName }}" data-unit-price="{{ $item->unit_price }}"
                                    data-line-total="{{ $item->line_total }}" data-qty="{{ $item->quantity }}"
                                    data-stock="{{ $state['stock'] }}" data-available="{{ $available ? 1 : 0 }}"
                                    data-reason="{{ $state['reason'] }}">

                                    {{-- Product --}}
                                    <div class="flex min-w-0 items-center gap-3">

                                        <input type="checkbox" @disabled(!$available)
                                            aria-label="Select {{ $itemName }}"
                                            class="item-checkbox h-4 w-4 shrink-0 rounded border-line text-primary focus:ring-primary disabled:cursor-not-allowed disabled:opacity-40">

                                        <a href="{{ route('buyer.products.show', $item->product) }}"
                                            class="row-thumb h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-line bg-ice-blue sm:h-20 sm:w-20 {{ $available ? '' : 'opacity-50' }}">

                                            @if ($item->product->main_image_url)
                                                <img src="{{ $item->product->main_image_url }}"
                                                    alt="{{ $itemName }}" loading="lazy"
                                                    class="h-full w-full object-cover">
                                            @endif
                                        </a>

                                        <div class="min-w-0 flex-1">

                                            <a href="{{ route('buyer.products.show', $item->product) }}"
                                                class="line-clamp-2 text-sm font-semibold text-charcoal transition-colors hover:text-primary">
                                                {{ $itemName }}
                                            </a>

                                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">

                                                <p
                                                    class="selected-options-label text-xs text-charcoal/60 {{ $item->selected_options ? '' : 'hidden' }}">
                                                    {{ $item->selected_options ? collect($item->selected_options)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ') : '' }}
                                                </p>

                                                @if ($item->product->options->isNotEmpty())
                                                    <button type="button"
                                                        class="change-variant-btn text-xs font-bold text-primary hover:underline">
                                                        Change
                                                    </button>
                                                @endif

                                            </div>

                                            <p
                                                class="row-notice mt-1 text-xs font-semibold text-sale-red {{ $notice ? '' : 'hidden' }}">
                                                {{ $notice }}
                                            </p>

                                            <p
                                                class="unit-price-cell mt-1 text-sm font-bold tabular-nums text-primary sm:hidden">
                                                ₱{{ number_format($item->unit_price, 2) }}
                                            </p>

                                        </div>
                                    </div>

                                    {{-- Unit Price (Desktop) --}}
                                    <p
                                        class="unit-price-cell hidden text-right text-sm tabular-nums text-charcoal/70 sm:block">
                                        ₱{{ number_format($item->unit_price, 2) }}
                                    </p>

                                    {{-- Quantity, Total and Remove --}}
                                    <div class="flex min-w-0 items-center justify-between gap-3 sm:contents">

                                        <div
                                            class="flex w-28 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-white sm:justify-self-center">

                                            <button type="button" @disabled(!$available)
                                                aria-label="Decrease quantity of {{ $itemName }}"
                                                class="qty-minus px-2.5 py-1.5 text-charcoal/60 transition-colors hover:bg-ice-blue hover:text-primary disabled:cursor-not-allowed disabled:opacity-40">
                                                −
                                            </button>

                                            <input type="number" inputmode="numeric" @disabled(!$available)
                                                aria-label="Quantity of {{ $itemName }}"
                                                class="qty-input w-10 border-x border-line py-1.5 text-center text-sm tabular-nums outline-none disabled:bg-transparent"
                                                value="{{ $item->quantity }}" min="1"
                                                max="{{ max(1, $state['stock']) }}">

                                            <button type="button" @disabled(!$available)
                                                aria-label="Increase quantity of {{ $itemName }}"
                                                class="qty-plus px-2.5 py-1.5 text-charcoal/60 transition-colors hover:bg-ice-blue hover:text-primary disabled:cursor-not-allowed disabled:opacity-40">
                                                +
                                            </button>
                                        </div>

                                        <p
                                            class="line-total shrink-0 text-right text-sm font-bold tabular-nums text-sale-red">
                                            ₱{{ number_format($item->line_total, 2) }}
                                        </p>

                                        <button type="button" aria-label="Remove {{ $itemName }} from cart"
                                            class="remove-btn shrink-0 rounded-lg border border-transparent p-2 text-charcoal/40 transition-colors hover:border-sale-red/30 hover:bg-sale-red/5 hover:text-sale-red sm:justify-self-end">

                                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                            </svg>

                                        </button>
                                    </div>

                                </div>
                            @endforeach

                        </div>
                    </section>
                @endforeach

            </div>

        @endif

    </main>

    {{-- Fixed Checkout Bar --}}
    @if (!$cartItems->isEmpty())
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white px-3 py-3 sm:px-6 lg:px-8">

            <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-x-4 gap-y-3">

                <div class="flex flex-wrap items-center gap-3 sm:gap-4">

                    <label class="flex items-center gap-2 text-xs font-semibold text-charcoal sm:text-sm">
                        <input type="checkbox" id="selectAllBottom"
                            class="h-4 w-4 rounded border-line text-primary focus:ring-primary">

                        Select All (<span id="totalItemCount">{{ $cartItems->count() }}</span>)
                    </label>

                    <button type="button" id="deleteSelectedBtn"
                        class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg border border-sale-red/30 bg-white px-4 py-2 text-xs font-bold text-sale-red transition-colors hover:bg-sale-red/5 disabled:cursor-not-allowed disabled:border-line disabled:text-charcoal/30 disabled:hover:bg-white"
                        disabled>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                        </svg>
                        Remove Selected
                    </button>

                </div>

                <div class="ml-auto flex items-center gap-3 sm:gap-4">

                    <div class="text-right">
                        <p class="text-[11px] text-charcoal/60 sm:text-xs">
                            Total (<span id="selectedCount">0</span> item<span id="selectedCountPlural">s</span>):
                        </p>

                        <p id="selectedSubtotal" class="text-base font-bold tabular-nums text-sale-red sm:text-lg">
                            ₱0.00
                        </p>

                        <p id="selectedShops" class="hidden text-[11px] text-charcoal/60"></p>
                    </div>

                    <button type="button" id="checkoutBtn"
                        class="min-h-10 rounded-lg bg-primary px-5 py-2 text-xs font-bold text-white transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:bg-charcoal/20 sm:px-8 sm:text-sm"
                        disabled>
                        Check Out
                    </button>

                </div>

            </div>
        </div>
    @endif

    {{-- Toast Notifications --}}
    <div id="toast" role="status" aria-live="polite"
        class="fixed bottom-28 right-4 z-[80] hidden max-w-sm rounded-lg px-5 py-3.5 text-sm font-medium text-white shadow-xl transition sm:right-6">
    </div>

    {{-- Change Variant Modal --}}
    <div id="cartVariantModalOverlay"
        class="fixed inset-0 z-[95] hidden items-end justify-center bg-charcoal/50 p-0 sm:items-center sm:p-4">

        <div role="dialog" aria-modal="true" aria-labelledby="cartVariantModalTitle"
            class="max-h-[90vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-3xl sm:p-6">

            <div class="flex items-start justify-between gap-3">

                <div class="min-w-0">
                    <p id="cartVariantModalTitle" class="line-clamp-2 text-sm font-semibold text-charcoal"></p>

                    <p id="cartVariantModalPrice" class="mt-1 text-xl font-bold tabular-nums text-primary">
                        ₱0.00
                    </p>

                    <p id="cartVariantModalStock" class="mt-1 text-xs text-charcoal/60">
                        0 in stock
                    </p>
                </div>

                <button type="button" id="cartVariantModalCloseBtn" aria-label="Close"
                    class="shrink-0 rounded-lg p-1.5 text-charcoal/50 transition-colors hover:bg-ice-blue hover:text-charcoal">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>

                </button>
            </div>

            <div class="my-5 h-px bg-line"></div>

            <div id="cartVariantOptionsContainer"></div>

            <p id="cartVariantModalWarning" class="hidden text-xs font-semibold text-sale-red">
                This combination isn't available. Please choose different options.
            </p>

            <button type="button" id="cartVariantModalConfirmBtn" disabled
                class="mt-6 min-h-12 w-full rounded-lg bg-primary px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:bg-charcoal/20">
                Confirm
            </button>

        </div>
    </div>

    {{-- Data Bridge for External JavaScript --}}
    <div id="cartPageData" hidden data-checkout-route="{{ route('buyer.checkout.index') }}"
        data-update-url="{{ route('buyer.cart.update', '__ID__') }}"
        data-destroy-url="{{ route('buyer.cart.destroy', '__ID__') }}"
        data-bulk-destroy-url="{{ route('buyer.cart.destroyMany') }}">
    </div>

    @php
        // Only lines with options need variant data (for the Change modal); archived variants are excluded.
        $cartDataForJs = $cartItems
            ->filter(fn($item) => $item->product->options->isNotEmpty())
            ->map(
                fn($item) => [
                    'id' => $item->id,
                    'productId' => $item->product_id,
                    'unitPrice' => (float) $item->unit_price,
                    'maxStock' => $item->variant?->stock_quantity ?? $item->product->stock_quantity,
                    'selectedOptions' => $item->selected_options,
                    'options' => $item->product->options
                        ->map(fn($opt) => ['name' => $opt->name, 'values' => $opt->values])
                        ->values(),
                    'variants' => $item->product->variants
                        ->where('is_active', true)
                        ->map(
                            fn($v) => [
                                'id' => $v->id,
                                'options' => $v->options,
                                'price' => (float) $v->price,
                                'stock_quantity' => $v->stock_quantity,
                            ],
                        )
                        ->values(),
                ],
            )
            ->values();
    @endphp

    <script id="cartData" type="application/json">
        @json($cartDataForJs)
    </script>

    {{-- Shared Message Widget --}}
    <x-Buyer.message-widget />
</body>

</html>
