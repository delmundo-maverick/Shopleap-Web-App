<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Edit Voucher {{ $shopVoucher->code }} | Shopleap Seller</title>
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
                        <h1 class="text-page-title truncate">Edit Voucher</h1>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">{{ $shopVoucher->code }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-4">
                    <x-notification-bell />
                </div>
            </header>

            <main class="min-w-0 flex-1 p-4 sm:p-6">
                <div class="mx-auto max-w-3xl">
                    <div class="mb-6 flex items-center justify-between gap-3">
                        <p class="text-sm text-charcoal/60">Update voucher details</p>
                        <a href="{{ route('seller.vouchers.show', $shopVoucher) }}"
                            class="rounded-xl border border-light-gray px-4 py-2 text-sm font-semibold text-charcoal hover:bg-ice-blue">Cancel</a>
                    </div>

                    <form action="{{ route('seller.vouchers.update', $shopVoucher) }}" method="POST"
                        class="rounded-lg bg-white p-6 shadow-sm">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Voucher Name</label>
                                <input type="text" name="name" value="{{ old('name', $shopVoucher->name) }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary"
                                    required>
                                @error('name')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Discount Type</label>
                                <select name="discount_type"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary"
                                    required>
                                    <option value="fixed"
                                        {{ old('discount_type', $shopVoucher->discount_type) === 'fixed' ? 'selected' : '' }}>
                                        Fixed Amount (₱)</option>
                                    <option value="percentage"
                                        {{ old('discount_type', $shopVoucher->discount_type) === 'percentage' ? 'selected' : '' }}>
                                        Percentage (%)</option>
                                </select>
                                @error('discount_type')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Discount Value</label>
                                <input type="number" name="discount_value" step="0.01" min="0"
                                    value="{{ old('discount_value', $shopVoucher->discount_value) }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary"
                                    required>
                                @error('discount_value')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Minimum Purchase
                                    (₱)</label>
                                <input type="number" name="min_purchase_amount" step="0.01" min="0"
                                    value="{{ old('min_purchase_amount', $shopVoucher->min_purchase_amount) }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary">
                                @error('min_purchase_amount')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Max Discount Amount
                                    (₱)</label>
                                <input type="number" name="max_discount_amount" step="0.01" min="0"
                                    value="{{ old('max_discount_amount', $shopVoucher->max_discount_amount) }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary">
                                <p class="mt-1 text-xs text-charcoal/50">For percentage discounts only</p>
                                @error('max_discount_amount')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Total Quantity</label>
                                <input type="number" name="total_quantity" min="1"
                                    value="{{ old('total_quantity', $shopVoucher->total_quantity) }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary">
                                <p class="mt-1 text-xs text-charcoal/50">Leave blank for unlimited</p>
                                @error('total_quantity')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Per User Limit</label>
                                <input type="number" name="per_user_limit" min="1"
                                    value="{{ old('per_user_limit', $shopVoucher->per_user_limit) }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary">
                                @error('per_user_limit')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Start Date</label>
                                <input type="datetime-local" name="starts_at"
                                    value="{{ old('starts_at', $shopVoucher->starts_at ? $shopVoucher->starts_at->format('Y-m-d\TH:i') : '') }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary">
                                @error('starts_at')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Expiry Date</label>
                                <input type="datetime-local" name="expires_at"
                                    value="{{ old('expires_at', $shopVoucher->expires_at ? $shopVoucher->expires_at->format('Y-m-d\TH:i') : '') }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary">
                                @error('expires_at')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold text-charcoal">Description</label>
                                <textarea name="description" rows="3"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary">{{ old('description', $shopVoucher->description) }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <a href="{{ route('seller.vouchers.show', $shopVoucher) }}"
                                class="rounded-xl border border-light-gray px-5 py-2.5 text-sm font-semibold text-charcoal hover:bg-ice-blue">Cancel</a>
                            <button type="submit"
                                class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-blue">Update
                                Voucher</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
