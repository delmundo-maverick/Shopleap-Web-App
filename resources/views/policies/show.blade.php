<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php($isPrivacy = $policy?->slug === \App\Models\PlatformPolicy::PRIVACY_SLUG || request()->routeIs('privacy*'))
    @php($fallbackTitle = $isPrivacy ? 'Privacy Policy' : 'Terms and Conditions')

    <title>{{ $policy?->title ?? $fallbackTitle }} | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <header class="border-b border-line bg-surface">
        <div class="mx-auto flex w-full max-w-4xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <x-logo :href="\App\Support\Portal::url('main')" image="images/Shopleap-Logo.png" class="text-primary" />
            <nav aria-label="Policies" class="flex flex-wrap gap-4 text-sm font-medium">
                <a href="{{ route('terms') }}" aria-current="{{ ! $isPrivacy ? 'page' : 'false' }}" class="underline-offset-4 hover:underline {{ ! $isPrivacy ? 'text-primary underline' : 'text-charcoal' }}">Terms and Conditions</a>
                <a href="{{ route('privacy') }}" aria-current="{{ $isPrivacy ? 'page' : 'false' }}" class="underline-offset-4 hover:underline {{ $isPrivacy ? 'text-primary underline' : 'text-charcoal' }}">Privacy Policy</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-4xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
        <a href="{{ $backUrl }}"
            class="mb-4 inline-flex items-center gap-1.5 text-xs font-semibold text-charcoal/60 transition hover:text-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            {{ $backLabel }}
        </a>

        <article class="rounded-sm border border-line bg-surface p-5 sm:p-8">
            @if ($policy && $policy->hasPublishedContent())
                <h1 class="text-2xl font-bold text-charcoal sm:text-3xl">{{ $policy->title }}</h1>
                <p class="mt-2 text-xs text-charcoal">
                    Last updated {{ $policy->updated_at->timezone('Asia/Manila')->format('F j, Y') }} &middot; Version {{ $policy->version }}
                </p>
                <p class="mt-4 rounded-sm border border-line bg-ice-blue p-4 text-sm leading-6">For buyers, sellers, logistics companies, hub managers, riders, administrators, and visitors across Shopleap's website and apps.</p>

                <div class="mt-6 max-w-none break-words text-sm leading-7 text-charcoal">
                    {!! $policy->contentHtml() !!}
                </div>
            @else
                <h1 class="text-2xl font-bold text-charcoal sm:text-3xl">
                    {{ $policy?->title ?? $fallbackTitle }}
                </h1>
                <div class="mt-5 rounded-sm border border-line bg-ice-blue px-5 py-4 text-sm leading-6 text-charcoal">
                    This policy has not been published yet. Please check back later or
                    <a href="{{ \App\Support\Portal::url('main') }}" class="font-semibold text-primary hover:underline">return to
                        Shopleap</a> if you need assistance.
                </div>
            @endif
        </article>
        <aside class="mt-5 rounded-sm border border-line bg-surface p-5 text-sm leading-6" aria-label="Policy support">
            <p class="font-semibold">Need help with these policies?</p>
            <p class="mt-1">For account, order, partner, or privacy questions, contact <a href="mailto:{{ $supportEmail }}" class="break-all font-medium text-primary underline">{{ $supportEmail }}</a>. Include the relevant reference, and keep passwords and OTPs private.</p>
        </aside>
    </main>

    <x-footer />

</body>

</html>
