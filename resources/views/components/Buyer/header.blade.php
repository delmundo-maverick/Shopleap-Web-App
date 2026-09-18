@props([
    'cartCount' => 0,
    'cartPreviewItems' => collect(),
])

<header class="sticky top-0 z-40 bg-primary text-white shadow-sm">

    {{-- =====================================================
         TOP UTILITY ROW
    ====================================================== --}}
    <div class="border-b border-white/10">
        <div class="mx-auto flex h-8 max-w-7xl items-center justify-between gap-5 px-5 text-xs text-white/80 sm:px-8">

            {{-- LEFT: utility links --}}
            <div class="flex items-center gap-5">
                <a href="#" class="hover:text-white">Track My Order</a>
                <a href="#" class="hover:text-white">Help Center</a>
            </div>

            {{-- RIGHT: notifications, help, profile --}}
            <div class="flex items-center gap-5">

                {{-- Notifications --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                        class="relative flex items-center gap-1.5 rounded-full py-1.5 pl-2 pr-3 transition hover:bg-white/10">
                        <span class="relative">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                class="absolute -right-1 -top-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-sale-red text-[9px] font-bold">3</span>
                        </span>
                        <span>Notifications</span>
                    </button>

                    <div x-show="open" x-cloak x-transition
                        class="absolute right-0 mt-2 w-72 overflow-hidden rounded-lg bg-white text-charcoal shadow-xl">
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
                        <p class="border-t border-light-gray px-4 py-2 text-center text-[10px] text-charcoal/35">
                            Sample notifications — live notifications coming soon.
                        </p>
                    </div>
                </div>

                {{-- Help --}}
                <a href="#" title="Help"
                    class="hidden items-center gap-1.5 rounded-full py-1.5 pl-2 pr-3 transition hover:bg-white/10 sm:flex">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Help</span>
                </a>

                {{-- Profile dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                        class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2.5 transition hover:bg-white/10">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-xs font-bold text-primary">
                            {{ strtoupper(substr(auth()->user()->name ?? 'B', 0, 1)) }}
                        </div>
                        <span
                            class="hidden max-w-[100px] truncate text-xs font-medium sm:block">{{ auth()->user()->name ?? 'Buyer' }}</span>
                    </button>

                    <div x-show="open" x-cloak x-transition
                        class="absolute right-0 mt-2 w-52 overflow-hidden rounded-lg bg-white py-1.5 text-charcoal shadow-xl">
                        <a href="#" class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">My Account</a>
                        <a href="#" class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">My Orders</a>
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

    {{-- =====================================================
         MAIN HEADER ROW
    ====================================================== --}}
    <div class="mx-auto flex h-[68px] max-w-7xl items-center gap-6 px-5 sm:px-8">

        {{-- LOGO — acts as home button --}}
        <a href="{{ route('buyer.home') }}" class="flex shrink-0 items-center gap-2">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
                {{-- LOGO PLACEHOLDER — replace /public/images/shopleap-logo.png with the real logo --}}
                <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap"
                    class="h-full w-full rounded-lg object-contain">
            </div>
            <span class="hidden text-xl font-extrabold tracking-tight sm:block">Shopleap</span>
        </a>

        {{-- SEARCH BAR with suggestions dropdown --}}
        <div class="relative flex-1" x-data="{ open: false }" @click.outside="open = false">
            <form action="#" method="GET" class="flex overflow-hidden rounded-sm bg-white">
                <input type="text" name="q" autocomplete="off" @focus="open = true"
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

            {{-- Suggestions / recent searches dropdown --}}
            <div x-show="open" x-cloak x-transition
                class="absolute left-0 right-0 top-full mt-1.5 rounded-lg bg-white p-3 text-charcoal shadow-xl">
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

        {{-- ICONS --}}
        <div class="flex shrink-0 items-center gap-1">

            {{-- Cart with hover preview --}}
            <div class="group relative">
                <a href="{{ route('buyer.cart.index') }}"
                    class="relative block rounded-full p-2.5 transition hover:bg-white/10">
                    <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span
                        class="absolute right-1.5 top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-white text-[10px] font-bold text-primary">
                        {{ $cartCount }}
                    </span>
                </a>

                {{-- Preview dropdown — shows on hover (desktop) --}}
                <div
                    class="invisible absolute right-0 top-full z-50 w-80 -translate-y-1 rounded-lg bg-white text-charcoal opacity-0 shadow-xl transition-all duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                    <p class="border-b border-light-gray px-4 py-3 text-sm font-bold">
                        {{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'items' }} in your cart
                    </p>

                    @if ($cartPreviewItems->isEmpty())
                        <p class="px-4 py-6 text-center text-xs text-charcoal/40">Your cart is empty.</p>
                    @else
                        <div class="max-h-64 overflow-y-auto">
                            @foreach ($cartPreviewItems as $item)
                                <div
                                    class="flex items-center gap-3 border-b border-light-gray px-4 py-3 last:border-b-0">
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded bg-ice-blue">
                                        @if ($item->product->main_image_url)
                                            <img src="{{ $item->product->main_image_url }}"
                                                alt="{{ $item->product->name }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs text-charcoal">{{ $item->product->name }}</p>
                                        <p class="text-xs font-bold text-primary">
                                            {{ $item->quantity }} ×
                                            ₱{{ number_format($item->product->display_price, 2) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="p-3">
                        <a href="{{ route('buyer.cart.index') }}"
                            class="block w-full rounded-lg bg-primary px-4 py-2.5 text-center text-xs font-bold text-white transition hover:bg-sky-blue">
                            View Cart
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>

</header>
