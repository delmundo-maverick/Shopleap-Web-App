@props(['active' => null])

<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-charcoal/40 lg:hidden"></div>

<aside id="sidebarNav"
    class="fixed inset-y-0 left-0 z-50 w-64 overflow-visible border-r border-light-gray bg-white transition-[width] duration-300 ease-in-out lg:static lg:z-auto lg:translate-x-0">

    {{-- Desktop collapse toggle --}}
    <button type="button" id="sidebarToggleBtn"
        class="absolute -right-3 top-8 z-[60] hidden h-7 w-7 items-center justify-center rounded-full border border-light-gray bg-white text-charcoal/50 shadow-sm transition-all duration-300 hover:bg-ice-blue hover:text-primary lg:flex"
        title="Toggle Sidebar">

        <svg id="sidebarToggleIcon" class="h-4 w-4 transition-transform duration-300" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </button>

    {{-- Sidebar content --}}
    <div id="sidebarContent"
        class="flex h-full w-full min-w-0 flex-col overflow-hidden transition-opacity duration-200">

        {{-- Logo/Header --}}
        <div class="flex h-20 shrink-0 items-center justify-between border-b border-light-gray px-6">

            <div class="flex min-w-0 items-center gap-3">

                <img src="{{ asset('images/shopleap-logo.png') }}" alt="Shopleap logo"
                    class="h-11 w-11 shrink-0 rounded-xl object-contain">

                <div class="min-w-0 flex-1">
                    <h1 class="text-lg font-bold leading-tight text-primary">
                        SHOPLEAP
                    </h1>

                    <p class="text-xs text-charcoal/60">
                        Admin Control Panel
                    </p>
                </div>

            </div>

            {{-- Mobile close button --}}
            <button type="button" id="sidebarCloseBtn"
                class="rounded-lg p-1.5 text-charcoal/50 transition hover:bg-ice-blue hover:text-primary lg:hidden">

                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>

            </button>

        </div>

        {{-- Navigation --}}
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">

            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'dashboard'
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>

                <span>System Overview</span>
            </a>


            <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                User Management
            </p>


            <a href="#"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'users'
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>

                <span>Buyers & Customers</span>
            </a>


            <a href="{{ route('admin.registrations.sellers.index') }}"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'sellers'
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h6m-6 0V11m6 0V5" />
                </svg>

                <span>Sellers & Merchants</span>
            </a>


            <a href="#"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'logistics'
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8" />
                </svg>

                <span>Logistics Partners</span>
            </a>


            <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Financials & Logs
            </p>


            <a href="#"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'transactions'
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2-2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>

                <span>All Transactions</span>
            </a>


            <a href="#"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'logs'
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>

                <span>System Activity Logs</span>
            </a>


            <p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wide text-charcoal/40">
                Configuration
            </p>


            <a href="#"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                {{ $active === 'settings'
                    ? 'bg-primary/10 text-primary font-semibold'
                    : 'text-charcoal/70 hover:bg-ice-blue hover:text-primary' }}">

                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426.756-2.924-1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

                </svg>

                <span>Global Platform Settings</span>
            </a>

        </nav>


        {{-- Logout --}}
        <div class="shrink-0 border-t border-light-gray p-3">

            <form method="POST" action="{{ route('logout') }}">

                @csrf

                <button type="submit"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-sale-red/80 transition hover:bg-sale-red/10 hover:text-sale-red">

                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />

                    </svg>

                    <span>Logout</span>

                </button>

            </form>

        </div>

    </div>

</aside>


<script>
    (function() {

        const sidebar = document.getElementById('sidebarNav');
        const sidebarContent = document.getElementById('sidebarContent');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const toggleIcon = document.getElementById('sidebarToggleIcon');

        let collapsed = false;


        /*
        |--------------------------------------------------------------------------
        | Desktop Sidebar Collapse
        |--------------------------------------------------------------------------
        */

        function applyCollapsedState() {

            if (window.innerWidth < 1024) {
                return;
            }

            if (collapsed) {

                // Collapse the actual sidebar
                sidebar.style.width = '0px';
                sidebar.style.borderRightWidth = '0px';

                // Completely hide the contents
                sidebarContent.style.opacity = '0';
                sidebarContent.style.pointerEvents = 'none';

                // Move the arrow
                toggleIcon.style.transform = 'rotate(180deg)';

            } else {

                // Restore sidebar
                sidebar.style.width = '16rem';
                sidebar.style.borderRightWidth = '';

                // Show contents
                sidebarContent.style.opacity = '1';
                sidebarContent.style.pointerEvents = '';

                // Restore arrow
                toggleIcon.style.transform = 'rotate(0deg)';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Desktop Toggle
        |--------------------------------------------------------------------------
        */

        if (toggleBtn) {

            toggleBtn.addEventListener('click', function() {

                collapsed = !collapsed;

                applyCollapsedState();

            });

        }


        /*
        |--------------------------------------------------------------------------
        | Mobile Sidebar
        |--------------------------------------------------------------------------
        */

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

                if (sidebar?.classList.contains('-translate-x-full')) {

                    openSidebar();

                } else {

                    closeSidebar();

                }

            }

        });


        if (closeBtn) {

            closeBtn.addEventListener('click', closeSidebar);

        }


        if (overlay) {

            overlay.addEventListener('click', closeSidebar);

        }


        /*
        |--------------------------------------------------------------------------
        | Responsive Resize
        |--------------------------------------------------------------------------
        */

        window.addEventListener('resize', function() {

            if (window.innerWidth >= 1024) {

                sidebar.classList.remove('-translate-x-full');

                applyCollapsedState();

            } else {

                // Reset desktop styles
                sidebar.style.width = '';
                sidebar.style.borderRightWidth = '';

                sidebarContent.style.opacity = '';
                sidebarContent.style.pointerEvents = '';

                // Reset desktop collapsed state
                collapsed = false;

            }

        });


    })();
</script>
