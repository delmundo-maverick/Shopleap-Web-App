@props(['active' => null])

{{-- Mobile Sidebar Overlay --}}
<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-charcoal/40 backdrop-blur-sm lg:hidden"></div>

<style>
    #sidebarNav.is-collapsed {
        width: 5rem;
    }

    #sidebarNav.is-collapsed .nav-label,
    #sidebarNav.is-collapsed .brand-text,
    #sidebarNav.is-collapsed .nav-section-title,
    #sidebarNav.is-collapsed .badge-tag {
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
    class="frost-panel-solid fixed inset-y-0 left-0 z-50 w-64 -translate-x-full overflow-visible transition-[width,transform] duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:z-auto lg:translate-x-0 border-r border-white/50">

    {{-- Sidebar content --}}
    <div id="sidebarContent" class="flex h-full w-full min-w-0 flex-col overflow-hidden">

        {{-- Logo / Header --}}
        <div class="flex h-20 shrink-0 items-center justify-between gap-2 border-b border-white/50 px-4">

            <div class="sidebar-header-inner flex min-w-0 items-center gap-3">
                <a href="{{ route('logistics.dashboard') }}" class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap logo"
                        class="h-11 w-11 shrink-0 rounded-xl object-contain">

                    <div class="brand-text min-w-0 flex-1">
                        <h1 class="text-lg font-bold leading-tight text-primary">
                            SHOPLEAP
                        </h1>
                        <p class="text-xs text-charcoal/60">
                            Logistics Hub
                        </p>
                    </div>
                </a>
            </div>

            <div class="flex shrink-0 items-center gap-1.5">

                {{-- Desktop collapse toggle button --}}
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

        {{-- Navigation Links --}}
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">

            <p class="nav-section-title px-3 pb-2 pt-2 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Overview
            </p>

            <a href="{{ route('logistics.dashboard') }}" title="Dashboard"
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
                Rider Management
            </p>

            <a href="{{ route('logistics.dashboard') }}" title="Rider Applications"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'riders'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="nav-label">Rider Applications</span>
                <span class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

            <a href="{{ route('logistics.dashboard') }}" title="Active Riders"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'riders-active'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="nav-label">Active Riders</span>
                <span class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Parcel Operations
            </p>

            <a href="{{ route('logistics.dashboard') }}" title="Incoming Parcels"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'incoming'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M20 12H4m16 0l-6-6m6 6l-6 6" />
                </svg>
                <span class="nav-label">Incoming Parcels</span>
                <span class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

            <a href="{{ route('logistics.dashboard') }}" title="Sort & Assign"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'sorting'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                <span class="nav-label">Sort &amp; Assign</span>
                <span class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

            <a href="{{ route('logistics.dashboard') }}" title="Monitor Deliveries"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'monitor'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <span class="nav-label">Monitor Deliveries</span>
                <span class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Reports
            </p>

            <a href="{{ route('logistics.dashboard') }}" title="Reports"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'reports'
                    ? 'frost-chip text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 17v-6h6v6M4 21h16a1 1 0 001-1V4a1 1 0 00-1-1H4a1 1 0 00-1 1v16a1 1 0 001 1z" />
                </svg>
                <span class="nav-label">Reports</span>
                <span class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

        </nav>

        {{-- Logout Trigger Section --}}
        <div class="shrink-0 border-t border-white/50 p-3">

            <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="logout-form">
                @csrf
                <button type="button" id="logoutTriggerBtn"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-sale-red/80 transition hover:bg-sale-red/10 hover:text-sale-red">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="nav-label">Log Out</span>
                </button>
            </form>

        </div>

    </div>

</aside>

{{-- Logout Confirmation Modal --}}
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
        <p class="mt-1.5 text-sm text-charcoal/60">You'll need to sign back in to access Logistics Hub.</p>

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

{{-- Sidebar Controller Script --}}
<script>
    (function() {
        const sidebar = document.getElementById('sidebarNav');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const toggleBtn = document.getElementById('sidebarToggleBtn');

        // Toggle Desktop Collapsed state
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('is-collapsed');
            });
        }

        // Check if Mobile Sidebar is Open
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

        // Global Event Delegation for buttons triggering sidebar (e.g. data-sidebar-toggle)
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

        // Modal Elements & Handler Logic
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

        // Sync viewport resizing states
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
            } else {
                sidebar.classList.remove('is-collapsed');
            }
        });
    })();
</script>
