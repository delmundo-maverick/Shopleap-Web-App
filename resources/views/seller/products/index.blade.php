<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Products | Shopleap Seller</title>
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
                        <h2 class="text-xl font-bold text-charcoal">My Products</h2>
                        <p class="text-sm text-charcoal/55">{{ $products->total() }} product(s) in your store</p>
                    </div>
                </div>

                <a href="{{ route('seller.products.create') }}"
                    class="flex items-center gap-2 rounded-xl bg-seller px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-seller/20 transition hover:bg-seller-light">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Product
                </a>
            </header>

            <main class="flex-1 space-y-5 p-6">

                @if (session('status'))
                    <div
                        class="rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- SEARCH & FILTERS -->
                <form method="GET"
                    class="flex flex-col gap-3 rounded-2xl border border-light-gray bg-white p-4 sm:flex-row sm:items-center">

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
                            class="w-full rounded-xl border border-light-gray bg-seller-bg py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-seller focus:bg-white focus:ring-4 focus:ring-seller/10">
                    </div>

                    <select name="category" onchange="this.form.submit()"
                        class="rounded-xl border border-light-gray bg-white px-3.5 py-2.5 text-sm outline-none focus:border-seller">
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
                        class="rounded-xl border border-light-gray bg-white px-3.5 py-2.5 text-sm outline-none focus:border-seller">
                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Status
                        </option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived
                        </option>
                    </select>

                    <button type="submit"
                        class="rounded-xl bg-seller px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-seller-light">
                        Search
                    </button>

                </form>

                <!-- TABLE -->
                <div class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-sm shadow-charcoal/5">

                    @if ($products->isEmpty())

                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <p class="text-sm font-semibold text-charcoal">No products found</p>
                            <p class="mt-1 text-xs text-charcoal/50">Try adjusting your search or filters, or add your
                                first product.</p>
                        </div>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-light-gray bg-seller-bg">
                                <tr>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Product</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Category</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Price</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Stock</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-light-gray">
                                @foreach ($products as $product)
                                    <tr data-row-id="{{ $product->id }}" class="transition hover:bg-seller-bg/60">
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-seller-bg">
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
                                            <button type="button" data-edit-product="{{ $product->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-seller px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-seller-light">
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

    <div id="productModalOverlay" class="fixed inset-0 z-[70] hidden items-center justify-center bg-charcoal/50 p-4">

        <div id="productModal" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b border-light-gray px-6 py-4">
                <h3 class="text-lg font-bold text-charcoal">Manage Product</h3>
                <button type="button" id="modalCloseBtn"
                    class="rounded-lg p-1.5 text-charcoal/50 transition hover:bg-seller-soft hover:text-seller">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="modalLoading" class="flex h-40 items-center justify-center text-sm text-charcoal/40">
                Loading...
            </div>

            <div id="modalBody" class="hidden space-y-5 p-6">

                <div id="modalImages" class="flex gap-2 overflow-x-auto"></div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Product Name</label>
                        <input type="text" id="fieldName"
                            class="w-full rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-seller focus:ring-4 focus:ring-seller/10">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Description</label>
                        <textarea id="fieldDescription" rows="3"
                            class="w-full resize-none rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-seller focus:ring-4 focus:ring-seller/10"></textarea>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Category</label>
                        <select id="fieldCategory"
                            class="w-full rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-seller focus:ring-4 focus:ring-seller/10">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['name'] }}">{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Subcategory</label>
                        <select id="fieldSubcategory"
                            class="w-full rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-seller focus:ring-4 focus:ring-seller/10">
                            <option value="">None</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Price (₱)</label>
                        <input type="number" id="fieldPrice" min="0" step="0.01"
                            class="w-full rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-seller focus:ring-4 focus:ring-seller/10">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Discount Price (₱)</label>
                        <input type="number" id="fieldDiscountPrice" min="0" step="0.01"
                            class="w-full rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-seller focus:ring-4 focus:ring-seller/10">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-charcoal">Stock Quantity</label>
                        <input type="number" id="fieldStock" min="0"
                            class="w-full rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-seller focus:ring-4 focus:ring-seller/10">
                    </div>
                </div>

                <button type="button" id="saveChangesBtn"
                    class="w-full rounded-xl bg-seller px-4 py-3 text-sm font-bold text-white transition hover:bg-seller-light">
                    Save Changes
                </button>

                <div class="rounded-xl border border-light-gray bg-seller-bg p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-seller">Listing Status</p>
                    <div class="flex gap-2">
                        <button type="button" data-set-status="active"
                            class="status-action-btn flex-1 rounded-lg border border-leaf-green/30 bg-leaf-green/10 px-3.5 py-2.5 text-xs font-semibold text-leaf-green transition hover:bg-leaf-green/20">
                            Activate
                        </button>
                        <button type="button" data-set-status="archived"
                            class="status-action-btn flex-1 rounded-lg border border-sale-red/30 bg-sale-red/10 px-3.5 py-2.5 text-xs font-semibold text-sale-red transition hover:bg-sale-red/20">
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
    <div id="toast"
        class="fixed bottom-6 right-6 z-[80] hidden max-w-sm rounded-xl px-5 py-3.5 text-sm font-medium text-white shadow-xl transition">
    </div>


    <style>
        .status-badge--active {
            background: rgb(220 252 231);
            color: rgb(22 163 74);
        }

        .status-badge--archived {
            background: rgb(229 231 235);
            color: rgb(75 85 99);
        }
    </style>


    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const CATEGORIES = @json($categories);

        const overlay = document.getElementById('productModalOverlay');
        const modalLoading = document.getElementById('modalLoading');
        const modalBody = document.getElementById('modalBody');
        let currentProductId = null;

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#DC2626' : '#0D9488';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3500);
        }

        function openModal() {
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        }

        function closeModal() {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            modalBody.classList.add('hidden');
            modalLoading.classList.remove('hidden');
        }

        document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });

        document.querySelectorAll('[data-edit-product]').forEach(btn => {
            btn.addEventListener('click', () => loadProduct(btn.dataset.editProduct));
        });

        function populateSubcategories(categoryName, selected = null) {
            const sub = document.getElementById('fieldSubcategory');
            const match = CATEGORIES.find(c => c.name === categoryName);
            sub.innerHTML = '<option value="">None</option>';
            if (!match) return;
            match.subcategories.forEach(name => {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                if (selected === name) opt.selected = true;
                sub.appendChild(opt);
            });
        }

        document.getElementById('fieldCategory').addEventListener('change', function() {
            populateSubcategories(this.value);
        });

        async function loadProduct(id) {
            currentProductId = id;
            openModal();
            modalBody.classList.add('hidden');
            modalLoading.classList.remove('hidden');

            try {
                const res = await fetch(`/seller/products/${id}/details`);
                const data = await res.json();

                document.getElementById('fieldName').value = data.name;
                document.getElementById('fieldDescription').value = data.description || '';
                document.getElementById('fieldCategory').value = data.category;
                populateSubcategories(data.category, data.subcategory);
                document.getElementById('fieldPrice').value = data.price;
                document.getElementById('fieldDiscountPrice').value = data.discount_price ?? '';
                document.getElementById('fieldStock').value = data.stock_quantity;

                const imagesEl = document.getElementById('modalImages');
                imagesEl.innerHTML = '';
                data.images.forEach(img => {
                    const el = document.createElement('img');
                    el.src = img.url;
                    el.className = 'h-20 w-20 shrink-0 rounded-lg object-cover border border-light-gray';
                    imagesEl.appendChild(el);
                });

                modalLoading.classList.add('hidden');
                modalBody.classList.remove('hidden');
            } catch (err) {
                showToast('Failed to load product details.', true);
                closeModal();
            }
        }

        document.getElementById('saveChangesBtn').addEventListener('click', async () => {
            const body = {
                name: document.getElementById('fieldName').value,
                description: document.getElementById('fieldDescription').value,
                category: document.getElementById('fieldCategory').value,
                subcategory: document.getElementById('fieldSubcategory').value || null,
                price: document.getElementById('fieldPrice').value,
                discount_price: document.getElementById('fieldDiscountPrice').value || null,
                stock_quantity: document.getElementById('fieldStock').value,
            };

            try {
                const res = await fetch(`/seller/products/${currentProductId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    showToast(data.message || 'Something went wrong.', true);
                    return;
                }

                showToast(data.message);
                updateRow(currentProductId, data.product);
            } catch (err) {
                showToast('Network error — please try again.', true);
            }
        });

        document.querySelectorAll('.status-action-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const status = btn.dataset.setStatus;
                try {
                    const res = await fetch(`/seller/products/${currentProductId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            status
                        }),
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        showToast(data.message || 'Something went wrong.', true);
                        return;
                    }

                    showToast(data.message);
                    updateRowStatus(currentProductId, data.status);
                } catch (err) {
                    showToast('Network error — please try again.', true);
                }
            });
        });

        function updateRow(id, product) {
            const row = document.querySelector(`tr[data-row-id="${id}"]`);
            if (!row) return;

            const priceEl = row.querySelector('[data-field="price"]');
            const displayPrice = product.discount_price ?? product.price;
            priceEl.textContent = `₱${Number(displayPrice).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

            row.querySelector('[data-field="stock"]').textContent = product.stock_quantity;
        }

        function updateRowStatus(id, status) {
            const row = document.querySelector(`tr[data-row-id="${id}"]`);
            if (!row) return;
            const badge = row.querySelector('[data-badge="status"]');
            badge.textContent = status;
            badge.className =
                `status-badge status-badge--${status} rounded-full px-2.5 py-1 text-xs font-semibold capitalize`;
        }
    </script>

</body>

</html>
