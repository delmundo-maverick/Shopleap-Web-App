<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php($fallbackTitle = request()->routeIs('privacy*') ? 'Privacy Policy' : 'Terms and Conditions')

    <title>{{ $policy?->title ?? $fallbackTitle }} | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <x-nav.guest :links="[['href' => route('terms'), 'label' => 'Terms'], ['href' => route('privacy'), 'label' => 'Privacy']]" />

    <main class="mx-auto w-full max-w-4xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
        <a href="{{ $backUrl }}"
            class="mb-4 inline-flex items-center gap-1.5 text-xs font-semibold text-charcoal/60 transition hover:text-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            {{ $backLabel }}
        </a>

        <article class="rounded-2xl bg-white p-6 shadow-sm shadow-charcoal/5 sm:p-8">
            @if ($policy && $policy->hasPublishedContent())
                <h1 class="text-2xl font-bold text-charcoal sm:text-3xl">{{ $policy->title }}</h1>
                <p class="mt-2 text-xs text-charcoal/50">
                    Last updated {{ $policy->updated_at->format('F j, Y') }} · v{{ $policy->version }}
                </p>

                <div class="mt-6 max-w-none text-sm leading-7 text-charcoal/80">
                    {!! $policy->contentHtml() !!}
                </div>
            @else
                <h1 class="text-2xl font-bold text-charcoal sm:text-3xl">
                    {{ $policy?->title ?? 'Policy unavailable' }}
                </h1>
                <div class="mt-5 rounded-xl border border-primary/10 bg-ice-blue px-5 py-4 text-sm leading-6 text-charcoal/70">
                    This policy has not been published yet. Please check back later or
                    <a href="{{ route('home') }}" class="font-semibold text-primary hover:underline">return to
                        Shopleap</a> if you need assistance.
                </div>
            @endif
        </article>
    </main>

    <footer class="border-t border-light-gray bg-white">
        <div class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-2 px-5 py-5 text-xs text-charcoal/60 sm:flex-row sm:px-8">
            <p>© {{ date('Y') }} Shopleap. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('terms') }}" class="hover:text-primary">Terms</a>
                <a href="{{ route('privacy') }}" class="hover:text-primary">Privacy</a>
            </div>
        </div>
    </footer>

</body>

</html>
