<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/messaging.js'])

    <title>Messages | Shopleap</title>
</head>


<body class="flex h-dvh flex-col overflow-hidden text-charcoal">

    <div class="frost-surface"></div>

    <div class="relative z-30 shrink-0">
        <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />
    </div>

    <main class="mx-auto flex min-h-0 w-full max-w-6xl flex-1 flex-col gap-2 px-3 py-3 sm:px-6 sm:py-4">
        <div class="flex shrink-0 items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-lg font-bold tracking-tight text-charcoal sm:text-xl">Messages</h1>
                <p class="hidden truncate text-sm text-charcoal/60 sm:block">
                    Chats with sellers and your support tickets.
                </p>
            </div>
            <a href="{{ route('help.index') }}"
                class="shrink-0 rounded-lg border border-primary/30 bg-white/70 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary/5">
                Help Center
            </a>
        </div>

        @include('messaging._panel', ['variant' => 'flat'])
    </main>

</body>

</html>
