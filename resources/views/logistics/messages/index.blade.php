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
                class="workspace-header">
                <div class="min-w-0">
                    <x-nav.toggle target="workspace-navigation" />
                    <div class="min-w-0">
                        <h2 class="workspace-header-title">Messages</h2>
                        <p class="workspace-header-subtitle">Admin, Sellers and Riders</p>
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
