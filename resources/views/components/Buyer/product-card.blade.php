@props(['product'])

@php
    // Only a real markdown counts: was_price must be higher than what the buyer pays.
    $hasDiscount = $product->was_price && $product->was_price > $product->display_price;
    $discountPct = $hasDiscount
        ? round((($product->was_price - $product->display_price) / $product->was_price) * 100)
        : null;

    $reviewCount = (int) ($product->reviews_count ?? 0);
    $currentTime = now();
    $isNew = $product->created_at?->gt($currentTime->copy()->subDay())
        && $product->created_at->lte($currentTime);
@endphp

<div
    class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-line bg-white transition-colors hover:border-primary">

    <a href="{{ route('buyer.products.show', $product) }}" class="flex flex-1 flex-col">

        <!-- IMAGE -->
        <div class="relative aspect-[5/4] w-full overflow-hidden bg-ice-blue">
            @if ($product->main_image_url)
                <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy" width="400" height="320"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
            @else
                <div class="flex h-full w-full items-center justify-center text-charcoal/25">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v14H4V6z" />
                    </svg>
                </div>
            @endif

            @if ($hasDiscount)
                <span
                    class="absolute left-2 top-2 rounded-sm bg-sale-red px-1.5 py-1 text-[11px] font-bold leading-none text-white">
                    -{{ $discountPct }}%
                </span>
            @endif
        </div>

        <!-- DETAILS -->
        <div class="flex flex-1 flex-col p-2.5 sm:p-3">

            <p class="line-clamp-2 min-h-[2.4rem] text-[13px] leading-snug text-charcoal">{{ $product->name }}</p>

            <div class="mt-1.5 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                <span class="text-[15px] font-bold tabular-nums text-primary sm:text-base">
                    &#8369;{{ number_format($product->display_price) }}
                </span>
                @if ($hasDiscount)
                    <span class="text-[11px] tabular-nums text-charcoal/50 line-through">
                        &#8369;{{ number_format($product->was_price) }}
                    </span>
                @endif
            </div>

            {{-- Rating when the query provides it; otherwise a "New" tag for recent listings. --}}
            @if ($reviewCount > 0)
                <p class="mt-1.5 flex items-center gap-1 text-[11px] text-charcoal/60">
                    <span class="text-amber-400">â˜…</span>
                    <span
                        class="font-semibold text-charcoal/80">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                    <span>({{ number_format($reviewCount) }})</span>
                </p>
            @elseif ($isNew)
                <p class="mt-1.5">
                    <span
                        class="inline-block rounded-sm border border-leaf-green/40 bg-leaf-green/10 px-1.5 py-0.5 text-[10px] font-semibold text-charcoal">
                        New Arrival
                    </span>
                </p>
            @endif

            @if ($product->seller_location)
                <p class="mt-auto flex items-center gap-1 pt-2 text-[11px] text-charcoal/60">
                    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="truncate">{{ $product->seller_location }}</span>
                </p>
            @endif

        </div>
    </a>

    @isset($actions)
        <div class="px-2.5 pb-2.5 sm:px-3 sm:pb-3">
            {{ $actions }}
        </div>
    @endisset

    {{-- Sibling of the link, not a child: valid HTML and independently focusable. --}}
    @if (auth()->user()?->role === 'buyer')
        <x-Buyer.wishlist-button :product="$product" class="absolute right-2 top-2 z-10" />
    @elseif (auth()->guest())
        <a href="{{ route('login', array_merge(['continue' => 'landing'], request()->only(['search', 'category']))) }}" aria-label="Log in to save {{ $product->name }}" class="absolute right-2 top-2 z-10 flex size-10 items-center justify-center rounded border border-line bg-white text-primary"><span aria-hidden="true">?</span></a>
    @endif
</div>
