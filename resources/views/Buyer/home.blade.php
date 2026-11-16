<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/home.js'])

    <title>Shopleap — Shop Smart. Shop Simple.</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />


    <!-- =====================================================
         MAIN
    ====================================================== -->
    <main class="mx-auto max-w-7xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8">

        <x-announcement-banner audience="buyer" variant="flat" />

        <!-- HERO: image carousel on the left, two smaller images stacked on the right -->
        @php
            // Images live in public/images/home/header/.
            // Suggested sizes: carousel slides about 1200 x 420 px, side tiles about 600 x 300 px.
            $slides = [
                [
                    'label' => 'Featured promotion 1',
                    'image' => asset('images/home/header/page-1.jpg'),
                ],
                [
                    'label' => 'Featured promotion 2',
                    'image' => asset('images/home/header/page-2.jpg'),
                ],
                [
                    'label' => 'Featured promotion 3',
                    'image' => asset('images/home/header/page-3.jpg'),
                ],
            ];

            $promoTiles = [
                [
                    'label' => 'Seller vouchers',
                    'image' => asset('images/home/header/seller-voucher.jpg'),
                ],
                [
                    'label' => 'Promo 2',
                    'image' => asset('images/home/header/side-2.jpg'),
                ],
            ];

            $placeholderIcon =
                'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v14H4V6z';
        @endphp

        <section aria-label="Featured promotions"
            class="mb-6 grid gap-3 sm:mb-8 lg:h-60 lg:grid-cols-[minmax(0,3fr)_minmax(0,1fr)]">

            {{-- CAROUSEL (behavior in resources/js/pages/buyer/home.js) --}}
            <div class="relative h-36 overflow-hidden rounded-xl border border-line bg-white sm:h-48 lg:h-full"
                data-carousel data-autoplay="5000" role="region" aria-roledescription="carousel"
                aria-label="Promotions">

                <div data-carousel-track
                    class="flex h-full transition-transform duration-500 ease-out motion-reduce:transition-none">
                    @foreach ($slides as $slide)
                        <div data-carousel-slide role="group" aria-roledescription="slide"
                            aria-label="{{ $loop->iteration }} of {{ count($slides) }}"
                            class="relative h-full w-full shrink-0 basis-full">
                            @if ($slide['image'])
                                <img src="{{ $slide['image'] }}" alt="{{ $slide['label'] }}"
                                    @if (!$loop->first) loading="lazy" @endif
                                    class="h-full w-full object-cover">
                            @else
                                <div
                                    class="flex h-full w-full flex-col items-center justify-center gap-2 bg-ice-blue text-charcoal/50">
                                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="{{ $placeholderIcon }}" />
                                    </svg>
                                    <span class="text-sm font-semibold">{{ $slide['label'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- PREVIOUS / NEXT (hidden on phones: swipe instead) --}}
                <button type="button" data-carousel-prev aria-label="Previous slide"
                    class="absolute left-3 top-1/2 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg border border-line bg-white text-charcoal transition-colors hover:text-primary sm:flex">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button type="button" data-carousel-next aria-label="Next slide"
                    class="absolute right-3 top-1/2 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg border border-line bg-white text-charcoal transition-colors hover:text-primary sm:flex">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            {{-- SIDE IMAGES: stacked on the right on desktop, a row under the carousel on mobile --}}
            <div class="grid grid-cols-2 gap-3 lg:min-h-0 lg:grid-cols-1 lg:grid-rows-2">
                @foreach ($promoTiles as $tile)
                    <div
                        class="relative flex h-20 min-h-0 items-center justify-center overflow-hidden rounded-xl border bg-ice-blue sm:h-24 lg:h-auto {{ $tile['image'] ? 'border-line' : 'border-dashed border-charcoal/25' }}">
                        @if ($tile['image'])
                            <img src="{{ $tile['image'] }}" alt="{{ $tile['label'] }}" loading="lazy"
                                class="h-full w-full object-cover">
                        @else
                            <span class="flex flex-col items-center gap-1.5 text-charcoal/50">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="{{ $placeholderIcon }}" />
                                </svg>
                                <span class="text-xs font-semibold">{{ $tile['label'] }}</span>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <!-- CATEGORIES -->
        @php
            $icons = [
                'paw' =>
                    'M4.5 9.5a2 2 0 114 0 2 2 0 01-4 0zm11 0a2 2 0 114 0 2 2 0 01-4 0zM9 5.5a2 2 0 114 0 2 2 0 01-4 0zm6 0a2 2 0 114 0 2 2 0 01-4 0zM7 15c0-2.5 2.5-4 5-4s5 1.5 5 4-2 3.5-5 3.5-5-1-5-3.5z',
                'device' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                'dress' => 'M9 3l3 3 3-3m-3 3v15M6 8l-2 3 2 2m12-5l2 3-2 2',
                'shirt' => 'M9 3L6 6l1 3-2 2v10h14V11l-2-2 1-3-3-3-3 2-3-2z',
                'baby' => 'M9 12h6m-6 4h6M9 5a3 3 0 116 0v2H9V5zM5 21a7 7 0 0114 0H5z',
                'home' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10',
                'ball' => 'M12 3a9 9 0 100 18 9 9 0 000-18zm0 0v18m9-9H3m14.5-6.5l-13 13m13 0l-13-13',
                'heart' => 'M12 21s-7-4.5-9.5-9A5.5 5.5 0 0112 5a5.5 5.5 0 019.5 7c-2.5 4.5-9.5 9-9.5 9z',
                'book' =>
                    'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20V4a2 2 0 00-2-2H6.5A2.5 2.5 0 004 4.5v15z',
                'food' => 'M18 8h1a4 4 0 010 8h-1M3 8h15v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3',
                'car' => 'M5 17h14M6 17a2 2 0 11-4 0 2 2 0 014 0zm12 0a2 2 0 11-4 0 2 2 0 014 0zM3 17V9l2-5h14l2 5v8',
                'office' => 'M9 3h6a2 2 0 012 2v14l-5-3-5 3V5a2 2 0 012-2z',
                'gem' => 'M6 3h12l4 6-10 12L2 9l4-6z',
                'pencil' =>
                    'M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z',
            ];
        @endphp

        <section class="mb-6 sm:mb-8">
            <h2 class="mb-3 text-base font-bold text-charcoal">Categories</h2>

            {{-- One connected block. Mobile: a single row that scrolls sideways. sm and up: a 7-column grid. --}}
            <div class="overflow-hidden rounded-xl border border-line bg-white">
                <div class="overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div
                        class="-mb-px -mr-px grid auto-cols-[5.5rem] grid-flow-col sm:auto-cols-auto sm:grid-flow-row sm:grid-cols-7">
                        @foreach ($categories as $category)
                            @php $iconPath = $icons[$category['icon']] ?? $icons['office']; @endphp
                            <a href="{{ route('buyer.products.index', ['category' => $category['name']]) }}"
                                class="group flex flex-col items-center gap-2 border-b border-r border-line px-1.5 py-3 text-center transition-colors hover:bg-ice-blue sm:px-2 sm:py-4">
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-white sm:h-12 sm:w-12">
                                    <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                            d="{{ $iconPath }}" />
                                    </svg>
                                </span>
                                <span
                                    class="line-clamp-2 text-[11px] leading-tight text-charcoal/75 group-hover:text-primary sm:text-xs">{{ $category['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- BEST SELLERS -->
        <section class="mb-6 sm:mb-8">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-base font-bold text-charcoal">Top Products / Best Sellers</h2>
                <a href="{{ route('buyer.products.index', ['sort' => 'bestseller']) }}"
                    class="shrink-0 text-xs font-semibold text-primary hover:underline">See all</a>
            </div>
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 lg:grid-cols-6">
                @foreach ($bestSellers as $product)
                    <x-Buyer.product-card :product="$product" />
                @endforeach
            </div>
        </section>

        <!-- DAILY DISCOVER / RECOMMENDED -->
        <section id="daily-discover" class="scroll-mt-28" aria-labelledby="daily-discover-heading">
            <div class="mb-3 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                <h2 id="daily-discover-heading" class="text-base font-bold text-charcoal">Daily Discover</h2>
                <span class="text-xs text-charcoal/60">Recommended for you</span>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 lg:grid-cols-6">
                @forelse ($recommended as $product)
                    <x-Buyer.product-card :product="$product" />
                @empty
                    <p class="col-span-full rounded-xl border border-line bg-white p-6 text-sm text-charcoal/60">No products to discover yet. Check back soon for new finds.</p>
                @endforelse
            </div>

            <!-- PAGINATION -->
            <div class="mt-6 sm:mt-8">
                {{ $recommended->onEachSide(1)->links('components.Buyer.discover-pagination') }}
            </div>
        </section>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->
    <footer class="mt-8 border-t border-line bg-white sm:mt-10">
        <div class="mx-auto max-w-7xl px-3 py-8 sm:px-6 sm:py-10 lg:px-8">

            <!-- CATEGORY SITEMAP -->
            <div class="mb-8 border-b border-line pb-8 sm:mb-10 sm:pb-10">
                <p class="mb-4 text-sm font-bold text-charcoal sm:mb-5">Shop by Category</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-6 sm:grid-cols-3 sm:gap-y-8 lg:grid-cols-5">
                    @foreach ($categories as $category)
                        <div>
                            <a href="{{ route('buyer.products.index', ['category' => $category['name']]) }}"
                                class="mb-2 block text-xs font-bold text-charcoal hover:text-primary">
                                {{ $category['name'] }}
                            </a>
                            <ul class="space-y-1.5">
                                @foreach ($category['subcategories'] as $sub)
                                    <li><span class="text-xs text-charcoal/60">{{ $sub }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>

    <x-Buyer.message-widget />
    <x-footer />
</body>

</html>
