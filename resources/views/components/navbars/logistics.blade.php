@props(['active' => null])
@vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/navbar.css', 'resources/js/shared/navbar.js'])

@php
    $sections = [
        'Overview' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'route' => 'logistics.dashboard',
                'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10',
            ],
        ],
        'Rider Management' => [
            [
                'key' => 'riders',
                'label' => 'Rider Applications',
                'route' => 'logistics.riders.index',
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            ],
            [
                'key' => 'areas',
                'label' => 'Service Areas',
                'route' => 'logistics.areas.index',
                'icon' =>
                    'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
            ],
        ],
        'Parcel Operations' => [
            [
                'key' => 'pickups',
                'label' => 'Pickup Requests',
                'route' => 'logistics.pickups.index',
                'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
            ],
            [
                'key' => 'incoming',
                'label' => 'Incoming Parcels',
                'route' => 'logistics.incoming.index',
                'icon' => 'M4 12h16M4 12l6-6M4 12l6 6',
            ],
            [
                'key' => 'sorting',
                'label' => 'Sort & Assign',
                'route' => 'logistics.sorting.index',
                'icon' => 'M4 6h16M4 12h16M4 18h7',
            ],
            [
                'key' => 'deliveries',
                'label' => 'Deliveries',
                'route' => 'logistics.deliveries.index',
                'icon' =>
                    'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
            ],
        ],
        'Reports' => [
            [
                'key' => 'reports',
                'label' => 'Reports',
                'route' => null,
                'icon' => 'M9 17v-6h6v6M4 21h16a1 1 0 001-1V4a1 1 0 00-1-1H4a1 1 0 00-1 1v16a1 1 0 001 1z',
            ],
        ],
        'General' => [
            [
                'key' => 'messages',
                'label' => 'Messages',
                'route' => 'messages.index',
                'badge' => true,
                'icon' =>
                    'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
            ],
            [
                'key' => 'help',
                'label' => 'Help / Support',
                'route' => 'messages.support',
                'icon' =>
                    'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ],
    ];
@endphp

<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-charcoal/40 backdrop-blur-sm lg:hidden"></div>

<aside id="sidebarNav" data-unread-url="{{ route('messages.unread-count') }}" data-storage-key="sidebarCollapsed"
    class="frost-panel-solid fixed inset-y-0 left-0 z-50 w-64 -translate-x-full overflow-visible transition-[width,transform] duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:z-auto lg:translate-x-0">

    {{-- Sidebar content --}}
    <div id="sidebarContent" class="flex h-full w-full min-w-0 flex-col overflow-hidden">

        {{-- Logo/Header --}}
        <div
            class="sidebar-header-container flex h-20 shrink-0 items-center justify-between gap-2 border-b border-white/50 px-4 transition-all">

            <div class="sidebar-header-inner flex min-w-0 items-center gap-3">

                <img src="{{ asset('images/Shopleap-Logo.png') }}" alt="Shopleap logo"
                    class="h-11 w-11 shrink-0 rounded-xl object-contain">

                <div class="brand-text min-w-0 flex-1">
                    <h1 class="font-logo text-lg font-bold leading-tight text-primary">
                        SHOPLEAP
                    </h1>

                    <p class="text-xs text-charcoal/60">
                        Logistics Center
                    </p>
                </div>

            </div>

            {{-- Separator & Toggle Menu Section --}}
            <div class="flex shrink-0 items-center gap-1.5 pl-3 border-l border-white/50">

                {{-- Desktop collapse toggle --}}
                <button type="button" id="sidebarToggleBtn"
                    class="frost-btn hidden h-9 w-9 items-center justify-center rounded-lg text-charcoal/60 transition-all duration-300 hover:bg-ice-blue hover:text-primary lg:flex"
                    title="Toggle Sidebar" aria-label="Toggle sidebar">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                {{-- Mobile close button --}}
                <button type="button" id="sidebarCloseBtn" aria-label="Close sidebar"
                    class="nav-label flex h-9 w-9 items-center justify-center rounded-lg text-charcoal/50 transition hover:bg-ice-blue hover:text-primary lg:hidden">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>

                </button>

            </div>

        </div>

        {{-- Navigation --}}
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">

            @foreach ($sections as $title => $links)
                <p
                    class="nav-section-title px-3 pb-2 {{ $loop->first ? 'pt-0' : 'pt-4' }} text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                    {{ $title }}
                </p>

                @foreach ($links as $link)
                    <a href="{{ $link['route'] ? route($link['route']) : '#' }}" title="{{ $link['label'] }}"
                        class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                        {{ $active === $link['key']
                            ? 'frost-chip text-primary font-semibold'
                            : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="{{ $link['icon'] }}" />
                        </svg>

                        <span class="nav-label flex-1">{{ $link['label'] }}</span>

                        @if (!$link['route'])
                            <span
                                class="nav-label shrink-0 rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
                        @elseif (!empty($link['badge']))
                            <span data-message-badge
                                class="nav-label hidden shrink-0 rounded-full bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white"></span>
                        @elseif (!empty($link['count']))
                            <span
                                class="nav-label shrink-0 rounded-full bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $link['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            @endforeach

        </nav>


        {{-- Logout --}}
        <div class="shrink-0 border-t border-white/50 p-3">

            <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="logout-form">

                @csrf

                <button type="button" id="logoutTriggerBtn"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-sale-red/80 transition hover:bg-sale-red/10 hover:text-sale-red">

                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>

                    <span class="nav-label">Logout</span>

                </button>

            </form>

        </div>

    </div>

</aside>


{{-- Logout confirmation modal — deliberately placed OUTSIDE <aside>, as a sibling.
     The sidebar uses translate-x-* (a CSS transform) to slide/collapse, and any
     ancestor with a transform becomes the containing block for position:fixed
     descendants. Keeping this modal as a sibling (not nested) is what makes it
     center on the real viewport instead of being trapped inside the sidebar. --}}
<div id="logoutModalOverlay"
    class="fixed inset-0 z-[90] hidden items-center justify-center bg-charcoal/40 p-4 backdrop-blur-sm">

    <div class="frost-panel-solid w-full max-w-sm rounded-3xl p-6 text-center" role="dialog" aria-modal="true"
        aria-labelledby="logoutTitle">

        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-sale-red/10 text-sale-red">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </div>

        <h3 id="logoutTitle" class="text-lg font-bold text-charcoal">Log out?</h3>
        <p class="mt-1.5 text-sm text-charcoal/60">You'll need to sign back in to access the Logistics Center.</p>

        <div class="mt-6 flex gap-3">
            <button type="button" id="logoutCancelBtn"
                class="frost-btn flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold text-charcoal transition hover:bg-ice-blue">
                Cancel
            </button>
            <button type="button" id="logoutConfirmBtn"
                class="flex-1 rounded-xl bg-sale-red px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-sale-red/30 transition hover:brightness-105">
                Log out
            </button>
        </div>

    </div>

</div>
