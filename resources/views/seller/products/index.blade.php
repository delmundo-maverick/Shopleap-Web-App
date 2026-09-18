<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Products | Shopleap Seller</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen text-charcoal" x-data="productsPage()">

    <div class="frost-surface"></div>

    <div class="flex min-h-screen">

        <x-navbars.seller active="inventory" />

        <div class="flex flex-1 flex-col">

            <!-- TOPBAR -->
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" @click="$store.sidebar.open = true"
                        class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">My Products</h2>
                        <p class="text-sm text-charcoal/55">{{ $products->total() }} product(s) in your store</p>
                    </div>
                </div>

                <a href="{{ route('seller.products.create') }}"
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Product
                </a>
            </header>

            <main class="flex-1 space-y-5 p-6">

                @if (session('status'))
                    <div
                        class="rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green backdrop-blur-sm">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- SEARCH & FILTERS -->
                <form method="GET"
                    class="frost-panel flex flex-col gap-3 rounded-2xl p-4 sm:flex-row sm:items-center">

                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search your products..."
                            class="frost-input w-full rounded-xl py-2.5 pl-10 pr-4 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                    </div>

                    <select name="category" onchange="this.form.submit()"
                        class="frost-input rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                        <option value="all" {{ request('category', 'all') === 'all' ? 'selected' : '' }}>All
                            Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat['name'] }}"
                                {{ request('category') === $cat['name'] ? 'selected' : '' }}>
                                {{ $cat['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" onchange="this.form.submit()"
                        class="frost-input rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Status
                        </option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived
                        </option>
                    </select>

                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
                        Search
                    </button>

                </form>

                <!-- TABLE -->
                <div class="frost-panel-solid overflow-hidden rounded-2xl">

                    @if ($products->isEmpty())

                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <p class="text-sm font-semibold text-charcoal">No products found</p>
                            <p class="mt-1 text-xs text-charcoal/50">Try adjusting your search or filters, or add your
                                first product.</p>
                        </div>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-white/50 bg-primary/5">
                                <tr>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Product</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Category</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Price</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Stock</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/50">
                                @foreach ($products as $product)
                                    <tr data-row-id="{{ $product->id }}" class="transition hover:bg-white/40">
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-ice-blue">
                                                    @if ($product->images->first())
                                                        <img src="{{ $product->images->first()->url }}"
                                                            alt="{{ $product->name }}"
                                                            class="h-full w-full object-cover">
                                                    @endif
                                                </div>
                                                <p class="line-clamp-2 max-w-xs font-semibold text-charcoal">
                                                    {{ $product->name }}</p>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-charcoal/70">{{ $product->category }}</td>
                                        <td class="px-5 py-3">
                                            <span data-field="price"
                                                class="font-semibold text-charcoal">₱{{ number_format($product->discount_price ?? $product->price, 2) }}</span>
                                            @if ($product->discount_price)
                                                <span
                                                    class="ml-1 text-xs text-charcoal/40 line-through">₱{{ number_format($product->price, 2) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <span data-field="stock"
                                                class="{{ $product->stock_quantity <= 5 ? 'text-sale-red font-semibold' : 'text-charcoal/70' }}">
                                                {{ $product->stock_quantity }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span data-badge="status"
                                                class="status-badge status-badge--{{ $product->status }} rounded-full px-2.5 py-1 text-xs font-semibold capitalize">
                                                {{ $product->status }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <button type="button" @click="openProduct({{ $product->id }})"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-blue">
                                                Manage
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    @endif

                </div>

                <div>{{ $products->links() }}</div>

            </main>

        </div>

    </div>


    <!-- =====================================================
         EDIT MODAL
    ====================================================== -->

    <div x-show="modalOpen" x-cloak x-transition.opacity @click.self="closeModal()"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-charcoal/50 p-4 backdrop-blur-sm">

        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="frost-panel-solid max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl">

            <div class="flex items-center justify-between border-b border-white/50 px-6 py-4">
                <h3 class="text-lg font-bold text-charcoal">Manage Product</h3>
                <button type="button" @click="closeModal()"
                    class="frost-btn rounded-lg p-1.5 text-charcoal/50 transition hover:text-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <img :src="img.url"
                            class="h-20 w-20 shrink-0 rounded-lg border border-white/50 object-cover">
                    </template>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Product Name</label>
                        <input type="text" x-model="form.name"
                            class="frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Description</label>
                        <textarea x-model="form.description" rows="3"
                            class="frost-input w-full resize-none rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10"></textarea>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Category</label>
                        <select x-model="form.category" @change="onCategoryChange()"
                            class="frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                            <template x-for="cat in categories" :key="cat.name">
                                <option :value="cat.name" x-text="cat.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Subcategory</label>
                        <select x-model="form.subcategory"
                            class="frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                            <option value="">None</option>
                            <template x-for="sub in subcategories" :key="sub">
                                <option :value="sub" x-text="sub"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Price (₱)</label>
                        <input type="number" x-model.number="form.price" min="0" step="0.01"
                            class="frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Discount Price (₱)</label>
                        <input type="number" x-model.number="form.discount_price" min="0" step="0.01"
                            class="frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Stock Quantity</label>
                        <input type="number" x-model.number="form.stock_quantity" min="0"
                            class="frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                    </div>
                </div>

                <button type="button" @click="save()"
                    class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-blue">
                    Save Changes
                </button>

                <div class="frost-panel rounded-xl p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Listing Status</p>
                    <div class="flex gap-2">
                        <button type="button" @click="setStatus('active')"
                            class="flex-1 rounded-lg border border-leaf-green/30 bg-leaf-green/10 px-3.5 py-2.5 text-xs font-semibold text-leaf-green backdrop-blur-sm transition hover:bg-leaf-green/20">
                            Activate
                        </button>
                        <button type="button" @click="setStatus('archived')"
                            class="flex-1 rounded-lg border border-sale-red/30 bg-sale-red/10 px-3.5 py-2.5 text-xs font-semibold text-sale-red backdrop-blur-sm transition hover:bg-sale-red/20">
                            Archive
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-charcoal/45">Archived products are hidden from buyers but not deleted.
                    </p>
                </div>

            </div>

        </div>

    </div>


    <!-- TOAST -->
    <div x-show="toast.show" x-cloak x-transition
        class="fixed bottom-6 right-6 z-[80] max-w-sm rounded-2xl px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-md"
        :style="`background: ${toast.error ? 'rgba(228,87,46,0.92)' : 'rgba(76,175,125,0.92)'}`"
        x-text="toast.message">
    </div>


    <style>
        .status-badge--active {
            background: color-mix(in srgb, var(--color-leaf-green) 16%, white);
            color: var(--color-leaf-green);
        }

        .status-badge--archived {
            background: color-mix(in srgb, var(--color-charcoal) 10%, white);
            color: color-mix(in srgb, var(--color-charcoal) 65%, white);
        }
    </style>


    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productsPage', () => ({
                modalOpen: false,
                loading: true,
                productId: null,
                categories: @json($categories),
                subcategories: [],
                images: [],
                form: {
                    name: '',
                    description: '',
                    category: '',
                    subcategory: '',
                    price: '',
                    discount_price: '',
                    stock_quantity: '',
                },
                toast: {
                    show: false,
                    message: '',
                    error: false,
                },

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]').content;
                },

                showToast(message, isError = false) {
                    this.toast = {
                        show: true,
                        message,
                        error: isError
                    };
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3500);
                },

                populateSubcategories(categoryName, selected = null) {
                    const match = this.categories.find(c => c.name === categoryName);
                    this.subcategories = match ? match.subcategories : [];
                    this.form.subcategory = (selected && this.subcategories.includes(selected)) ?
                        selected : '';
                },

                onCategoryChange() {
                    this.populateSubcategories(this.form.category);
                },

                async openProduct(id) {
                    this.productId = id;
                    this.modalOpen = true;
                    this.loading = true;

                    try {
                        const res = await fetch(`/seller/products/${id}/details`);
                        const data = await res.json();

                        this.form.name = data.name;
                        this.form.description = data.description || '';
                        this.form.category = data.category;
                        this.populateSubcategories(data.category, data.subcategory);
                        this.form.price = data.price;
                        this.form.discount_price = data.discount_price ?? '';
                        this.form.stock_quantity = data.stock_quantity;
                        this.images = data.images;

                        this.loading = false;
                    } catch (err) {
                        this.showToast('Failed to load product details.', true);
                        this.closeModal();
                    }
                },

                closeModal() {
                    this.modalOpen = false;
                    this.loading = true;
                    this.images = [];
                },

                async save() {
                    try {
                        const res = await fetch(`/seller/products/${this.productId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                name: this.form.name,
                                description: this.form.description,
                                category: this.form.category,
                                subcategory: this.form.subcategory || null,
                                price: this.form.price,
                                discount_price: this.form.discount_price || null,
                                stock_quantity: this.form.stock_quantity,
                            }),
                        });
                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            this.showToast(data.message || 'Something went wrong.', true);
                            return;
                        }

                        this.showToast(data.message);
                        this.updateRow(this.productId, data.product);
                    } catch (err) {
                        this.showToast('Network error — please try again.', true);
                    }
                },

                async setStatus(status) {
                    try {
                        const res = await fetch(`/seller/products/${this.productId}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                status
                            }),
                        });
                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            this.showToast(data.message || 'Something went wrong.', true);
                            return;
                        }

                        this.showToast(data.message);
                        this.updateRowStatus(this.productId, data.status);
                    } catch (err) {
                        this.showToast('Network error — please try again.', true);
                    }
                },

                updateRow(id, product) {
                    const row = document.querySelector(`tr[data-row-id="${id}"]`);
                    if (!row) return;

                    const priceEl = row.querySelector('[data-field="price"]');
                    const displayPrice = product.discount_price ?? product.price;
                    priceEl.textContent =
                        `₱${Number(displayPrice).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

                    row.querySelector('[data-field="stock"]').textContent = product.stock_quantity;
                },

                updateRowStatus(id, status) {
                    const row = document.querySelector(`tr[data-row-id="${id}"]`);
                    if (!row) return;
                    const badge = row.querySelector('[data-badge="status"]');
                    badge.textContent = status;
                    badge.className =
                        `status-badge status-badge--${status} rounded-full px-2.5 py-1 text-xs font-semibold capitalize`;
                },
            }));
        });
    </script>

</body>

</html>
