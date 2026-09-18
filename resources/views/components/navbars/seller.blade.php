@props(['active' => null])

<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-charcoal/40 backdrop-blur-sm lg:hidden"></div>

<style> 
    #sidebarNav.is-collapsed {
        width: 5rem;
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
                        Seller Center
                    </p>
                </div>

            </div>

            <div class="flex shrink-0 items-center gap-1.5">

                {{-- Desktop collapse toggle (hamburger, square, inside the navbar) --}}
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

            <a href="{{ route('seller.dashboard') }}" title="Dashboard"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'dashboard'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>

                <span class="nav-label">Dashboard</span>
            </a>


            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Order Management
            </p>


            <a href="{{ route('seller.products.index') }}" title="Manage Inventory"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'inventory'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>

                <span class="nav-label">Manage Inventory</span>
            </a>


            <a href="{{ route('seller.orders.index', ['status' => 'pending']) }}" title="Order Notifications"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'orders'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 0h6m-6 0H5a2 2 0 01-2-2V7a2 2 0 012-2h3.28a1 1 0 01.948.684l.5 1.5a1 1 0 00.949.685H15.28a1 1 0 01.948.684l.5 1.5a1 1 0 00.949.685H19a2 2 0 012 2v6a2 2 0 01-2 2h-4" />
                </svg>

                <span class="nav-label">Order Notifications</span>
            </a>


            <a href="{{ route('seller.orders.index', ['status' => 'to_ship']) }}" title="Prepare & Ship Orders"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'prepare'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M20 12H4m16 0l-4-4m4 4l-4 4M4 12l4-4M4 12l4 4" />
                </svg>

                <span class="nav-label">Prepare &amp; Ship Orders</span>
            </a>


            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Insights
            </p>


            <a href="#" title="Generate Report"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'reports'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a1 1 0 001-1V9.5a1 1 0 00-.4-.8l-6-4.5a1 1 0 00-1.2 0l-6 4.5a1 1 0 00-.4.8V18a1 1 0 001 1z" />
                </svg>

                <span class="nav-label">Generate Report</span>
            </a>


            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                General
            </p>


            <a href="#" title="Chat / Messaging"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'chat'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>

                <span class="nav-label">Chat / Messaging</span>
            </a>


            <a href="#" title="Account Management"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'account'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>

                <span class="nav-label">Account Management</span>
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


{{-- Logout confirmation modal — deliberately placed OUTSIDE <aside>, as a sibling.
     The sidebar uses translate-x-* (a CSS transform) to slide/collapse, and any
     ancestor with a transform becomes the containing block for position:fixed
     descendants. Keeping this modal as a sibling (not nested) is what makes it
     center on the real viewport instead of being trapped inside the sidebar. --}}
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
        <p class="mt-1.5 text-sm text-charcoal/60">You'll need to sign back in to access Seller Center.</p>

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
