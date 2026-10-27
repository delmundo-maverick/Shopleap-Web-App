<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Create Voucher | Shopleap</title>
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
                    <h1 class="text-base font-semibold text-charcoal md:text-lg">Create Voucher</h1>
                </div>

                <div class="flex items-center gap-2">
                    <x-notification-bell />
                </div>
            </header>

            <main class="flex-1 px-4 py-6 md:px-6">
    <div class="mx-auto max-w-3xl px-4 py-6">
        <div class="mb-6">
            <a href="{{ route('seller.vouchers.index') }}" class="text-sm text-primary hover:underline">← Back to Vouchers</a>
            <h1 class="mt-2 text-xl font-bold text-charcoal md:text-2xl">Create Shop Voucher</h1>
        </div>

        <form action="{{ route('seller.vouchers.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="rounded-lg bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-charcoal">Basic Information</h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-charcoal">Voucher Code</label>
                    <div class="flex gap-2">
                        <input type="text" name="code" id="voucherCode" value="{{ old('code') }}" placeholder="Leave empty to auto-generate" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm uppercase focus:border-primary">
                        <button type="button" onclick="generateCode()" class="shrink-0 rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/5">Generate Code</button>
                    </div>
                    @error('code') <p class="mt-1 text-xs text-sale-red">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-charcoal">Voucher Name (optional)</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-charcoal">Description (optional)</label>
                    <textarea name="description" rows="2" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-charcoal">Discount Settings</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-charcoal">Discount Type</label>
                        <select name="discount_type" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                            <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount (₱)</option>
                            <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-charcoal">Discount Value</label>
                        <input type="number" step="0.01" name="discount_value" value="{{ old('discount_value') }}" required class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                        @error('discount_value') <p class="mt-1 text-xs text-sale-red">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-charcoal">Min. Purchase Amount (₱)</label>
                        <input type="number" step="0.01" name="min_purchase_amount" value="{{ old('min_purchase_amount', 0) }}" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-charcoal">Max Discount Cap (₱) - Required for %</label>
                        <input type="number" step="0.01" name="max_discount_amount" value="{{ old('max_discount_amount') }}" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                        @error('max_discount_amount') <p class="mt-1 text-xs text-sale-red">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-charcoal">Usage & Validity</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-charcoal">Total Quantity (leave empty for unlimited)</label>
                        <input type="number" name="total_quantity" value="{{ old('total_quantity') }}" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-charcoal">Per Buyer Usage Limit</label>
                        <input type="number" name="per_buyer_limit" value="{{ old('per_buyer_limit', 1) }}" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-charcoal">Start Date/Time (optional)</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-charcoal">Expiration Date/Time (optional)</label>
                        <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="w-full rounded-lg border border-light-gray px-3 py-2 text-sm focus:border-primary">
                        @error('expires_at') <p class="mt-1 text-xs text-sale-red">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="accent-primary">
                    <span class="text-sm text-charcoal">Activate voucher immediately</span>
                </label>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-sky-blue">Create Voucher</button>
                <a href="{{ route('seller.vouchers.index') }}" class="rounded-xl border border-light-gray px-6 py-2.5 text-sm font-semibold text-charcoal hover:bg-ice-blue">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function generateCode() {
            fetch('{{ route('seller.vouchers.generate-code') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
