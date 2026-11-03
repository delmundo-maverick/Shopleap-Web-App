<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Vouchers | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">
    <x-Buyer.header />

    @php
        // Same button style as the orders page.
        $btnOutline =
            'inline-flex min-h-9 shrink-0 items-center justify-center rounded-lg border border-line bg-white px-4 py-1.5 text-xs font-bold text-charcoal transition-colors hover:border-primary hover:text-primary';
    @endphp

    <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-4 sm:px-6 sm:py-6 lg:px-8">

        <div class="mb-4 sm:mb-6">
            <h1 class="text-xl font-bold text-charcoal md:text-2xl">Shop Vouchers</h1>
            <p class="mt-1 text-sm text-charcoal/60">Discover and use vouchers from your favorite sellers</p>
        </div>

        {{-- AVAILABLE VOUCHERS --}}
        <section class="mb-3 overflow-hidden rounded-xl border border-line bg-white sm:mb-4">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <h2 class="text-sm font-bold text-charcoal sm:text-base">Available Vouchers</h2>
                @if ($available->isNotEmpty())
                    <span class="rounded-sm bg-ice-blue px-1.5 py-0.5 text-[10px] font-bold text-charcoal/60">
                        {{ $available->count() }}
                    </span>
                @endif
            </div>

            @if ($available->isEmpty())
                <div class="flex flex-col items-center justify-center p-8 text-center sm:p-16">
                    <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v3a2 2 0 100 4v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a2 2 0 100-4V7a2 2 0 012-2z" />
                    </svg>
                    <p class="mt-4 text-sm font-semibold text-charcoal">No available vouchers right now</p>
                </div>
            @else
                <div class="grid gap-3 p-4 sm:grid-cols-2 sm:gap-4 sm:p-5 lg:grid-cols-3">
                    @foreach ($available as $voucher)
                        <article class="flex flex-col overflow-hidden rounded-xl border border-line bg-white">
                            <div class="flex-1 p-4">
                                <p class="text-2xl font-bold tabular-nums text-primary">
                                    @if ($voucher->discount_type === 'percentage')
                                        {{ rtrim(rtrim(number_format($voucher->discount_value, 2), '0'), '.') }}% OFF
                                    @else
                                        ₱{{ number_format($voucher->discount_value, 2) }} OFF
                                    @endif
                                </p>
                                <p class="mt-1 truncate text-xs font-semibold text-charcoal">
                                    {{ $voucher->seller?->sellerProfile?->business_name ?? $voucher->seller?->name }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span
                                        class="rounded-sm border border-line bg-ice-blue px-2 py-0.5 text-[11px] font-semibold text-charcoal/70">
                                        Min. spend ₱{{ number_format($voucher->min_purchase_amount, 2) }}
                                    </span>
                                    @if ($voucher->remaining_quantity !== null)
                                        <span
                                            class="rounded-sm border border-line bg-ice-blue px-2 py-0.5 text-[11px] font-semibold text-charcoal/70">
                                            {{ $voucher->remaining_quantity }} left
                                        </span>
                                    @endif
                                </div>

                                @if ($voucher->expires_at)
                                    <p class="mt-3 text-xs text-charcoal/60">
                                        Expires {{ $voucher->expires_at->format('M d, Y h:i A') }}
                                    </p>
                                @endif
                            </div>

                            <div
                                class="flex items-center justify-between gap-3 border-t border-dashed border-line bg-ice-blue px-4 py-3">
                                <code
                                    class="truncate text-sm font-bold tracking-widest text-charcoal">{{ $voucher->code }}</code>
                                <button type="button" data-code="{{ $voucher->code }}"
                                    onclick="navigator.clipboard.writeText(this.dataset.code); this.textContent = 'Copied'; setTimeout(() => this.textContent = 'Copy Code', 1500)"
                                    class="{{ $btnOutline }}">Copy Code</button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- REDEEMED VOUCHERS --}}
        <section class="overflow-hidden rounded-xl border border-line bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <h2 class="text-sm font-bold text-charcoal sm:text-base">Redeemed Vouchers</h2>
                @if ($redeemed->isNotEmpty())
                    <span class="rounded-sm bg-ice-blue px-1.5 py-0.5 text-[10px] font-bold text-charcoal/60">
                        {{ $redeemed->count() }}
                    </span>
                @endif
            </div>

            @if ($redeemed->isEmpty())
                <div class="flex flex-col items-center justify-center p-8 text-center sm:p-16">
                    <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-4 text-sm font-semibold text-charcoal">No redeemed vouchers yet</p>
                </div>
            @else
                <div class="divide-y divide-line">
                    @foreach ($redeemed as $redemption)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-charcoal">
                                    {{ $redemption->voucher?->code }}</p>
                                <p class="mt-0.5 truncate text-xs text-charcoal/60">
                                    {{ $redemption->seller?->sellerProfile?->business_name ?? $redemption->seller?->name }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold tabular-nums text-leaf-green">
                                    -₱{{ number_format($redemption->discount_amount, 2) }}
                                </p>
                                <p class="mt-1 flex items-center justify-end gap-1.5 text-xs text-charcoal/60">
                                    <span
                                        class="rounded-sm border border-line bg-ice-blue px-2 py-0.5 text-[10px] font-semibold text-charcoal/70">
                                        {{ ucfirst($redemption->status) }}
                                    </span>
                                    @if ($redemption->order_id)
                                        <span>Order #{{ $redemption->order_id }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </main>

    <x-Buyer.message-widget />
    <x-footer />
</body>

</html>
