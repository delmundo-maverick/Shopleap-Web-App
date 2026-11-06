<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Discount | {{ $product->name }} | Shopleap Seller</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    @php
        $type = old('type', $discount?->type ?? 'percent');
        $stateStyles = [
            'live' => 'bg-leaf-green/10 text-leaf-green',
            'scheduled' => 'bg-blue-100 text-blue-600',
            'ended' => 'bg-sale-red/10 text-sale-red',
            'paused' => 'bg-light-gray text-charcoal/60',
        ];
        $inputCls =
            'w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10';
    @endphp

    <div class="flex min-h-screen">

        <x-navbars.seller active="inventory" />

        <div class="flex min-w-0 flex-1 flex-col">

            <!-- TOPBAR -->
            <header class="flex h-20 items-center justify-between border-b border-light-gray bg-white px-6">
                <div class="flex items-center gap-3">
                    <x-nav.toggle target="workspace-navigation" />
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Product Discount</h2>
                        <p class="text-sm text-charcoal/55">Run a sale on a single product</p>
                    </div>
                </div>

                <a href="{{ route('seller.products.index') }}"
                    class="text-sm font-semibold text-primary hover:underline">&larr; Back to products</a>
            </header>

            <main class="flex-1 p-6">

                @if (session('status'))
                    <div
                        class="mx-auto mb-5 max-w-3xl rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mx-auto mb-5 max-w-3xl rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3">
                        <p class="text-sm font-semibold text-sale-red">Please review the fields below.</p>
                        <ul class="mt-1 list-inside list-disc text-xs text-sale-red/80">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mx-auto max-w-3xl space-y-6">

                    <!-- PRODUCT SUMMARY -->
                    <section
                        class="flex items-center gap-4 rounded-2xl border border-light-gray bg-white p-5 shadow-sm shadow-charcoal/5">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                            @if ($product->main_image_url)
                                <img src="{{ $product->main_image_url }}" alt=""
                                    class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-bold text-charcoal">{{ $product->name }}</p>
                            <p class="text-sm text-charcoal/60">
                                {{ $product->hasVariants() ? 'Starting price' : 'Regular price' }}:
                                <span class="font-semibold">₱{{ number_format($product->price, 2) }}</span>
                            </p>
                        </div>
                        @if ($discount)
                            <span
                                class="shrink-0 rounded-full px-3 py-1 text-xs font-bold uppercase {{ $stateStyles[$discount->state] }}">
                                {{ $discount->state }}
                            </span>
                        @endif
                    </section>

                    <!-- DISCOUNT FORM -->
                    <form method="POST" action="{{ route('seller.products.discount.update', $product) }}"
                        class="space-y-6">
                        @csrf

                        <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                            <p class="mb-4 text-xs font-bold uppercase tracking-wide text-primary">Discount</p>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="type" class="mb-2 block text-sm font-semibold text-charcoal">Type
                                        *</label>
                                    <select id="type" name="type" class="{{ $inputCls }}">
                                        <option value="percent" @selected($type === 'percent')>Percentage (%)</option>
                                        <option value="fixed" @selected($type === 'fixed')>Fixed amount (₱)</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="value" class="mb-2 block text-sm font-semibold text-charcoal">
                                        <span id="valueLabel">Value *</span>
                                    </label>
                                    <input type="number" id="value" name="value" required min="0.01"
                                        step="0.01" value="{{ old('value', $discount?->value) }}"
                                        placeholder="e.g. 20" class="{{ $inputCls }}">
                                </div>

                                <div>
                                    <label for="starts_at"
                                        class="mb-2 block text-sm font-semibold text-charcoal">Starts</label>
                                    <input type="datetime-local" id="starts_at" name="starts_at"
                                        value="{{ old('starts_at', $discount?->starts_at?->format('Y-m-d\TH:i')) }}"
                                        class="{{ $inputCls }}">
                                    <p class="mt-1.5 text-xs text-charcoal/45">Leave empty to start right away.</p>
                                </div>

                                <div>
                                    <label for="ends_at"
                                        class="mb-2 block text-sm font-semibold text-charcoal">Ends</label>
                                    <input type="datetime-local" id="ends_at" name="ends_at"
                                        value="{{ old('ends_at', $discount?->ends_at?->format('Y-m-d\TH:i')) }}"
                                        class="{{ $inputCls }}">
                                    <p class="mt-1.5 text-xs text-charcoal/45">Leave empty to run until you remove it.
                                    </p>
                                </div>
                            </div>

                            <label class="mt-5 flex items-center gap-2 text-sm text-charcoal">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-primary"
                                    @checked(old('is_active', $discount?->is_active ?? true))>
                                Discount is active (untick to pause without deleting)
                            </label>

                            @if ($product->hasVariants())
                                <p class="mt-4 rounded-lg bg-ice-blue/60 px-3 py-2 text-xs text-charcoal/60">
                                    This product has variants. The discount is applied to every variant's price.
                                </p>
                            @endif
                        </section>

                        <!-- PREVIEW -->
                        <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">What buyers will see
                            </p>
                            <div class="flex items-baseline gap-2">
                                <span id="previewPrice"
                                    class="text-3xl font-bold text-primary">₱{{ number_format($product->price, 2) }}</span>
                                <span id="previewWas" class="hidden text-base text-charcoal/40 line-through"></span>
                                <span id="previewBadge"
                                    class="hidden rounded bg-sale-red/10 px-2 py-1 text-xs font-semibold text-sale-red"></span>
                            </div>
                        </section>

                        <button type="submit"
                            class="w-full rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue">
                            {{ $discount ? 'Update Discount' : 'Save Discount' }}
                        </button>
                    </form>

                    @if ($discount)
                        <form method="POST" action="{{ route('seller.products.discount.destroy', $product) }}"
                            onsubmit="return confirm('Remove this discount? Buyers will see the regular price again.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full rounded-xl border border-sale-red/30 bg-white px-6 py-3 text-sm font-bold text-sale-red transition hover:bg-sale-red/5">
                                Remove Discount
                            </button>
                        </form>
                    @endif

                </div>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const base = {{ (float) $product->price }};
            const MAX_PERCENT = {{ \App\Models\ProductDiscount::MAX_PERCENT }};
            const typeEl = document.getElementById('type');
            const valueEl = document.getElementById('value');
            const label = document.getElementById('valueLabel');
            const price = document.getElementById('previewPrice');
            const was = document.getElementById('previewWas');
            const badge = document.getElementById('previewBadge');
            const peso = n => '₱' + n.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            function refresh() {
                const isPercent = typeEl.value === 'percent';
                const v = parseFloat(valueEl.value);

                label.textContent = isPercent ? 'Percent off *' : 'Amount off (₱) *';
                valueEl.max = isPercent ? MAX_PERCENT : Math.max(base - 0.01, 0.01);

                const discounted = isNaN(v) ? base : (isPercent ? base * (1 - v / 100) : base - v);
                const valid = !isNaN(v) && v > 0 && discounted > 0 && discounted < base;

                if (valid) {
                    price.textContent = peso(Math.round(discounted * 100) / 100);
                    was.textContent = peso(base);
                    badge.textContent = '-' + Math.round((1 - discounted / base) * 100) + '% OFF';
                    was.classList.remove('hidden');
                    badge.classList.remove('hidden');
                } else {
                    price.textContent = peso(base);
                    was.classList.add('hidden');
                    badge.classList.add('hidden');
                }
            }

            typeEl.addEventListener('change', refresh);
            valueEl.addEventListener('input', refresh);
            refresh();
        })();
    </script>

</body>

</html>
