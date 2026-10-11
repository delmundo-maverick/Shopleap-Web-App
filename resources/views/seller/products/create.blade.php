<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Add Product | Shopleap Seller</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <div class="flex min-h-screen">

        <x-navbars.seller active="inventory" />

        <div class="flex flex-1 flex-col">

            <!-- TOPBAR -->
            <header class="flex h-20 items-center justify-between border-b border-light-gray bg-white px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Add Product</h2>
                        <p class="text-sm text-charcoal/55">List a new item in your store</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6">

                @if (session('status'))
                    <div
                        class="mb-5 rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3">
                        <p class="text-sm font-semibold text-sale-red">Please review the fields below.</p>
                        <ul class="mt-1 list-inside list-disc text-xs text-sale-red/80">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('seller.products.store') }}" enctype="multipart/form-data"
                    id="productForm" class="mx-auto max-w-3xl space-y-6">
                    @csrf

                    <!-- BASIC INFO -->
                    <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                        <p class="mb-4 text-xs font-bold uppercase tracking-wide text-primary">Basic Information</p>

                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Product Name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    maxlength="150" placeholder="e.g. Wireless Bluetooth Earbuds"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Description</label>
                                <textarea name="description" rows="4" maxlength="2000"
                                    placeholder="Describe the product's features, materials, size, etc."
                                    class="w-full resize-none rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">{{ old('description') }}</textarea>
                            </div>

                            <div class="rounded-xl border border-light-gray bg-ice-blue/50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Category</p>
                                <p class="mt-1 text-sm font-semibold text-charcoal">{{ $sellerCategory ?? 'Not set' }}
                                </p>
                                <p class="mt-1 text-xs text-charcoal/45">
                                    Automatically set from your registered business category. To change it, update your
                                    seller profile.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- PRICING & STOCK -->
                    <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                        <p class="mb-1 text-xs font-bold uppercase tracking-wide text-primary">Pricing &amp; Stock</p>
                        <p id="pricingHint" class="mb-4 text-xs text-charcoal/50">
                            Set a single price and stock count, or add options below to price each variant separately.
                        </p>

                        <div id="basePricingFields" class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Price (₱) *</label>
                                <input type="number" id="basePrice" name="price" value="{{ old('price') }}" required
                                    min="0" step="0.01" placeholder="0.00"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Discount Price (₱)</label>
                                <input type="number" id="baseDiscountPrice" name="discount_price"
                                    value="{{ old('discount_price') }}" min="0" step="0.01"
                                    placeholder="Optional"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                                <p class="mt-1.5 text-xs text-charcoal/45">Must be lower than the regular price.</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Stock Quantity *</label>
                                <input type="number" id="baseStock" name="stock_quantity"
                                    value="{{ old('stock_quantity') }}" required min="0" placeholder="0"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                            </div>
                        </div>
                    </section>

                    <!-- OPTIONS & VARIANTS -->
                    <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-primary">Options & Variants</p>
                                <p class="mt-1 text-xs text-charcoal/50">
                                    Add option groups like Pack Size, Flavor, Color or Size. Each combination can have
                                    its own price and stock.
                                </p>
                            </div>
                            <button type="button" id="addOptionBtn"
                                class="shrink-0 rounded-lg border border-primary/30 bg-primary/5 px-3 py-2 text-xs font-bold text-primary transition hover:bg-primary/10">
                                + Add Option
                            </button>
                        </div>

                        <div id="optionsContainer" class="space-y-3"></div>

                        <button type="button" id="generateVariantsBtn"
                            class="mt-4 hidden w-full rounded-xl border border-dashed border-primary/30 bg-primary/5 px-4 py-2.5 text-xs font-bold text-primary transition hover:bg-primary/10">
                            Generate Variant Combinations
                        </button>

                        <div id="variantsWrapper" class="mt-5 hidden">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Variant Pricing &amp;
                                Stock</p>
                            <div class="overflow-x-auto rounded-xl border border-light-gray">
                                <table class="w-full text-sm">
                                    <thead
                                        class="bg-ice-blue/60 text-left text-xs font-semibold uppercase tracking-wide text-charcoal/50">
                                        <tr>
                                            <th class="px-4 py-3">Combination</th>
                                            <th class="px-4 py-3">Price (₱) *</th>
                                            <th class="px-4 py-3">Stock *</th>
                                            <th class="px-4 py-3">SKU</th>
                                        </tr>
                                    </thead>
                                    <tbody id="variantsBody" class="divide-y divide-light-gray"></tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- IMAGES -->
                    <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                        <p class="mb-1 text-xs font-bold uppercase tracking-wide text-primary">Product Photos</p>
                        <p class="mb-4 text-xs text-charcoal/50">Upload 1–6 images. First image will be used as the
                            main
                            thumbnail.</p>

                        <label for="images"
                            class="group flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-light-gray bg-ice-blue px-6 py-8 text-center transition hover:border-primary/40 hover:bg-ice-blue/60">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-primary shadow-sm transition group-hover:bg-primary group-hover:text-white">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 16V4m0 0L8 8m4-4l4 4M5 20h14" />
                                </svg>
                            </div>
                            <p class="mt-3 text-sm font-bold text-charcoal">Click to upload photos</p>
                            <p class="mt-1 text-xs text-charcoal/50">JPG, PNG or WEBP — max 3MB each</p>
                            <input type="file" id="images" name="images[]" multiple required
                                accept=".jpg,.jpeg,.png,.webp" class="sr-only">
                        </label>

                        <div id="imagePreviewGrid" class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-6"></div>
                    </section>

                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-seller/20 transition hover:bg-sky-blue hover:shadow-xl">
                        Add Product
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                    </button>

                </form>

            </main>

        </div>

    </div>

    <script>
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        } [c]));

        const MAX_OPTION_GROUPS = 3;
        const inputCls =
            'w-full rounded-lg border border-light-gray px-3 py-2 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10';

        // ---------------------------------------------------
        // OPTION GROUPS (e.g. "Pack Size" -> "Pack of 4, Pack of 5")
        // ---------------------------------------------------
        let optionRowCount = 0;
        const optionsContainer = document.getElementById('optionsContainer');
        const addOptionBtn = document.getElementById('addOptionBtn');
        const generateVariantsBtn = document.getElementById('generateVariantsBtn');
        const variantsWrapper = document.getElementById('variantsWrapper');
        const variantsBody = document.getElementById('variantsBody');
        const basePricingFields = document.getElementById('basePricingFields');
        const pricingHint = document.getElementById('pricingHint');
        const basePrice = document.getElementById('basePrice');
        const baseStock = document.getElementById('baseStock');
        const baseDiscountPrice = document.getElementById('baseDiscountPrice');

        // Remembers previously entered price/stock/sku per combination key
        // so regenerating variants doesn't wipe out what was typed.
        const savedVariantData = new Map();

        function addOptionRow(name = '', values = '') {
            const index = optionRowCount++;
            const row = document.createElement('div');
            row.className = 'flex items-start gap-3 rounded-xl border border-light-gray p-3';
            row.dataset.rowIndex = index;
            row.innerHTML = `
                <div class="flex-1">
                    <input type="text" name="options[${index}][name]" value="${esc(name)}" maxlength="100"
                        placeholder="Option name (e.g. Pack Size)" class="option-name-input ${inputCls}">
                </div>
                <div class="flex-[2]">
                    <input type="text" name="options[${index}][values]" value="${esc(values)}" maxlength="500"
                        placeholder="Values, comma-separated (e.g. Pack of 4, Pack of 5)" class="option-values-input ${inputCls}">
                </div>
                <button type="button" aria-label="Remove option" class="remove-option-btn mt-1 shrink-0 rounded-lg p-2 text-charcoal/40 transition hover:bg-sale-red/10 hover:text-sale-red">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            row.querySelector('.remove-option-btn').addEventListener('click', () => {
                row.remove();
                refreshOptionState();
            });
            row.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', () => {
                    // [ and ] would break the variants[i][options][<name>] field names.
                    if (input.classList.contains('option-name-input')) {
                        input.value = input.value.replace(/[\[\]]/g, '');
                    }
                    refreshOptionState();
                });
            });
            optionsContainer.appendChild(row);
            refreshOptionState();
        }

        function getCurrentOptionGroups() {
            return Array.from(optionsContainer.children)
                .map(row => ({
                    name: row.querySelector('.option-name-input').value.trim(),
                    values: [...new Set(
                        row.querySelector('.option-values-input').value
                        .split(',')
                        .map(v => v.trim())
                        .filter(Boolean)
                    )],
                }))
                .filter(group => group.name && group.values.length > 0);
        }

        function refreshOptionState() {
            const groups = getCurrentOptionGroups();
            const atLimit = optionsContainer.children.length >= MAX_OPTION_GROUPS;

            addOptionBtn.disabled = atLimit;
            addOptionBtn.classList.toggle('opacity-50', atLimit);
            generateVariantsBtn.classList.toggle('hidden', groups.length === 0);

            // If options no longer valid, fall back to simple pricing.
            if (groups.length === 0) {
                showVariantMode(false);
                return;
            }

            // Keep the variant table in step with the options once it exists.
            if (!variantsWrapper.classList.contains('hidden')) {
                renderVariantRows();
            }
        }

        function cartesianProduct(groups) {
            return groups.reduce((acc, group) => {
                const combos = [];
                acc.forEach(prefix => {
                    group.values.forEach(value => {
                        combos.push({
                            ...prefix,
                            [group.name]: value
                        });
                    });
                });
                return combos;
            }, [{}]);
        }

        function showVariantMode(enabled) {
            variantsWrapper.classList.toggle('hidden', !enabled);
            basePricingFields.classList.toggle('hidden', enabled);
            pricingHint.textContent = enabled ?
                'Price and stock are now set per variant below.' :
                'Set a single price and stock count, or add options below to price each variant separately.';

            if (enabled) {
                basePrice.removeAttribute('required');
                baseStock.removeAttribute('required');
                baseDiscountPrice.value = '';
            } else {
                basePrice.setAttribute('required', 'required');
                baseStock.setAttribute('required', 'required');
                variantsBody.innerHTML = '';
            }
        }

        function renderVariantRows() {
            const groups = getCurrentOptionGroups();
            if (groups.length === 0) {
                showVariantMode(false);
                return;
            }

            const combos = cartesianProduct(groups);
            variantsBody.innerHTML = '';

            combos.forEach((combo, i) => {
                const comboKey = JSON.stringify(combo);
                const saved = savedVariantData.get(comboKey) || {};
                const label = Object.entries(combo).map(([k, v]) => `${k}: ${v}`).join(', ');

                const hiddenInputs = Object.entries(combo)
                    .map(([name, value]) =>
                        `<input type="hidden" name="variants[${i}][options][${esc(name)}]" value="${esc(value)}">`)
                    .join('');

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-4 py-3 font-medium text-charcoal">
                        ${esc(label)}
                        ${hiddenInputs}
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" name="variants[${i}][price]" value="${esc(saved.price ?? '')}" min="0" step="0.01" required
                            class="variant-price-input w-28 rounded-lg border border-light-gray px-2 py-1.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" name="variants[${i}][stock_quantity]" value="${esc(saved.stock ?? '')}" min="0" step="1" required
                            class="variant-stock-input w-24 rounded-lg border border-light-gray px-2 py-1.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" name="variants[${i}][sku]" value="${esc(saved.sku ?? '')}" maxlength="100" placeholder="Optional"
                            class="variant-sku-input w-28 rounded-lg border border-light-gray px-2 py-1.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </td>
                `;

                row.querySelector('.variant-price-input').addEventListener('input', e =>
                    saveVariantField(comboKey, 'price', e.target.value));
                row.querySelector('.variant-stock-input').addEventListener('input', e =>
                    saveVariantField(comboKey, 'stock', e.target.value));
                row.querySelector('.variant-sku-input').addEventListener('input', e =>
                    saveVariantField(comboKey, 'sku', e.target.value));

                variantsBody.appendChild(row);
            });

            showVariantMode(true);
        }

        function saveVariantField(comboKey, field, value) {
            const existing = savedVariantData.get(comboKey) || {};
            existing[field] = value;
            savedVariantData.set(comboKey, existing);
        }

        addOptionBtn.addEventListener('click', () => addOptionRow());
        generateVariantsBtn.addEventListener('click', renderVariantRows);

        // Before submit, if using variants, fill the (hidden) base price/stock
        // so nothing blocks submission — the backend recalculates them anyway.
        document.getElementById('productForm').addEventListener('submit', function() {
            if (!variantsWrapper.classList.contains('hidden')) {
                const prices = Array.from(document.querySelectorAll('.variant-price-input'))
                    .map(el => parseFloat(el.value)).filter(v => !isNaN(v));
                const stocks = Array.from(document.querySelectorAll('.variant-stock-input'))
                    .map(el => parseInt(el.value)).filter(v => !isNaN(v));

                basePrice.value = prices.length ? Math.min(...prices) : 0;
                baseStock.value = stocks.length ? stocks.reduce((a, b) => a + b, 0) : 0;
            }
        });

        // ---------------------------------------------------
        // RESTORE after a server-side validation error
        // ---------------------------------------------------
        (function restoreOldInput() {
            const oldOptions = Object.values(@json(old('options', [])) || {});
            const oldVariants = Object.values(@json(old('variants', [])) || {});

            if (!oldOptions.length) return;

            oldOptions.forEach(o => addOptionRow(o.name ?? '', o.values ?? ''));

            oldVariants.forEach(v => {
                if (!v.options) return;
                savedVariantData.set(JSON.stringify(v.options), {
                    price: v.price ?? '',
                    stock: v.stock_quantity ?? '',
                    sku: v.sku ?? '',
                });
            });

            if (oldVariants.length) renderVariantRows();
        })();

        // ---------------------------------------------------
        // IMAGE PREVIEW
        // ---------------------------------------------------
        const imagesInput = document.getElementById('images');
        const previewGrid = document.getElementById('imagePreviewGrid');

        imagesInput.addEventListener('change', function() {
            previewGrid.innerHTML = '';
            Array.from(this.files).slice(0, 6).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = e => {
                    const wrapper = document.createElement('div');
                    wrapper.className =
                        'relative aspect-square overflow-hidden rounded-lg border border-light-gray';
                    wrapper.innerHTML = `
                        <img src="${e.target.result}" class="h-full w-full object-cover">
                        ${index === 0 ? '<span class="absolute left-1 top-1 rounded bg-primary px-1.5 py-0.5 text-[9px] font-bold text-white">Main</span>' : ''}
                    `;
                    previewGrid.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>

</body>

</html>
