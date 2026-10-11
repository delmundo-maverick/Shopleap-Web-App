<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/help.js'])

    <title>Help Center | Shopleap</title>
</head>

<body class="min-h-screen text-charcoal">
    <div class="frost-surface"></div>

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    <main class="px-3 py-4 sm:px-6 sm:py-6">
        @include('help._center')
    </main>
</body>

</html>
