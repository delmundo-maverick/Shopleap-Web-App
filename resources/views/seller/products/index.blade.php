@php
    $statusVariants = ['draft' => 'warning', 'active' => 'success', 'archived' => 'neutral'];
    $lowStock = (int) config('shop.low_stock_threshold', 5);

    // Mobile: each table row becomes a card, each cell shows its own label (from data-label).
    // Desktop (md+): normal table. Labels are CSS pseudo-elements so products.js can still
    // overwrite the cell text via [data-field] without touching the label.
    $labelCell =
        'flex items-center justify-between gap-3 before:text-xs before:font-medium before:text-charcoal/50 before:content-[attr(data-label)] md:table-cell md:before:hidden';

    // Same idea for the variants table inside the modal (label left, input right).
    $variantCell =
        'grid grid-cols-[5rem_1fr] items-start gap-2 before:pt-2 before:text-xs before:font-medium before:text-charcoal/50 before:content-[attr(data-label)] md:table-cell md:before:hidden';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>My Products | Shopleap Seller</title>

    {{-- Keep products.js BEFORE app.js: it registers on alpine:init, which app.js fires when it starts Alpine. --}}
    @vite(['resources/js/pages/seller/products.js', 'resources/js/app.js', 'resources/css/app.css', 'resources/css/pages/seller/products.css'])
</head>

<body class="min-h-screen overflow-x-hidden text-charcoal" :class="{ 'overflow-hidden': modalOpen }"
    x-data="productsPage(@js([
    'categories' => $categories,
    'statusVariants' => $statusVariants,
    'lowStock' => $lowStock,
    'routes' => [
        'details' => route('seller.products.details', '__ID__'),
        'update' => route('seller.products.update', '__ID__'),
        'status' => route('seller.products.status', '__ID__'),
        'images' => route('seller.products.images.store', '__ID__'),
        'imageUpdate' => route('seller.products.images.update', ['product' => '__ID__', 'image' => '__IMAGE__']),
        'imageDestroy' => route('seller.products.images.destroy', ['product' => '__ID__', 'image' => '__IMAGE__']),
        'variantImage' => route('seller.products.variants.image', ['product' => '__ID__', 'variant' => '__VARIANT__']),
        'variantImageDestroy' => route('seller.products.variants.image.destroy', ['product' => '__ID__', 'variant' => '__VARIANT__']),
    ],
]))">

    <div class="frost-surface pointer-events-none" aria-hidden="true"></div>

    <div class="flex min-h-screen">

        <x-navbars.seller active="inventory" />

        <div class="flex min-w-0 flex-1 flex-col">

            <!-- TOPBAR -->
            <header
                class="frost-panel-solid sticky top-0 z-10 flex h-16 items-center justify-between gap-3 border-b border-white/50 px-4 sm:h-20 sm:px-6">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <button type="button" data-sidebar-toggle aria-label="Open menu"
                        class="frost-btn shrink-0 rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-page-title truncate">My Products</h1>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">{{ $products->total() }} product(s) in
                            your store</p>
                    </div>
                </div>

                <a href="{{ route('seller.products.create') }}" aria-label="Add product"
                    class="btn-primary shrink-0 shadow-lg shadow-primary/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Add Product</span>
                </a>
            </header>

            <main class="min-w-0 flex-1 space-y-4 p-4 sm:space-y-5 sm:p-6">

                @if (session('status'))
                    <div role="status"
                        class="rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm text-success backdrop-blur-sm">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- SEARCH & FILTERS -->
                <form method="GET" role="search"
                    class="frost-panel grid grid-cols-2 gap-3 rounded-2xl p-4 sm:grid-cols-4 lg:flex lg:items-center">

                    <div class="relative col-span-2 sm:col-span-4 lg:flex-1">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-charcoal/50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="search" name="search" value="{{ request('search') }}"
                            placeholder="Search your products..." aria-label="Search your products"
                            class="frost-input field-control pl-10 font-medium">
                    </div>

                    <select name="category" aria-label="Filter by category" @change="$el.form.submit()"
                        class="frost-input field-control col-span-2 min-w-0 sm:col-span-1 lg:w-auto">
                        <option value="all" @selected(request('category', 'all') === 'all')>All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat['name'] }}" @selected(request('category') === $cat['name'])>
                                {{ $cat['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" aria-label="Filter by status" @change="$el.form.submit()"
                        class="frost-input field-control min-w-0 lg:w-auto">
                        <option value="all" @selected(request('status', 'all') === 'all')>All Status</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                    </select>

                    <select name="stock" aria-label="Filter by stock level" @change="$el.form.submit()"
                        class="frost-input field-control min-w-0 lg:w-auto">
                        <option value="all" @selected(request('stock', 'all') === 'all')>All Stock</option>
                        <option value="low" @selected(request('stock') === 'low')>Low Stock</option>
                        <option value="out" @selected(request('stock') === 'out')>Out of Stock</option>
                    </select>

                    <button type="submit" class="btn-primary col-span-2 sm:col-span-1">Search</button>
                </form>

                <!-- PRODUCT LIST (cards on mobile, table from md) -->
                <div class="frost-panel-solid overflow-hidden rounded-2xl">

                    @forelse ($products as $product)
                        @if ($loop->first)
                            <div class="overflow-x-auto">
                                <table class="products-table block w-full md:table">
                                    <thead class="hidden md:table-header-group">
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">Stock</th>
                                            <th scope="col">Status</th>
                                            <th scope="col"><span class="sr-only">Actions</span></th>
                                        </tr>
                                    </thead>
                                    {{-- Divider: on md+ every cell of every row except the last gets a bottom line.
                                         Applied on cells so it works with any border-collapse mode.
                                         Mobile rows are already separate bordered cards. --}}
                                    <tbody
                                        class="block space-y-3 p-3 md:table-row-group md:space-y-0 md:p-0 md:[&>tr:not(:last-child)>td]:border-b md:[&>tr:not(:last-child)>td]:border-charcoal/10">
                        @endif

                        @php
                            $thumb = $product->main_image_url;
                            $variant = $statusVariants[$product->status] ?? 'neutral';
                            $hasVariants = $product->hasVariants();
                            $lowVariants = $product->lowStockVariantCount($lowStock);
                        @endphp

                        <tr data-row-id="{{ $product->id }}"
                            class="block rounded-xl border border-white/50 bg-white/30 p-3 md:table-row md:rounded-none md:border-0 md:bg-transparent md:p-0">
                            <td class="block md:table-cell">
                                <div class="flex items-center gap-3">
                                    <div class="product-thumb shrink-0">
                                        @if ($thumb)
                                            <img src="{{ $thumb }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <p data-field="name"
                                        class="line-clamp-2 min-w-0 font-semibold text-charcoal md:max-w-xs">
                                        {{ $product->name }}</p>
                                </div>
                            </td>
                            <td data-label="Category" data-field="category"
                                class="{{ $labelCell }} text-charcoal/70">{{ $product->category }}</td>
                            <td data-label="Price" class="{{ $labelCell }} md:whitespace-nowrap">
                                <div class="min-w-0 text-right md:text-left">
                                    @if ($hasVariants)
                                        <span class="mr-1 text-xs text-charcoal/50">From</span>
                                    @endif
                                    <span data-field="price"
                                        class="font-semibold text-charcoal">₱{{ number_format($product->display_price, 2) }}</span>
                                    <span data-field="original-price"
                                        @class([
                                            'ml-1 text-xs text-charcoal/40 line-through',
                                            'hidden' => !$product->was_price,
                                        ])>₱{{ number_format($product->price, 2) }}</span>
                                    @if ($product->liveDiscount)
                                        <span
                                            class="ml-1 rounded bg-sale-red/10 px-1.5 py-0.5 text-[10px] font-bold text-sale-red">{{ $product->liveDiscount->label }}</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Stock" class="{{ $labelCell }}">
                                <div class="text-right md:text-left">
                                    <span data-field="stock"
                                        @class(['stock-cell', 'is-low' => $product->isLowStock($lowStock)])>{{ $product->stock_quantity }}</span>
                                    <span data-field="stock-note" @class(['stock-note', 'hidden' => $lowVariants === 0])>{{ $lowVariants }}
                                        {{ $lowVariants === 1 ? 'variant' : 'variants' }} low</span>
                                </div>
                            </td>
                            <td data-label="Status" class="{{ $labelCell }}">
                                <span data-badge="status"
                                    class="status-badge badge--{{ $variant }}">{{ $product->status }}</span>
                            </td>
                            <td class="block pt-1 md:table-cell md:pt-0 md:text-right">
                                <div class="flex items-center gap-2 md:justify-end">
                                    <a href="{{ route('seller.products.discount.edit', $product) }}"
                                        class="btn-soft flex-1 text-center !px-3.5 !py-2 !text-xs md:flex-none">Discount</a>
                                    <button type="button" @click="openProduct({{ $product->id }})"
                                        aria-label="Manage {{ $product->name }}"
                                        class="btn-primary flex-1 !px-3.5 !py-2 !text-xs !font-semibold md:flex-none">
                                        Manage
                                    </button>
                                </div>
                            </td>
                        </tr>

                        @if ($loop->last)
                            </tbody>
                            </table>
                </div>
                @endif
            @empty
                <div class="flex h-64 flex-col items-center justify-center px-4 text-center">
                    <p class="text-sm font-semibold text-charcoal">No products found</p>
                    <p class="mt-1 text-xs text-charcoal/50">Try adjusting your search or filters, or add
                        your first product.</p>
                </div>
                @endforelse

        </div>

        <div class="pagination-frost overflow-x-auto">{{ $products->withQueryString()->links() }}</div>

        </main>

    </div>

    </div>


    <!-- =====================================================
         MANAGE PRODUCT MODAL (bottom sheet on mobile, centered dialog from sm)
    ====================================================== -->
    <div x-show="modalOpen" x-cloak x-transition.opacity @click.self="closeModal()"
        @keydown.escape.window="modalOpen && closeModal()"
        class="fixed inset-0 z-[70] flex items-end justify-center bg-charcoal/50 backdrop-blur-sm sm:items-center sm:p-4">

        <div x-show="modalOpen" role="dialog" aria-modal="true" aria-labelledby="manage-product-title"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            class="frost-panel-solid max-h-[92dvh] w-full max-w-3xl overflow-y-auto overscroll-contain rounded-t-2xl sm:max-h-[90vh] sm:rounded-2xl">

            <div
                class="sticky top-0 z-10 flex items-center justify-between border-b border-white/50 bg-white/90 px-4 py-3 backdrop-blur sm:px-6 sm:py-4">
                <h2 id="manage-product-title" class="text-lg font-bold text-charcoal">Manage Product</h2>
                <button type="button" @click="closeModal()" aria-label="Close"
                    class="frost-btn rounded-lg p-2 text-charcoal/50 transition hover:text-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div x-show="loading" class="flex h-40 items-center justify-center text-sm text-charcoal/40">
                Loading...
            </div>

            <div x-show="!loading" x-cloak class="space-y-5 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:p-6">

                <div class="frost-panel rounded-xl p-3 sm:p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-section-label">Product Images</p>
                            <p class="text-xs text-charcoal/50">
                                <span x-text="images.length"></span> of 5 &middot; the first image is the main
                                thumbnail.
                            </p>
                        </div>

                        <label class="btn-soft cursor-pointer"
                            :class="{ 'pointer-events-none opacity-50': images.length >= 5 || imageBusy }">
                            <span x-text="imageBusy ? 'Uploading...' : 'Add Image'"></span>
                            <input type="file" accept=".jpg,.jpeg,.png,.webp" class="sr-only"
                                :disabled="images.length >= 5 || imageBusy" @change="uploadImage($event)">
                        </label>
                    </div>

                    <p x-show="errors.image" x-text="errors.image?.[0]" class="field-error"></p>

                    <div x-show="images.length" class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                        <template x-for="(img, index) in images" :key="img.id">
                            <div class="relative overflow-hidden rounded-lg border border-white/60 bg-ice-blue/50">
                                <img :src="img.url" alt="" class="aspect-square w-full object-cover">

                                <span x-show="index === 0"
                                    class="absolute left-1 top-1 rounded bg-primary px-1.5 py-0.5 text-[9px] font-bold text-white">Main</span>

                                <div class="absolute inset-x-0 bottom-0 flex gap-1 bg-charcoal/70 p-1">
                                    <label
                                        class="flex-1 cursor-pointer rounded bg-white/90 px-1 py-1 text-center text-[10px] font-semibold text-charcoal transition hover:bg-white">
                                        Replace
                                        <input type="file" accept=".jpg,.jpeg,.png,.webp" class="sr-only"
                                            :disabled="imageBusy" @change="replaceImage(img, $event)">
                                    </label>
                                    <button type="button"
                                        class="flex-1 rounded bg-sale-red/90 px-1 py-1 text-[10px] font-semibold text-white transition hover:bg-sale-red"
                                        :disabled="imageBusy" @click="removeImage(img)">Remove</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <p x-show="!images.length"
                        class="rounded-lg border border-dashed border-white/70 bg-white/40 px-3 py-4 text-center text-xs text-charcoal/45">
                        No general product images yet. Variant photos still count for publishing.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <div class="sm:col-span-2">
                        <label for="f-name" class="field-label">Product Name</label>
                        <input id="f-name" type="text" x-model="form.name" class="frost-input field-control"
                            :aria-invalid="errors.name ? 'true' : 'false'">
                        <p x-show="errors.name" x-text="errors.name?.[0]" class="field-error"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="f-description" class="field-label">Description</label>
                        <textarea id="f-description" x-model="form.description" rows="3" class="frost-input field-control resize-none"
                            :aria-invalid="errors.description ? 'true' : 'false'"></textarea>
                        <p x-show="errors.description" x-text="errors.description?.[0]" class="field-error"></p>
                    </div>

                    <div>
                        <label for="f-category" class="field-label">Category</label>
                        <select id="f-category" x-model="form.category" @change="onCategoryChange()"
                            class="frost-input field-control" :aria-invalid="errors.category ? 'true' : 'false'">
                            <template x-for="cat in categories" :key="cat.name">
                                <option :value="cat.name" x-text="cat.name"></option>
                            </template>
                        </select>
                        <p x-show="errors.category" x-text="errors.category?.[0]" class="field-error"></p>
                    </div>

                    <div>
                        <label for="f-subcategory" class="field-label">Subcategory</label>
                        <select id="f-subcategory" x-model="form.subcategory" class="frost-input field-control"
                            :aria-invalid="errors.subcategory ? 'true' : 'false'">
                            <option value="">None</option>
                            <template x-for="sub in subcategories" :key="sub">
                                <option :value="sub" x-text="sub"></option>
                            </template>
                        </select>
                        <p x-show="errors.subcategory" x-text="errors.subcategory?.[0]" class="field-error"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="f-weight" class="field-label">Product Weight (kg)</label>
                        <input id="f-weight" type="number" inputmode="decimal" x-model="form.weight_kg"
                            min="0" step="0.001" placeholder="e.g. 0.500" class="frost-input field-control"
                            :aria-invalid="errors.weight_kg ? 'true' : 'false'">
                        <p class="mt-1 text-xs text-charcoal/45">
                            Default weight per unit. Each variant can override it below.
                        </p>
                        <p x-show="errors.weight_kg" x-text="errors.weight_kg?.[0]" class="field-error"></p>
                    </div>

                    <!-- Simple products: price / stock live on the product -->
                    <div x-show="!hasVariants">
                        <label for="f-price" class="field-label">Price (₱)</label>
                        <input id="f-price" type="number" inputmode="decimal" x-model.number="form.price"
                            min="0" step="0.01" class="frost-input field-control"
                            :aria-invalid="errors.price ? 'true' : 'false'">
                        <p x-show="errors.price" x-text="errors.price?.[0]" class="field-error"></p>
                    </div>

                    <div x-show="!hasVariants">
                        <label for="f-stock" class="field-label">Stock Quantity</label>
                        <input id="f-stock" type="number" inputmode="numeric"
                            x-model.number="form.stock_quantity" min="0" class="frost-input field-control"
                            :aria-invalid="errors.stock_quantity ? 'true' : 'false'">
                        <p x-show="errors.stock_quantity" x-text="errors.stock_quantity?.[0]" class="field-error">
                        </p>
                    </div>

                </div>

                <!-- Variant products: price and stock are managed per variant -->
                <template x-if="hasVariants">
                    <div class="frost-panel rounded-xl p-3 sm:p-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-section-label">Variants</p>
                            <p class="text-xs text-charcoal/55">
                                Total stock
                                <span class="font-semibold text-charcoal" x-text="totalStock"></span>
                                &middot; Starting from
                                <span class="font-semibold text-charcoal" x-text="money(startingPrice)"></span>
                            </p>
                        </div>

                        <p x-show="errors.variants" x-text="errors.variants?.[0]" class="field-error mb-2"></p>

                        <div class="overflow-x-auto">
                            <table class="variant-table block w-full md:table">
                                <thead class="hidden md:table-header-group">
                                    <tr>
                                        <th scope="col">Combination</th>
                                        <th scope="col">Price (₱)</th>
                                        <th scope="col">Stock</th>
                                        <th scope="col">SKU</th>
                                        <th scope="col">Weight (kg)</th>
                                        <th scope="col">Image</th>
                                        <th scope="col">Active</th>
                                    </tr>
                                </thead>
                                <tbody class="block space-y-3 md:table-row-group md:space-y-0">
                                    <template x-for="(v, i) in variants" :key="v.id">
                                        <tr :class="{ 'is-inactive': !v.is_active }"
                                            class="block rounded-lg border border-white/50 bg-white/30 p-3 md:table-row md:rounded-none md:border-0 md:bg-transparent md:p-0">
                                            <td class="block pb-2 font-medium text-charcoal md:table-cell md:pb-0"
                                                x-text="variantLabel(v)"></td>
                                            <td data-label="Price" class="{{ $variantCell }}">
                                                <div class="min-w-0">
                                                    <input type="number" inputmode="decimal" min="0"
                                                        step="0.01" x-model.number="v.price"
                                                        class="frost-input field-control w-full md:w-28"
                                                        :aria-label="'Price for ' + variantLabel(v)"
                                                        :aria-invalid="variantError(i, 'price') ? 'true' : 'false'">
                                                    <p x-show="variantError(i, 'price')"
                                                        x-text="variantError(i, 'price')" class="field-error"></p>
                                                </div>
                                            </td>
                                            <td data-label="Stock" class="{{ $variantCell }}">
                                                <div class="min-w-0">
                                                    <input type="number" inputmode="numeric" min="0"
                                                        x-model.number="v.stock_quantity"
                                                        class="frost-input field-control w-full md:w-24"
                                                        :class="v.is_active && isLow(v.stock_quantity) ?
                                                            '!text-danger font-semibold' : ''"
                                                        :aria-label="'Stock for ' + variantLabel(v)"
                                                        :aria-invalid="variantError(i, 'stock_quantity') ? 'true' : 'false'">
                                                    <p x-show="variantError(i, 'stock_quantity')"
                                                        x-text="variantError(i, 'stock_quantity')"
                                                        class="field-error"></p>
                                                </div>
                                            </td>
                                            <td data-label="SKU" class="{{ $variantCell }}">
                                                <div class="min-w-0">
                                                    <input type="text" maxlength="100" x-model="v.sku"
                                                        placeholder="Optional"
                                                        class="frost-input field-control w-full md:w-28"
                                                        :aria-label="'SKU for ' + variantLabel(v)"
                                                        :aria-invalid="variantError(i, 'sku') ? 'true' : 'false'">
                                                    <p x-show="variantError(i, 'sku')" x-text="variantError(i, 'sku')"
                                                        class="field-error"></p>
                                                </div>
                                            </td>
                                            <td data-label="Weight" class="{{ $variantCell }}">
                                                <div class="min-w-0">
                                                    <input type="number" inputmode="decimal" min="0"
                                                        step="0.001" x-model="v.weight_kg" placeholder="Default"
                                                        class="frost-input field-control w-full md:w-24"
                                                        :aria-label="'Weight in kg for ' + variantLabel(v)"
                                                        :aria-invalid="variantError(i, 'weight_kg') ? 'true' : 'false'">
                                                    <p x-show="variantError(i, 'weight_kg')"
                                                        x-text="variantError(i, 'weight_kg')" class="field-error"></p>
                                                </div>
                                            </td>
                                            <td data-label="Image" class="{{ $variantCell }}">
                                                <div class="min-w-0 space-y-1.5">
                                                    <img x-show="v.image_url" :src="v.image_url" alt=""
                                                        class="h-10 w-10 rounded-lg border border-white/60 object-cover">

                                                    <div class="flex flex-wrap items-center gap-1">
                                                        <label
                                                            class="cursor-pointer rounded-md bg-white/80 px-2 py-1 text-[10px] font-semibold text-charcoal transition hover:bg-white">
                                                            <span x-text="v.image_url ? 'Replace' : 'Upload'"></span>
                                                            <input type="file" accept=".jpg,.jpeg,.png,.webp"
                                                                class="sr-only" :disabled="imageBusy"
                                                                @change="uploadVariantImage(v, $event)">
                                                        </label>

                                                        <button type="button" x-show="v.image_url"
                                                            class="rounded-md bg-sale-red/10 px-2 py-1 text-[10px] font-semibold text-sale-red transition hover:bg-sale-red/20"
                                                            :disabled="imageBusy"
                                                            @click="removeVariantImage(v)">Remove</button>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Active" class="{{ $variantCell }} md:text-center">
                                                <input type="checkbox" x-model="v.is_active"
                                                    class="mt-2 h-5 w-5 accent-primary md:h-4 md:w-4"
                                                    :aria-label="'Show ' + variantLabel(v) + ' to buyers'">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <p class="mt-2 text-xs text-charcoal/45">Inactive variants are hidden from buyers. They are
                            never deleted, so past orders keep their history. Price and stock for the whole product are
                            calculated from the active variants.</p>
                    </div>
                </template>

                <button type="button" @click="save()" :disabled="saving" class="btn-primary w-full !py-3">
                    <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                </button>

                <div class="frost-panel rounded-xl p-3 sm:p-4">
                    <p class="text-section-label mb-3">Listing Status</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="setStatus('active')"
                            :disabled="saving || status === 'active'" class="btn-soft btn-soft--success flex-1">
                            Publish
                        </button>
                        <button type="button" @click="setStatus('draft')"
                            :disabled="saving || status === 'draft'" class="btn-soft btn-soft--warning flex-1">
                            Save as Draft
                        </button>
                        <button type="button" @click="setStatus('archived')"
                            :disabled="saving || status === 'archived'" class="btn-soft btn-soft--danger flex-1">
                            Archive
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-charcoal/45">
                        Drafts and archived products are hidden from buyers but never deleted. Drafts can be edited and
                        published later.
                    </p>
                </div>

            </div>

        </div>

    </div>


    <!-- TOAST -->
    <div x-show="toast.show" x-cloak x-transition role="status" aria-live="polite"
        :class="toast.error ? 'bg-danger/90' : 'bg-success/90'" x-text="toast.message"
        class="fixed inset-x-4 bottom-[calc(1rem+env(safe-area-inset-bottom))] z-[80] rounded-2xl px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-md sm:inset-x-auto sm:bottom-6 sm:right-6 sm:max-w-sm">
    </div>

</body>

</html>
