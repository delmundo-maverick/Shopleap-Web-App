@props([
    'seller',
    'followersCount' => null,
    'isFollowing' => null,
    'variant' => 'light',
])

@php
    $id = $seller->id;

    $base = 'inline-flex items-center justify-center gap-2 border px-3 py-2 text-xs font-bold transition disabled:cursor-wait disabled:opacity-60';

    [$followClass, $followingClass] = match ($variant) {
        'inverse' => [
            'border-white/80 bg-transparent text-white hover:bg-white/10',
            'border-white bg-white text-primary hover:bg-white/90',
        ],
        default => [
            'border-primary bg-primary/5 text-primary hover:bg-primary/10',
            'border-primary bg-primary text-white hover:bg-sky-blue',
        ],
    };
@endphp

<button type="button" x-data
    x-init="$store.follow.prime({{ $id }}, @js($followersCount), @js((bool) $isFollowing))"
    @click="$store.follow.toggle({{ $id }})"
    :aria-pressed="$store.follow.has({{ $id }})"
    :aria-label="$store.follow.has({{ $id }}) ? 'Unfollow this shop' : 'Follow this shop'"
    :disabled="!! $store.follow.pending[{{ $id }}]"
    :class="$store.follow.has({{ $id }}) ? @js($followingClass) : @js($followClass)"
    {{ $attributes->merge(['class' => $base]) }}>

    <svg x-show="!$store.follow.has({{ $id }})" class="h-4 w-4" fill="none" stroke="currentColor"
        viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
    </svg>

    <svg x-show="$store.follow.has({{ $id }})" x-cloak class="h-4 w-4" fill="none" stroke="currentColor"
        viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>

    <span x-text="$store.follow.has({{ $id }}) ? 'Following' : 'Follow'">Follow</span>
</button>
