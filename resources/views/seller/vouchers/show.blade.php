<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ $shopVoucher->code }} | Shop Vouchers | Shopleap Seller</title>
</head>

<body class="min-h-screen overflow-x-hidden bg-ice-blue text-charcoal">
    <div class="flex min-h-screen">
        <x-navbars.seller active="vouchers" />

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">

            <!-- TOPBAR (same as products / orders / vouchers) -->
            <header
                class="frost-panel-solid sticky top-0 z-10 flex h-16 items-center justify-between gap-3 border-b border-white/50 px-4 sm:h-20 sm:px-6">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <button type="button" id="sidebarToggle" data-sidebar-toggle aria-label="Open menu"
                        class="frost-btn shrink-0 rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <h1 class="text-page-title truncate">Voucher Details</h1>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">{{ $shopVoucher->code }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-4">
                    <x-notification-bell />
                </div>
            </header>

            <main class="min-w-0 flex-1 p-4 sm:p-6">
                <div class="mx-auto max-w-5xl">
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <h2 class="text-xl font-bold text-charcoal md:text-2xl">{{ $shopVoucher->code }}</h2>
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $shopVoucher->is_active ? 'bg-leaf-green/10 text-leaf-green' : 'bg-charcoal/5 text-charcoal/60' }}">
                                    {{ $shopVoucher->is_active ? 'Active' : ucfirst($shopVoucher->status) }}
                                </span>
                            </div>
                            @if ($shopVoucher->name)
                                <p class="mt-1 text-sm text-charcoal/60">{{ $shopVoucher->name }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('seller.vouchers.edit', $shopVoucher) }}"
                                class="rounded-xl border border-light-gray px-4 py-2 text-sm font-semibold text-charcoal hover:bg-ice-blue">Edit</a>
                            <form action="{{ route('seller.vouchers.toggle', $shopVoucher) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="rounded-xl px-4 py-2 text-sm font-semibold {{ $shopVoucher->is_active ? 'border border-sale-red/40 text-sale-red hover:bg-sale-red/5' : 'border border-leaf-green/40 text-leaf-green hover:bg-leaf-green/5' }}">
                                    {{ $shopVoucher->is_active ? 'Disable' : 'Activate' }}
                                </button>
                            </form>
                            <a href="{{ route('seller.vouchers.index') }}"
                                class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-sky-blue">Back
                                to Vouchers</a>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg bg-white p-5 shadow-sm">
                            <h2 class="mb-4 text-sm font-bold text-charcoal">Voucher Details</h2>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Discount Type</span>
                                    <span
                                        class="font-semibold text-charcoal">{{ $shopVoucher->discount_type === 'percentage' ? 'Percentage' : 'Fixed Amount' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Discount Value</span>
                                    <span class="font-semibold text-charcoal">
                                        @if ($shopVoucher->discount_type === 'percentage')
                                            {{ $shopVoucher->discount_value }}%
                                        @else
                                            ₱{{ number_format($shopVoucher->discount_value, 2) }}
                                        @endif
                                    </span>
                                </div>
                                @if ($shopVoucher->discount_type === 'percentage' && $shopVoucher->max_discount_amount)
                                    <div class="flex items-center justify-between">
                                        <span class="text-charcoal/60">Max Discount</span>
                                        <span
                                            class="font-semibold text-charcoal">₱{{ number_format($shopVoucher->max_discount_amount, 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Minimum Purchase</span>
                                    <span
                                        class="font-semibold text-charcoal">₱{{ number_format($shopVoucher->min_purchase_amount, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Usage Limit</span>
                                    <span
                                        class="font-semibold text-charcoal">{{ $shopVoucher->total_quantity ? $shopVoucher->total_quantity : 'Unlimited' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Used</span>
                                    <span class="font-semibold text-charcoal">{{ $shopVoucher->used_quantity }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg bg-white p-5 shadow-sm">
                            <h2 class="mb-4 text-sm font-bold text-charcoal">Validity & Performance</h2>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Start Date</span>
                                    <span
                                        class="font-semibold text-charcoal">{{ $shopVoucher->starts_at ? $shopVoucher->starts_at->format('M d, Y h:i A') : 'Immediate' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Expiry Date</span>
                                    <span
                                        class="font-semibold text-charcoal">{{ $shopVoucher->expires_at ? $shopVoucher->expires_at->format('M d, Y h:i A') : 'No Expiry' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Total Redemptions</span>
                                    <span class="font-semibold text-charcoal">{{ $stats['total_redemptions'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-charcoal/60">Total Discount Given</span>
                                    <span
                                        class="font-semibold text-leaf-green">₱{{ number_format($stats['total_discount'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($shopVoucher->description)
                        <div class="mt-4 rounded-lg bg-white p-5 shadow-sm">
                            <h2 class="mb-2 text-sm font-bold text-charcoal">Description</h2>
                            <p class="text-sm text-charcoal/70">{{ $shopVoucher->description }}</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</body>

</html>
