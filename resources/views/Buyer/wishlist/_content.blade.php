<div data-account-content="wishlist">
    @php
        // Same button styles as the orders page.
        $btnBase =
            'inline-flex min-h-9 w-full items-center justify-center rounded-lg px-4 py-2 text-xs font-bold transition-colors';
        $btnPrimary = "$btnBase bg-primary text-white hover:bg-primary/90";
    @endphp

        <div class="mb-4 sm:mb-6">
            <h1 class="text-xl font-bold text-charcoal md:text-2xl">My Wishlist</h1>
            <p class="mt-1 text-sm text-charcoal/60">
                <span x-data x-text="$store.wishlist.count">{{ $products->total() }}</span> saved item(s)
            </p>
        </div>

        {{-- Empty state (also appears when the last item is un-hearted) --}}
        <div x-data x-show="$store.wishlist.count === 0" x-cloak
            class="flex flex-col items-center justify-center rounded-xl border border-line bg-white p-8 text-center sm:p-16">
            <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <p class="mt-4 text-sm font-semibold text-charcoal">Your wishlist is empty</p>
            <p class="mt-1 text-xs text-charcoal/60">Tap the heart on any product to save it for later.</p>
            <a href="{{ route('buyer.products.index') }}" class="{{ $btnPrimary }} mt-5 !w-auto !min-h-10 !px-5">
                Browse Products
            </a>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach ($products as $product)
                    <div x-data x-show="$store.wishlist.has({{ $product->id }})"
                        x-transition.opacity class="min-w-0">
                        <x-Buyer.product-card :product="$product" />
                    </div>
                @endforeach
            </div>

            <div class="pagination-frost mt-6 flex justify-center overflow-x-auto">
                {{ $products->onEachSide(1)->links() }}
            </div>
        @endif

</div>
