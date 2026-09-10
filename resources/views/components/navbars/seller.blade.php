@props(['active' => null])

<aside id="sidebarNav"
    class="sidebar sticky top-0 flex h-screen w-64 shrink-0 flex-col border-r border-white/50 bg-white/70 backdrop-blur-xl">

    <!-- BRAND -->
    <div class="brand-row flex h-20 items-center gap-3 border-b border-white/50 px-5">
        <!-- LOGO PLACEHOLDER — replace /public/images/shopleap-logo.png with the real logo file -->
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-seller-soft">
            <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap logo"
                class="h-full w-full rounded-xl object-contain">
        </div>
        <div class="nav-label min-w-0 flex-1">
            <h1 class="truncate text-lg font-bold leading-tight text-seller">Shopleap</h1>
            <p class="truncate text-xs text-charcoal/60">Seller Center</p>
        </div>
    </div>

    <!-- HAMBURGER — toggles the sidebar between full and icon-only -->
    <button type="button" id="sidebarToggleBtn" aria-label="Toggle sidebar"
        class="flex h-12 w-full shrink-0 items-center gap-3 border-b border-white/50 px-5 text-charcoal/60 transition hover:bg-seller-soft hover:text-seller">
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <span class="nav-label text-sm font-medium">Collapse menu</span>
    </button>

    <!-- NAV -->
    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-5">

        <a href="{{ route('seller.dashboard') }}" title="Dashboard"
            class="nav-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'dashboard' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="nav-label">Dashboard</span>
        </a>

        <p class="nav-section-label px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
            Order Management</p>
        <hr class="nav-section-divider my-3 border-t border-white/60">

        <a href="{{ route('seller.products.index') }}" title="Manage Inventory"
            class="nav-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'inventory' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span class="nav-label">Manage Inventory</span>
        </a>

        <a href="#" title="Order Notifications"
            class="nav-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'orders' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 0h6m-6 0H5a2 2 0 01-2-2V7a2 2 0 012-2h3.28a1 1 0 01.948.684l.5 1.5a1 1 0 00.949.685H15.28a1 1 0 01.948.684l.5 1.5a1 1 0 00.949.685H19a2 2 0 012 2v6a2 2 0 01-2 2h-4" />
            </svg>
            <span class="nav-label">Order Notifications</span>
        </a>

        <a href="#" title="Prepare &amp; Ship Orders"
            class="nav-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'prepare' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M20 12H4m16 0l-4-4m4 4l-4 4M4 12l4-4M4 12l4 4" />
            </svg>
            <span class="nav-label">Prepare &amp; Ship Orders</span>
        </a>

        <p class="nav-section-label px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
            Insights</p>
        <hr class="nav-section-divider my-3 border-t border-white/60">

        <a href="#" title="Generate Report"
            class="nav-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'reports' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a1 1 0 001-1V9.5a1 1 0 00-.4-.8l-6-4.5a1 1 0 00-1.2 0l-6 4.5a1 1 0 00-.4.8V18a1 1 0 001 1z" />
            </svg>
            <span class="nav-label">Generate Report</span>
        </a>

        <p class="nav-section-label px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
            General</p>
        <hr class="nav-section-divider my-3 border-t border-white/60">

        <a href="#" title="Chat / Messaging"
            class="nav-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'chat' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="nav-label">Chat / Messaging</span>
        </a>

        <a href="#" title="Account Management"
            class="nav-item flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'account' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="nav-label">Account Management</span>
        </a>

    </nav>

    <!-- LOGOUT -->
    <div class="border-t border-white/50 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Logout"
                class="nav-item flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-sale-red/80 transition hover:bg-sale-red/10 hover:text-sale-red">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="nav-label">Logout</span>
            </button>
        </form>
    </div>

</aside>

<style>
    .sidebar {
        transition: width .25s ease;
    }

    .nav-item {
        white-space: nowrap;
    }

    .nav-section-divider {
        display: none;
    }

    /* Collapsed state: hide all text, center icons, show dividers instead of section labels */
    .sidebar.sidebar-collapsed {
        width: 5rem;
    }

    .sidebar.sidebar-collapsed .nav-label,
    .sidebar.sidebar-collapsed .nav-section-label {
        display: none;
    }

    .sidebar.sidebar-collapsed .nav-section-divider {
        display: block;
    }

    .sidebar.sidebar-collapsed .brand-row {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .sidebar.sidebar-collapsed #sidebarToggleBtn {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .sidebar.sidebar-collapsed .nav-item {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }
</style>

<script>
    // Sidebar collapse toggle — icon-only rail when collapsed, remembered across page loads.
    (function() {
        const sidebar = document.getElementById('sidebarNav');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const STORAGE_KEY = 'sidebarCollapsed';

        function applyState(collapsed) {
            sidebar.classList.toggle('sidebar-collapsed', collapsed);
        }

        applyState(localStorage.getItem(STORAGE_KEY) === '1');

        toggleBtn.addEventListener('click', function() {
            const collapsed = !sidebar.classList.contains('sidebar-collapsed');
            applyState(collapsed);
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        });
    })();
</script>
