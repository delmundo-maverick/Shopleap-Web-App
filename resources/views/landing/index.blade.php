<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Shopleap is an online marketplace connecting Filipino shoppers with trusted sellers — secure payments, nationwide delivery, and everyday deals.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Two-weight type system: Sora carries headlines/branding, Inter carries body copy and numbers. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap"
        rel="stylesheet">

    <title>Shopleap | Shop Smart. Shop Simple.</title>

    <style>
        :root {
            --font-display: 'Sora', ui-sans-serif, system-ui, sans-serif;
            --font-body: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }

        .font-display {
            font-family: var(--font-display);
        }

        .font-body {
            font-family: var(--font-body);
        }

        /* Hero deal carousel — the one deliberately animated moment on the page. */
        .hero-slide {
            opacity: 0;
            visibility: hidden;
            transition: opacity .6s ease;
            position: absolute;
            inset: 0;
        }

        .hero-slide.is-active {
            opacity: 1;
            visibility: visible;
            position: relative;
        }

        .hero-dot {
            width: 1.5rem;
            height: 0.25rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .35);
            transition: background .3s ease, width .3s ease;
        }

        .hero-dot.is-active {
            background: #fff;
            width: 2.25rem;
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-slide {
                transition: none;
            }
        }

        /* Horizontal scroller for the category rail on small screens, no visible scrollbar clutter. */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="font-body bg-white text-charcoal antialiased">

    @php

        $categories = [
            [
                'name' => 'Pet Supplies',
                'path' => 'M4 9a2 2 0 114 0 2 2 0 01-4 0zm5-3a2 2 0 114 0 2 2 0 01-4 0zm5 3a2 2 0 114 0 2 2 0 01-4 0zM6.5 13a2.5 2.5 0 115 0 2.5 2.5 0 01-5 0zM12 15c2.5 0 5 1.5 5 3.5S14.5 21 12 21s-5-.5-5-2.5S9.5 15 12 15z',
                'iconBg' => 'bg-amber-50',
                'iconText' => 'text-amber-600',
                'hoverBg' => 'group-hover:bg-amber-600',
                'subcategories' => ['Dog Food & Treats', 'Cat Litter & Accessories', 'Aquariums & Fish Supplies', 'Bird Feeders & Food', 'Pet Grooming Products', 'Pet Health & Wellness'],
            ],
            [
                'name' => 'Electronics and Gadgets',
                'path' => 'M9 3h6v4H9zM5 7h14v14H5zM9 11h6M9 15h6',
                'iconBg' => 'bg-blue-50',
                'iconText' => 'text-blue-600',
                'hoverBg' => 'group-hover:bg-blue-600',
                'subcategories' => ['Mobile Phones & Accessories', 'Laptops, Desktops & Monitors', 'Audio & Video Equipment', 'Smart Home Devices', 'Cameras & Photography', 'Wearable Technology'],
            ],
            [
                'name' => "Women's Apparel",
                'path' => 'M9 3l3 2 3-2-1 4h-4L9 3zM8 7l-3 13h14L16 7l-2 3h-4L8 7z',
                'iconBg' => 'bg-pink-50',
                'iconText' => 'text-pink-600',
                'hoverBg' => 'group-hover:bg-pink-600',
                'subcategories' => ['Dresses & Skirts', 'Tops & Blouses', 'Activewear & Yoga Pants', 'Lingerie & Sleepwear', 'Jackets & Coats', 'Shoes & Accessories'],
            ],
            [
                'name' => "Men's Apparel",
                'path' => 'M8 4l4 2 4-2 3 4-3 2v11H8V10L5 8l3-4z',
                'iconBg' => 'bg-indigo-50',
                'iconText' => 'text-indigo-600',
                'hoverBg' => 'group-hover:bg-indigo-600',
                'subcategories' => ['Suits & Blazers', 'Casual Shirts & Pants', 'Outerwear & Jackets', 'Activewear & Fitness Gear', 'Shoes & Accessories', 'Grooming Products'],
            ],
            [
                'name' => 'Kids and Baby',
                'path' => 'M9 12h6m-6 4h6M9 5a3 3 0 116 0v2H9V5zM5 21a7 7 0 0114 0H5z',
                'iconBg' => 'bg-yellow-50',
                'iconText' => 'text-yellow-600',
                'hoverBg' => 'group-hover:bg-yellow-600',
                'subcategories' => ['Baby Clothes & Accessories', 'Toys & Games', 'Educational Materials', 'Strollers & Gear', 'Nursery Furniture', 'Safety and Health'],
            ],
            [
                'name' => 'Home and Garden',
                'path' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10',
                'iconBg' => 'bg-emerald-50',
                'iconText' => 'text-emerald-600',
                'hoverBg' => 'group-hover:bg-emerald-600',
                'subcategories' => ['Kitchen Appliances', 'Furniture & Decor', 'Gardening Tools', 'Outdoor Living', 'Home Improvement Tools', 'Bedding & Bath'],
            ],
            [
                'name' => 'Sports and Outdoors',
                'path' => 'M12 3a9 9 0 100 18 9 9 0 000-18zm0 0v18m9-9H3m14.5-6.5l-13 13m13 0l-13-13',
                'iconBg' => 'bg-orange-50',
                'iconText' => 'text-orange-600',
                'hoverBg' => 'group-hover:bg-orange-600',
                'subcategories' => ['Fitness Equipment', 'Camping & Hiking Gear', 'Sports Apparel', 'Cycling & Bikes', 'Water Sports', 'Team Sports Equipment'],
            ],
            [
                'name' => 'Health and Beauty',
                'path' => 'M12 21s-7-4.5-9.5-9A5.5 5.5 0 0112 5a5.5 5.5 0 019.5 7c-2.5 4.5-9.5 9-9.5 9z',
                'iconBg' => 'bg-fuchsia-50',
                'iconText' => 'text-fuchsia-600',
                'hoverBg' => 'group-hover:bg-fuchsia-600',
                'subcategories' => ['Skincare Products', 'Haircare Solutions', 'Makeup & Cosmetics', 'Personal Care Appliances', "Men's Grooming", 'Health Supplements'],
            ],
            [
                'name' => 'Books and Media',
                'path' => 'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20V4a2 2 0 00-2-2H6.5A2.5 2.5 0 004 4.5v15z',
                'iconBg' => 'bg-violet-50',
                'iconText' => 'text-violet-600',
                'hoverBg' => 'group-hover:bg-violet-600',
                'subcategories' => ['Fiction & Non-Fiction Books', 'Magazines & Periodicals', 'Music CDs & Vinyl Records', 'Movie DVDs & Blu-ray', 'Video Games & Consoles', 'Educational DVDs'],
            ],
            [
                'name' => 'Food and Gourmet',
                'path' => 'M7 2v7a2 2 0 002 2v11M7 2a2 2 0 00-2 2v5a2 2 0 002 2M11 2v20M16 2c-1.5 0-3 1.5-3 4v4c0 1.5 1 2 2 2v10',
                'iconBg' => 'bg-red-50',
                'iconText' => 'text-red-600',
                'hoverBg' => 'group-hover:bg-red-600',
                'subcategories' => ['Baking Supplies & Ingredients', 'Coffee, Tea & Beverages', 'Snacks & Candy', 'Specialty Foods & International Cuisine', 'Organic and Health Foods', 'Meal Kits & Prepped Foods'],
            ],
            [
                'name' => 'Automotive & Motorcycle',
                'path' => 'M5 17h14M7 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0M5 17l1.5-5.5A2 2 0 018.4 10h7.2a2 2 0 011.9 1.5L19 17M7 10V6a1 1 0 011-1h8a1 1 0 011 1v4',
                'iconBg' => 'bg-slate-100',
                'iconText' => 'text-slate-600',
                'hoverBg' => 'group-hover:bg-slate-600',
                'subcategories' => ['Protective Gear', 'Maintenance & Repair Tools', 'Parts & Accessories', 'Electrical Components', 'Tires, Wheels, and Fluids'],
            ],
            [
                'name' => 'Furniture and Office Equipment',
                'path' => 'M4 6h16v2H4zM6 8v10M18 8v10M4 14h16M9 18v2M15 18v2',
                'iconBg' => 'bg-teal-50',
                'iconText' => 'text-teal-600',
                'hoverBg' => 'group-hover:bg-teal-600',
                'subcategories' => ['Office Desks & Chairs', 'Storage Cabinets & Shelving', 'Conference & Meeting Furniture', 'Computer Tables & Workstations', 'Ergonomic Accessories', 'Office Lighting & Fixtures'],
            ],
            [
                'name' => 'Jewelry and Watches',
                'path' => 'M12 2l4 5-4 15-4-15 4-5zM8 7h8',
                'iconBg' => 'bg-amber-100',
                'iconText' => 'text-amber-700',
                'hoverBg' => 'group-hover:bg-amber-700',
                'subcategories' => ['Necklaces & Pendants', 'Rings & Earrings', 'Bracelets & Bangles', 'Watches for Men & Women', 'Fashion Jewelry', 'Jewelry Storage & Care'],
            ],
            [
                'name' => 'Office and School Supplies',
                'path' => 'M4 20l4-1 11-11-3-3L5 16l-1 4zm12-14l3 3',
                'iconBg' => 'bg-cyan-50',
                'iconText' => 'text-cyan-600',
                'hoverBg' => 'group-hover:bg-cyan-600',
                'subcategories' => ['Notebooks & Paper Products', 'Writing Instruments', 'Office Furniture', 'Printers & Printing Supplies', 'School Bags & Backpacks', 'Arts & Craft Materials'],
            ],
        ];
    @endphp

    <!-- =========================================================
         HEADER
         Utility strip + search, mirrored from the buyer dashboard
         so the marketplace feels like one continuous product.
    ========================================================== -->
    <header class="sticky top-0 z-50 shadow-sm">

        <!-- Utility strip -->
        <div class="bg-charcoal text-white/80">
            <div class="mx-auto flex h-9 max-w-7xl items-center justify-between px-5 text-xs sm:px-8">
                <div class="flex items-center gap-5">
                    <a href="{{ route('register.seller') }}" class="hover:text-white">Sell on Shopleap</a>
                    <a href="#" class="hidden hover:text-white sm:inline">Track My Order</a>
                    <a href="#" class="hidden hover:text-white sm:inline">Help Center</a>
                </div>
                <div class="flex items-center gap-2 text-white/70">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M12 21c4-4.2 7-7.9 7-11.3A7 7 0 005 9.7C5 13.1 8 16.8 12 21z" />
                        <circle cx="12" cy="9.5" r="2.3" stroke-width="1.8" />
                    </svg>
                    <span>Delivering nationwide, Philippines</span>
                </div>
            </div>
        </div>

        <!-- Main row: logo, search, auth -->
        <div class="border-b border-light-gray bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-5 py-3.5 sm:px-8">

                <a href="/" class="flex shrink-0 items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-light-gray">
                        {{-- LOGO: drop the real mark at public/images/shopleap-logo.png, same file used in the footer below --}}
                        <img src="{{ asset('storage\images\Shopleap Logo.png') }}" alt="Shopleap"
                            class="h-full w-full object-contain">
                    </div>
                    <div class="hidden sm:block">
                        <p class="font-display text-xl font-bold leading-none tracking-tight text-charcoal">Shopleap</p>
                        <p class="mt-1 text-[11px] text-charcoal/55">Shop Smart. Shop Simple.</p>
                    </div>
                </a>

                <!-- Search bar with a lightweight suggestions panel, adapted from the buyer dashboard -->
                <div class="relative order-3 w-full flex-1 sm:order-none sm:w-auto" id="searchWrapper">
                    <form action="{{ route('register') }}" method="GET"
                        class="flex overflow-hidden rounded-lg border border-light-gray focus-within:border-primary">
                        <input type="text" id="searchInput" name="q" autocomplete="off"
                            placeholder="Search products, brands, and sellers"
                            class="w-full px-4 py-2.5 text-sm text-charcoal outline-none">
                        <button type="submit"
                            class="flex shrink-0 items-center justify-center bg-primary px-5 text-white transition hover:bg-sky-blue">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>

                    <div id="searchSuggestions"
                        class="absolute left-0 right-0 top-full z-40 mt-1.5 hidden rounded-lg border border-light-gray bg-white p-3 shadow-lg">
                        <p class="mb-2 text-xs font-semibold text-charcoal/45">Popular right now</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['wireless earbuds', 'running shoes', 'skincare set', 'desk lamp', 'rice cooker'] as $trend)
                                <button type="button"
                                    class="rounded-full bg-ice-blue px-3 py-1 text-xs text-charcoal/70 transition hover:bg-primary/10 hover:text-primary">
                                    {{ $trend }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-3">
                    <a href="{{ route('login') }}"
                        class="rounded-lg px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-ice-blue">
                        Login
                    </a>
                    <a href="{{ route('register') }}"
                        class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-blue">
                        Register
                    </a>
                </div>

                <!-- Mobile menu toggle -->
                <button id="mobile-menu-button" type="button" aria-label="Open navigation menu" aria-expanded="false"
                    class="order-2 rounded-lg p-2 text-charcoal transition hover:bg-ice-blue sm:hidden">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Secondary nav strip -->
        <nav class="hidden border-b border-light-gray bg-ice-blue sm:block">
            <div
                class="mx-auto flex max-w-7xl items-center gap-7 px-5 py-2.5 text-sm font-medium text-charcoal/75 sm:px-8">
                <a href="#home" class="hover:text-primary">Home</a>
                <a href="#categories" class="hover:text-primary">Categories</a>
                <a href="#deals" class="hover:text-primary">Today's Deals</a>
                <a href="#about" class="hover:text-primary">About</a>
                <a href="#why-shopleap" class="hover:text-primary">Why Shopleap?</a>
                <a href="{{ route('register.seller') }}"
                    class="ml-auto font-semibold text-leaf-green hover:text-leaf-green/80">Become a Seller</a>
            </div>
        </nav>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden border-b border-light-gray bg-white sm:hidden">
            <div class="space-y-1 px-5 py-4">
                <a href="#home"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue">Home</a>
                <a href="#categories"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue">Categories</a>
                <a href="#deals"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue">Today's
                    Deals</a>
                <a href="#about"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue">About</a>
                <a href="#why-shopleap"
                    class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue">Why
                    Shopleap?</a>
                <a href="{{ route('register.seller') }}"
                    class="block rounded-lg px-4 py-3 text-sm font-semibold text-leaf-green hover:bg-ice-blue">Become a
                    Seller</a>
                <div class="mt-3 grid grid-cols-2 gap-3 border-t border-light-gray pt-4">
                    <a href="{{ route('login') }}"
                        class="rounded-lg border border-primary px-4 py-3 text-center text-sm font-semibold text-primary hover:bg-ice-blue">Login</a>
                    <a href="{{ route('register') }}"
                        class="rounded-lg bg-primary px-4 py-3 text-center text-sm font-semibold text-white hover:bg-sky-blue">Register</a>
                </div>
            </div>
        </div>
    </header>


    <main>

        <!-- =========================================================
             HERO — rotating deal carousel + category rail underneath.
        ========================================================== -->
        <section id="home" class="bg-primary">
            <div class="relative mx-auto max-w-7xl overflow-hidden px-5 py-10 sm:px-8 sm:py-14" id="heroCarousel">

                @php
                    $slides = [
                        [
                            'eyebrow' => 'New here? Get ₱150 off your first order',
                            'headline' => 'Everything you need, from sellers you can trust.',
                            'copy' =>
                                'Browse thousands of listings across fashion, electronics, home, and more — with buyer protection on every order.',
                            'cta' => 'Start Shopping',
                            // Drop a 4:3 photo at this path (or swap for a real asset()/CDN url).
                            'image' => asset('storage\images\hero\Landing 1.png'),
                        ],
                        [
                            'eyebrow' => 'Payday Sale · Sept 9–15',
                            'headline' => 'Up to 40% off home and living essentials.',
                            'copy' =>
                                'Restock the kitchen, refresh the living room, or finally get that desk lamp — all with cash-on-delivery available.',
                            'cta' => 'See the Deals',
                            'image' => asset('storage\images\hero\Landing 2.png'),
                        ],
                        [
                            'eyebrow' => 'For sellers',
                            'headline' => 'Turn your stock into a storefront.',
                            'copy' =>
                                'List your products, reach shoppers nationwide, and get paid securely — no setup fees to start.',
                            'cta' => 'Become a Seller',
                            'image' => asset('storage\images\hero\Landing 3.png'),
                        ],
                    ];
                @endphp

                <div class="relative min-h-[280px] sm:min-h-[260px]">
                    @foreach ($slides as $i => $slide)
                        <div class="hero-slide @if ($i === 0) is-active @endif"
                            data-slide="{{ $i }}">
                            <div class="grid items-center gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                                <div class="max-w-xl">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-white">
                                        <span class="h-1.5 w-1.5 rounded-full bg-leaf-green"></span>
                                        {{ $slide['eyebrow'] }}
                                    </span>
                                    <h2
                                        class="font-display mt-5 text-3xl font-bold leading-tight text-white sm:text-4xl lg:text-[2.6rem]">
                                        {{ $slide['headline'] }}
                                    </h2>
                                    <p class="mt-4 max-w-md text-[15px] leading-7 text-white/80">
                                        {{ $slide['copy'] }}
                                    </p>
                                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                                        <a href="{{ route('register') }}"
                                            class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-sm font-bold text-primary transition hover:bg-ice-blue">
                                            {{ $slide['cta'] }}
                                        </a>
                                        <a href="#deals"
                                            class="inline-flex items-center justify-center rounded-lg border border-white/40 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                                            Browse Deals
                                        </a>
                                    </div>
                                </div>

                                <!-- Deal photo — plain <img>, just point it at the real asset -->
                                <div class="hidden lg:block">
                                    <div
                                        class="ml-auto aspect-[4/3] max-w-sm overflow-hidden rounded-2xl border border-white/15 bg-white/5">
                                        <img src="{{ $slide['image'] }}" alt="{{ $slide['headline'] }}"
                                            class="h-full w-full object-cover" loading="lazy">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex justify-center gap-2 lg:justify-start" id="heroDots">
                    @foreach ($slides as $i => $slide)
                        <button type="button" class="hero-dot @if ($i === 0) is-active @endif"
                            data-dot="{{ $i }}" aria-label="Show slide {{ $i + 1 }}"></button>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Trust strip — the fastest way to signal "this is a real marketplace."
             Each badge now gets its own color instead of one flat primary tint,
             so the icon color hints at the badge's meaning (protection = green,
             verified = blue, payments = purple, delivery = orange). -->
        <section class="border-b border-light-gray bg-white">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-5 py-6 sm:px-8 lg:grid-cols-4">
                @php
                    $trust = [
                        [
                            'label' => 'Buyer Protection',
                            'sub' => 'Refund if items don\'t arrive as described',
                            'path' => 'M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z',
                            'bg' => 'bg-emerald-50',
                            'text' => 'text-emerald-600',
                        ],
                        [
                            'label' => 'Verified Sellers',
                            'sub' => 'Every seller account is reviewed',
                            'path' => 'M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z',
                            'bg' => 'bg-blue-50',
                            'text' => 'text-blue-600',
                        ],
                        [
                            'label' => 'Secure Payments',
                            'sub' => 'GCash, Maya, cards, and cash on delivery',
                            'path' => 'M3 7h18M5 7v12h14V7M8 7V5a4 4 0 018 0v2',
                            'bg' => 'bg-purple-50',
                            'text' => 'text-purple-600',
                        ],
                        [
                            'label' => 'Nationwide Delivery',
                            'sub' => 'Tracked shipping to any address',
                            'path' =>
                                'M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z',
                            'bg' => 'bg-orange-50',
                            'text' => 'text-orange-600',
                        ],
                    ];
                @endphp
                @foreach ($trust as $item)
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $item['bg'] }} {{ $item['text'] }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="{{ $item['path'] }}" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-charcoal">{{ $item['label'] }}</p>
                            <p class="mt-0.5 text-xs leading-5 text-charcoal/55">{{ $item['sub'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>


        <!-- =========================================================
             CATEGORY RAIL — same circular-icon language as the buyer
             dashboard's category grid, now with a distinct color per
             category (see the $categories array above) instead of
             every icon sharing the same primary-blue tint.
        ========================================================== -->
        <section id="categories" class="bg-white py-14">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="flex items-end justify-between">
                    <h2 class="font-display text-2xl font-bold text-charcoal sm:text-3xl">Browse categories</h2>
                    <a href="{{ route('register') }}"
                        class="hidden text-sm font-semibold text-primary hover:underline sm:inline">See all</a>
                </div>

                <div
                    class="no-scrollbar mt-8 flex gap-6 overflow-x-auto pb-2 sm:grid sm:grid-cols-4 sm:gap-x-4 sm:gap-y-8 sm:overflow-visible lg:grid-cols-7">
                    @foreach ($categories as $category)
                        <a href="{{ route('register') }}"
                            class="group flex w-20 shrink-0 flex-col items-center gap-2.5 text-center sm:w-auto">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full {{ $category['iconBg'] }} {{ $category['iconText'] }} transition {{ $category['hoverBg'] }} group-hover:text-white">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                        d="{{ $category['path'] }}" />
                                </svg>
                            </div>
                            <span
                                class="text-xs leading-tight text-charcoal/70 group-hover:text-primary">{{ $category['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>


        <!-- =========================================================
             TODAY'S DEALS — product cards styled like the buyer
             dashboard's product grid (₱ pricing, discount tag, rating).
        ========================================================== -->
        <section id="deals" class="bg-ice-blue py-14">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="flex items-end justify-between">
                    <div>
                        <h2 class="font-display text-2xl font-bold text-charcoal sm:text-3xl">Today's deals</h2>
                        <p class="mt-1.5 text-sm text-charcoal/60">A preview of what's selling right now — create an
                            account to shop these.</p>
                    </div>
                    <a href="{{ route('register') }}"
                        class="hidden text-sm font-semibold text-primary hover:underline sm:inline">See all</a>
                </div>

                @php
                    $deals = [
                        [
                            'name' => 'Wireless Bluetooth Earbuds',
                            'price' => 899,
                            'was' => 1299,
                            'rating' => 4.8,
                            'sold' => '2.3k sold',
                            'image' => asset('storage\images\products\Earbuds.png'),
                        ],
                        [
                            'name' => 'Running Shoes, Unisex',
                            'price' => 1650,
                            'was' => null,
                            'rating' => 4.6,
                            'sold' => '860 sold',
                            'image' => asset('storage\images\products\shoes.png'),
                        ],
                        [
                            'name' => 'Ceramic Non-Stick Pan Set',
                            'price' => 1120,
                            'was' => 1580,
                            'rating' => 4.9,
                            'sold' => '1.1k sold',
                            'image' => asset('storage\images\products\nonstick.png'),
                        ],
                        [
                            'name' => 'Minimalist Desk Lamp',
                            'price' => 540,
                            'was' => null,
                            'rating' => 4.7,
                            'sold' => '540 sold',
                            'image' => asset('storage\images\products\lamp.png'),
                        ],
                        [
                            'name' => '10,000mAh Power Bank',
                            'price' => 799,
                            'was' => 999,
                            'rating' => 4.7,
                            'sold' => '3.4k sold',
                            'image' => asset('storage\images\products\powerbank.png'),
                        ],
                        [
                            'name' => 'Everyday Tote Bag',
                            'price' => 450,
                            'was' => null,
                            'rating' => 4.5,
                            'sold' => '310 sold',
                            'image' => asset('storage\images\products\totebag.png'),
                        ],
                    ];
                @endphp

                <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($deals as $item)
                        @php
                            $discount = $item['was'] ? round((1 - $item['price'] / $item['was']) * 100) : null;
                        @endphp
                        <a href="{{ route('register') }}"
                            class="group overflow-hidden rounded-lg border border-light-gray bg-white transition hover:shadow-md">
                            <div class="relative aspect-square overflow-hidden bg-ice-blue">
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}"
                                    class="h-full w-full object-cover" loading="lazy">
                                @if ($discount)
                                    <span
                                        class="absolute left-2 top-2 rounded bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white">-{{ $discount }}%</span>
                                @endif
                            </div>
                            <div class="p-3">
                                <p class="line-clamp-2 text-xs leading-5 text-charcoal">{{ $item['name'] }}</p>
                                <div class="mt-2 flex items-baseline gap-1.5">
                                    <span
                                        class="text-sm font-bold text-primary">₱{{ number_format($item['price']) }}</span>
                                    @if ($item['was'])
                                        <span
                                            class="text-[11px] text-charcoal/40 line-through">₱{{ number_format($item['was']) }}</span>
                                    @endif
                                </div>
                                <div class="mt-1.5 flex items-center gap-1 text-[11px] text-charcoal/50">
                                    <span class="text-[#F5A623]">★</span>
                                    <span>{{ $item['rating'] }}</span>
                                    <span>·</span>
                                    <span>{{ $item['sold'] }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>


        <!-- =========================================================
             ABOUT — text-only now that the placeholder photo is gone.
             Centered as a single column instead of the old 2-col grid.
        ========================================================== -->
        <section id="about" class="bg-white py-16">
            <div class="mx-auto max-w-3xl px-5 text-center sm:px-8">
                <span class="text-sm font-semibold text-primary">About Shopleap</span>
                <h2 class="font-display mt-2 text-2xl font-bold leading-tight text-charcoal sm:text-3xl">
                    A marketplace built around trust, not just transactions.
                </h2>
                <p class="mt-5 leading-7 text-charcoal/70">
                    Shopleap connects everyday shoppers with sellers across the Philippines — from small
                    home businesses to established brands. Every seller account is reviewed before they can
                    list, and every order is covered by our buyer protection policy.
                </p>
                <p class="mt-4 leading-7 text-charcoal/70">
                    Whether you're restocking essentials or looking for something specific, Shopleap keeps
                    browsing, checkout, and delivery tracking in one simple place.
                </p>

                <div class="mx-auto mt-8 grid max-w-lg grid-cols-3 gap-6 border-t border-light-gray pt-6">
                    <div>
                        <p class="font-display text-2xl font-bold text-charcoal">12k+</p>
                        <p class="mt-1 text-xs text-charcoal/55">Active sellers</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl font-bold text-charcoal">80</p>
                        <p class="mt-1 text-xs text-charcoal/55">Provinces reached</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl font-bold text-charcoal">4.7/5</p>
                        <p class="mt-1 text-xs text-charcoal/55">Average buyer rating</p>
                    </div>
                </div>
            </div>
        </section>


        <!-- =========================================================
             WHY SHOPLEAP
        ========================================================== -->
        <section id="why-shopleap" class="bg-ice-blue py-16">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="max-w-xl">
                    <span class="text-sm font-semibold text-primary">Why Shopleap?</span>
                    <h2 class="font-display mt-2 text-2xl font-bold text-charcoal sm:text-3xl">
                        The details that make shopping feel safe.
                    </h2>
                </div>

                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $features = [
                            [
                                'title' => 'Reviewed Sellers',
                                'desc' => 'Every seller completes an approval process before their store goes live.',
                            ],
                            [
                                'title' => 'Order Protection',
                                'desc' => 'Full refund if an order doesn\'t arrive or doesn\'t match its listing.',
                            ],
                            [
                                'title' => 'Real-Time Tracking',
                                'desc' => 'Follow every order from checkout to your doorstep, courier by courier.',
                            ],
                            [
                                'title' => 'Flexible Payments',
                                'desc' => 'Pay with GCash, Maya, major cards, or cash on delivery.',
                            ],
                        ];
                    @endphp
                    @foreach ($features as $feature)
                        <div class="rounded-xl bg-white p-6">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-leaf-green/15 text-leaf-green">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="mt-4 font-semibold text-charcoal">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-charcoal/60">{{ $feature['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>


        <!-- =========================================================
             SELLER CTA
        ========================================================== -->
        <section class="bg-primary">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center gap-6 px-5 py-14 text-center sm:px-8 lg:flex-row lg:text-left">
                <div class="flex-1">
                    <h2 class="font-display text-2xl font-bold text-white sm:text-3xl">Have something to sell?</h2>
                    <p class="mx-auto mt-3 max-w-xl text-white/80 lg:mx-0">
                        Set up your storefront in minutes and reach shoppers across the Philippines —
                        no listing fees to get started.
                    </p>
                </div>
                <a href="{{ route('register.seller') }}"
                    class="shrink-0 rounded-lg bg-white px-7 py-3.5 text-sm font-bold text-primary transition hover:bg-ice-blue">
                    Become a Seller
                </a>
            </div>
        </section>

    </main>


    <!-- =========================================================
         FOOTER — payment/delivery badges borrowed from the buyer
         dashboard footer, plus the full category + subcategory
         sitemap (all 14 categories, shares the $categories array
         defined above so the rail and footer never drift apart).
    ========================================================== -->
    <footer class="bg-charcoal text-white">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8">

            <!-- CATEGORY SITEMAP -->
            <div class="mb-10 border-b border-white/10 pb-10">
                <p class="mb-5 text-sm font-bold">Shop by Category</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($categories as $category)
                        <div>
                            <a href="{{ route('register') }}"
                                class="mb-2 block text-xs font-bold text-white hover:text-white/80">
                                {{ $category['name'] }}
                            </a>
                            <ul class="space-y-1.5">
                                @foreach ($category['subcategories'] as $sub)
                                    <li><a href="{{ route('register') }}"
                                            class="text-xs text-white/55 hover:text-white">{{ $sub }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-5">

                <div class="lg:col-span-2">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-white/10">
                            {{-- Same logo asset as the header --}}
                            <img src="{{ asset('storage\images\Shopleap Logo.png') }}" alt="Shopleap"
                                class="h-full w-full object-contain">
                        </div>
                        <span class="font-display text-xl font-bold">Shopleap</span>
                    </div>
                    <p class="mt-4 max-w-sm text-sm leading-6 text-white/60">
                        An online marketplace connecting Filipino shoppers with reviewed sellers —
                        secure payments, tracked delivery, and support when something goes wrong.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Customer Service</h3>
                    <ul class="mt-4 space-y-2.5 text-sm text-white/60">
                        <li><a href="#" class="hover:text-white">Help Center</a></li>
                        <li><a href="#" class="hover:text-white">How to Buy</a></li>
                        <li><a href="#" class="hover:text-white">Returns & Refunds</a></li>
                        <li><a href="#" class="hover:text-white">Contact Us</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Company</h3>
                    <ul class="mt-4 space-y-2.5 text-sm text-white/60">
                        <li><a href="#about" class="hover:text-white">About Us</a></li>
                        <li><a href="{{ route('register.seller') }}" class="hover:text-white">Sell on Shopleap</a>
                        </li>
                        <li><a href="#" class="hover:text-white">Terms & Policies</a></li>
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Payments & Delivery</h3>
                    <p class="mt-4 text-xs uppercase tracking-wide text-white/40">Payment methods</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (['GCash', 'Maya', 'Visa', 'Mastercard', 'COD'] as $payment)
                            <span
                                class="rounded border border-white/15 px-2 py-1 text-[10px] font-semibold text-white/70">{{ $payment }}</span>
                        @endforeach
                    </div>
                    <p class="mt-4 text-xs uppercase tracking-wide text-white/40">Delivery partners</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (['J&T', 'LBC', 'Ninja Van', 'Flash'] as $courier)
                            <span
                                class="rounded border border-white/15 px-2 py-1 text-[10px] font-semibold text-white/70">{{ $courier }}</span>
                        @endforeach
                    </div>
                </div>

            </div>

            <div
                class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-white/45 sm:flex-row">
                <p>&copy; {{ date('Y') }} Shopleap. All rights reserved.</p>
                <p>Shop Smart. Shop Simple.</p>
            </div>
        </div>
    </footer>


    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuButton.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.contains('hidden');
            mobileMenu.classList.toggle('hidden');
            mobileMenuButton.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                mobileMenuButton.setAttribute('aria-expanded', 'false');
            });
        });

        // Search suggestions panel
        const searchWrapper = document.getElementById('searchWrapper');
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        searchInput.addEventListener('focus', () => searchSuggestions.classList.remove('hidden'));
        document.addEventListener('click', (e) => {
            if (!searchWrapper.contains(e.target)) searchSuggestions.classList.add('hidden');
        });

        // Hero deal carousel — auto-advances, pauses on hover, and is manually controllable.
        (function() {
            const root = document.getElementById('heroCarousel');
            const slides = Array.from(root.querySelectorAll('.hero-slide'));
            const dots = Array.from(root.querySelectorAll('.hero-dot'));
            let index = 0;
            let timer;

            function show(i) {
                slides[index].classList.remove('is-active');
                dots[index].classList.remove('is-active');
                index = (i + slides.length) % slides.length;
                slides[index].classList.add('is-active');
                dots[index].classList.add('is-active');
            }

            function start() {
                timer = setInterval(() => show(index + 1), 5000);
            }

            dots.forEach((dot, i) => dot.addEventListener('click', () => {
                show(i);
                clearInterval(timer);
                start();
            }));

            root.addEventListener('mouseenter', () => clearInterval(timer));
            root.addEventListener('mouseleave', start);

            start();
        })();
    </script>

</body>

</html>
