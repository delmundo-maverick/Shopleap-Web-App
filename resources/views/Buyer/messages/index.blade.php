<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/messaging.js'])

    <title>Messages | Shopleap</title>
</head>

<body class="min-h-screen text-charcoal">

    {{-- Ambient background used across the app --}}
    <div class="frost-surface"></div>

    <x-buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8">
        <div class="mb-6 flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-charcoal">Messages</h1>
                <p class="mt-1 text-sm text-charcoal/60">
                    Conversations with sellers about your orders and inquiries.
                </p>
            </div>
        </div>

        <div class="frost-panel rounded-2xl p-4 sm:p-6">
            @include('messaging._panel', ['variant' => 'flat'])
        </div>
    </main>

    <x-buyer.message-widget />

</body>

</html>
