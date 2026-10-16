<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>@yield('title') | Shopleap Admin</title>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen text-charcoal" x-data="{ announcementModal: false, editingAnnouncement: null }">
    <div class="frost-surface"></div>

    <div class="flex min-h-screen">
        <x-navbars.admin active="settings" />

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">@yield('title')</h2>
                        <p class="text-sm text-charcoal/55">@yield('subtitle')</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div class="w-full space-y-5">

                    @if (session('status'))
                        <div
                            class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                            {{ session('status') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div
                            class="frost-panel rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @yield('content')

                </div>
            </main>
        </div>
    </div>

    @yield('modals')

</body>

</html>
