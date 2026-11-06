@props(['title', 'subtitle' => null, 'active' => null, 'scripts' => []])
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(array_merge(['resources/css/app.css'], $scripts, ['resources/js/app.js']))

    <title>{{ $title }} | Shopleap Logistics</title>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    <div class="flex min-h-screen">
        <x-navbars.logistics :active="$active" />

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="workspace-header">
                <div class="min-w-0">
                    <x-nav.toggle target="workspace-navigation" />
                    <div>
                        <h2 class="workspace-header-title">{{ $title }}</h2>
                        @if ($subtitle)
                            <p class="workspace-header-subtitle">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                <x-notification-bell />
            </header>

            <main class="flex-1 space-y-5 p-6">
                <x-announcement-banner audience="logistics" />

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

                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
