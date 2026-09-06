<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Add Product | Shopleap Seller</title>
</head>

<body class="min-h-screen bg-seller-bg text-charcoal">

    <div class="flex min-h-screen">

        <x-navbars.seller active="inventory" />

        <div class="flex flex-1 flex-col">

            <!-- TOPBAR -->
            <header class="flex h-20 items-center justify-between border-b border-light-gray bg-white px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="rounded-lg p-2 text-charcoal/60 transition hover:bg-seller-soft hover:text-seller lg:hidden">
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
                    class="mx-auto max-w-3xl space-y-6">
                    @csrf

                    <!-- BASIC INFO -->
                    <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                        <p class="mb-4 text-xs font-bold uppercase tracking-wide text-seller">Basic Information</p>

                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Product Name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    maxlength="150" placeholder="e.g. Wireless Bluetooth Earbuds"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-seller focus:ring-4 focus:ring-seller/10">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Description</label>
                                <textarea name="description" rows="4" maxlength="2000"
                                    placeholder="Describe the product's features, materials, size, etc."
                                    class="w-full resize-none rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-seller focus:ring-4 focus:ring-seller/10">{{ old('description') }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-charcoal">Category *</label>
                                    <select id="category" name="category" required
                                        class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-seller focus:ring-4 focus:ring-seller/10">
                                        <option value="" disabled {{ old('category') ? '' : 'selected' }}>Select
                                            category</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat['name'] }}"
                                                {{ old('category') === $cat['name'] ? 'selected' : '' }}>
                                                {{ $cat['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-charcoal">Subcategory</label>
                                    <select id="subcategory" name="subcategory"
                                        class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-seller focus:ring-4 focus:ring-seller/10 disabled:bg-seller-bg disabled:text-charcoal/40"
                                        {{ old('category') ? '' : 'disabled' }}>
                                        <option value="">Select category first</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- PRICING & STOCK -->
                    <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                        <p class="mb-4 text-xs font-bold uppercase tracking-wide text-seller">Pricing &amp; Stock</p>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Price (₱) *</label>
                                <input type="number" name="price" value="{{ old('price') }}" required
                                    min="0" step="0.01" placeholder="0.00"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-seller focus:ring-4 focus:ring-seller/10">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Discount Price (₱)</label>
                                <input type="number" name="discount_price" value="{{ old('discount_price') }}"
                                    min="0" step="0.01" placeholder="Optional"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-seller focus:ring-4 focus:ring-seller/10">
                                <p class="mt-1.5 text-xs text-charcoal/45">Must be lower than the regular price.</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-charcoal">Stock Quantity *</label>
                                <input type="number" name="stock_quantity" value="{{ old('stock_quantity') }}"
                                    required min="0" placeholder="0"
                                    class="w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-seller focus:ring-4 focus:ring-seller/10">
                            </div>
                        </div>
                    </section>

                    <!-- IMAGES -->
                    <section class="rounded-2xl border border-light-gray bg-white p-6 shadow-sm shadow-charcoal/5">
                        <p class="mb-1 text-xs font-bold uppercase tracking-wide text-seller">Product Photos</p>
                        <p class="mb-4 text-xs text-charcoal/50">Upload 1–6 images. First image will be used as the main
                            thumbnail.</p>

                        <label for="images"
                            class="group flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-light-gray bg-seller-bg px-6 py-8 text-center transition hover:border-seller/40 hover:bg-seller-soft/60">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-seller shadow-sm transition group-hover:bg-seller group-hover:text-white">
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
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-seller px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-seller/20 transition hover:bg-seller-light hover:shadow-xl">
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
        // ---------------------------------------------------
        // CATEGORY -> SUBCATEGORY CASCADE (client-side, from
        // the same config the server validates against)
        // ---------------------------------------------------
        const CATEGORIES = @json($categories);

        const categorySelect = document.getElementById('category');
        const subcategorySelect = document.getElementById('subcategory');

        function populateSubcategories(categoryName, oldSubcategory = null) {
            const match = CATEGORIES.find(c => c.name === categoryName);
            if (!match) {
                subcategorySelect.innerHTML = '<option value="">Select category first</option>';
                subcategorySelect.disabled = true;
                return;
            }

            subcategorySelect.innerHTML = '<option value="">Select subcategory (optional)</option>';
            match.subcategories.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub;
                opt.textContent = sub;
                if (oldSubcategory === sub) opt.selected = true;
                subcategorySelect.appendChild(opt);
            });
            subcategorySelect.disabled = false;
        }

        categorySelect.addEventListener('change', function() {
            populateSubcategories(this.value);
        });

        // Restore subcategory on validation failure
        @if (old('category'))
            populateSubcategories(@json(old('category')), @json(old('subcategory')));
        @endif

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
                        ${index === 0 ? '<span class="absolute left-1 top-1 rounded bg-seller px-1.5 py-0.5 text-[9px] font-bold text-white">Main</span>' : ''}
                    `;
                    previewGrid.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>

</body>

</html>
