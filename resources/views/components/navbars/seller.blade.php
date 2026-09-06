@props(['active' => null])

<!-- Mobile dark backdrop, shown only while sidebar is open -->
<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-charcoal/40 lg:hidden"></div>

<aside id="sidebarNav"
    class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-light-gray bg-white transition-transform duration-300 ease-in-out lg:static lg:z-auto lg:w-64 lg:translate-x-0">

    <!-- BRAND -->
    <div class="flex h-20 items-center gap-3 border-b border-light-gray px-6">

        <!-- LOGO PLACEHOLDER — replace /public/images/shopleap-logo.png with the real logo file -->
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-seller-soft">
            <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap logo"
                class="h-full w-full rounded-xl object-contain">
        </div>

        <div class="flex-1">
            <h1 class="text-lg font-bold leading-tight text-seller">Shopleap</h1>
            <p class="text-xs text-charcoal/60">Seller Center</p>
        </div>

        <!-- Close button — mobile only -->
        <button type="button" id="sidebarCloseBtn"
            class="rounded-lg p-1.5 text-charcoal/50 transition hover:bg-seller-soft hover:text-seller lg:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- NAV -->
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">

        <a href="{{ route('seller.dashboard') }}"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'dashboard' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
        </a>

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">Order Management</p>

        <a href="{{ route('seller.products.create') }}"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'inventory' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            Manage Inventory
        </a>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'orders' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 0h6m-6 0H5a2 2 0 01-2-2V7a2 2 0 012-2h3.28a1 1 0 01.948.684l.5 1.5a1 1 0 00.949.685H15.28a1 1 0 01.948.684l.5 1.5a1 1 0 00.949.685H19a2 2 0 012 2v6a2 2 0 01-2 2h-4" />
            </svg>
            Order Notifications
        </a>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'prepare' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M20 12H4m16 0l-4-4m4 4l-4 4M4 12l4-4M4 12l4 4" />
            </svg>
            Prepare &amp; Ship Orders
        </a>

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">Insights</p>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'reports' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a1 1 0 001-1V9.5a1 1 0 00-.4-.8l-6-4.5a1 1 0 00-1.2 0l-6 4.5a1 1 0 00-.4.8V18a1 1 0 001 1z" />
            </svg>
            Generate Report
        </a>

        <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">General</p>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'chat' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Chat / Messaging
        </a>

        <a href="#"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
            {{ $active === 'account' ? 'bg-seller/10 text-seller font-semibold' : 'text-charcoal/70 hover:bg-seller-soft hover:text-seller' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Account Management
        </a>

    </nav>

    <!-- LOGOUT -->
    <div class="border-t border-light-gray p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-sale-red/80 transition hover:bg-sale-red/10 hover:text-sale-red">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        const openBtns = document.querySelectorAll('[data-sidebar-toggle]');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        openBtns.forEach(function(btn) {
            btn.addEventListener('click', openSidebar);
        });

        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) closeSidebar();
        });
    })();
</script>
