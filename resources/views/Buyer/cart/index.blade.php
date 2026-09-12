<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Cart | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-navbars.buyer active="home" />

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
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <!-- ITEMS -->
                <div class="space-y-3 lg:col-span-2">
                    @foreach ($cartItems as $item)
                        <div data-cart-item-id="{{ $item->id }}"
                            class="flex items-center gap-4 rounded-lg bg-white p-4 shadow-sm shadow-charcoal/5">

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
                                <p class="mt-1 text-sm font-bold text-primary">
                                    ₱{{ number_format($item->product->display_price, 2) }}</p>
                            </div>

                            <div class="flex items-center rounded-lg border border-light-gray">
                                <button type="button"
                                    class="qty-minus px-2.5 py-1.5 text-charcoal/60 hover:text-primary">−</button>
                                <input type="number"
                                    class="qty-input w-12 border-x border-light-gray py-1.5 text-center text-sm outline-none"
                                    value="{{ $item->quantity }}" min="1"
                                    max="{{ $item->product->stock_quantity }}">
                                <button type="button"
                                    class="qty-plus px-2.5 py-1.5 text-charcoal/60 hover:text-primary">+</button>
                            </div>

                            <p class="line-total w-24 shrink-0 text-right text-sm font-bold text-charcoal">
                                ₱{{ number_format($item->line_total, 2) }}
                            </p>

                            <button type="button"
                                class="remove-btn shrink-0 rounded-lg p-2 text-charcoal/40 transition hover:bg-sale-red/10 hover:text-sale-red">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                </svg>
                            </button>

                        </div>
                    @endforeach
                </div>

                <!-- SUMMARY -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                        <h3 class="mb-4 text-sm font-bold text-charcoal">Order Summary</h3>

                        <div class="flex items-center justify-between text-sm text-charcoal/70">
                            <span>Subtotal</span>
                            <span id="cartSubtotal"
                                class="font-semibold text-charcoal">₱{{ number_format($total, 2) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-charcoal/40">Shipping and vouchers are calculated at checkout.</p>

                        <div class="my-4 h-px bg-light-gray"></div>

                        <button type="button" disabled
                            class="w-full cursor-not-allowed rounded-xl bg-charcoal/20 px-6 py-3.5 text-sm font-bold text-white"
                            title="Checkout is coming in a future update">
                            Proceed to Checkout
                        </button>
                        <p class="mt-2 text-center text-xs text-charcoal/40">Checkout is coming soon.</p>
                    </div>
                </div>

            </div>

        @endif

    </main>


    <div id="toast"
        class="fixed bottom-6 right-6 z-[80] hidden max-w-sm rounded-xl px-5 py-3.5 text-sm font-medium text-white shadow-xl transition">
    </div>


    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

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

                row.querySelector('.line-total').textContent =
                    `₱${Number(data.line_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                document.getElementById('cartSubtotal').textContent =
                    `₱${Number(data.cart_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                updateHeaderBadge(data.cart_count);
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

                    row.remove();
                    document.getElementById('cartSubtotal').textContent =
                        `₱${Number(data.cart_total).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                    updateHeaderBadge(data.cart_count);

                    if (data.cart_count === 0) {
                        window.location.reload(); // shows the empty-cart state cleanly
                    }
                } catch (err) {
                    showToast('Network error — please try again.', true);
                }
            });
        });
    </script>

</body>

</html>
