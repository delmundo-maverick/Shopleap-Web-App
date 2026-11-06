@props(['variant' => 'frost']) {{-- 'frost' for the seller/admin/logistics headers, 'primary' for the blue buyer header --}}

@auth
    @php
        $isPrimary = $variant === 'primary';

        $bellButton = $isPrimary
            ? 'relative flex h-11 w-11 items-center justify-center rounded-full text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40'
            : 'frost-btn relative flex h-11 w-11 items-center justify-center rounded-xl text-charcoal/70 transition hover:text-primary focus:outline-none focus:ring-4 focus:ring-primary/10';

        $badgeRing = $isPrimary ? 'ring-primary' : 'ring-white';
    @endphp



    <div class="relative shrink-0" @keydown.escape.window="open = false" x-data="shopleapBell({
        unread: {{ (int) auth()->user()->unreadNotifications()->count() }},
        feedUrl: @js(route('notifications.feed', [], false)),
        readUrl: @js(route('notifications.read', ['id' => '__ID__'], false)),
        readAllUrl: @js(route('notifications.read-all', [], false)),
        allUrl: @js(route('notifications.index', [], false)),
        csrf: @js(csrf_token()),
    })">

        <button type="button" @click="toggle()" :aria-expanded="open.toString()" aria-haspopup="true"
            aria-label="Notifications" class="{{ $bellButton }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 00-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m2 0v1a2 2 0 004 0v-1" />
            </svg>

            <span x-show="unread > 0" x-cloak x-text="badge()"
                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-sale-red px-1 text-[11px] font-bold leading-none text-white ring-2 {{ $badgeRing }}"></span>
        </button>

        <div x-show="open" x-cloak x-transition.opacity.duration.150ms @click.outside="open = false"
            class="fixed inset-x-3 top-[4.5rem] z-[70] overflow-hidden rounded-2xl border border-light-gray bg-white text-charcoal shadow-xl shadow-charcoal/10 sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-96">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-light-gray px-4 py-3">
                <p class="text-sm font-bold text-charcoal">Notifications</p>

                <button type="button" x-show="unread > 0" x-cloak @click="markAll()"
                    class="text-xs font-semibold text-primary hover:underline">
                    Mark all as read
                </button>
            </div>

            {{-- List --}}
            <div class="max-h-[26rem] divide-y divide-light-gray overflow-y-auto">
                <p x-show="loading && !items.length" class="px-4 py-8 text-center text-xs text-charcoal/50">
                    Loading…
                </p>

                <div x-show="failed && !items.length" x-cloak class="px-4 py-8 text-center">
                    <p class="text-xs text-charcoal/50">Couldn't load your notifications.</p>
                    <button type="button" @click="refresh()"
                        class="mt-2 text-xs font-semibold text-primary hover:underline">
                        Try again
                    </button>
                </div>

                <div x-show="!loading && !failed && !items.length" x-cloak class="px-4 py-10 text-center">
                    <p class="text-sm font-semibold text-charcoal">You're all caught up</p>
                    <p class="mt-1 text-xs text-charcoal/50">Updates about your orders appear here.</p>
                </div>

                <template x-for="item in items" :key="item.id">
                    <button type="button" @click="go(item)"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-ice-blue/60"
                        :class="item.read ? '' : 'bg-primary/5'">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                            :class="item.read ? 'bg-transparent' : 'bg-primary'"></span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm"
                                :class="item.read ? 'font-semibold text-charcoal/80' : 'font-bold text-charcoal'"
                                x-text="item.title"></span>
                            <span class="mt-0.5 line-clamp-2 block text-xs text-charcoal/60" x-text="item.body"></span>
                            <span class="mt-1 block text-[11px] text-charcoal/40" x-text="item.time"></span>
                        </span>
                    </button>
                </template>
            </div>

            {{-- Footer --}}
            <a href="{{ route('notifications.index') }}"
                class="block border-t border-light-gray px-4 py-3 text-center text-xs font-semibold text-primary transition hover:bg-ice-blue/60">
                View all notifications
            </a>
        </div>
    </div>
@endauth
