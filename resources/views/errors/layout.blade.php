<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') | Shopleap</title>
    @if (is_file(public_path('build/manifest.json')))
        @vite('resources/css/app.css')
    @endif
</head>
<body class="flex min-h-screen items-center justify-center bg-ice-blue p-6 font-sans text-charcoal">
    <main class="w-full max-w-lg rounded-2xl border border-line bg-white p-8 text-center shadow-sm">
        <p class="text-lg font-bold text-primary">Shopleap</p>
        <p class="mt-6 text-5xl font-bold text-primary">@yield('code')</p>
        <h1 class="mt-4 text-2xl font-bold">@yield('title')</h1>
        <p class="mt-3 text-sm leading-6 text-charcoal/70">@yield('message')</p>
        <a href="{{ url('/') }}" class="mt-6 inline-flex rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white">Go to home</a>
        <nav aria-label="Policies" class="mt-6 flex flex-wrap justify-center gap-4 text-sm text-primary">
            <a href="{{ \App\Support\Portal::route('main', 'terms') }}">Terms and Conditions</a>
            <a href="{{ \App\Support\Portal::route('main', 'privacy') }}">Privacy Policy</a>
        </nav>
    </main>
</body>
</html>
