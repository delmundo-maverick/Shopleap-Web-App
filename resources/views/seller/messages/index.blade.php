<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/messaging.js'])

    <title>Messages | Shopleap Seller</title>
</head>

{{-- x-data kept: messaging._panel may rely on Alpine. The sidebar toggle itself no longer needs it. --}}

<body class="h-dvh overflow-hidden text-charcoal" x-data="{}">
    <div class="frost-surface pointer-events-none" aria-hidden="true"></div>

    <div class="flex h-dvh">
        <x-navbars.seller active="messages" />

        <div class="flex h-dvh min-w-0 flex-1 flex-col">
            <header
                class="frost-panel-solid z-10 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-charcoal/10 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    {{-- Same toggle as the dashboard --}}
                    <x-nav.toggle target="workspace-navigation" />
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-bold leading-tight text-charcoal sm:text-xl">Messages</h2>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">Buyers, Admin, Logistics and Riders
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-4">
                    <x-notification-bell />
                </div>
            </header>

            <main class="flex min-h-0 min-w-0 flex-1 flex-col p-2 sm:p-4">
                @include('messaging._panel', ['variant' => 'frosted'])
            </main>
        </div>
    </div>
</body>

</html>
