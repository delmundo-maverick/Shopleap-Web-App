<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Shopleap — Shop Smart. Shop Simple.</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <!-- =====================================================
         HEADER
    ====================================================== -->
    <header class="sticky top-0 z-40 bg-primary text-white shadow-sm">

        <!-- Top thin utility row -->
        <div class="border-b border-white/10">
            <div
                class="mx-auto flex h-8 max-w-7xl items-center justify-between gap-5 px-5 text-xs text-white/80 sm:px-8">

                <!-- LEFT: utility links -->
                <div class="flex items-center gap-5">
                    <a href="#" class="hover:text-white">Seller Centre</a>
                    <a href="#" class="hover:text-white">Track My Order</a>
                    <a href="#" class="hover:text-white">Help Center</a>
                </div>

                <!-- RIGHT: notifications, help, profile -->
                <div class="flex items-center gap-5">

                    <!-- Notifications -->
                    <div class="relative" id="notifWrapper">
                        <button type="button" id="notifToggle"
                            class="relative rounded-full p-2.5 transition hover:bg-white/10">
                            <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                class="absolute right-1.5 top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-sale-red text-[10px] font-bold">3</span>
                        </button>
                        <div id="notifMenu"
                            class="absolute right-0 mt-2 hidden w-72 overflow-hidden rounded-lg bg-white text-charcoal shadow-xl">
                            <p class="border-b border-light-gray px-4 py-3 text-sm font-bold">Notifications</p>
                            <div class="max-h-72 overflow-y-auto">
                                <a href="#"
                                    class="block border-b border-light-gray px-4 py-3 text-xs transition hover:bg-ice-blue">
                                    <p class="font-semibold text-charcoal">Order shipped</p>
                                    <p class="mt-0.5 text-charcoal/50">Your order is on its way — track it now.</p>
                                </a>
                                <a href="#"
                                    class="block border-b border-light-gray px-4 py-3 text-xs transition hover:bg-ice-blue">
                                    <p class="font-semibold text-charcoal">Flash Sale starts soon</p>
                                    <p class="mt-0.5 text-charcoal/50">Don't miss out — up to 50% off today only.</p>
                                </a>
                                <a href="#" class="block px-4 py-3 text-xs transition hover:bg-ice-blue">
                                    <p class="font-semibold text-charcoal">Welcome to Shopleap!</p>
                                    <p class="mt-0.5 text-charcoal/50">Your account has been approved.</p>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Help -->
                    <a href="#" title="Help"
                        class="hidden rounded-full p-2.5 transition hover:bg-white/10 sm:block">
                        <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        </a>

                    <!-- Profile dropdown -->
                    <div class="relative" id="profileWrapper">
                        <button type="button" id="profileToggle"
                            class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2.5 transition hover:bg-white/10">
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-xs font-bold text-primary">
                                {{ strtoupper(substr(auth()->user()->name ?? 'B', 0, 1)) }}
                            </div>
                            <span
                                class="hidden max-w-[100px] truncate text-xs font-medium sm:block">{{ auth()->user()->name ?? 'Buyer' }}</span>
                        </button>
                        <div id="profileMenu"
                            class="absolute right-0 mt-2 hidden w-52 overflow-hidden rounded-lg bg-white py-1.5 text-charcoal shadow-xl">
                            <a href="#" class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">My
                                Account</a>
                            <a href="#" class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">My
                                Orders</a>
                            <a href="#" class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">Chat /
                                Messages</a>
                            <div class="my-1 h-px bg-light-gray"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="block w-full px-4 py-2.5 text-left text-sm text-sale-red transition hover:bg-sale-red/10">Logout</button>
                            </form>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Main header row -->
        <div class="mx-auto flex h-[68px] max-w-7xl items-center gap-6 px-5 sm:px-8">

            <!-- LOGO — acts as home button -->
            <a href="{{ route('buyer.home') }}" class="flex shrink-0 items-center gap-2">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
                    <!-- LOGO PLACEHOLDER — replace /public/images/shopleap-logo.png with the real logo -->
                    <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap"
                        class="h-full w-full rounded-lg object-contain">
                </div>
                <span class="hidden text-xl font-extrabold tracking-tight sm:block">Shopleap</span>
            </a>

            <!-- SEARCH BAR with suggestions dropdown -->
            <div class="relative flex-1" id="searchWrapper">
                <form action="#" method="GET" class="flex overflow-hidden rounded-sm bg-white">
                    <input type="text" id="searchInput" name="q" autocomplete="off"
                        placeholder="Search products, brands, and categories"
                        class="w-full px-4 py-2.5 text-sm text-charcoal outline-none">
                    <button type="submit"
                        class="flex shrink-0 items-center justify-center bg-sky-blue px-5 text-white transition hover:brightness-105">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>

                <!-- Suggestions / recent searches dropdown -->
                <div id="searchSuggestions"
                    class="absolute left-0 right-0 top-full mt-1.5 hidden rounded-lg bg-white p-3 text-charcoal shadow-xl">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-charcoal/40">Recent Searches</p>
                    <div class="mb-3 flex flex-wrap gap-2">
                        @foreach (['wireless earbuds', 'running shoes', 'desk lamp', 'water bottle'] as $recent)
                            <button type="button"
                                class="rounded-full bg-ice-blue px-3 py-1 text-xs text-charcoal/70 transition hover:bg-primary/10 hover:text-primary">
                                {{ $recent }}
                            </button>
                        @endforeach
                    </div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-charcoal/40">Trending</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['skincare set', 'mechanical keyboard', 'yoga mat', 'power bank'] as $trend)
                            <button type="button"
                                class="rounded-full bg-ice-blue px-3 py-1 text-xs text-charcoal/70 transition hover:bg-primary/10 hover:text-primary">
                                {{ $trend }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ICONS -->
            <div class="flex shrink-0 items-center gap-1">

                <!-- Cart with hover preview -->
                <div class="group relative">
                    <a href="#" class="relative block rounded-full p-2.5 transition hover:bg-white/10">
                        <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span
                            class="absolute right-1.5 top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-white text-[10px] font-bold text-primary">2</span>
                    </a>

                    <!-- Preview dropdown — shows on hover (desktop) -->
                    <div
                        class="invisible absolute right-0 top-full z-50 w-80 -translate-y-1 rounded-lg bg-white text-charcoal opacity-0 shadow-xl transition-all duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                        <p class="border-b border-light-gray px-4 py-3 text-sm font-bold">2 items in your cart</p>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="flex items-center gap-3 border-b border-light-gray px-4 py-3">
                                <div class="h-12 w-12 shrink-0 rounded bg-ice-blue"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs text-charcoal">Wireless Bluetooth Earbuds</p>
                                    <p class="text-xs font-bold text-primary">₱899</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div class="h-12 w-12 shrink-0 rounded bg-ice-blue"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs text-charcoal">Running Shoes Unisex</p>
                                    <p class="text-xs font-bold text-primary">₱1,650</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-3">
                            <a href="#"
                                class="block w-full rounded-lg bg-primary px-4 py-2.5 text-center text-xs font-bold text-white transition hover:bg-sky-blue">
                                View Cart
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </header>


    <!-- =====================================================
         MAIN
    ====================================================== -->
    <main class="mx-auto max-w-7xl px-5 py-6 sm:px-8">

        <!-- BANNER -->
        <div class="mb-6 h-40 overflow-hidden rounded-lg bg-gradient-to-r from-primary to-sky-blue sm:h-56">
            <div class="flex h-full items-center px-8 text-white">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Limited Time</p>
                    <h2 class="mt-1 text-2xl font-extrabold sm:text-3xl">Up to 50% Off Today</h2>
                    <p class="mt-1 text-sm text-white/85">Shop the best deals across all categories</p>
                </div>
            </div>
        </div>

        <!-- CATEGORY GRID -->
        <section class="mb-8 rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
            <div class="grid grid-cols-4 gap-4 sm:grid-cols-5 lg:grid-cols-7">
                @foreach ($categories as $category)
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
                            'food' =>
                                'M18 8h1a4 4 0 010 8h-1M3 8h15v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3',
                            'car' =>
                                'M5 17h14M6 17a2 2 0 11-4 0 2 2 0 014 0zm12 0a2 2 0 11-4 0 2 2 0 014 0zM3 17V9l2-5h14l2 5v8',
                            'office' => 'M9 3h6a2 2 0 012 2v14l-5-3-5 3V5a2 2 0 012-2z',
                            'gem' => 'M6 3h12l4 6-10 12L2 9l4-6z',
                            'pencil' =>
                                'M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z',
                        ];
                        $iconPath = $icons[$category['icon']] ?? $icons['office'];
                    @endphp
                    <a href="#" class="group flex flex-col items-center gap-2 text-center">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-ice-blue text-primary transition group-hover:bg-primary group-hover:text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="{{ $iconPath }}" />
                            </svg>
                        </div>
                        <span
                            class="text-[11px] leading-tight text-charcoal/70 group-hover:text-primary">{{ $category['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <!-- BEST SELLERS -->
        <section class="mb-8">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-bold text-charcoal">Top Products / Best Sellers</h2>
                <a href="#" class="text-xs font-semibold text-primary hover:underline">See all</a>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($bestSellers as $product)
                    <x-Buyer.product-card :product="$product" />
                @endforeach
            </div>
        </section>

        <!-- DAILY DISCOVER / RECOMMENDED -->
        <section>
            <div class="mb-3 flex items-center gap-2">
                <h2 class="text-base font-bold text-charcoal">Daily Discover</h2>
                <span class="text-xs text-charcoal/45">Recommended for you</span>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($recommended as $product)
                    <x-Buyer.product-card :product="$product" />
                @endforeach
            </div>

            <!-- PAGINATION -->
            <div class="mt-8 flex justify-center">
                {{ $recommended->onEachSide(1)->links() }}
            </div>
        </section>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->
    <footer class="mt-10 border-t border-light-gray bg-white">
        <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">

            <!-- CATEGORY SITEMAP -->
            <div class="mb-10 border-b border-light-gray pb-10">
                <p class="mb-5 text-sm font-bold text-charcoal">Shop by Category</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($categories as $category)
                        <div>
                            <a href="#" class="mb-2 block text-xs font-bold text-charcoal hover:text-primary">
                                {{ $category['name'] }}
                            </a>
                            <ul class="space-y-1.5">
                                @foreach ($category['subcategories'] as $sub)
                                    <li><a href="#"
                                            class="text-xs text-charcoal/60 hover:text-primary">{{ $sub }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-charcoal/50">Customer Service</p>
                    <ul class="space-y-2 text-xs text-charcoal/65">
                        <li><a href="#" class="hover:text-primary">Help Center</a></li>
                        <li><a href="#" class="hover:text-primary">How to Buy</a></li>
                        <li><a href="#" class="hover:text-primary">Returns & Refunds</a></li>
                        <li><a href="#" class="hover:text-primary">Contact Us</a></li>
                        <li><a href="#" class="hover:text-primary">Track My Order</a></li>
                    </ul>
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-charcoal/50">About Shopleap</p>
                    <ul class="space-y-2 text-xs text-charcoal/65">
                        <li><a href="#" class="hover:text-primary">About Us</a></li>
                        <li><a href="#" class="hover:text-primary">Careers</a></li>
                        <li><a href="{{ route('register.seller') }}" class="hover:text-primary">Sell on Shopleap</a>
                        </li>
                        <li><a href="#" class="hover:text-primary">Terms & Policies</a></li>
                        <li><a href="#" class="hover:text-primary">Privacy Policy</a></li>
                    </ul>
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-charcoal/50">Payment Methods</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['GCash', 'Maya', 'Visa', 'Mastercard', 'COD'] as $payment)
                            <span
                                class="rounded border border-light-gray px-2 py-1 text-[10px] font-semibold text-charcoal/60">{{ $payment }}</span>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-charcoal/50">Delivery Partners</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['J&T', 'LBC', 'Ninja Van', 'Flash'] as $courier)
                            <span
                                class="rounded border border-light-gray px-2 py-1 text-[10px] font-semibold text-charcoal/60">{{ $courier }}</span>
                        @endforeach
                    </div>
                </div>

            </div>

            <div
                class="mt-8 flex flex-col items-center justify-between gap-3 border-t border-light-gray pt-6 sm:flex-row">
                <p class="text-xs text-charcoal/50">© {{ date('Y') }} Shopleap. All rights reserved.</p>
                <p class="text-xs text-charcoal/50">Shop Smart. Shop Simple.</p>
            </div>

        </div>
    </footer>


    <script>
        function wireDropdown(wrapperId, toggleId, menuId) {
            const wrapper = document.getElementById(wrapperId);
            const toggle = document.getElementById(toggleId);
            const menu = document.getElementById(menuId);
            if (!wrapper || !menu) return;

            if (toggle) {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    menu.classList.toggle('hidden');
                });
            }

            document.addEventListener('click', function(e) {
                if (!wrapper.contains(e.target)) menu.classList.add('hidden');
            });
        }

        wireDropdown('notifWrapper', 'notifToggle', 'notifMenu');
        wireDropdown('profileWrapper', 'profileToggle', 'profileMenu');

        // Search suggestions — show on focus, hide on outside click
        const searchWrapper = document.getElementById('searchWrapper');
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');

        searchInput.addEventListener('focus', () => searchSuggestions.classList.remove('hidden'));
        document.addEventListener('click', function(e) {
            if (!searchWrapper.contains(e.target)) searchSuggestions.classList.add('hidden');
        });
    </script>

</body>

</html>
