@props(['active' => null])

@php
    $sections = [
        'Overview' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'logistics.dashboard',
             'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10'],
        ],
        'Rider Management' => [
            ['key' => 'riders', 'label' => 'Rider Applications', 'route' => 'logistics.riders.index',
             'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['key' => 'areas', 'label' => 'Service Areas', 'route' => 'logistics.areas.index',
             'icon' => 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
        ],
        'Parcel Operations' => [
            ['key' => 'pickups', 'label' => 'Pickup Requests', 'route' => 'logistics.pickups.index',
             'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
            ['key' => 'incoming', 'label' => 'Incoming Parcels', 'route' => 'logistics.incoming.index',
             'icon' => 'M4 12h16M4 12l6-6M4 12l6 6'],
            ['key' => 'sorting', 'label' => 'Sort & Assign', 'route' => 'logistics.sorting.index',
             'icon' => 'M4 6h16M4 12h16M4 18h7'],
            ['key' => 'deliveries', 'label' => 'Deliveries', 'route' => 'logistics.deliveries.index',
             'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
        ],
        'Reports' => [
            ['key' => 'reports', 'label' => 'Reports', 'route' => null,
             'icon' => 'M9 17v-6h6v6M4 21h16a1 1 0 001-1V4a1 1 0 00-1-1H4a1 1 0 00-1 1v16a1 1 0 001 1z'],
        ],
        'General' => [
            ['key' => 'messages', 'label' => 'Messages', 'route' => 'messages.index', 'badge' => true,
             'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
            ['key' => 'help', 'label' => 'Help / Support', 'route' => 'messages.support',
             'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ],
    ];
@endphp

<aside
    :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="frost-panel-solid fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col border-r border-white/50 transition-transform duration-300 lg:static lg:translate-x-0">

    <div class="flex h-20 items-center justify-between border-b border-white/50 px-6">
        <a href="{{ route('logistics.dashboard') }}" class="flex items-center gap-2">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap" class="h-full w-full rounded-lg object-contain">
            </div>
            <span class="text-lg font-extrabold tracking-tight text-primary">Shopleap</span>
        </a>
        <button type="button" @click="$store.sidebar.open = false"
            class="frost-btn rounded-lg p-1.5 text-charcoal/50 transition hover:text-primary lg:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-4">
        @foreach ($sections as $title => $links)
            <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wide text-charcoal/40 {{ $loop->first ? '' : 'mt-6' }}">{{ $title }}</p>

            @foreach ($links as $link)
                <a href="{{ $link['route'] ? route($link['route']) : '#' }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition
                        {{ $active === $link['key'] ? 'bg-primary text-white shadow-sm' : 'text-charcoal/70 hover:bg-white/60' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $link['icon'] }}" />
                    </svg>
                    <span class="flex-1">{{ $link['label'] }}</span>

                    @if (! $link['route'])
                        <span class="rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
                    @elseif (! empty($link['badge']))
                        <span x-data="{ count: 0 }"
                            x-init="const load = () => fetch('{{ route('messages.unread-count') }}', { headers: { Accept: 'application/json' } }).then(r => r.ok ? r.json() : { count: 0 }).then(d => count = d.count).catch(() => {}); load(); setInterval(load, 20000)"
                            x-show="count > 0" style="display: none"
                            x-text="count > 9 ? '9+' : count"
                            class="rounded-full bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white"></span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="border-t border-white/50 p-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-charcoal/60 transition hover:bg-sale-red/10 hover:text-sale-red">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Log Out
            </button>
        </form>
    </div>
</aside>

<div x-show="$store.sidebar.open" x-cloak @click="$store.sidebar.open = false"
    class="fixed inset-0 z-30 bg-charcoal/40 lg:hidden"></div>
