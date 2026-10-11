<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/help.js'])

    <title>Help Center | Shopleap Logistics</title>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    <div class="flex min-h-screen">
        <x-navbars.logistics active="help" />

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-charcoal/10 px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold leading-tight text-charcoal sm:text-xl">Help Center</h2>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">Open a ticket with Shopleap Support</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-3 sm:p-6">
                @include('help._center')
            </main>
        </div>
    </div>
</body>

</html>
