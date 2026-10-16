@props(['active' => null])
@vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/navbar.css', 'resources/js/shared/navbar.js'])


<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-charcoal/40 backdrop-blur-sm lg:hidden"></div>

<aside id="sidebarNav" data-unread-url="{{ route('messages.unread-count') }}" data-storage-key="sidebarCollapsed"
    class="frost-panel-solid fixed inset-y-0 left-0 z-50 w-64 -translate-x-full overflow-visible transition-[width,transform] duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:z-auto lg:translate-x-0">

    {{-- Sidebar content --}}
    <div id="sidebarContent" class="flex h-full w-full min-w-0 flex-col overflow-hidden">

        {{-- Logo/Header with clear bottom separation border --}}
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
                        Admin Control Panel
                    </p>
                </div>

            </div>

            {{-- Separator & Toggle Menu Section --}}
            <div class="flex shrink-0 items-center gap-1.5 pl-3 border-l border-white/50">

                {{-- Desktop collapse toggle --}}
                <button type="button" id="sidebarToggleBtn"
                    class="frost-btn hidden h-9 w-9 items-center justify-center rounded-lg text-charcoal/60 transition-all duration-300 hover:bg-ice-blue hover:text-primary lg:flex"
                    title="Toggle Sidebar">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                {{-- Mobile close button --}}
                <button type="button" id="sidebarCloseBtn"
                    class="nav-label flex h-9 w-9 items-center justify-center rounded-lg text-charcoal/50 transition hover:bg-ice-blue hover:text-primary lg:hidden">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>

                </button>

            </div>

        </div>

        {{-- Navigation --}}
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">

            <a href="{{ route('admin.dashboard') }}" title="System Overview"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'dashboard'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>

                <span class="nav-label">Dashboard</span>
            </a>


            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                User Management
            </p>


            <a href="{{ route('admin.registrations.buyers.index') }}" title="Buyers & Customers"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'users'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>

                <span class="nav-label">Buyers & Customers</span>
            </a>


            <a href="{{ route('admin.registrations.sellers.index') }}" title="Sellers & Merchants"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'sellers'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h6m-6 0V11m6 0V5" />
                </svg>

                <span class="nav-label">Sellers & Merchants</span>
            </a>


            <a href="{{ route('admin.registrations.logistics.index') }}" title="Logistics Partners"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'logistics'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8" />
                </svg>

                <span class="nav-label">Logistics Partners</span>
            </a>


            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Support
            </p>


            <a href="{{ route('messages.index') }}" title="Messages"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'messages'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>

                <span class="nav-label flex-1">Messages</span>
                <span data-message-badge
                    class="nav-label hidden shrink-0 rounded-full bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white"></span>
            </a>


            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Financials & Logs
            </p>


            <a href="{{ route('admin.transactions.index') }}" title="All Transactions"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'transactions'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>

                <span class="nav-label">All Transactions</span>
            </a>


            <a href="{{ route('admin.logs.index') }}" title="System Activity Logs"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'logs'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>

                <span class="nav-label">System Activity Logs</span>
            </a>



            {{-- Platform Settings --}}
            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Platform Settings
            </p>

            <a href="{{ route('admin.settings.index') }}" title="General Settings"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ request()->routeIs('admin.settings.index', 'admin.settings.general')
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M4 6h16M4 12h16M4 18h16M9 3v6m6 0v6m-3 0v6" />
                </svg>

                <span class="nav-label">General</span>
            </a>


            <a href="{{ route('admin.settings.shipping.index') }}" title="Shipping Settings"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ request()->routeIs('admin.settings.shipping', 'admin.settings.shipping.*')
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M3 7h11v10H3zM14 10h4l3 3v4h-7zM7 20a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z" />
                </svg>

                <span class="nav-label">Shipping</span>
            </a>


            <a href="{{ route('admin.settings.announcements.index') }}" title="Announcements"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ request()->routeIs('admin.settings.announcements.*')
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 00-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m2 0v1a2 2 0 004 0v-1m-4-11V4" />
                </svg>

                <span class="nav-label">Announcements</span>
            </a>


            <a href="{{ route('admin.settings.policies.index') }}" title="Policies"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ request()->routeIs('admin.settings.policies.*')
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M9 13h6m-6 4h6" />
                </svg>

                <span class="nav-label">Policies</span>
            </a>


        </nav>


        {{-- Logout --}}
        <div class="shrink-0 border-t border-white/50 p-3">

            <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="logout-form">

                @csrf

                <button type="button" id="logoutTriggerBtn"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-sale-red/80 transition hover:bg-sale-red/10 hover:text-sale-red">

                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />

                    </svg>

                    <span class="nav-label">Logout</span>

                </button>

            </form>

        </div>

    </div>

</aside>


{{-- Logout confirmation modal --}}
<div id="logoutModalOverlay"
    class="fixed inset-0 z-[90] hidden items-center justify-center bg-charcoal/40 p-4 backdrop-blur-sm">

    <div class="frost-panel-solid w-full max-w-sm rounded-3xl p-6 text-center">

        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-sale-red/10 text-sale-red">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </div>

        <h3 class="text-lg font-bold text-charcoal">Log out?</h3>
        <p class="mt-1.5 text-sm text-charcoal/60">You'll need to sign back in to access the admin panel.</p>

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
