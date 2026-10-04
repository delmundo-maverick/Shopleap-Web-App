<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ $policy->title }} | Shopleap</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <header class="border-b border-light-gray bg-white">
        <div class="mx-auto flex h-20 max-w-3xl items-center px-5 sm:px-8">
            <a href="{{ route('home') }}" class="text-lg font-bold text-primary">Shopleap</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-5 py-10 sm:px-8">
        <div class="rounded-2xl bg-white p-8 shadow-sm shadow-charcoal/5">
            <h1 class="text-2xl font-bold text-charcoal">{{ $policy->title }}</h1>
            <p class="mt-1 text-xs text-charcoal/50">Last updated {{ $policy->updated_at->format('F j, Y') }} ·
                v{{ $policy->version }}</p>

            <div class="prose prose-sm mt-6 max-w-none whitespace-pre-line text-charcoal/80">
                {{ $policy->body }}
            </div>
        </div>
    </main>

</body>

</html>
