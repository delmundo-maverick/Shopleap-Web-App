@props(['active' => null])

<button type="button" id="sidebarOpenBtn"
    class="fixed left-4 top-4 z-30 hidden rounded-xl border border-light-gray bg-white p-2.5 shadow-sm transition hover:bg-ice-blue lg:block"
    title="Open Sidebar">
    <svg class="h-6 w-6 text-charcoal/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
</button>

<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-charcoal/40 lg:hidden"></div>

<aside id="sidebarNav"
    class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-light-gray bg-white transition-all duration-300 ease-in-out lg:hidden">

    <div class="flex h-20 items-center justify-between border-b border-light-gray px-6">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ice-blue"></div>
            <div class="flex-1">
                <h1 class="text-lg font-bold leading-tight text-primary">Shopleap</h1>
                <p class="text-xs text-charcoal/60">Logistics Center</p>
            </div>
        </div>

        <button type="button" id="sidebarCollapseBtn"
            class="hidden rounded-lg p-1.5 text-charcoal/50 transition hover:bg-ice-blue hover:text-primary lg:block"
            title="Collapse Sidebar">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <button type="button" id="sidebarCloseBtn"
            class="rounded-lg p-1.5 text-charcoal/50 transition hover:bg-ice-blue hover:text-primary lg:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'dashboard' ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Delivery Dashboard
        </a>

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">Deliveries</p>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'requests' ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            Available Requests
        </a>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'active-delivery' ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M13 16h6a1 1 0 001-1v-3.28a1 1 0 00-.293-.707l-2.72-2.72A1 1 0 0016.28 8H13m0 8V8m0 8H9m4-8H9m0 8a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM9 8H5a1 1 0 00-1 1v6a1 1 0 001 1" />
            </svg>
            Active Delivery
        </a>

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">Insights</p>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'profit' ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M12 8c-1.66 0-3 .9-3 2s1.34 2 3 2 3 .9 3 2-1.34 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 2v8m0 0v2m0-2c-1.11 0-2.08-.402-2.599-1" />
            </svg>
            Profit Dashboard
        </a>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'history' ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Delivery History
        </a>

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">General</p>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'chat' ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Chat / Messaging
        </a>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'account' ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Account Management
        </a>

    </nav>

    <div class="border-t border-light-gray p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-sale-red/80 transition hover:bg-sale-red/10 hover:text-sale-red">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </button>
        </form>
    </div>

</aside>

<script>
    (function() {
        const sidebar = document.getElementById('sidebarNav');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const openBtn = document.getElementById('sidebarOpenBtn');

        // Toggle Desktop Visibility (Default is hidden)
        function showDesktopSidebar() {
            sidebar?.classList.remove('lg:hidden');
            openBtn?.classList.add('lg:hidden');
        }

        function hideDesktopSidebar() {
            sidebar?.classList.add('lg:hidden');
            openBtn?.classList.remove('lg:hidden');
        }

        if (openBtn) openBtn.addEventListener('click', showDesktopSidebar);
        if (collapseBtn) collapseBtn.addEventListener('click', hideDesktopSidebar);

        // Mobile Controls
        function openSidebar() {
            sidebar?.classList.remove('-translate-x-full');
            overlay?.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar?.classList.add('-translate-x-full');
            overlay?.classList.add('hidden');
        }

        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-sidebar-toggle]')) {
                e.preventDefault();
                sidebar?.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
            }
        });

        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) closeSidebar();
        });
    })();
</script>
