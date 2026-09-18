@props(['active' => null])

<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-charcoal/40 backdrop-blur-sm lg:hidden"></div>

<style>
    /* Collapsed (icon-only) state for the desktop sidebar */
    #sidebarNav.is-collapsed {
        width: 5rem;
        /* 80px — enough room for a centered icon */
    }

    #sidebarNav.is-collapsed .nav-label,
    #sidebarNav.is-collapsed .brand-text,
    #sidebarNav.is-collapsed .nav-section-title {
        display: none;
    }

    #sidebarNav.is-collapsed .nav-link {
        justify-content: center;
        padding-left: 0.625rem;
        padding-right: 0.625rem;
    }

    #sidebarNav.is-collapsed .sidebar-header-inner {
        justify-content: center;
    }

    #sidebarNav.is-collapsed .logout-form button {
        justify-content: center;
        padding-left: 0.625rem;
        padding-right: 0.625rem;
    }
</style>

{{--
    FIXED: was previously hand-rolled with raw utilities
    (bg-white/70 backdrop-blur-xl shadow-[...]) instead of the shared
    frost-panel-solid class the Seller sidebar already uses. Now both
    reference the same app.css definition — change the blur/opacity once,
    both sidebars update together.
--}}
<aside id="sidebarNav"
    class="frost-panel-solid fixed inset-y-0 left-0 z-50 w-64 -translate-x-full overflow-visible transition-[width,transform] duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:z-auto lg:translate-x-0">

    {{-- Sidebar content --}}
    <div id="sidebarContent" class="flex h-full w-full min-w-0 flex-col overflow-hidden">

        {{-- Logo/Header --}}
        <div class="flex h-20 shrink-0 items-center justify-between gap-2 border-b border-white/50 px-4">

            <div class="sidebar-header-inner flex min-w-0 items-center gap-3">

                {{-- FIXED: backslashes -> forward slashes (URLs always need /, this was breaking on production) --}}
                <img src="{{ asset('storage/images/Shopleap Logo.png') }}" alt="Shopleap logo"
                    class="h-11 w-11 shrink-0 rounded-xl object-contain">

                <div class="brand-text min-w-0 flex-1">
                    <h1 class="text-lg font-bold leading-tight text-primary">
                        SHOPLEAP
                    </h1>

                    <p class="text-xs text-charcoal/60">
                        Admin Control Panel
                    </p>
                </div>

            </div>

            <div class="flex shrink-0 items-center gap-1.5">

                {{-- Desktop collapse toggle — now uses frost-btn instead of hand-rolled border/bg/blur --}}
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

                <span class="nav-label">System Overview</span>
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


            <a href="#" title="Logistics Partners"
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
                Financials & Logs
            </p>


            <a href="#" title="All Transactions"
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


            <a href="#" title="System Activity Logs"
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


            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Configuration
            </p>


            <a href="#" title="Global Platform Settings"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'settings'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426.756-2.924-1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

                </svg>

                <span class="nav-label">Global Platform Settings</span>
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


{{-- Logout confirmation modal — kept as a sibling of <aside>, same reasoning as Seller's version:
     the sidebar's translate-x-* transform would otherwise trap a fixed-position modal inside it. --}}
<div id="logoutModalOverlay"
    class="fixed inset-0 z-[90] hidden items-center justify-center bg-charcoal/40 p-4 backdrop-blur-sm">

    {{-- FIXED: was hand-rolled (border-white/60 bg-white/90 backdrop-blur-2xl) -> now frost-panel-solid --}}
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
            {{-- FIXED: was hand-rolled border/bg -> now frost-btn --}}
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


<script>
    (function() {

        const sidebar = document.getElementById('sidebarNav');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const toggleBtn = document.getElementById('sidebarToggleBtn');


        if (toggleBtn) {

            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('is-collapsed');
            });

        }

        function isOpen() {
            return !sidebar?.classList.contains('-translate-x-full');
        }

        function openSidebar() {
            sidebar?.classList.remove('-translate-x-full');
            overlay?.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar?.classList.add('-translate-x-full');
            overlay?.classList.add('hidden');
        }

        function toggleSidebar() {
            if (isOpen()) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }


        document.addEventListener('click', function(e) {

            if (e.target.closest('[data-sidebar-toggle]')) {

                e.preventDefault();
                toggleSidebar();

            }

        });


        if (closeBtn) {

            closeBtn.addEventListener('click', closeSidebar);

        }


        if (overlay) {

            overlay.addEventListener('click', closeSidebar);

        }


        const logoutForm = document.getElementById('logoutForm');
        const logoutTriggerBtn = document.getElementById('logoutTriggerBtn');
        const logoutModalOverlay = document.getElementById('logoutModalOverlay');
        const logoutCancelBtn = document.getElementById('logoutCancelBtn');
        const logoutConfirmBtn = document.getElementById('logoutConfirmBtn');

        function openLogoutModal() {
            logoutModalOverlay.classList.remove('hidden');
            logoutModalOverlay.classList.add('flex');
        }

        function closeLogoutModal() {
            logoutModalOverlay.classList.add('hidden');
            logoutModalOverlay.classList.remove('flex');
        }

        if (logoutTriggerBtn) {
            logoutTriggerBtn.addEventListener('click', openLogoutModal);
        }

        if (logoutCancelBtn) {
            logoutCancelBtn.addEventListener('click', closeLogoutModal);
        }

        if (logoutModalOverlay) {
            logoutModalOverlay.addEventListener('click', function(e) {
                if (e.target === logoutModalOverlay) closeLogoutModal();
            });
        }

        if (logoutConfirmBtn) {
            logoutConfirmBtn.addEventListener('click', function() {
                logoutForm.submit();
            });
        }


        window.addEventListener('resize', function() {

            if (window.innerWidth >= 1024) {

                sidebar.classList.remove('-translate-x-full');

            } else {

                sidebar.classList.remove('is-collapsed');

            }

        });


    })();
</script>
