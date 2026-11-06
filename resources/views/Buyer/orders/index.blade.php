@use('App\Models\Order')
@use('Illuminate\Support\Str')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/orders.js'])
    <title>My Orders | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    {{-- Header --}}
    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    {{-- Main Content --}}
    <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-4 sm:px-6 sm:py-6 lg:px-8">
        @include('Buyer.orders._content')
    </main>

    <x-Buyer.message-widget />
    <x-footer />
</body>

</html>
