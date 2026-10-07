<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Shopleap is an online marketplace connecting Filipino shoppers with trusted sellers — secure payments, nationwide delivery, and everyday deals.">
    <title>Shopleap | Shop Smart. Shop Simple.</title>

    <link rel="icon" type="image/png" href="{{ asset('images/shopleap-logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js', 'resources/js/landing.js'])
</head>

<body class="font-body bg-white text-charcoal antialiased">

    @php
        $categories = config('landing.categories');
        $logo = asset('images/shopleap-logo.png');

        $riderRegisterUrl = Route::has('register.rider')
            ? route('register.rider')
            : (Route::has('register.courier')
                ? route('register.courier')
                : route('register'));

        // A slide/CTA target is a route name, "rider", or a "#anchor".
        $resolveTarget = fn(string $target) => match (true) {
            $target === 'rider' => $riderRegisterUrl,
            str_starts_with($target, '#') => $target,
            default => route($target),
        };
    @endphp

    {{-- ================= HEADER ================= --}}
    <header class="sticky top-0 z-50 shadow-sm">

        <div class="bg-charcoal text-white/80">
            <div
                class="mx-auto flex h-9 max-w-7xl items-center justify-between px-5 text-xs sm:px-8 2xl:max-w-[1600px]">
                <div class="flex items-center gap-5">
                    <a href="{{ route('register.seller') }}" class="hover:text-white">Sell on Shopleap</a>
                    <a href="{{ $riderRegisterUrl }}" class="hover:text-white">Ride with Shopleap</a>
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

        <div class="border-b border-light-gray bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-5 py-3.5 sm:px-8 2xl:max-w-[1600px]">

                <a href="/" class="flex shrink-0 items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-light-gray">
                        <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap"
                            class="h-full w-full object-contain">
                    </div>
                    <div class="hidden sm:block">
                        <p class="font-display text-xl font-bold leading-none tracking-tight text-charcoal">Shopleap</p>
                        <p class="mt-1 text-[11px] text-charcoal/55">Shop Smart. Shop Simple.</p>
                    </div>
                </a>

                <div class="relative order-3 w-full flex-1 sm:order-none sm:w-auto" id="searchWrapper">
                    <form action="{{ route('register') }}" method="GET"
                        class="flex overflow-hidden rounded-lg border border-light-gray focus-within:border-primary">
                        <input type="text" id="searchInput" name="q" autocomplete="off"
                            placeholder="Search products, brands, and sellers"
                            class="w-full px-4 py-2.5 text-sm text-charcoal outline-none">
                        <button type="submit" aria-label="Search"
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
                            @foreach (config('landing.trending_searches') as $trend)
                                <button type="button" data-trend="{{ $trend }}"
                                    class="rounded-full bg-ice-blue px-3 py-1 text-xs text-charcoal/70 transition hover:bg-primary/10 hover:text-primary">
                                    {{ $trend }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-3">
                    <a href="{{ route('login') }}"
                        class="rounded-lg px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-ice-blue">Login</a>
                    <a href="{{ route('register') }}"
                        class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-blue">Register</a>
                </div>

                <button id="mobile-menu-button" type="button" aria-label="Open navigation menu" aria-expanded="false"
                    class="order-2 rounded-lg p-2 text-charcoal transition hover:bg-ice-blue sm:hidden">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <nav class="hidden border-b border-light-gray bg-ice-blue sm:block">
            <div
                class="mx-auto flex max-w-7xl items-center gap-7 px-5 py-2.5 text-sm font-medium text-charcoal/75 sm:px-8 2xl:max-w-[1600px]">
                @foreach (config('landing.nav') as $link)
                    <a href="{{ $link['href'] }}" class="hover:text-primary">{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ route('register.seller') }}"
                    class="ml-auto font-semibold text-leaf-green hover:text-leaf-green/80">Become a Seller</a>
            </div>
        </nav>

        <div id="mobile-menu" class="hidden border-b border-light-gray bg-white sm:hidden">
            <div class="space-y-1 px-5 py-4">
                @foreach (config('landing.nav') as $link)
                    <a href="{{ $link['href'] }}"
                        class="block rounded-lg px-4 py-3 text-sm font-medium text-charcoal hover:bg-ice-blue">{{ $link['label'] }}</a>
                @endforeach
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

        {{-- ================= HERO ================= --}}
        <section id="home" class="hero-bg relative overflow-hidden bg-primary">
            <div class="relative mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:py-12 2xl:max-w-[1600px]" id="heroCarousel">

                {{-- All slides share one grid cell, so the hero is always as tall as its tallest slide. --}}
                <div class="hero-stack">
                    @foreach (config('landing.slides') as $i => $slide)
                        <div class="hero-slide @if ($i === 0) is-active @endif">
                            <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
                                <div class="max-w-xl">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-white">
                                        <span class="h-1.5 w-1.5 rounded-full bg-leaf-green"></span>
                                        {{ $slide['eyebrow'] }}
                                    </span>
                                    <h2
                                        class="font-display mt-5 text-3xl font-bold leading-tight text-white sm:text-4xl lg:text-[2.6rem]">
                                        {{ $slide['headline'] }}</h2>
                                    <p class="mt-4 max-w-md text-[15px] leading-7 text-white/80">{{ $slide['copy'] }}
                                    </p>
                                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                                        <a href="{{ $resolveTarget($slide['cta_target']) }}"
                                            class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-sm font-bold text-primary transition hover:bg-ice-blue">{{ $slide['cta'] }}</a>
                                        <a href="#deals"
                                            class="inline-flex items-center justify-center rounded-lg border border-white/40 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Browse
                                            Deals</a>
                                    </div>
                                </div>

                                {{-- Image fills the whole right column and is shown on every screen size --}}
                                <div
                                    class="aspect-[16/10] w-full overflow-hidden rounded-2xl bg-white/10 shadow-2xl ring-1 ring-white/20 lg:aspect-[4/3]">
                                    <img src="{{ asset('images/hero/' . $slide['image']) }}"
                                        alt="{{ $slide['headline'] }}" class="h-full w-full object-cover"
                                        @if ($i > 0) loading="lazy" @endif data-fallback="hide">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-center gap-2 lg:justify-start" id="heroDots">
                    @foreach (config('landing.slides') as $i => $slide)
                        <button type="button" class="hero-dot @if ($i === 0) is-active @endif"
                            aria-label="Show slide {{ $i + 1 }}"></button>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================= TRUST STRIP ================= --}}
        <section class="reveal-section border-b border-light-gray bg-white">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-5 py-6 sm:px-8 lg:grid-cols-4 2xl:max-w-[1600px]">
                @foreach (config('landing.trust') as $item)
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

        {{-- ================= CATEGORIES ================= --}}
        <section id="categories" class="reveal-section bg-white py-14">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 2xl:max-w-[1600px]">
                <div class="flex items-end justify-between">
                    <h2 class="font-display text-2xl font-bold text-charcoal sm:text-3xl">Browse categories</h2>
                    <a href="{{ route('register') }}"
                        class="hidden text-sm font-semibold text-primary hover:underline sm:inline">See all</a>
                </div>

                <div
                    class="no-scrollbar mt-8 flex gap-6 overflow-x-auto pb-2 sm:grid sm:grid-cols-4 sm:gap-x-4 sm:gap-y-8 sm:overflow-visible lg:grid-cols-7">
                    @foreach ($categories as $category)
                        <a href="{{ route('register') }}"
                            class="reveal group flex w-20 shrink-0 flex-col items-center gap-2.5 text-center sm:w-auto">
                            <div
                                class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full {{ $category['iconBg'] }} ring-1 ring-inset ring-black/5 transition group-hover:ring-primary/30">
                                <img src="{{ asset('images/categories/' . $category['slug'] . '.png') }}"
                                    alt="{{ $category['name'] }}" loading="lazy" data-fallback="hide"
                                    class="h-full w-full object-cover">
                            </div>
                            <span
                                class="text-xs leading-tight text-charcoal/70 group-hover:text-primary">{{ $category['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================= TODAY'S DEALS ================= --}}
        <section id="deals" class="reveal-section bg-ice-blue py-14">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 2xl:max-w-[1600px]">
                <div class="flex items-end justify-between">
                    <div>
                        <h2 class="font-display text-2xl font-bold text-charcoal sm:text-3xl">Today's deals</h2>
                        <p class="mt-1.5 text-sm text-charcoal/60">A preview of what's selling right now — create an
                            account to shop these.</p>
                    </div>
                    <a href="{{ route('register') }}"
                        class="hidden text-sm font-semibold text-primary hover:underline sm:inline">See all</a>
                </div>

                <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @forelse ($deals as $product)
                        @php
                            $discount = $product->was_price
                                ? round((1 - $product->display_price / $product->was_price) * 100)
                                : null;
                        @endphp
                        <a href="{{ route('register') }}"
                            class="reveal group overflow-hidden rounded-lg border border-light-gray bg-white transition hover:shadow-md">
                            <div class="relative aspect-square overflow-hidden bg-ice-blue">
                                <img src="{{ $product->main_image_url ?? $logo }}" alt="{{ $product->name }}"
                                    class="h-full w-full object-cover" loading="lazy">
                                @if ($discount)
                                    <span
                                        class="absolute left-2 top-2 rounded bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white">-{{ $discount }}%</span>
                                @endif
                            </div>
                            <div class="p-3">
                                <p class="line-clamp-2 text-xs leading-5 text-charcoal">{{ $product->name }}</p>
                                <div class="mt-2 flex items-baseline gap-1.5">
                                    <span
                                        class="text-sm font-bold text-primary">₱{{ number_format($product->display_price, 2) }}</span>
                                    @if ($product->was_price)
                                        <span
                                            class="text-[11px] text-charcoal/40 line-through">₱{{ number_format($product->was_price, 2) }}</span>
                                    @endif
                                </div>
                                <p class="mt-1.5 truncate text-[11px] text-charcoal/50">
                                    {{ $product->seller?->messaging_name ?? 'Shopleap Seller' }}</p>
                            </div>
                        </a>
                    @empty
                        <p class="col-span-full text-center text-sm text-charcoal/50">Deals will appear here as sellers
                            list products.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ================= HOW IT WORKS ================= --}}
        <section id="how-it-works" class="reveal-section bg-white py-16">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 2xl:max-w-[1600px]">
                <div class="max-w-xl">
                    <span class="text-sm font-semibold text-primary">How It Works</span>
                    <h2 class="font-display mt-2 text-2xl font-bold text-charcoal sm:text-3xl">Three ways to be part of
                        Shopleap.</h2>
                </div>

                <div class="mt-10 grid gap-5 sm:grid-cols-3">
                    <div class="reveal rounded-xl border border-light-gray p-6">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M3 3h2l2.4 12.2a2 2 0 002 1.8h8.2a2 2 0 002-1.6L21 8H6" />
                                <circle cx="9" cy="20" r="1.3" />
                                <circle cx="17" cy="20" r="1.3" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-charcoal">Shop</h3>
                        <p class="mt-2 text-sm leading-6 text-charcoal/60">Browse listings across every category,
                            checkout securely, and track delivery to your door.</p>
                        <a href="{{ route('register') }}"
                            class="mt-4 inline-block text-sm font-semibold text-primary hover:underline">Create a buyer
                            account →</a>
                    </div>
                    <div class="reveal rounded-xl border border-light-gray p-6">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-leaf-green/15 text-leaf-green">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-charcoal">Sell</h3>
                        <p class="mt-2 text-sm leading-6 text-charcoal/60">List your products, reach shoppers
                            nationwide, and get paid securely with no setup fees.</p>
                        <a href="{{ route('register.seller') }}"
                            class="mt-4 inline-block text-sm font-semibold text-primary hover:underline">Become a
                            seller →</a>
                    </div>
                    <div class="reveal rounded-xl border border-light-gray p-6">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="6" cy="18" r="2.3" stroke-width="1.7" />
                                <circle cx="17" cy="18" r="2.3" stroke-width="1.7" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M6 18h6l3-8h3M12 10L9 18" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-charcoal">Ride</h3>
                        <p class="mt-2 text-sm leading-6 text-charcoal/60">Deliver orders on your own schedule, using
                            your own motorcycle or bike, and get paid weekly.</p>
                        <a href="{{ $riderRegisterUrl }}"
                            class="mt-4 inline-block text-sm font-semibold text-primary hover:underline">Apply to ride
                            →</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= RIDERS ================= --}}
        <section id="riders" class="reveal-section bg-charcoal">
            <div
                class="mx-auto grid max-w-7xl items-center gap-10 px-5 py-16 sm:px-8 lg:grid-cols-2 2xl:max-w-[1600px]">
                <div>
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-white">
                        <span class="h-1.5 w-1.5 rounded-full bg-leaf-green"></span>
                        Now hiring riders nationwide
                    </span>
                    <h2 class="font-display mt-5 text-2xl font-bold text-white sm:text-3xl">Ride with Shopleap, earn on
                        your own terms.</h2>
                    <p class="mt-4 max-w-md leading-7 text-white/70">
                        Deliver orders around your own schedule using your own motorcycle or bike.
                        No office hours, no quotas — just accept deliveries when you're free.
                    </p>

                    <ul class="mt-6 space-y-3">
                        @foreach (config('landing.rider_benefits') as $benefit)
                            <li class="flex items-start gap-2.5 text-sm text-white/80">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-leaf-green" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $benefit }}
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ $riderRegisterUrl }}"
                        class="mt-8 inline-flex items-center justify-center rounded-lg bg-white px-7 py-3.5 text-sm font-bold text-charcoal transition hover:bg-ice-blue">
                        Apply to Become a Rider
                    </a>
                </div>

                <div class="reveal">
                    <div class="aspect-[4/3] overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                        <img src="{{ asset('images/rider-hero.png') }}" alt="Shopleap rider"
                            class="h-full w-full object-cover" loading="lazy" data-fallback="hide">
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= ABOUT ================= --}}
        <section id="about" class="reveal-section bg-white py-16">
            <div class="mx-auto max-w-3xl px-5 text-center sm:px-8">
                <span class="text-sm font-semibold text-primary">About Shopleap</span>
                <h2 class="font-display mt-2 text-2xl font-bold leading-tight text-charcoal sm:text-3xl">A marketplace
                    built around trust, not just transactions.</h2>
                <p class="mt-5 leading-7 text-charcoal/70">
                    Shopleap connects everyday shoppers with sellers across the Philippines — from small
                    home businesses to established brands. Every seller account is reviewed before they can
                    list, and every order is covered by our buyer protection policy.
                </p>
                <p class="mt-4 leading-7 text-charcoal/70">
                    Whether you're restocking essentials, looking for something specific, or earning as a
                    delivery rider, Shopleap keeps the whole experience in one simple place.
                </p>

                <div class="mx-auto mt-8 grid max-w-lg grid-cols-3 gap-6 border-t border-light-gray pt-6">
                    <div>
                        <p class="font-display text-2xl font-bold text-charcoal">
                            {{ number_format($stats['sellers']) }}+</p>
                        <p class="mt-1 text-xs text-charcoal/55">Active sellers</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl font-bold text-charcoal">
                            {{ number_format($stats['riders']) }}+</p>
                        <p class="mt-1 text-xs text-charcoal/55">Delivery riders</p>
                    </div>
                    <div>
                        <p class="font-display text-2xl font-bold text-charcoal">
                            {{ number_format($stats['products']) }}</p>
                        <p class="mt-1 text-xs text-charcoal/55">Products listed</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= WHY SHOPLEAP ================= --}}
        <section id="why-shopleap" class="reveal-section bg-ice-blue py-16">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 2xl:max-w-[1600px]">
                <div class="max-w-xl">
                    <span class="text-sm font-semibold text-primary">Why Shopleap?</span>
                    <h2 class="font-display mt-2 text-2xl font-bold text-charcoal sm:text-3xl">The details that make
                        shopping feel safe.</h2>
                </div>

                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (config('landing.features') as $feature)
                        <div class="reveal rounded-xl bg-white p-6">
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

        {{-- ================= FAQ ================= --}}
        <section id="faq" class="reveal-section bg-white py-16">
            <div class="mx-auto max-w-3xl px-5 sm:px-8">
                <div class="text-center">
                    <span class="text-sm font-semibold text-primary">FAQ</span>
                    <h2 class="font-display mt-2 text-2xl font-bold text-charcoal sm:text-3xl">Common questions</h2>
                </div>

                <div class="mt-8 divide-y divide-light-gray border-t border-b border-light-gray">
                    @foreach (config('landing.faqs') as $faq)
                        <details class="faq-item group py-4">
                            <summary
                                class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-charcoal">
                                {{ $faq['q'] }}
                                <svg class="faq-chevron h-4 w-4 shrink-0 text-charcoal/40 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-sm leading-6 text-charcoal/60">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================= SELLER + RIDER CTA ================= --}}
        <section class="reveal-section bg-primary">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center gap-6 px-5 py-14 text-center sm:px-8 lg:flex-row lg:text-left 2xl:max-w-[1600px]">
                <div class="flex-1">
                    <h2 class="font-display text-2xl font-bold text-white sm:text-3xl">Have something to sell, or a
                        bike to ride?</h2>
                    <p class="mx-auto mt-3 max-w-xl text-white/80 lg:mx-0">
                        Set up your storefront or start delivering in minutes — no listing fees, no office hours.
                    </p>
                </div>
                <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register.seller') }}"
                        class="rounded-lg bg-white px-7 py-3.5 text-sm font-bold text-primary transition hover:bg-ice-blue">Become
                        a Seller</a>
                    <a href="{{ $riderRegisterUrl }}"
                        class="rounded-lg border border-white/40 px-7 py-3.5 text-sm font-bold text-white transition hover:bg-white/10">Become
                        a Rider</a>
                </div>
            </div>
        </section>

    </main>


    {{-- ================= FOOTER ================= --}}
    <footer class="bg-charcoal text-white">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 2xl:max-w-[1600px]">

            <div class="mb-10 border-b border-white/10 pb-10">
                <p class="mb-5 text-sm font-bold">Shop by Category</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($categories as $category)
                        <div>
                            <a href="{{ route('register') }}"
                                class="mb-2 block text-xs font-bold text-white hover:text-white/80">{{ $category['name'] }}</a>
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
                            <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap" class="h-full w-full object-contain">
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
                        <li><a href="{{ $riderRegisterUrl }}" class="hover:text-white">Ride with Shopleap</a></li>
                        <li><a href="#" class="hover:text-white">Terms & Policies</a></li>
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Payments & Delivery</h3>
                    <p class="mt-4 text-xs uppercase tracking-wide text-white/40">Payment methods</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (config('landing.payment_methods') as $payment)
                            <span
                                class="rounded border border-white/15 px-2 py-1 text-[10px] font-semibold text-white/70">{{ $payment }}</span>
                        @endforeach
                    </div>
                    <p class="mt-4 text-xs uppercase tracking-wide text-white/40">Delivery partners</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (config('landing.delivery_partners') as $courier)
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

</body>

</html>
