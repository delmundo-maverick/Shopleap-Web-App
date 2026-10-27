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

    <header class="sticky top-0 z-40 border-b border-white/10 bg-primary">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-3 md:px-8">

            <!-- LOGO -->
            <a href="{{ route('buyer.home') }}" class="flex shrink-0 items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center overflow-hidden">
                    <img src="{{ asset('images/Shopleap-Logo-White.png') }}" alt="Shopleap"
                        class="h-full w-full object-contain">
                </span>
                <span>
                    <span class="block text-base font-logo font-bold leading-tight text-white sm:text-lg">
                        Shopleap
                    </span>
                    <span class="hidden text-xs text-white/80 sm:block">
                        Shop Smart. Shop Simple.
                    </span>
                </span>
            </a>

            <!-- SECURE CHECKOUT LABEL -->
            <div class="flex items-center gap-2 text-sm font-semibold text-white/80">
                <svg class="h-4.5 w-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                        d="M16 8V6a4 4 0 00-8 0v2M5 8h14a1 1 0 011 1v10a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z" />
                </svg>
                <span class="hidden text-white sm:inline">Secure Checkout</span>
            </div>

        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-5 pb-32 md:px-8 md:py-6 md:pb-32">

        {{-- STEP INDICATOR --}}
        <div class="mb-6 flex items-center justify-center gap-3 text-xs font-semibold text-charcoal/40 sm:text-sm">
            <span class="flex items-center gap-1.5 text-charcoal/40">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-charcoal/10 text-[10px]">
                    ₱�
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

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <div class="space-y-5 lg:col-span-2">

                    <!-- DELIVERY ADDRESS -->
                    <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-charcoal">
                                <svg class="h-4.5 w-4.5 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                        d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Delivery Address
                            </h3>

                            @if (auth()->user()->buyerProfile && Route::has('buyer.profile.edit'))
                                <a href="{{ route('buyer.profile.edit') }}"
                                    class="text-xs font-semibold text-primary hover:underline">
                                    Change
                                </a>
                            @endif
                        </div>

                        @if (auth()->user()->buyerProfile)
                            <div class="rounded-lg border border-light-gray bg-ice-blue/40 p-4">
                                <p class="text-sm font-semibold text-charcoal">
                                    {{ auth()->user()->name }}
                                    <span class="ml-2 font-normal text-charcoal/50">
                                        {{ auth()->user()->buyerProfile->contact_no }}
                                    </span>
                                </p>

                                <p class="mt-1 text-sm text-charcoal/70">
                                    {{ auth()->user()->buyerProfile->house_number }}
                                    {{ auth()->user()->buyerProfile->street }},
                                    {{ auth()->user()->buyerProfile->barangay }},
                                    {{ auth()->user()->buyerProfile->municipality }},
                                    {{ auth()->user()->buyerProfile->province }}
                                </p>
                            </div>
                        @else
                            <div
                                class="flex items-center justify-between rounded-lg border border-dashed border-sale-red/30 bg-sale-red/5 p-4">
                                <p class="text-sm text-sale-red">
                                    No address on file. Add one before placing your order.
                                </p>

                                <a href="{{ Route::has('buyer.profile.edit') ? route('buyer.profile.edit') : '#' }}"
                                    class="shrink-0 rounded-lg bg-sale-red px-3 py-1.5 text-xs font-bold text-white transition hover:brightness-110">
                                    Add Address
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- ITEMS GROUPED BY SELLER -->
                    @foreach ($groupedBySeller as $sellerId => $items)
                        @php
                            $seller = $items->first()->product->seller;
                            $sellerSubtotal = $items->sum('line_total');
                            $sellerBlocked = in_array($sellerId, $blockedSellerIds ?? []);
                        @endphp

                        <div class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">

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
                            @if ($sellerBlocked)
                                <div
                                    class="mb-3 rounded-lg border border-sale-red/20 bg-sale-red/10 px-3 py-2 text-xs text-sale-red">
                                    This seller can't deliver to your address yet. Remove these items from your cart to
                                    continue.
                                    <a href="{{ route('buyer.cart.index') }}" class="font-semibold underline">
                                        Back to cart
                                    </a>
                                </div>
                            @endif

                            <!-- SELLER PRODUCTS -->
                            <div class="divide-y divide-light-gray">
                                @foreach ($items as $item)
                                    <div class="flex items-center gap-3 py-3">
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
                                                Qty: {{ $item->quantity }} +�
                                                ₱{{ number_format($item->unit_price, 2) }}
                                            </p>
                                        </div>

                                        <p class="shrink-0 text-sm font-bold text-charcoal">
                                            ₱{{ number_format($item->line_total, 2) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            <!-- SELLER SUBTOTAL AND SHIPPING -->
                            <div class="mt-3 space-y-1.5 border-t border-light-gray pt-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-charcoal/50">
                                        {{ $items->count() }}
                                        item{{ $items->count() === 1 ? '' : 's' }} from this seller
                                    </span>

                                    <span class="font-bold text-charcoal">
                                        ₱{{ number_format($sellerSubtotal, 2) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-xs text-charcoal/60">
                                    <span>Shipping fee</span>

                                    <span class="font-semibold text-charcoal">
                                        ₱{{ number_format($shippingBySeller[$sellerId]['fee'], 2) }}
                                    </span>
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

                        </div>
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
                <div class="lg:col-span-1">
                    <div class="sticky top-24 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <h3 class="mb-4 text-sm font-bold text-charcoal">Order Total</h3>

                        <!-- Voucher Section -->
                        <div class="mb-4" x-data="voucherCheckout()" x-init="init()">
                            <label class="mb-1.5 block text-xs font-semibold text-charcoal">Shop Voucher</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="code" placeholder="Enter voucher code" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm uppercase outline-none focus:border-primary">
                                <button type="button" @click="applyVoucher()" class="shrink-0 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-sky-blue">Apply</button>
                            </div>
                            <p x-show="message" x-text="message" :class="success ? 'text-leaf-green' : 'text-sale-red'" class="mt-1.5 text-xs"></p>
                            <input type="hidden" name="voucher_code" :value="appliedCode">
                        </div>

                        <div class="flex items-center justify-between gap-3 text-sm text-charcoal/70">
                            <span>
                                Merchandise Subtotal
                                ({{ $groupedBySeller->flatten(1)->sum('quantity') }}
                                item{{ $groupedBySeller->flatten(1)->sum('quantity') === 1 ? '' : 's' }})
                            </span>

                            <span class="shrink-0 font-semibold text-charcoal">
                                ₱{{ number_format($total, 2) }}
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 text-sm text-charcoal/70">
                            <span>
                                Shipping Fee ({{ $groupedBySeller->count() }}
                                parcel{{ $groupedBySeller->count() === 1 ? '' : 's' }})
                            </span>

                            <span class="shrink-0 font-semibold text-charcoal">
                                ₱{{ number_format($shippingTotal, 2) }}
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 text-sm text-charcoal/70" x-data="{ disc: 0 }" @voucher-applied.window="disc = $event.detail.discount">
                            <span>Voucher Discount</span>
                            <span class="shrink-0 font-semibold text-leaf-green">- ₱<span x-text="disc.toFixed(2)"></span></span>
                        </div>

                        <div class="my-4 h-px bg-light-gray"></div>

                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-bold text-charcoal">Total Payment</span>

                            <span class="text-xl font-bold text-primary">
                                ₱<span x-data="{ total: {{ $grandTotal }}, disc: 0 }" @voucher-applied.window="disc = $event.detail.discount" x-text="(total - disc).toFixed(2)"></span>
                            </span>
                        </div>

                        <p class="mb-4 text-right text-[11px] text-charcoal/40">
                            VAT included, where applicable
                        </p>

                        <!-- DISABLE CHECKOUT IF ANY SELLER IS BLOCKED -->
                        <button type="submit" @disabled(!empty($blockedSellerIds))
                            class="w-full rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50">
                            @if (!empty($blockedSellerIds))
                                Delivery Unavailable
                            @else
                                Place Order
                            @endif
                        </button>

                        @if (!empty($blockedSellerIds))
                            <p class="mt-2 text-center text-xs text-sale-red">
                                Remove items from sellers that cannot deliver to your address before checking out.
                            </p>
                        @endif

                        <p class="mt-3 text-center text-[11px] leading-relaxed text-charcoal/40">
                            By placing your order, you agree to Shopleap's
                            <a href="#" class="text-primary hover:underline">Terms of Service</a>
                            and
                            <a href="#" class="text-primary hover:underline">Privacy Policy</a>.
                        </p>
                    </div>
                </div>

            </div>
        </form>

    </main>

    <x-Buyer.message-widget />
    <x-footer />
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('voucherCheckout', () => ({
                code: '',
                appliedCode: '',
                message: '',
                success: false,
                init() {},
                async applyVoucher() {
                    this.message = '';
                    this.success = false;
                    if (!this.code) {
                        this.message = 'Please enter a voucher code.';
                        this.success = false;
                        return;
                    }
                    try {
                        const formData = new FormData();
                        formData.append('code', this.code);
                        formData.append('_token', document.querySelector('input[name="_token"]').value);
                        const response = await fetch('{{ route("buyer.vouchers.apply") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.appliedCode = data.voucher_code;
                            this.success = true;
                            this.message = data.message || 'Voucher applied successfully.';
                            window.dispatchEvent(new CustomEvent('voucher-applied', { detail: { discount: parseFloat(data.discount) || 0, code: data.voucher_code } }));
                        } else {
                            this.success = false;
                            this.message = data.message || 'Failed to apply voucher.';
                            window.dispatchEvent(new CustomEvent('voucher-applied', { detail: { discount: 0 } }));
                        }
                    } catch (e) {
                        this.success = false;
                        this.message = 'An error occurred while applying voucher.';
                        window.dispatchEvent(new CustomEvent('voucher-applied', { detail: { discount: 0 } }));
                    }
                }
            }));
        });
    </script>
</body>

</html>
