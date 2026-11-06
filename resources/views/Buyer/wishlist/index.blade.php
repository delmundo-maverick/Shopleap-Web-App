<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Wishlist | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-4 sm:px-8 sm:py-6">
        @include('Buyer.wishlist._content')
    </main>

    <x-Buyer.message-widget />
    <x-footer />
</body>

</html>
