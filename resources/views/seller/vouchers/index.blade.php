<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Shop Vouchers | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">
    <div class="flex min-h-screen">
        <x-navbars.seller active="vouchers" />

        <div class="flex min-h-screen flex-1 flex-col">
            <header class="flex h-16 items-center justify-between border-b border-light-gray bg-white px-4 md:px-6">
                <div class="flex items-center gap-3">
                    <button id="sidebarToggle"
                        class="rounded-lg p-2 text-charcoal/70 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-base font-semibold text-charcoal md:text-lg">Shop Vouchers</h1>
                </div>

                <div class="flex items-center gap-2">
                    <x-notification-bell /> 
                </div>
            </header>

            <main class="flex-1 px-4 py-6 md:px-6">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-charcoal md:text-2xl">Shop Vouchers</h1>
                            <p class="mt-1 text-sm text-charcoal/60">Create and manage discount vouchers for your store</p>
                        </div>
                        <a href="{{ route('seller.vouchers.create') }}" class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-blue">
                            Create Voucher
                        </a>
                    </div>

                    @if(session('status'))
                        <div class="mb-4 rounded-lg bg-leaf-green/10 px-4 py-2.5 text-sm text-leaf-green">{{ session('status') }}</div>
                    @endif

                    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-ice-blue/40">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-charcoal">Code</th>
                                        <th class="px-4 py-3 text-left font-semibold text-charcoal">Discount</th>
                                        <th class="px-4 py-3 text-left font-semibold text-charcoal">Min. Spend</th>
                                        <th class="px-4 py-3 text-left font-semibold text-charcoal">Usage</th>
                                        <th class="px-4 py-3 text-left font-semibold text-charcoal">Validity</th>
                                        <th class="px-4 py-3 text-left font-semibold text-charcoal">Status</th>
                                        <th class="px-4 py-3 text-right font-semibold text-charcoal">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-light-gray">
                                    @forelse($vouchers as $voucher)
                                        <tr class="hover:bg-ice-blue/10">
                                            <td class="px-4 py-3">
                                                <p class="font-semibold text-charcoal">{{ $voucher->code }}</p>
                                                <p class="text-xs text-charcoal/50">{{ $voucher->name }}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($voucher->discount_type === 'percentage')
                                                    {{ $voucher->discount_value }}% @if($voucher->max_discount_amount) <span class="text-xs text-charcoal/50">(max ₱{{ number_format($voucher->max_discount_amount, 2) }})</span> @endif
                                                @else
                                                    ₱{{ number_format($voucher->discount_value, 2) }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">₱{{ number_format($voucher->min_purchase_amount, 2) }}</td>
                                            <td class="px-4 py-3">{{ $voucher->used_quantity }} / {{ $voucher->total_quantity ?? '∞' }} <span class="text-xs text-charcoal/50">({{ $voucher->redemptions_count ?? 0 }} redemptions)</span></td>
                                            <td class="px-4 py-3 text-xs text-charcoal/60">
                                                @if($voucher->starts_at) {{ $voucher->starts_at->format('M d, Y') }} @else Now @endif
                                                -
                                                @if($voucher->expires_at) {{ $voucher->expires_at->format('M d, Y') }} @else No expiry @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $voucher->is_active ? 'bg-leaf-green/10 text-leaf-green' : 'bg-charcoal/5 text-charcoal/60' }}">
                                                    {{ $voucher->is_active ? 'Active' : ucfirst($voucher->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right space-x-2">
                                                <a href="{{ route('seller.vouchers.show', $voucher) }}" class="text-xs font-semibold text-primary hover:underline">View</a>
                                                <a href="{{ route('seller.vouchers.edit', $voucher) }}" class="text-xs font-semibold text-primary hover:underline">Edit</a>
                                                <form action="{{ route('seller.vouchers.toggle', $voucher) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-semibold {{ $voucher->is_active ? 'text-sale-red' : 'text-leaf-green' }} hover:underline">{{ $voucher->is_active ? 'Disable' : 'Activate' }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-charcoal/50">No vouchers yet. Create your first voucher.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">{{ $vouchers->links() }}</div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
