<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Create Voucher | Shopleap Seller</title>
</head>

<body class="min-h-screen overflow-x-hidden bg-ice-blue text-charcoal">
    <div class="flex min-h-screen">
        <x-navbars.seller active="vouchers" />

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">

            <!-- TOPBAR (same as products / orders / vouchers) -->
            <header
                class="frost-panel-solid sticky top-0 z-10 flex h-16 items-center justify-between gap-3 border-b border-white/50 px-4 sm:h-20 sm:px-6">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <x-nav.toggle target="workspace-navigation" />

                    <div class="min-w-0">
                        <h1 class="text-page-title truncate">Create Voucher</h1>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">Add a new shop voucher</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-4">
                    <x-notification-bell />
                </div>
            </header>

            <main class="min-w-0 flex-1 p-4 sm:p-6">
                <div class="mx-auto max-w-3xl">
                    <div class="mb-6">
                        <a href="{{ route('seller.vouchers.index') }}" class="text-sm text-primary hover:underline">←
                            Back to Vouchers</a>
                    </div>

                    <form action="{{ route('seller.vouchers.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                            <h2 class="text-base font-bold text-charcoal">Basic Information</h2>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-charcoal">Voucher Code</label>
                                <div class="flex gap-2">
                                    <input type="text" name="code" id="voucherCode" value="{{ old('code') }}"
                                        placeholder="Leave empty to auto-generate"
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm uppercase focus:border-primary">
                                    <button type="button" onclick="generateCode()"
                                        class="shrink-0 rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/5">Generate
                                        Code</button>
                                </div>
                                @error('code')
                                    <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-charcoal">Voucher Name
                                    (optional)</label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-charcoal">Description
                                    (optional)</label>
                                <textarea name="description" rows="2"
                                    class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <div class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                            <h2 class="text-base font-bold text-charcoal">Discount Settings</h2>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-charcoal">Discount Type</label>
                                    <select name="discount_type"
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                                        <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>
                                            Fixed Amount (₱)
                                        </option>
                                        <option value="percentage"
                                            {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>Percentage
                                            (%)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-charcoal">Discount Value</label>
                                    <input type="number" step="0.01" name="discount_value"
                                        value="{{ old('discount_value') }}" required
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                                    @error('discount_value')
                                        <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-charcoal">Min. Purchase Amount
                                        (₱)</label>
                                    <input type="number" step="0.01" name="min_purchase_amount"
                                        value="{{ old('min_purchase_amount', 0) }}"
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-charcoal">Max Discount Cap (₱) -
                                        Required for %</label>
                                    <input type="number" step="0.01" name="max_discount_amount"
                                        value="{{ old('max_discount_amount') }}"
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                                    @error('max_discount_amount')
                                        <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                            <h2 class="text-base font-bold text-charcoal">Usage & Validity</h2>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="mb-4">
                                <label class="mb-1 block text-sm font-semibold" for="claim_limit">Claim limit (optional)</label>
                                <input id="claim_limit" type="number" name="claim_limit" min="1" value="{{ old('claim_limit') }}" class="w-full rounded border border-line px-3 py-2 text-sm">
                                <p class="mt-1 text-xs text-charcoal/60">Maximum buyers who can claim. Leave empty for unlimited. Each claim is usable once.</p>
                                @error('claim_limit') <p class="mt-1 text-xs text-sale-red">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                    <label class="mb-1 block text-sm font-medium text-charcoal">Total Quantity (leave
                                        empty for unlimited)</label>
                                    <input type="number" name="total_quantity" value="{{ old('total_quantity') }}"
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-charcoal">Per Buyer Usage
                                        Limit</label>
                                    <input type="number" name="per_buyer_limit"
                                        value="{{ old('per_buyer_limit', 1) }}"
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-charcoal">Start Date/Time
                                        (optional)</label>
                                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-charcoal">Expiration Date/Time
                                        (optional)</label>
                                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"
                                        class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                                    @error('expires_at')
                                        <p class="mt-1 text-xs text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" value="1"
                                    {{ old('is_active') ? 'checked' : '' }} class="accent-primary">
                                <span class="text-sm text-charcoal">Activate voucher immediately</span>
                            </label>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                class="rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-sky-blue">Create
                                Voucher</button>
                            <a href="{{ route('seller.vouchers.index') }}"
                                class="rounded-xl border border-light-gray px-6 py-2.5 text-sm font-semibold text-charcoal hover:bg-ice-blue">Cancel</a>
                        </div>
                    </form>
                </div>

                <script>
                    function generateCode() {
                        fetch('{{ route('seller.vouchers.generate-code') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content'),
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                document.getElementById('voucherCode').value = data.code;
                            });
                    }
                </script>
            </main>
        </div>
    </div>
</body>

</html>
