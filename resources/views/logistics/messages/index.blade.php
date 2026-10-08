<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/messaging.js'])

    <title>Messages | Shopleap Logistics</title>
</head>

<body class="h-dvh overflow-hidden text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    <div class="flex h-dvh">
        <x-navbars.logistics active="messages" />

        <div class="flex h-dvh min-w-0 flex-1 flex-col">
            <header
                class="frost-panel-solid z-30 flex h-16 shrink-0 items-center justify-between border-b border-charcoal/10 px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" @click="$store.sidebar.open = true"
                        class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold leading-tight text-charcoal sm:text-xl">Messages</h2>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">Admin, Sellers and Riders</p>
                    </div>
                </div>
            </header>

            <main class="flex min-h-0 flex-1 flex-col p-3 sm:p-4">
                @include('messaging._panel', ['variant' => 'frosted'])
            </main>
        </div>
    </div>
</body>

</html>
