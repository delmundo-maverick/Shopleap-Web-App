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

            {{-- 1. Dashboard --}}
            <a href="{{ route('logistics.dashboard') }}" title="Dashboard"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                {{ $active === 'dashboard'
                    ? 'frost-chip bg-primary/10 text-primary font-bold shadow-xs'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0 transition-colors {{ $active === 'dashboard' ? 'text-primary' : 'text-charcoal/50' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                </svg>
                <span class="nav-label">Dashboard</span>
            </a>

            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Rider Management
            </p>

            {{-- 2. Rider Applications --}}
            <a href="{{ route('logistics.riders.index') }}" title="Rider Applications"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                {{ $active === 'rider-apps' || $active === 'riders'
                    ? 'frost-chip bg-primary/10 text-primary font-bold shadow-xs'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0 transition-colors {{ $active === 'rider-apps' || $active === 'riders' ? 'text-primary' : 'text-charcoal/50' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="nav-label">Rider Applications</span>
            </a>

            {{-- 3. Active Riders --}}
            <a href="{{ route('logistics.dashboard') }}" title="Active Riders"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                {{ $active === 'riders-active'
                    ? 'frost-chip bg-primary/10 text-primary font-bold shadow-xs'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0 transition-colors {{ $active === 'riders-active' ? 'text-primary' : 'text-charcoal/50' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="nav-label">Active Riders</span>
                <span
                    class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Parcel Operations
            </p>

            {{-- 4. Pickup Requests --}}
            <a href="{{ route('logistics.pickups.index') }}" title="Pickup Requests"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                {{ $active === 'pickups'
                    ? 'frost-chip bg-primary/10 text-primary font-bold shadow-xs'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0 transition-colors {{ $active === 'pickups' ? 'text-primary' : 'text-charcoal/50' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4l3-3m0 0l3 3m-3-3v6" />
                </svg>
                <span class="nav-label">Pickup Requests</span>
            </a>

            {{-- 5. Incoming Parcels --}}
            <a href="{{ route('logistics.incoming.index') }}" title="Incoming Parcels"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                {{ $active === 'incoming'
                    ? 'frost-chip bg-primary/10 text-primary font-bold shadow-xs'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0 transition-colors {{ $active === 'incoming' ? 'text-primary' : 'text-charcoal/50' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <span class="nav-label">Incoming Parcels</span>
            </a>

            {{-- 6. Sort & Assign --}}
            <a href="{{ route('logistics.dashboard') }}" title="Sort & Assign"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                {{ $active === 'sorting'
                    ? 'frost-chip bg-primary/10 text-primary font-bold shadow-xs'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0 transition-colors {{ $active === 'sorting' ? 'text-primary' : 'text-charcoal/50' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                </svg>
                <span class="nav-label">Sort &amp; Assign</span>
                <span
                    class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

            {{-- 7. Monitor Deliveries --}}
            <a href="{{ route('logistics.dashboard') }}" title="Monitor Deliveries"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                {{ $active === 'monitor'
                    ? 'frost-chip bg-primary/10 text-primary font-bold shadow-xs'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0 transition-colors {{ $active === 'monitor' ? 'text-primary' : 'text-charcoal/50' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="nav-label">Monitor Deliveries</span>
                <span
                    class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
            </a>

            <p class="nav-section-title px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Reports
            </p>

            {{-- 8. Reports --}}
            <a href="{{ route('logistics.dashboard') }}" title="Reports"
                class="nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                {{ $active === 'reports'
                    ? 'frost-chip bg-primary/10 text-primary font-bold shadow-xs'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
                <svg class="h-5 w-5 shrink-0 transition-colors {{ $active === 'reports' ? 'text-primary' : 'text-charcoal/50' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="nav-label">Reports</span>
                <span
                    class="badge-tag ml-auto rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">Soon</span>
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
