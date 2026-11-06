<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/messaging.js'])

    <title>Messages | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <div class="frost-surface"></div>

    {{-- Header --}}
    <div class="relative z-30 shrink-0">
        <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />
    </div>

    {{-- Main Content --}}
    <main class="relative z-10 mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 px-3 py-5 md:px-8 md:py-6">
        @include('Buyer.messages._content')
    </main>

    {{-- Footer --}}
    <x-footer />

</body>

</html>
