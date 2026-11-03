@php
    // Mobile: each row becomes a card, each cell shows its label (from data-label).
    // md+: normal table. Labels are CSS pseudo-elements, so no extra markup is needed.
    $cell =
        'flex items-center justify-between gap-3 px-1 py-1.5 before:text-xs before:font-medium before:text-charcoal/50 before:content-[attr(data-label)] md:table-cell md:px-5 md:py-4 md:before:hidden';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Shop Vouchers | Shopleap Seller</title>
</head>

<body class="min-h-screen overflow-x-hidden bg-ice-blue text-charcoal">
    <div class="flex min-h-screen">
        <x-navbars.seller active="vouchers" />

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">

            <!-- TOPBAR (same as products / orders) -->
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
                        <h1 class="text-page-title truncate">Shop Vouchers</h1>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">{{ $vouchers->total() }} voucher(s) in
                            your store</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-4">
                    <x-notification-bell />
                </div>
            </header>

            {{-- Full width (no max-w container), same as the products and orders pages --}}
            <main class="min-w-0 flex-1 p-4 sm:p-6">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-charcoal/60">Create and manage discount vouchers for your store</p>
                    <a href="{{ route('seller.vouchers.create') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-blue">
                        Create Voucher
                    </a>
                </div>

                @if (session('status'))
                    <div role="status" class="mb-4 rounded-lg bg-leaf-green/10 px-4 py-2.5 text-sm text-leaf-green">
                        {{ session('status') }}</div>
                @endif

                <!-- VOUCHER LIST (cards on mobile, table from md) -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">

                    @if ($vouchers->isEmpty())
                        <div class="flex h-64 flex-col items-center justify-center px-4 text-center">
                            <p class="text-sm text-charcoal/50">No vouchers yet. Create your first voucher.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="block w-full text-left text-sm md:table">
                                <thead class="hidden bg-ice-blue/40 md:table-header-group">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal">Code</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal">Discount</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal">Min. Spend</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal">Usage</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal">Validity</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal">Status</th>
                                        <th scope="col" class="px-5 py-3 text-right font-semibold text-charcoal">
                                            Actions</th>
                                    </tr>
                                </thead>

                                {{-- Divider: on md+ every cell of every row except the last gets a bottom line.
                                     Mobile rows are already separate bordered cards. --}}
                                <tbody
                                    class="block space-y-3 p-3 md:table-row-group md:space-y-0 md:p-0 md:[&>tr:not(:last-child)>td]:border-b md:[&>tr:not(:last-child)>td]:border-light-gray">
                                    @foreach ($vouchers as $voucher)
                                        <tr
                                            class="block rounded-lg border border-light-gray p-3 hover:bg-ice-blue/10 md:table-row md:rounded-none md:border-0 md:p-0">

                                            <td class="block px-1 pb-1 md:table-cell md:px-5 md:py-4">
                                                <p class="text-base font-semibold text-charcoal md:text-sm">
                                                    {{ $voucher->code }}</p>
                                                <p class="text-xs text-charcoal/50">{{ $voucher->name }}</p>
                                            </td>

                                            <td data-label="Discount" class="{{ $cell }}">
                                                <div class="min-w-0 text-right md:text-left">
                                                    @if ($voucher->discount_type === 'percentage')
                                                        {{ $voucher->discount_value }}%
                                                        @if ($voucher->max_discount_amount)
                                                            <span class="text-xs text-charcoal/50">(max
                                                                ₱{{ number_format($voucher->max_discount_amount, 2) }})</span>
                                                        @endif
                                                    @else
                                                        ₱{{ number_format($voucher->discount_value, 2) }}
                                                    @endif
                                                </div>
                                            </td>

                                            <td data-label="Min. Spend" class="{{ $cell }}">
                                                ₱{{ number_format($voucher->min_purchase_amount, 2) }}
                                            </td>

                                            <td data-label="Usage" class="{{ $cell }}">
                                                <div class="min-w-0 text-right md:text-left">
                                                    {{ $voucher->used_quantity }} /
                                                    {{ $voucher->total_quantity ?? '∞' }}
                                                    <span
                                                        class="text-xs text-charcoal/50">({{ $voucher->redemptions_count ?? 0 }}
                                                        redemptions)</span>
                                                </div>
                                            </td>

                                            <td data-label="Validity"
                                                class="{{ $cell }} text-xs text-charcoal/60">
                                                <span class="min-w-0 text-right md:text-left">
                                                    {{ $voucher->starts_at ? $voucher->starts_at->format('M d, Y') : 'Now' }}
                                                    -
                                                    {{ $voucher->expires_at ? $voucher->expires_at->format('M d, Y') : 'No expiry' }}
                                                </span>
                                            </td>

                                            <td data-label="Status" class="{{ $cell }}">
                                                <span
                                                    class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $voucher->is_active ? 'bg-leaf-green/10 text-leaf-green' : 'bg-charcoal/5 text-charcoal/60' }}">
                                                    {{ $voucher->is_active ? 'Active' : ucfirst($voucher->status) }}
                                                </span>
                                            </td>

                                            <td class="block pt-2 md:table-cell md:px-5 md:py-4 md:pt-4 md:text-right">
                                                <div class="flex items-center gap-4 md:justify-end">
                                                    <a href="{{ route('seller.vouchers.show', $voucher) }}"
                                                        class="text-xs font-semibold text-primary hover:underline">View</a>
                                                    <a href="{{ route('seller.vouchers.edit', $voucher) }}"
                                                        class="text-xs font-semibold text-primary hover:underline">Edit</a>
                                                    <form action="{{ route('seller.vouchers.toggle', $voucher) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                            class="text-xs font-semibold {{ $voucher->is_active ? 'text-sale-red' : 'text-leaf-green' }} hover:underline">{{ $voucher->is_active ? 'Disable' : 'Activate' }}</button>
                                                    </form>
                                                </div>
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>

                <div class="mt-4 overflow-x-auto">{{ $vouchers->links() }}</div>
            </main>
        </div>
    </div>
</body>

</html>
