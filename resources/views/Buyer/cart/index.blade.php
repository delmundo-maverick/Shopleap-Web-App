<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Cart | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal pb-24">
    @php
        $headerCartCount = $cartCount ?? $cartItems->sum('quantity');
        $headerCartPreview = $cartPreviewItems ?? $cartItems->take(3);
    @endphp

    <x-buyer.header :cart-count="$headerCartCount" :cart-preview-items="$headerCartPreview" />

    <main class="mx-auto max-w-5xl px-5 py-6 sm:px-8">

        <h1 class="mb-5 text-2xl font-bold text-charcoal">My Cart</h1>

        @if ($cartItems->isEmpty())

            <div
                class="flex flex-col items-center justify-center rounded-lg bg-white p-16 text-center shadow-sm shadow-charcoal/5">
                <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="mt-4 text-sm font-semibold text-charcoal">Your cart is empty</p>
                <p class="mt-1 text-xs text-charcoal/50">Browse the shop and add something you like.</p>
                <a href="{{ route('buyer.home') }}"
                    class="mt-5 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                    Continue Shopping
                </a>
            </div>
        @else
            {{-- ===================================================
                 TABLE HEADER ROW (Shopee-style column labels)
            ==================================================== --}}
            <div
                class="mb-2 hidden items-center gap-4 rounded-lg bg-white px-5 py-3 text-xs font-semibold uppercase tracking-wide text-charcoal/40 shadow-sm shadow-charcoal/5 sm:flex">
                <input type="checkbox" id="selectAllTop"
                    class="h-4 w-4 shrink-0 rounded border-light-gray text-primary focus:ring-primary">
                <span class="flex-1">Product</span>
                <span class="w-24 shrink-0 text-right">Unit Price</span>
                <span class="w-28 shrink-0 text-center">Quantity</span>
                <span class="w-24 shrink-0 text-right">Total Price</span>
                <span class="w-20 shrink-0 text-right">Actions</span>
            </div>

            {{-- ===================================================
                 ITEMS
            ==================================================== --}}
            <div class="space-y-2" id="cartItemsList">
                @foreach ($cartItems as $item)
                    <div data-cart-item-id="{{ $item->id }}" data-line-total="{{ $item->line_total }}"
                        class="flex items-center gap-4 rounded-lg bg-white p-4 shadow-sm shadow-charcoal/5">

                        <input type="checkbox"
                            class="item-checkbox h-4 w-4 shrink-0 rounded border-light-gray text-primary focus:ring-primary">

                        <a href="{{ route('buyer.products.show', $item->product) }}"
                            class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                            @if ($item->product->main_image_url)
                                <img src="{{ $item->product->main_image_url }}" alt="{{ $item->product->name }}"
                                    class="h-full w-full object-cover">
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('buyer.products.show', $item->product) }}"
                                class="line-clamp-2 text-sm font-medium text-charcoal hover:text-primary">
                                {{ $item->product->name }}
                            </a>
                            <p class="mt-1 text-sm font-bold text-primary sm:hidden">
                                ₱{{ number_format($item->product->display_price, 2) }}</p>
                        </div>

                        <p class="hidden w-24 shrink-0 text-right text-sm text-charcoal/70 sm:block">
                            ₱{{ number_format($item->product->display_price, 2) }}
                        </p>

                        <div class="flex w-28 shrink-0 items-center justify-center rounded-lg border border-light-gray">
                            <button type="button"
                                class="qty-minus px-2.5 py-1.5 text-charcoal/60 hover:text-primary">−</button>
                            <input type="number"
                                class="qty-input w-10 border-x border-light-gray py-1.5 text-center text-sm outline-none"
                                value="{{ $item->quantity }}" min="1"
                                max="{{ $item->product->stock_quantity }}">
                            <button type="button"
                                class="qty-plus px-2.5 py-1.5 text-charcoal/60 hover:text-primary">+</button>
                        </div>

                        <p class="line-total w-24 shrink-0 text-right text-sm font-bold text-sale-red">
                            ₱{{ number_format($item->line_total, 2) }}
                        </p>

                        <div class="flex w-20 shrink-0 justify-end">
                            <button type="button"
                                class="remove-btn rounded-lg p-2 text-charcoal/40 transition hover:bg-sale-red/10 hover:text-sale-red">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>

                    </div>
                @endforeach
            </div>

        @endif

    </main>

    {{-- ===================================================
         STICKY SELECTION / CHECKOUT BAR (Shopee-style)
    ==================================================== --}}
    @if (!$cartItems->isEmpty())
        <div
            class="fixed inset-x-0 bottom-0 z-30 border-t border-light-gray bg-white px-5 py-3 shadow-[0_-4px_12px_rgba(0,0,0,0.06)] sm:px-8">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4">

                <div class="flex items-center gap-4 text-sm">
                    <label class="flex items-center gap-2 font-medium text-charcoal">
                        <input type="checkbox" id="selectAllBottom"
                            class="h-4 w-4 rounded border-light-gray text-primary focus:ring-primary">
                        Select All (<span id="totalItemCount">{{ $cartItems->count() }}</span>)
                    </label>
                    <button type="button" id="deleteSelectedBtn"
                        class="font-medium text-sale-red transition hover:underline disabled:cursor-not-allowed disabled:text-charcoal/30 disabled:hover:no-underline"
                        disabled>
                        Delete
                    </button>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs text-charcoal/50">
                            Total (<span id="selectedCount">0</span> item<span id="selectedCountPlural">s</span>):
                        </p>
                        <p id="selectedSubtotal" class="text-lg font-bold text-sale-red">₱0.00</p>
                    </div>
                    <button type="button" id="checkoutBtn"
                        class="rounded-xl bg-primary px-8 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:bg-charcoal/20 disabled:shadow-none"
                        disabled>
                        Check Out
                    </button>
                </div>

            </div>
        </div>
    @endif

    <div id="toast"
        class="fixed bottom-24 right-6 z-[80] hidden max-w-sm rounded-xl px-5 py-3.5 text-sm font-medium text-white shadow-xl transition">
    </div>


    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const checkoutRoute = "{{ route('buyer.checkout.index') }}";
        const selectedIds = new Set();

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#DC2626' : '#16A34A';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        function updateHeaderBadge(count) {
            const badge = document.getElementById('buyerCartBadge');
            if (badge) badge.textContent = count;
        }

        function peso(amount) {
            return `₱${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
        }

        // -----------------------------------------------------
        // SELECTION / STICKY FOOTER LOGIC
        // -----------------------------------------------------
        function getAllRows() {
            return document.querySelectorAll('[data-cart-item-id]');
        }

        function updateFooter() {
            const rows = getAllRows();
            let subtotal = 0;

            rows.forEach(row => {
                const id = row.dataset.cartItemId;
                if (selectedIds.has(id)) {
                    subtotal += parseFloat(row.dataset.lineTotal || 0);
                }
            });

            const count = selectedIds.size;
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('selectedCountPlural').textContent = count === 1 ? '' : 's';
            document.getElementById('selectedSubtotal').textContent = peso(subtotal);
            document.getElementById('totalItemCount').textContent = rows.length;

            const checkoutBtn = document.getElementById('checkoutBtn');
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            checkoutBtn.disabled = count === 0;
            deleteBtn.disabled = count === 0;

            const allChecked = rows.length > 0 && count === rows.length;
            ['selectAllTop', 'selectAllBottom'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                el.checked = allChecked;
                el.indeterminate = count > 0 && !allChecked;
            });
        }

        function toggleSelectAll(checked) {
            getAllRows().forEach(row => {
                const checkbox = row.querySelector('.item-checkbox');
                checkbox.checked = checked;
                const id = row.dataset.cartItemId;
                checked ? selectedIds.add(id) : selectedIds.delete(id);
            });
            updateFooter();
        }

        document.getElementById('selectAllTop')?.addEventListener('change', e => toggleSelectAll(e.target.checked));
        document.getElementById('selectAllBottom')?.addEventListener('change', e => toggleSelectAll(e.target.checked));

        document.querySelectorAll('.item-checkbox').forEach(checkbox => {
            const row = checkbox.closest('[data-cart-item-id]');
            checkbox.addEventListener('change', () => {
                const id = row.dataset.cartItemId;
                checkbox.checked ? selectedIds.add(id) : selectedIds.delete(id);
                updateFooter();
            });
        });

        // -----------------------------------------------------
        // DELETE SELECTED (bulk)
        // -----------------------------------------------------
        document.getElementById('deleteSelectedBtn')?.addEventListener('click', async () => {
            if (selectedIds.size === 0) return;
            if (!confirm(`Remove ${selectedIds.size} item(s) from your cart?`)) return;

            const idsToRemove = Array.from(selectedIds);
            let lastData = null;

            for (const id of idsToRemove) {
                try {
                    const res = await fetch(`/buyer/cart/item/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        document.querySelector(`[data-cart-item-id="${id}"]`)?.remove();
                        selectedIds.delete(id);
                        lastData = data;
                    }
                } catch (err) {
                    showToast('Network error while removing an item.', true);
                }
            }

            if (lastData) updateHeaderBadge(lastData.cart_count);
            updateFooter();

            if (getAllRows().length === 0) {
                window.location.reload(); // shows the empty-cart state cleanly
            }
        });

        // -----------------------------------------------------
        // CHECKOUT (only the selected items)
        // -----------------------------------------------------
        document.getElementById('checkoutBtn')?.addEventListener('click', () => {
            if (selectedIds.size === 0) return;
            const params = new URLSearchParams();
            selectedIds.forEach(id => params.append('items[]', id));
            window.location.href = `${checkoutRoute}?${params.toString()}`;
        });

        // -----------------------------------------------------
        // QUANTITY / REMOVE (per item — same as before)
        // -----------------------------------------------------
        async function patchQuantity(row, quantity) {
            const id = row.dataset.cartItemId;
            try {
                const res = await fetch(`/buyer/cart/item/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        quantity
                    }),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    showToast(data.message || 'Could not update quantity.', true);
                    return;
                }

                row.dataset.lineTotal = data.line_total;
                row.querySelector('.line-total').textContent = peso(data.line_total);
                updateHeaderBadge(data.cart_count);
                updateFooter();
            } catch (err) {
                showToast('Network error — please try again.', true);
            }
        }

        document.querySelectorAll('[data-cart-item-id]').forEach(row => {
            const input = row.querySelector('.qty-input');

            row.querySelector('.qty-minus').addEventListener('click', () => {
                const newVal = Math.max(1, parseInt(input.value || 1) - 1);
                input.value = newVal;
                patchQuantity(row, newVal);
            });

            row.querySelector('.qty-plus').addEventListener('click', () => {
                const max = parseInt(input.max);
                const newVal = Math.min(max, parseInt(input.value || 1) + 1);
                input.value = newVal;
                patchQuantity(row, newVal);
            });

            input.addEventListener('change', () => {
                let val = parseInt(input.value || 1);
                val = Math.max(1, Math.min(parseInt(input.max), val));
                input.value = val;
                patchQuantity(row, val);
            });

            row.querySelector('.remove-btn').addEventListener('click', async () => {
                const id = row.dataset.cartItemId;
                try {
                    const res = await fetch(`/buyer/cart/item/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        showToast('Could not remove item.', true);
                        return;
                    }

                    selectedIds.delete(id);
                    row.remove();
                    updateHeaderBadge(data.cart_count);
                    updateFooter();

                    if (data.cart_count === 0) {
                        window.location.reload(); // shows the empty-cart state cleanly
                    }
                } catch (err) {
                    showToast('Network error — please try again.', true);
                }
            });
        });

        updateFooter();
    </script>

</body>

</html>
