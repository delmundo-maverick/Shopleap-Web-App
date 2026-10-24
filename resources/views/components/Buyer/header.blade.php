@props([
    'cartCount' => null,
    'cartPreviewItems' => null,
])

@php
    $headerUser = auth()->user();

    // Compute cart data only when the page didn't pass it in.
if ($cartCount === null || $cartPreviewItems === null) {
    $cartPreviewItems = $headerUser
        ? $headerUser->cartItems()->with('product')->latest()->take(5)->get()
        : collect();
    $cartCount = $headerUser ? $headerUser->cartItems()->count() : 0;
}

// One query per request, shared with the wishlist hearts on the page.
$wishlistIds = app(\App\Support\WishlistIds::class);
$wishlistCount = $wishlistIds->count();

$wishlistPayload = [
    'ids' => $wishlistIds->all(),
    'count' => $wishlistCount,
    'url' => route('buyer.wishlist.store', '__ID__'),
    'loginUrl' => route('login'),
    ];
@endphp

@vite('resources/js/components/buyer-header.js')

<header class="sticky top-0 z-50 bg-primary text-white shadow-sm">

    {{-- TOP UTILITY ROW: desktop only --}}
    <div class="hidden border-b border-white/10 md:block">
        <div class="mx-auto flex h-8 max-w-7xl items-center justify-between gap-5 px-8 text-xs text-white/80">

            <div class="flex items-center gap-5">
                <a href="{{ route('buyer.orders.index', ['tab' => 'to_deliver']) }}" class="hover:text-white">
                    Track My Order
                </a>
            </div>

            <div class="flex items-center gap-5">

                {{-- Notifications --}}
                <x-notification-bell-script />

                <div class="relative" @click.outside="open = false" @keydown.escape.window="open = false"
                    x-data="shopleapBell({
                        unread: {{ (int) ($headerUser?->unreadNotifications()->count() ?? 0) }},
                        feedUrl: @js(route('notifications.feed', [], false)),
                        readUrl: @js(route('notifications.read', ['id' => '__ID__'], false)),
                        readAllUrl: @js(route('notifications.read-all', [], false)),
                        allUrl: @js(route('notifications.index', [], false)),
                        csrf: @js(csrf_token()),
                    })">
                    <button type="button" @click="toggle()"
                        class="relative flex items-center gap-1.5 rounded-full py-1.5 pl-2 pr-3 transition hover:bg-white/10">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span>Notifications</span>
                    </button>

                    <div x-show="open" x-cloak x-transition
                        class="absolute right-0 mt-2 w-72 rounded-lg bg-white p-4 text-charcoal shadow-xl">
                        <p class="mb-1 text-sm font-bold text-charcoal">Notifications</p>

                        {{-- The notifications: click one to open the page it is about --}}
                        <div class="-mx-4 max-h-80 divide-y divide-light-gray overflow-y-auto">
                            <template x-for="item in items" :key="item.id">
                                <button type="button" @click="go(item)"
                                    class="flex w-full items-start gap-2.5 px-4 py-2.5 text-left transition hover:bg-ice-blue"
                                    :class="item.read ? '' : 'bg-primary/5'">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                        :class="item.read ? 'bg-transparent' : 'bg-primary'"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-xs"
                                            :class="item.read ? 'font-semibold text-charcoal/80' : 'font-bold text-charcoal'"
                                            x-text="item.title"></span>
                                        <span class="mt-0.5 line-clamp-2 block text-xs text-charcoal/60"
                                            x-text="item.body"></span>
                                        <span class="mt-0.5 block text-[11px] text-charcoal/40"
                                            x-text="item.time"></span>
                                    </span>
                                </button>
                            </template>
                        </div>

                        {{-- Empty state: your original, unchanged --}}
                        <div x-show="!items.length" class="flex flex-col items-center py-6 text-center">
                            <svg class="h-9 w-9 text-charcoal/20" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <p class="mt-2 text-xs text-charcoal/50"
                                x-text="loading ? 'Loading…' : (failed ? 'Could not load notifications' : 'No notifications yet')">
                                No notifications yet</p>
                        </div>

                        <div x-show="items.length" x-cloak
                            class="-mx-4 -mb-4 mt-1 flex items-center justify-between border-t border-light-gray px-4 py-2.5 text-xs font-semibold">
                            <button type="button" x-show="unread > 0" @click="markAll()"
                                class="text-primary hover:underline">Mark all as read</button>
                            <a href="{{ route('notifications.index') }}"
                                class="ml-auto text-primary hover:underline">View
                                all</a>
                        </div>
                    </div>
                </div>

                {{-- Help --}}
                <a href="{{ route('messages.support') }}" title="Help"
                    class="flex items-center gap-1.5 rounded-full py-1.5 pl-2 pr-3 transition hover:bg-white/10">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Help</span>
                </a>

                {{-- Profile dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false"
                    :class="{ 'z-[100]': open, 'z-10': !open }">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                        class="relative flex items-center gap-2 rounded-full py-1 pl-1 pr-2.5 transition hover:bg-white/10">
                        <div
                            class="flex h-7 w-7 items-center justify-center overflow-hidden rounded-full bg-white text-xs font-bold text-primary">
                            @if ($headerUser?->avatar_url)
                                <img src="{{ $headerUser->avatar_url }}" alt=""
                                    class="h-full w-full object-cover">
                            @else
                                {{ strtoupper(substr($headerUser->name ?? 'B', 0, 1)) }}
                            @endif
                        </div>
                        <span class="max-w-[100px] truncate text-xs font-medium">
                            {{ $headerUser->name ?? 'Buyer' }}
                        </span>
                    </button>

                    <div x-show="open" x-cloak x-transition
                        class="absolute right-0 mt-2 w-52 overflow-hidden rounded-lg bg-white py-1.5 text-charcoal shadow-xl ring-1 ring-black/5">
                        <a href="{{ route('buyer.account.index') }}"
                            class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">My Account</a>
                        <a href="{{ route('buyer.orders.index') }}"
                            class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">My Purchases</a>
                        <a href="{{ route('buyer.wishlist.index') }}"
                            class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">My Wishlist</a>
                        <a href="{{ route('messages.index') }}"
                            class="block px-4 py-2.5 text-sm transition hover:bg-ice-blue">Chat / Messages</a>

                        <div class="my-1 h-px bg-light-gray"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="block w-full px-4 py-2.5 text-left text-sm text-sale-red transition hover:bg-sale-red/10">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN ROW: mobile = search + wishlist + cart, desktop = logo + search + cart --}}
    <div class="mx-auto flex h-[60px] max-w-7xl items-center gap-2 px-3 md:h-[68px] md:gap-6 md:px-8">

        {{-- LOGO: desktop only --}}
        <div class="hidden shrink-0 md:block">
            <x-logo />
        </div>

        {{-- SEARCH --}}
        <div class="relative min-w-0 flex-1" x-data="{ open: false }" @click.outside="open = false">
            <form id="buyerSearchForm" action="{{ route('buyer.products.index') }}" method="GET"
                class="flex overflow-hidden rounded-sm bg-white">
                <input type="text" id="buyerSearchInput" name="search" autocomplete="off" @focus="open = true"
                    value="{{ request('search') }}" placeholder="Search products, brands, and categories"
                    class="w-full min-w-0 px-3 py-2.5 text-sm text-charcoal outline-none md:px-4">
                <button type="submit" aria-label="Search"
                    class="flex shrink-0 items-center justify-center bg-sky-blue px-4 text-white transition hover:brightness-105 md:px-5">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </form>

            <div x-show="open" x-cloak x-transition
                class="absolute left-0 right-0 top-full mt-1.5 rounded-lg bg-white p-3 text-charcoal shadow-xl">

                <div id="recentSearchesSection" class="mb-3 hidden">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/40">Recent Searches</p>
                        <button type="button" id="clearRecentSearchesBtn"
                            class="text-[11px] font-semibold text-primary hover:underline">Clear</button>
                    </div>
                    <div id="recentSearchesList" class="flex flex-wrap gap-2"></div>
                </div>

                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-charcoal/40">Trending</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (['skincare set', 'mechanical keyboard', 'yoga mat', 'power bank'] as $trend)
                        <button type="button"
                            class="trending-search-chip rounded-full bg-ice-blue px-3 py-1 text-xs text-charcoal/70 transition hover:bg-primary/10 hover:text-primary">
                            {{ $trend }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- WISHLIST: mobile only (desktop uses the profile dropdown). Delete this block to remove it. --}}
        <a href="{{ route('buyer.wishlist.index') }}" aria-label="Wishlist"
            class="relative block shrink-0 rounded-full p-2.5 transition hover:bg-white/10 md:hidden">
            <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <span x-show="$store.wishlist.count > 0" x-cloak x-text="$store.wishlist.count"
                class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold text-primary">{{ $wishlistCount }}</span>
        </a>

        {{-- CART --}}
        <div class="group relative shrink-0">
            <a href="{{ route('buyer.cart.index') }}" aria-label="Cart"
                class="relative block rounded-full p-2.5 transition hover:bg-white/10">
                <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span id="buyerCartBadge"
                    class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold text-primary">
                    {{ $cartCount }}
                </span>
            </a>

            {{-- Hover preview: desktop only --}}
            <div
                class="invisible absolute right-0 top-full z-50 hidden w-80 -translate-y-1 rounded-lg bg-white text-charcoal opacity-0 shadow-xl transition-all duration-150 md:block md:group-hover:visible md:group-hover:translate-y-0 md:group-hover:opacity-100">
                <p class="border-b border-light-gray px-4 py-3 text-sm font-bold">
                    {{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'items' }} in your cart
                </p>

                @if ($cartPreviewItems->isEmpty())
                    <p class="px-4 py-6 text-center text-xs text-charcoal/40">Your cart is empty.</p>
                @else
                    <div class="max-h-64 overflow-y-auto">
                        @foreach ($cartPreviewItems as $item)
                            <div class="flex items-center gap-3 border-b border-light-gray px-4 py-3 last:border-b-0">
                                <div class="h-12 w-12 shrink-0 overflow-hidden rounded bg-ice-blue">
                                    @if ($item->product->main_image_url)
                                        <img src="{{ $item->product->main_image_url }}"
                                            alt="{{ $item->product->name }}" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs text-charcoal">{{ $item->product->name }}</p>
                                    <p class="text-xs font-bold text-primary">
                                        {{ $item->quantity }} × ₱{{ number_format($item->product->display_price, 2) }}
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
</header>

{{-- WISHLIST: seed data for the Alpine store (must stay after </header>) --}}
<script id="wishlistData" type="application/json">
    @json($wishlistPayload)
</script>

{{-- WISHLIST: toast shown for wishlist and add-to-cart feedback --}}
<div x-data="{ show: false, message: '', error: false, timer: null }"
    @wishlist-toast.window="
        message = $event.detail.message; error = $event.detail.error; show = true;
        clearTimeout(timer); timer = setTimeout(() => show = false, 3000)"
    x-show="show" x-cloak x-transition role="status" aria-live="polite"
    :class="error ? 'bg-sale-red' : 'bg-leaf-green'"
    class="fixed bottom-20 right-3 z-[80] max-w-[calc(100vw-1.5rem)] rounded-xl px-4 py-3 text-sm font-medium text-white shadow-xl sm:bottom-6 sm:right-6 sm:max-w-sm">
    <span x-text="message"></span>
</div>
