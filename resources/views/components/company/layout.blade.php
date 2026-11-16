@props(['title', 'description' => '', 'scripts' => []])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | ShopLeap Company</title>
    @vite(array_merge(['resources/css/app.css'], $scripts, ['resources/js/app.js']))
    <link rel="icon" href="{{ asset('images/Shopleap-Logo.png') }}">
</head>
<body class="min-h-screen bg-ice-blue text-charcoal">
    <div class="flex min-h-screen">
        <x-navbars.company />
        <div class="flex min-w-0 flex-1 flex-col">
            <x-seller.page-header :title="$title" :description="$description" class="bg-surface! shadow-none! backdrop-blur-none!" />
            <main class="flex-1 space-y-5 p-4 sm:p-6">
                @if (session('status'))<p role="status" class="rounded-sm border border-line bg-surface p-4 text-success">{{ session('status') }}</p>@endif
                @if ($errors->any())<div role="alert" class="rounded-sm border border-danger bg-surface p-4 text-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
