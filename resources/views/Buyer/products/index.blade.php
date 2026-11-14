<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ $search ? "Search: {$search}" : ($category ?: 'All Products') }} | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    <main class="mx-auto max-w-7xl px-5 py-6 sm:px-8">

        <div class="mb-5">
            <h1 class="text-xl font-bold text-charcoal">
                @if ($search)
                    Search results for "{{ $search }}"
                @elseif ($category)
                    {{ $category }}
                @else
                    All Products
                @endif
            </h1>
            <p class="mt-1 text-sm text-charcoal/50">{{ $products->total() }} item(s) found</p>
        </div>

        <!-- CATEGORY PILLS -->
        <div class="mb-5 flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('buyer.products.index', array_filter(['search' => $search, 'sort' => $sort])) }}"
                class="shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition
                    {{ !$category ? 'bg-primary text-white' : 'bg-white text-charcoal/70 hover:bg-primary/10 hover:text-primary' }}">
                All
            </a>
            @foreach ($categories as $cat)
                <a href="{{ route('buyer.products.index', array_filter(['search' => $search, 'category' => $cat['name'], 'sort' => $sort])) }}"
                    class="shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition
                        {{ $category === $cat['name'] ? 'bg-primary text-white' : 'bg-white text-charcoal/70 hover:bg-primary/10 hover:text-primary' }}">
                    {{ $cat['name'] }}
                </a>
            @endforeach
        </div>

        <!-- SORT -->
        <div class="mb-5 flex justify-end">
            <form method="GET">
                <input type="hidden" name="search" value="{{ $search }}">
                <input type="hidden" name="category" value="{{ $category }}">
                <select name="sort" onchange="this.form.submit()"
                    class="rounded-xl border border-light-gray bg-white px-3.5 py-2 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <option value="newest" @selected($sort === 'newest')>Newest</option>
                    <option value="bestseller" @selected($sort === 'bestseller')>Best Selling</option>
                    <option value="price_low" @selected($sort === 'price_low')>Price: Low to High</option>
                    <option value="price_high" @selected($sort === 'price_high')>Price: High to Low</option>
                </select>
            </form>
        </div>

        @if ($products->isEmpty())
            <div
                class="flex flex-col items-center justify-center rounded-lg bg-white p-16 text-center shadow-sm shadow-charcoal/5">
                <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <p class="mt-4 text-sm font-semibold text-charcoal">No products found</p>
                <p class="mt-1 text-xs text-charcoal/50">Try a different search term or category.</p>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($products as $product)
                    <x-Buyer.product-card :product="$product" />
                @endforeach
            </div>

            <div class="pagination-frost mt-8 flex justify-center">
                {{ $products->onEachSide(1)->links() }}
            </div>
        @endif

    </main>

    <x-footer />
</body>

</html>
