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
        <x-buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />
    </div>

    <main class="mx-auto flex min-h-0 w-full max-w-6xl flex-1 flex-col gap-2 px-3 py-3 sm:px-6 sm:py-4">
        <div class="flex shrink-0 items-baseline gap-3">
            <h1 class="text-lg font-bold tracking-tight text-charcoal sm:text-xl">Messages</h1>
            <p class="hidden truncate text-sm text-charcoal/60 sm:block">
                Conversations with sellers about your orders and inquiries.
            </p>
        </div>

        @include('messaging._panel', ['variant' => 'flat'])
    </main>

</body>

</html>
