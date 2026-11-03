@props(['product', 'variant' => 'icon'])

@php
    $id = $product->id;

    $base = match ($variant) {
        'full'
            => 'inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-line bg-white px-4 py-3 text-sm font-semibold text-charcoal transition-colors hover:border-sale-red hover:text-sale-red',
        'bar'
            => 'flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-line bg-white transition-colors active:bg-ice-blue',
        default
            => 'inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-line bg-white px-2.5 text-xs font-semibold text-charcoal transition-colors hover:border-sale-red hover:text-sale-red',
    };

    $iconSize = in_array($variant, ['full', 'bar'], true) ? 'h-5 w-5' : 'h-4 w-4';

    $labelAdd = $variant === 'full' ? 'Add to wishlist' : 'Save to wishlist';
@endphp

<button type="button" x-data @click.prevent.stop="$store.wishlist.toggle({{ $id }})"
    :aria-pressed="$store.wishlist.has({{ $id }})"
    :aria-label="$store.wishlist.has({{ $id }}) ? 'Remove from wishlist' : '{{ $labelAdd }}'"
    {{ $attributes->merge(['class' => $base]) }}>

    @if (!in_array($variant, ['full', 'bar'], true))
        <span x-text="$store.wishlist.has({{ $id }}) ? 'Saved' : 'Save'">Save</span>
    @endif

    <svg class="{{ $iconSize }} shrink-0 transition-colors"
        :class="$store.wishlist.has({{ $id }}) ? 'fill-sale-red stroke-sale-red' : 'fill-none stroke-charcoal/60'"
        viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
    </svg>

    @if ($variant === 'full')
        <span x-text="$store.wishlist.has({{ $id }}) ? 'Wishlisted' : 'Add to Wishlist'">Add to Wishlist</span>
    @endif
</button>
