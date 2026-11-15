<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $title }} | Shopleap</title>
</head>
<body class="min-h-screen bg-ice-blue text-charcoal" x-data="{}">
    <div class="flex min-h-screen">
        <x-dynamic-component :component="$navigation" />
        <div class="flex min-w-0 flex-1 flex-col">
            <x-seller.page-header :title="$title" description="Account balances and transaction history" class="bg-surface! bg-none! shadow-none! backdrop-blur-none!" />
            <main class="space-y-5 p-4 sm:p-6">
                @include('money.history')
            </main>
        </div>
    </div>
</body>
</html>
