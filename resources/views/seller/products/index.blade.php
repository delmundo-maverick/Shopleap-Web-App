@php
    $statusVariants = ['active' => 'success', 'archived' => 'neutral'];
    $lowStock = (int) config('shop.low_stock_threshold', 5);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>My Products | Shopleap Seller</title>

    @vite(['resources/js/pages/seller/products.js', 'resources/js/app.js', 'resources/css/app.css', 'resources/css/pages/seller/products.css'])
</head>

<body class="min-h-screen text-charcoal" x-data="productsPage(@js([
    'categories' => $categories,
    'statusVariants' => $statusVariants,
    'lowStock' => $lowStock,
    'routes' => [
        'details' => route('seller.products.details', '__ID__'),
        'update' => route('seller.products.update', '__ID__'),
        'status' => route('seller.products.status', '__ID__'),
    ],
]))">

    <div class="frost-surface"></div>

    <div class="flex min-h-screen">

        <x-navbars.seller active="inventory" />

        <div class="flex min-w-0 flex-1 flex-col">

            <!-- TOPBAR -->
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" @click="$store.sidebar.open = true" aria-label="Open sidebar"
                        class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-page-title">My Products</h1>
                        <p class="text-sm text-charcoal/55">{{ $products->total() }} product(s) in your store</p>
                    </div>
                </div>

                <a href="{{ route('seller.products.create') }}" class="btn-primary shadow-lg shadow-primary/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Product
                </a>
            </header>

            <main class="flex-1 space-y-5 p-6">

                @if (session('status'))
                    <div role="status"
                        class="rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm text-success backdrop-blur-sm">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- SEARCH & FILTERS -->
                <form method="GET" role="search"
                    class="frost-panel flex flex-col gap-3 rounded-2xl p-4 sm:flex-row sm:items-center">

                    <div class="relative flex-1">
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
                        class="frost-input field-control sm:w-auto">
                        <option value="all" @selected(request('category', 'all') === 'all')>All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat['name'] }}" @selected(request('category') === $cat['name'])>
                                {{ $cat['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" aria-label="Filter by status" @change="$el.form.submit()"
                        class="frost-input field-control sm:w-auto">
                        <option value="all" @selected(request('status', 'all') === 'all')>All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                    </select>

                    <select name="stock" aria-label="Filter by stock level" @change="$el.form.submit()"
                        class="frost-input field-control sm:w-auto">
                        <option value="all" @selected(request('stock', 'all') === 'all')>All Stock</option>
                        <option value="low" @selected(request('stock') === 'low')>Low Stock</option>
                        <option value="out" @selected(request('stock') === 'out')>Out of Stock</option>
                    </select>

                    <button type="submit" class="btn-primary">Search</button>
                </form>

                <!-- TABLE -->
                <div class="frost-panel-solid overflow-hidden rounded-2xl">

                    @forelse ($products as $product)
                        @if ($loop->first)
                            <div class="overflow-x-auto">
                                <table class="products-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">Stock</th>
                                            <th scope="col">Status</th>
                                            <th scope="col"><span class="sr-only">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        @endif

                        @php
                            $thumb = $product->images->first();
                            $variant = $statusVariants[$product->status] ?? 'neutral';
                            $hasVariants = $product->hasVariants();
                            $lowVariants = $product->lowStockVariantCount($lowStock);
                        @endphp

                        <tr data-row-id="{{ $product->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="product-thumb">
                                        @if ($thumb)
                                            <img src="{{ $thumb->url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <p data-field="name" class="line-clamp-2 max-w-xs font-semibold text-charcoal">
                                        {{ $product->name }}</p>
                                </div>
                            </td>
                            <td data-field="category" class="text-charcoal/70">{{ $product->category }}</td>
                            <td class="whitespace-nowrap">
                                @if ($hasVariants)
                                    <span class="mr-1 text-xs text-charcoal/50">From</span>
                                @endif
                                <span data-field="price"
                                    class="font-semibold text-charcoal">₱{{ number_format($product->discount_price ?? $product->price, 2) }}</span>
                                <span data-field="original-price"
                                    @class([
                                        'ml-1 text-xs text-charcoal/40 line-through',
                                        'hidden' => !$product->discount_price,
                                    ])>₱{{ number_format($product->price, 2) }}</span>
                            </td>
                            <td>
                                <span data-field="stock"
                                    @class(['stock-cell', 'is-low' => $product->isLowStock($lowStock)])>{{ $product->stock_quantity }}</span>
                                <span data-field="stock-note" @class(['stock-note', 'hidden' => $lowVariants === 0])>{{ $lowVariants }}
                                    {{ $lowVariants === 1 ? 'variant' : 'variants' }} low</span>
                            </td>
                            <td>
                                <span data-badge="status"
                                    class="status-badge badge--{{ $variant }}">{{ $product->status }}</span>
                            </td>
                            <td class="text-right">
                                <button type="button" @click="openProduct({{ $product->id }})"
                                    aria-label="Manage {{ $product->name }}"
                                    class="btn-primary !px-3.5 !py-2 !text-xs !font-semibold">
                                    Manage
                                </button>
                            </td>
                        </tr>

                        @if ($loop->last)
                            </tbody>
                            </table>
                </div>
                @endif
            @empty
                <div class="flex h-64 flex-col items-center justify-center text-center">
                    <p class="text-sm font-semibold text-charcoal">No products found</p>
                    <p class="mt-1 text-xs text-charcoal/50">Try adjusting your search or filters, or add
                        your first product.</p>
                </div>
                @endforelse

        </div>

        <div class="pagination-frost">{{ $products->withQueryString()->links() }}</div>

        </main>

    </div>

    </div>


    <!-- =====================================================
         MANAGE PRODUCT MODAL
    ====================================================== -->
    <div x-show="modalOpen" x-cloak x-transition.opacity @click.self="closeModal()"
        @keydown.escape.window="modalOpen && closeModal()"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-charcoal/50 p-4 backdrop-blur-sm">

        <div x-show="modalOpen" role="dialog" aria-modal="true" aria-labelledby="manage-product-title"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="frost-panel-solid max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl">

            <div class="flex items-center justify-between border-b border-white/50 px-6 py-4">
                <h2 id="manage-product-title" class="text-lg font-bold text-charcoal">Manage Product</h2>
                <button type="button" @click="closeModal()" aria-label="Close"
                    class="frost-btn rounded-lg p-1.5 text-charcoal/50 transition hover:text-primary">
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

            <div x-show="!loading" x-cloak class="space-y-5 p-6">

                <div x-show="images.length" class="flex gap-2 overflow-x-auto">
                    <template x-for="img in images" :key="img.url">
                        <img :src="img.url" alt=""
                            class="h-20 w-20 shrink-0 rounded-lg border border-white/50 object-cover">
                    </template>
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

                    <!-- Simple products: price / discount / stock live on the product -->
                    <div x-show="!hasVariants">
                        <label for="f-price" class="field-label">Price (₱)</label>
                        <input id="f-price" type="number" inputmode="decimal" x-model.number="form.price"
                            min="0" step="0.01" class="frost-input field-control"
                            :aria-invalid="errors.price ? 'true' : 'false'">
                        <p x-show="errors.price" x-text="errors.price?.[0]" class="field-error"></p>
                    </div>

                    <div x-show="!hasVariants">
                        <label for="f-discount" class="field-label">Discount Price (₱)</label>
                        <input id="f-discount" type="number" inputmode="decimal"
                            x-model.number="form.discount_price" min="0" step="0.01"
                            class="frost-input field-control"
                            :aria-invalid="errors.discount_price ? 'true' : 'false'">
                        <p x-show="errors.discount_price" x-text="errors.discount_price?.[0]" class="field-error">
                        </p>
                    </div>

                    <div x-show="!hasVariants" class="sm:col-span-2">
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
                    <div class="frost-panel rounded-xl p-4">
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
                            <table class="variant-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Combination</th>
                                        <th scope="col">Price (₱)</th>
                                        <th scope="col">Stock</th>
                                        <th scope="col">SKU</th>
                                        <th scope="col">Active</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(v, i) in variants" :key="v.id">
                                        <tr :class="{ 'is-inactive': !v.is_active }">
                                            <td class="font-medium text-charcoal" x-text="variantLabel(v)"></td>
                                            <td>
                                                <input type="number" inputmode="decimal" min="0"
                                                    step="0.01" x-model.number="v.price"
                                                    class="frost-input field-control w-28"
                                                    :aria-label="'Price for ' + variantLabel(v)"
                                                    :aria-invalid="variantError(i, 'price') ? 'true' : 'false'">
                                                <p x-show="variantError(i, 'price')" x-text="variantError(i, 'price')"
                                                    class="field-error"></p>
                                            </td>
                                            <td>
                                                <input type="number" inputmode="numeric" min="0"
                                                    x-model.number="v.stock_quantity"
                                                    class="frost-input field-control w-24"
                                                    :class="v.is_active && isLow(v.stock_quantity) ?
                                                        '!text-danger font-semibold' : ''"
                                                    :aria-label="'Stock for ' + variantLabel(v)"
                                                    :aria-invalid="variantError(i, 'stock_quantity') ? 'true' : 'false'">
                                                <p x-show="variantError(i, 'stock_quantity')"
                                                    x-text="variantError(i, 'stock_quantity')" class="field-error">
                                                </p>
                                            </td>
                                            <td>
                                                <input type="text" maxlength="100" x-model="v.sku"
                                                    placeholder="Optional" class="frost-input field-control w-28"
                                                    :aria-label="'SKU for ' + variantLabel(v)"
                                                    :aria-invalid="variantError(i, 'sku') ? 'true' : 'false'">
                                                <p x-show="variantError(i, 'sku')" x-text="variantError(i, 'sku')"
                                                    class="field-error"></p>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" x-model="v.is_active"
                                                    class="mt-2 h-4 w-4 accent-primary"
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

                <div class="frost-panel rounded-xl p-4">
                    <p class="text-section-label mb-3">Listing Status</p>
                    <div class="flex gap-2">
                        <button type="button" @click="setStatus('active')" :disabled="saving"
                            class="btn-soft btn-soft--success flex-1">Activate</button>
                        <button type="button" @click="setStatus('archived')" :disabled="saving"
                            class="btn-soft btn-soft--danger flex-1">Archive</button>
                    </div>
                    <p class="mt-2 text-xs text-charcoal/45">Archived products are hidden from buyers but not
                        deleted.</p>
                </div>

            </div>

        </div>

    </div>


    <!-- TOAST -->
    <div x-show="toast.show" x-cloak x-transition role="status" aria-live="polite"
        :class="toast.error ? 'bg-danger/90' : 'bg-success/90'" x-text="toast.message"
        class="fixed bottom-6 right-6 z-[80] max-w-sm rounded-2xl px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-md">
    </div>

</body>

</html>
