@props(['audience', 'variant' => 'frosted'])

@php
    $items = \App\Models\Announcement::active()->forAudience($audience)->latest()->get();
    $isFrosted = $variant === 'frosted';
    $cardClass = $isFrosted
        ? 'frost-panel border border-primary/20 bg-primary/5'
        : 'border border-primary/15 bg-primary/5 shadow-sm shadow-charcoal/5';
@endphp

@if ($items->isNotEmpty())
    <div class="mb-4 space-y-2">
        @foreach ($items as $item)
            <div data-announcement-id="{{ $item->id }}"
                class="announcement-banner flex items-start gap-3 rounded-2xl px-4 py-3 {{ $cardClass }}">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                <div class="min-w-0 flex-1 text-sm">
                    <p class="font-bold text-charcoal">{{ $item->title }}</p>
                    <p class="mt-0.5 text-charcoal/70">{{ $item->body }}</p>
                </div>
                <button type="button"
                    class="announcement-dismiss-btn shrink-0 text-charcoal/40 transition hover:text-charcoal"
                    data-id="{{ $item->id }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endforeach
    </div>

    @once
        @vite('resources/js/components/announcement-banner.js')
    @endonce
@endif
