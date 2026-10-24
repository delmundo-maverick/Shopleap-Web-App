@props(['product', 'variant' => 'icon'])

@php
    $id = $product->id;

    $base = match ($variant) {
        'full'
            => 'inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-light-gray bg-white px-4 py-3 text-sm font-semibold text-charcoal transition hover:border-sale-red hover:text-sale-red',
        'bar'
            => 'flex h-12 w-12 shrink-0 items-center justify-center rounded-sm border border-light-gray bg-white transition active:bg-ice-blue',
        default
            => 'flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm shadow-charcoal/10 backdrop-blur transition hover:scale-110 hover:bg-white',
    };
@endphp

{{-- .prevent.stop keeps the click from following a parent <a> (product cards are links). --}}
<button type="button" x-data @click.prevent.stop="$store.wishlist.toggle({{ $id }})"
    :aria-pressed="$store.wishlist.has({{ $id }})"
    :aria-label="$store.wishlist.has({{ $id }}) ? 'Remove from wishlist' : 'Add to wishlist'"
    {{ $attributes->merge(['class' => $base]) }}>

    <svg class="h-5 w-5 transition"
        :class="$store.wishlist.has({{ $id }}) ? 'fill-sale-red stroke-sale-red' : 'fill-none stroke-charcoal/60'"
        viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
    </svg>

    @if ($variant === 'full')
        <span x-text="$store.wishlist.has({{ $id }}) ? 'Wishlisted' : 'Add to Wishlist'">Add to Wishlist</span>
    @endif
</button>
