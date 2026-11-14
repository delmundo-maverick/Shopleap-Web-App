<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Checkout | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <x-nav.shell id="checkout-navigation" label="Checkout navigation" class="buyer-header [&_.nav-actions]:ml-auto!">
        <x-slot:brand>
            <div class="flex items-center gap-3">
                <x-logo class="[&>.font-logo]:hidden sm:[&>.font-logo]:block" />
                <span class="hidden border-l border-white/25 pl-3 text-sm font-medium text-white/85 sm:block">Secure checkout</span>
            </div>
        </x-slot:brand>
        <x-slot:actions>
            <a href="{{ route('buyer.cart.index') }}"
                class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:px-4 sm:text-sm">
                <svg class="size-5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 12H5m7-7-7 7 7 7" />
                </svg>
                <span>Back to cart</span>
            </a>
        </x-slot:actions>
        <x-slot:mobileMenu><x-nav.link :href="route('buyer.home')">Home</x-nav.link><x-nav.link :href="route('buyer.account.index')">My Account</x-nav.link></x-slot:mobileMenu>
    </x-nav.shell>

    <main x-data="checkoutPage(@js(['quoteUrl' => route('buyer.checkout.index'), 'items' => $itemIds, 'addressId' => $selectedAddress?->id, 'shipping' => $shippingBySeller, 'shops' => $shops, 'selectedVouchers' => old('vouchers', []), 'blocked' => $blockedSellerIds]))" @buyer-address-selected.window="quote($event.detail.id)" @cart-updated.window="quote(addressId)" @keydown.escape.window="picker = null" class="mx-auto w-full max-w-7xl flex-1 px-3 py-5 pb-80 md:px-8 md:py-6 md:pb-80 lg:pb-12">

        {{-- STEP INDICATOR --}}
        <div class="mb-6 flex items-center justify-center gap-3 text-xs font-semibold text-charcoal/40 sm:text-sm">
            <span class="flex items-center gap-1.5 text-charcoal/40">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-charcoal/10 text-[10px]">
                    1
                </span>
                Cart
            </span>

            <span class="h-px w-8 bg-light-gray sm:w-16"></span>

            <span class="flex items-center gap-1.5 text-primary">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] text-white">
                    2
                </span>
                Checkout
            </span>

            <span class="h-px w-8 bg-light-gray sm:w-16"></span>

            <span class="flex items-center gap-1.5 text-charcoal/40">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-charcoal/10 text-[10px]">
                    3
                </span>
                Order Placed
            </span>
        </div>

        <h1 class="mb-5 text-2xl font-bold text-charcoal">Checkout</h1>

        {{-- VALIDATION ERRORS --}}
        @if ($errors->any())
            <div
                class="mb-5 flex items-start gap-2 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                <svg class="mt-0.5 h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('buyer.checkout.store') }}">
            @csrf

            <input type="hidden" name="items" value="{{ $itemIds ?? request('items') }}">

            <div class="grid grid-cols-1 gap-6">

                <div class="space-y-5">

                    <x-buyer-addresses :addresses="$addresses" :checkout="true" :selected="$selectedAddress?->id" />
                    <p x-show="quoting" class="text-sm" role="status">Updating shipping...</p>
                    <p x-show="quoteError" x-text="quoteError" class="text-sm text-sale-red" role="alert"></p>

                    <!-- ITEMS GROUPED BY SELLER -->
                    @foreach ($groupedBySeller as $sellerId => $items)
                        @php
                            $seller = $items->first()->product->seller;
                            $sellerSubtotal = $items->sum('line_total');
                        @endphp

                        <section x-show="shops[{{ (int) $sellerId }}]" class="rounded-lg bg-white p-4 shadow-sm shadow-charcoal/5 sm:p-5">

                            <!-- SELLER NAME -->
                            <p class="mb-3 flex items-center gap-2 text-sm font-bold text-charcoal">
                                <svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5" />
                                </svg>

                                {{ $seller->sellerProfile->business_name ?? $seller->name }}
                            </p>

                            {{-- BLOCKED SELLER NOTICE --}}
                            <div x-show="blocked.includes({{ (int) $sellerId }})"
                                    class="mb-3 rounded-lg border border-sale-red/20 bg-sale-red/10 px-3 py-2 text-xs text-sale-red">
                                    This seller can't deliver to your address yet. Remove these items from your cart to
                                    continue.
                                    <a href="{{ route('buyer.cart.index') }}" class="font-semibold underline">
                                        Back to cart
                                    </a>
                                </div>
                            <!-- SELLER PRODUCTS -->
                            <div class="app-table-body">
                                @foreach ($items as $item)
                                    <div x-show="shops[{{ (int) $sellerId }}]?.items[{{ $item->id }}]" class="flex items-center gap-3 py-3">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                                            @if ($item->product->main_image_url)
                                                <img src="{{ $item->product->main_image_url }}"
                                                    alt="{{ $item->product->name }}"
                                                    class="h-full w-full object-cover">
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-1 text-sm text-charcoal">
                                                {{ $item->product->name }}
                                            </p>

                                            @if ($item->selected_options)
                                                <p class="text-xs text-charcoal/45">
                                                    {{ collect($item->selected_options)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ') }}
                                                </p>
                                            @endif

                                            <p class="text-xs text-charcoal/50">
                                                Qty: <span x-text="shops[{{ (int) $sellerId }}]?.items[{{ $item->id }}]?.quantity">{{ $item->quantity }}</span>
                                                × <span x-text="money(shops[{{ (int) $sellerId }}]?.items[{{ $item->id }}]?.unit_price)">₱{{ number_format($item->unit_price, 2) }}</span>
                                            </p>
                                        </div>

                                        <p class="shrink-0 text-sm font-bold text-charcoal">
                                            <span x-text="money(shops[{{ (int) $sellerId }}]?.items[{{ $item->id }}]?.subtotal)">₱{{ number_format($item->line_total, 2) }}</span>
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            <!-- SELLER SUBTOTAL AND SHIPPING -->
                            <div class="mt-3 space-y-1.5 border-t border-light-gray pt-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-charcoal/50">
                                        Items subtotal
                                    </span>

                                    <span class="font-bold text-charcoal">
                                        <span x-text="money(shops[{{ (int) $sellerId }}]?.subtotal)">₱{{ number_format($sellerSubtotal, 2) }}</span>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <span class="text-charcoal/60">Voucher discount</span>
                                    <span class="font-semibold text-leaf-green" x-text="'- ' + money(shopDiscount({{ (int) $sellerId }}))"></span>
                                </div>

                                <div class="flex items-center justify-between text-xs text-charcoal/60">
                                    <span>Shipping fee</span>

                                    <span class="font-semibold text-charcoal">
                                        ₱<span x-text="Number(shipping[{{ (int) $sellerId }}]?.fee || 0).toFixed(2)">{{ number_format($shippingBySeller[$sellerId]['fee'], 2) }}</span>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between border-t border-light-gray pt-2 font-bold">
                                    <span>Shop total</span>
                                    <span class="text-primary" x-text="money(shopTotal({{ (int) $sellerId }}))"></span>
                                </div>
                            </div>

                            <div class="mt-4 border-t border-light-gray pt-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-semibold">Shop voucher</p>
                                        <p x-show="selections[{{ (int) $sellerId }}]" x-text="selections[{{ (int) $sellerId }}]" class="text-sm text-leaf-green"></p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button type="button" @click="removeVoucher({{ (int) $sellerId }})" x-show="selections[{{ (int) $sellerId }}]" class="text-xs font-semibold text-sale-red">Remove</button>
                                        <button type="button" @click="openPicker({{ (int) $sellerId }})" class="rounded-lg border border-primary px-3 py-2 text-xs font-semibold text-primary">Select voucher</button>
                                    </div>
                                </div>
                                <input type="hidden" name="vouchers[{{ $sellerId }}]" :value="selections[{{ (int) $sellerId }}] || ''">
                                <p x-show="messages[{{ (int) $sellerId }}]" x-text="messages[{{ (int) $sellerId }}]" class="mt-2 text-xs text-sale-red" role="status"></p>
                            </div>

                            <div x-cloak x-show="picker === {{ (int) $sellerId }}" @click.self="picker = null" class="fixed inset-0 z-50 flex items-center justify-center bg-charcoal/50 p-3" role="dialog" aria-modal="true" aria-labelledby="voucher-title-{{ $sellerId }}">
                                <div class="flex max-h-[80dvh] w-full max-w-lg flex-col rounded-xl bg-white p-4 shadow-xl sm:p-5">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h2 id="voucher-title-{{ $sellerId }}" class="font-bold">Vouchers · {{ $seller->sellerProfile->business_name ?? $seller->name }}</h2>
                                        <button type="button" @click="picker = null" class="shrink-0 rounded px-2 py-1 text-sm text-charcoal/60" aria-label="Close voucher picker">Close</button>
                                    </div>
                                    <p class="mb-3 text-xs text-charcoal/60">Your claimed vouchers for this shop. Discounts apply to eligible items only.</p>
                                    <div class="space-y-2 overflow-y-auto">
                                        <p x-show="!(shops[{{ (int) $sellerId }}]?.vouchers.length)" class="py-6 text-center text-sm text-charcoal/60">No claimed vouchers for this shop yet.</p>
                                        <template x-for="voucher in (shops[{{ (int) $sellerId }}]?.vouchers || [])" :key="voucher.code">
                                            <button type="button" @click="selectVoucher({{ (int) $sellerId }}, voucher)" :disabled="quoting || !!quoteError || !!voucherReason(voucher)" class="block w-full rounded-lg border border-light-gray p-3 text-left hover:border-primary disabled:cursor-not-allowed disabled:bg-ice-blue disabled:opacity-60">
                                                <span class="block text-sm font-semibold" x-text="voucher.code + (voucher.name ? ' · ' + voucher.name : '')"></span>
                                                <span class="mt-1 block text-xs text-charcoal/60" x-text="'Minimum spend ' + money(voucher.minimum)"></span>
                                                <span x-show="!voucherReason(voucher)" class="mt-1 block text-sm font-semibold text-leaf-green" x-text="'Save ' + money(voucher.discount)"></span>
                                                <span x-show="voucherReason(voucher)" class="mt-1 block text-xs text-sale-red" x-text="voucherReason(voucher)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- NOTE TO THIS SELLER -->
                            <div class="mt-3">
                                <label for="note-{{ $sellerId }}"
                                    class="mb-1 block text-xs font-semibold text-charcoal">
                                    Note to seller
                                    <span class="font-normal text-charcoal/40">(optional)</span>
                                </label>

                                <textarea id="note-{{ $sellerId }}" name="notes[{{ $sellerId }}]" rows="2" maxlength="255"
                                    placeholder="e.g. Leave at the guard house"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm text-charcoal outline-none focus:border-primary">{{ old("notes.{$sellerId}") }}</textarea>

                                @error("notes.{$sellerId}")
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                        </section>
                    @endforeach

                    <!-- PAYMENT METHOD -->
                    <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-charcoal">
                            <svg class="h-4.5 w-4.5 text-primary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            Payment Method
                        </h3>

                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-primary bg-primary/5 px-4 py-3">
                            <input type="radio" name="payment_method" value="cod" checked
                                class="accent-primary">

                            <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3 .672 3 1.5-1.343 1.5-3 1.5m0-6c1.11 0 2.08.402 2.599 1M12 8V6.5m0 8V17m0-9c-1.11 0-2.08.402-2.599 1M12 17c1.11 0 2.08-.402 2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <span class="text-sm font-medium text-charcoal">
                                Cash on Delivery (COD)
                            </span>
                        </label>

                        <p class="mt-2 flex items-center gap-1.5 text-xs text-charcoal/40">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            More payment methods coming soon.
                        </p>
                    </div>

                </div>

                <!-- ORDER SUMMARY -->
                <div class="fixed inset-x-0 bottom-0 z-30 border-t border-light-gray bg-white lg:static lg:rounded-lg">
                    <div class="mx-auto max-w-7xl p-3 shadow-sm shadow-charcoal/5 sm:px-5 lg:p-5">
                        <h3 class="mb-2 hidden text-sm font-bold text-charcoal lg:block">Overall summary</h3>

                        <div class="flex items-center justify-between gap-3 text-sm text-charcoal/70">
                            <span>
                                Items subtotal
                            </span>

                            <span class="shrink-0 font-semibold text-charcoal">
                                <span x-text="money(subtotal)">₱{{ number_format($total, 2) }}</span>
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 text-sm text-charcoal/70">
                            <span>
                                Total shipping (<span x-text="Object.keys(shops).length">{{ $groupedBySeller->count() }}</span> parcels)
                            </span>

                            <span class="shrink-0 font-semibold text-charcoal">
                                ₱<span x-text="shippingTotal.toFixed(2)">{{ number_format($shippingTotal, 2) }}</span>
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 text-sm text-charcoal/70">
                            <span>Total voucher discounts</span>
                            <span class="shrink-0 font-semibold text-leaf-green">- ₱<span
                                    x-text="discount.toFixed(2)"></span></span>
                        </div>

                        <div class="my-2 h-px bg-light-gray"></div>

                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-bold text-charcoal">Total Payment</span>

                            <span class="text-xl font-bold text-primary">
                                <span x-text="money(grandTotal)"></span>
                            </span>
                        </div>

                        <p class="mb-3 hidden text-right text-[11px] text-charcoal/40 lg:block">
                            VAT included, where applicable
                        </p>

                        <p x-show="!Object.keys(shops).length" class="mb-2 text-center text-xs text-sale-red" role="status">Your selected items are no longer in the cart. <a href="{{ route('buyer.cart.index') }}" class="underline">Return to cart</a>.</p>
                        <button type="submit" :disabled="quoting || !!quoteError || !addressId || blocked.length > 0 || !Object.keys(shops).length" @disabled(!$selectedAddress || !empty($blockedSellerIds))
                            class="btn btn-primary w-full" x-text="quoting ? 'Updating shipping...' : (!addressId ? 'Select a delivery address' : (blocked.length ? 'Delivery unavailable' : 'Place order'))">Place order</button>
                        <p x-show="blocked.length" class="mt-2 text-center text-xs text-sale-red">One or more sellers cannot deliver to this address. Choose another address or update your cart.</p>

                        <p class="mt-2 text-center text-[11px] leading-relaxed text-charcoal/40">
                            By placing your order, you agree to Shopleap's
                            <a href="{{ route('terms') }}" class="text-primary hover:underline">Terms and Conditions</a>
                            and
                            <a href="{{ route('privacy') }}" class="text-primary hover:underline">Privacy Policy</a>.
                        </p>
                    </div>
                </div>

            </div>
        </form>

    </main>

    <x-Buyer.message-widget />
    <x-footer />

</body>

</html>
