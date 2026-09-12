<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ $product->name }} | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-navbars.buyer active="home" />

    <main class="mx-auto max-w-7xl px-5 py-6 sm:px-8">

        <a href="{{ route('buyer.home') }}"
            class="mb-4 inline-flex items-center gap-1 text-xs text-charcoal/50 hover:text-primary">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to shopping
        </a>

        <div class="grid grid-cols-1 gap-8 rounded-lg bg-white p-6 shadow-sm shadow-charcoal/5 lg:grid-cols-2">

            <!-- IMAGE GALLERY -->
            <div>
                <div id="mainImageWrapper" class="aspect-square w-full overflow-hidden rounded-lg bg-ice-blue/60">
                    @if ($product->images->isNotEmpty())
                        <img id="mainImage" src="{{ $product->images->first()->url }}" alt="{{ $product->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-charcoal/25">
                            <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v14H4V6z" />
                            </svg>
                        </div>
                    @endif
                </div>

                @if ($product->images->count() > 1)
                    <div class="mt-3 grid grid-cols-6 gap-2">
                        @foreach ($product->images as $img)
                            <button type="button" data-thumb="{{ $img->url }}"
                                class="thumb-btn aspect-square overflow-hidden rounded-lg border-2 {{ $loop->first ? 'border-primary' : 'border-transparent' }}">
                                <img src="{{ $img->url }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- DETAILS -->
            <div>
                <span class="rounded bg-leaf-green/10 px-2 py-1 text-xs font-semibold text-leaf-green">New
                    Arrival</span>

                <h1 class="mt-3 text-2xl font-bold text-charcoal">{{ $product->name }}</h1>

                <div class="mt-3 flex items-baseline gap-2">
                    <span
                        class="text-3xl font-bold text-primary">₱{{ number_format($product->display_price, 2) }}</span>
                    @if ($product->was_price)
                        <span
                            class="text-base text-charcoal/40 line-through">₱{{ number_format($product->was_price, 2) }}</span>
                    @endif
                </div>

                <div class="mt-4 flex items-center gap-4 text-xs text-charcoal/50">
                    <span>{{ $product->stock_quantity }} in stock</span>
                    <span>·</span>
                    <span>{{ $product->category }}{{ $product->subcategory ? ' — ' . $product->subcategory : '' }}</span>
                </div>

                @if ($product->seller_location)
                    <p class="mt-2 flex items-center gap-1 text-xs text-charcoal/50">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Ships from {{ $product->seller_location }}
                    </p>
                @endif

                <div class="my-5 h-px bg-light-gray"></div>

                <!-- QUANTITY -->
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold text-charcoal">Quantity</span>
                    <div class="flex items-center rounded-lg border border-light-gray">
                        <button type="button" id="qtyMinus"
                            class="px-3 py-2 text-charcoal/60 hover:text-primary">−</button>
                        <input type="number" id="qtyInput" value="1" min="1"
                            max="{{ $product->stock_quantity }}"
                            class="w-14 border-x border-light-gray py-2 text-center text-sm outline-none">
                        <button type="button" id="qtyPlus"
                            class="px-3 py-2 text-charcoal/60 hover:text-primary">+</button>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="mt-6 flex gap-3">
                    <button type="button" id="addToCartBtn" data-product-id="{{ $product->id }}"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl border-2 border-primary bg-white px-6 py-3.5 text-sm font-bold text-primary transition hover:bg-primary/5">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Add to Cart
                    </button>
                    <a href="{{ route('buyer.cart.index') }}"
                        class="flex flex-1 items-center justify-center rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue">
                        View Cart
                    </a>
                </div>

                <div class="my-6 h-px bg-light-gray"></div>

                <h3 class="mb-2 text-sm font-bold text-charcoal">Product Description</h3>
                <p class="whitespace-pre-line text-sm leading-relaxed text-charcoal/70">
                    {{ $product->description ?: 'No description provided.' }}</p>
            </div>

        </div>

    </main>


    <!-- TOAST -->
    <div id="toast"
        class="fixed bottom-6 right-6 z-[80] hidden max-w-sm rounded-xl px-5 py-3.5 text-sm font-medium text-white shadow-xl transition">
    </div>


    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Thumbnail switching
        document.querySelectorAll('.thumb-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('mainImage').src = this.dataset.thumb;
                document.querySelectorAll('.thumb-btn').forEach(b => b.classList.remove('border-primary'));
                document.querySelectorAll('.thumb-btn').forEach(b => b.classList.add('border-transparent'));
                this.classList.remove('border-transparent');
                this.classList.add('border-primary');
            });
        });

        // Quantity stepper
        const qtyInput = document.getElementById('qtyInput');
        document.getElementById('qtyMinus').addEventListener('click', () => {
            qtyInput.value = Math.max(1, parseInt(qtyInput.value || 1) - 1);
        });
        document.getElementById('qtyPlus').addEventListener('click', () => {
            const max = parseInt(qtyInput.max);
            qtyInput.value = Math.min(max, parseInt(qtyInput.value || 1) + 1);
        });

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#DC2626' : '#16A34A';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        // Add to cart
        document.getElementById('addToCartBtn').addEventListener('click', async function() {
            const productId = this.dataset.productId;
            const quantity = parseInt(qtyInput.value || 1);

            try {
                const res = await fetch(`/buyer/cart/${productId}`, {
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
                    showToast(data.message || 'Could not add to cart.', true);
                    return;
                }

                showToast(data.message);
                const badge = document.getElementById('buyerCartBadge');
                if (badge) badge.textContent = data.cart_count;
            } catch (err) {
                showToast('Network error — please try again.', true);
            }
        });
    </script>

</body>

</html>
