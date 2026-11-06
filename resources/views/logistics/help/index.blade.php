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
                class="workspace-header">
                <div class="min-w-0">
                    <x-nav.toggle target="workspace-navigation" />
                    <div class="min-w-0">
                        <h2 class="workspace-header-title">Help Center</h2>
                        <p class="workspace-header-subtitle">Open a ticket with Shopleap Support</p>
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
