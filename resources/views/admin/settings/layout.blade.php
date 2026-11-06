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
                class="workspace-header">
                <div class="min-w-0">
                    <x-nav.toggle target="workspace-navigation" />
                    <div>
                        <h2 class="workspace-header-title">@yield('title')</h2>
                        <p class="workspace-header-subtitle">@yield('subtitle')</p>
                    </div>
                </div>
                <x-notification-bell />
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
