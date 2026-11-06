@props([
    'cartCount' => null,
    'cartPreviewItems' => null,
])

@php
    $headerUser = auth()->user();

    // Compute cart data only when the page didn't pass it in.
    if ($cartCount === null || $cartPreviewItems === null) {
        $cartPreviewItems = $headerUser
            ? $headerUser->cartItems()->with(['product.images', 'product.variants', 'product.liveDiscount', 'variant'])->latest()->take(5)->get()
            : collect();
        $cartCount = $headerUser ? $headerUser->cartItems()->sum('quantity') : 0;
    }

    $cartPreviewItems = new \Illuminate\Database\Eloquent\Collection($cartPreviewItems->all());
    $cartPreviewItems->loadMissing(['product.images', 'product.variants', 'product.liveDiscount', 'variant']);

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

<x-nav.shell id="buyer-navigation" variant="buyer" label="Buyer navigation" class="buyer-storefront-navigation">
    <x-slot:brand><x-logo /></x-slot:brand>
    <x-slot:location>    <a href="{{ route('buyer.account.index') }}#address" data-buyer-header-location
        class="block truncate py-2 text-xs md:hidden">
        {{ $headerUser?->defaultAddress ? ($headerUser->defaultAddress->label ?: 'Deliver to') . ', ' . $headerUser->defaultAddress->city_municipality : 'Add delivery address' }}
    </a>

</x-slot:location>
    <x-slot:desktopLinks><x-nav.link class="buyer-header-top-link" :active="request()->routeIs('buyer.account.*')" :href="route('buyer.account.index').'#address'">Delivery address</x-nav.link><x-nav.link class="buyer-header-top-link" :active="request()->routeIs('buyer.orders.*')" :href="route('buyer.orders.index', ['tab' => 'to_deliver'])">Track My Order</x-nav.link></x-slot:desktopLinks>
    <x-slot:utilityActions>                {{-- Notifications --}}
                <x-notification-bell-script />

                <div id="buyer-notification-feed" class="relative" @click.outside="open = false" @keydown.escape.window="open = false"
                    x-data="shopleapBell({
                        unread: {{ (int) ($headerUser?->unreadNotifications()->count() ?? 0) }},
                        feedUrl: @js(route('notifications.feed', [], false)),
                        readUrl: @js(route('notifications.read', ['id' => '__ID__'], false)),
                        readAllUrl: @js(route('notifications.read-all', [], false)),
                        allUrl: @js(route('notifications.index', [], false)),
                        csrf: @js(csrf_token()),
                    })">
                    <button type="button" @click="toggle()"
                        class="buyer-header-top-link relative flex items-center rounded-lg transition-colors">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span>Notifications</span>
                    </button>

                    <div x-show="open" x-cloak x-transition
                        class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl border border-line bg-white p-4 text-charcoal shadow-lg">
                        <p class="mb-1 text-sm font-bold text-charcoal">Notifications</p>

                        {{-- The notifications: click one to open the page it is about --}}
                        <div class="-mx-4 max-h-80 divide-y divide-line overflow-y-auto">
                            <template x-for="item in items" :key="item.id">
                                <button type="button" @click="go(item)"
                                    class="flex w-full items-start gap-2.5 px-4 py-2.5 text-left transition-colors hover:bg-ice-blue"
                                    :class="item.read ? '' : 'bg-primary/5'">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                        :class="item.read ? 'bg-transparent' : 'bg-primary'"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-xs"
                                            :class="item.read ? 'font-semibold text-charcoal/80' : 'font-bold text-charcoal'"
                                            x-text="item.title"></span>
                                        <span class="mt-0.5 line-clamp-2 block text-xs text-charcoal/70"
                                            x-text="item.body"></span>
                                        <span class="mt-0.5 block text-[11px] text-charcoal/60"
                                            x-text="item.time"></span>
                                    </span>
                                </button>
                            </template>
                        </div>

                        {{-- Empty state --}}
                        <div x-show="!items.length" class="flex flex-col items-center py-6 text-center">
                            <svg class="h-9 w-9 text-charcoal/20" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <p class="mt-2 text-xs text-charcoal/60"
                                x-text="loading ? 'LoadingÃ¢â‚¬Â¦' : (failed ? 'Could not load notifications' : 'No notifications yet')">
                                No notifications yet</p>
                        </div>

                        <div x-show="items.length" x-cloak
                            class="-mx-4 -mb-4 mt-1 flex items-center justify-between border-t border-line bg-ice-blue px-4 py-2.5 text-xs font-semibold">
                            <button type="button" x-show="unread > 0" @click="markAll()"
                                class="text-primary hover:underline">Mark all as read</button>
                            <a href="{{ route('notifications.index') }}"
                                class="ml-auto text-primary hover:underline">View all</a>
                        </div>
                    </div>
                </div>

                {{-- Vouchers removed from header; available in profile dropdown --}}

                {{-- Help --}}
                <a href="{{ route('messages.support') }}" title="Help"
                    class="buyer-header-top-link flex items-center rounded-lg transition-colors">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Help</span>
                </a>

                {{-- Profile dropdown --}}
                <div id="buyer-desktop-profile" class="relative" x-data="{ open: false }" @click.outside="open = false"
                    :class="{ 'z-[100]': open, 'z-10': !open }">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                        class="buyer-header-top-link relative flex items-center rounded-lg transition-colors">
                        <div
                            class="flex h-7 w-7 items-center justify-center overflow-hidden rounded-full bg-white text-xs font-bold text-primary">
                            @if ($headerUser?->avatar_url)
                                <img src="{{ $headerUser->avatar_url }}" alt=""
                                    class="h-full w-full object-cover">
                            @else
                                {{ strtoupper(substr($headerUser->name ?? 'B', 0, 1)) }}
                            @endif
                        </div>
                        <span class="max-w-[100px] truncate">
                            {{ $headerUser->name ?? 'Buyer' }}
                        </span>
                    </button>

                    <div x-show="open" x-cloak x-transition
                        class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-line bg-white py-1.5 text-charcoal shadow-lg">
                        <a href="{{ route('buyer.account.index') }}"
                            class="block px-4 py-2.5 text-sm transition-colors hover:bg-ice-blue">My Account</a>
                        <a href="{{ route('buyer.orders.index') }}"
                            class="block px-4 py-2.5 text-sm transition-colors hover:bg-ice-blue">Purchases</a>
                        <a href="{{ route('buyer.wishlist.index') }}"
                            class="block px-4 py-2.5 text-sm transition-colors hover:bg-ice-blue">Wishlist</a>
                        <a href="{{ route('buyer.vouchers.index') }}"
                            class="block px-4 py-2.5 text-sm transition-colors hover:bg-ice-blue">Vouchers</a>
                        <a href="{{ route('messages.index') }}"
                            class="block px-4 py-2.5 text-sm transition-colors hover:bg-ice-blue">Chat / Messages</a>

                        <div class="my-1 h-px bg-line"></div>

                        <button type="button" aria-label="Log out"
                            class="flex min-h-11 w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-normal text-sale-red transition-colors hover:bg-ice-blue focus-visible:outline-2 focus-visible:outline-primary"
                            @click="$dispatch('open-logout-modal', { navigationId: 'buyer-navigation', trigger: $el })">
                            <svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5H5v14h4m5-14 7 7-7 7m7-7H9" />
                            </svg>
                            Log out
                        </button>
                    </div>
                </div>
</x-slot:utilityActions>
    <x-slot:search><x-nav.search :buyer="true" /></x-slot:search>
    <x-slot:actions>        {{-- WISHLIST: mobile only (desktop uses the profile dropdown). Delete this block to remove it. --}}
        <a href="{{ route('buyer.wishlist.index') }}" aria-label="Wishlist"
            class="relative block shrink-0 rounded-lg p-2.5 transition-colors hover:bg-white/10 md:hidden">
            <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <span x-show="$store.wishlist.count > 0" x-cloak x-text="$store.wishlist.count"
                class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-sm bg-white px-1 text-[10px] font-bold text-primary">{{ $wishlistCount }}</span>
        </a>

        {{-- CART --}}
        <div class="group relative shrink-0">
            <a href="{{ route('buyer.cart.index') }}" aria-label="Cart"
                class="relative block rounded-lg p-2.5 transition-colors hover:bg-white/10">
                <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span id="buyerCartBadge"
                    class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-sm bg-white px-1 text-[10px] font-bold text-primary">
                    {{ $cartCount }}
                </span>
            </a>

            {{-- Hover preview: desktop only --}}
            <div
                class="invisible absolute right-0 top-full z-50 hidden w-80 -translate-y-1 overflow-hidden rounded-xl border border-line bg-white text-charcoal opacity-0 shadow-lg transition-all duration-150 md:block md:group-hover:visible md:group-hover:translate-y-0 md:group-hover:opacity-100">
                <p class="border-b border-line px-4 py-3 text-sm font-bold">
                    {{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'items' }} in your cart
                </p>

                @if ($cartPreviewItems->isEmpty())
                    <p class="px-4 py-6 text-center text-xs text-charcoal/60">Your cart is empty.</p>
                @else
                    <div class="max-h-64 overflow-y-auto">
                        @foreach ($cartPreviewItems as $item)
                            @continue(!$item->product)
                            @php
                                $previewImage = $item->variant?->image_url ?? $item->product->main_image_url;
                                $previewOptions = $item->variant?->options ?: $item->selected_options;
                            @endphp
                            <div class="flex items-center gap-3 border-b border-line px-4 py-3 last:border-b-0">
                                <div
                                    class="h-12 w-12 shrink-0 overflow-hidden rounded-md border border-line bg-ice-blue">
                                    @if ($previewImage)
                                        <img src="{{ $previewImage }}"
                                            alt="{{ $item->product->name }}" width="48" height="48" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs text-charcoal">{{ $item->product->name }}</p>
                                    @if ($previewOptions)
                                        <p class="mt-0.5 text-xs text-charcoal/60">{{ collect($previewOptions)->map(fn ($value, $name) => "{$name}: {$value}")->implode(', ') }}</p>
                                    @endif
                                    <p class="text-xs font-bold tabular-nums text-primary">
                                        {{ $item->quantity }} &times; &#8369;{{ number_format($item->unit_price, 2) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="border-t border-line bg-ice-blue p-3">
                    <a href="{{ route('buyer.cart.index') }}"
                        class="block w-full rounded-lg bg-primary px-4 py-2.5 text-center text-xs font-bold text-white transition-colors hover:bg-primary/90">
                        View Cart
                    </a>
                </div>
            </div>
        </div>

</x-slot:actions>
    <x-slot:mobileUser><div id="buyer-mobile-profile"><x-nav.user-menu id="buyer-mobile-profile-menu" /></div></x-slot:mobileUser>
    <x-slot:mobileMenu>
        <x-nav.link :href="route('buyer.home')">Home</x-nav.link>
        <x-nav.link :href="route('buyer.products.index')">Browse products</x-nav.link>
        <x-nav.link :href="route('buyer.account.index')">My Account</x-nav.link>
        <x-nav.link :href="route('buyer.orders.index')">Purchases</x-nav.link>
        <x-nav.link :href="route('buyer.wishlist.index')">Wishlist</x-nav.link>
        <x-nav.link :href="route('buyer.vouchers.index')">Vouchers</x-nav.link>
        <x-nav.link :href="route('messages.index')">Messages</x-nav.link>
        <x-nav.link :href="route('notifications.index')">Notifications</x-nav.link>
    </x-slot:mobileMenu>
</x-nav.shell>

{{-- WISHLIST: seed data for the Alpine store (must stay after </header>) --}}
<script id="wishlistData" type="application/json">@json($wishlistPayload)</script>

{{-- WISHLIST / FOLLOW: shared toast for page-level action feedback --}}
<div x-data="{
    show: false,
    message: '',
    error: false,
    timer: null,
    notify(detail) {
        this.message = detail.message;
        this.error = detail.error;
        this.show = true;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => (this.show = false), 3000);
    },
}" @wishlist-toast.window="notify($event.detail)"
    @seller-follow-toast.window="notify($event.detail)" x-show="show" x-cloak x-transition role="status"
    aria-live="polite" :class="error ? 'bg-sale-red' : 'bg-charcoal'"
    class="fixed bottom-20 right-3 z-[80] max-w-[calc(100vw-1.5rem)] rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg sm:bottom-6 sm:right-6 sm:max-w-sm">
    <span x-text="message"></span>
</div>
