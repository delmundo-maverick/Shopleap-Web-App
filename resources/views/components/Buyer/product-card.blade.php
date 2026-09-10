@props(['product'])

<a href="#"
    class="group block overflow-hidden rounded-lg border border-light-gray bg-white transition hover:shadow-lg hover:shadow-charcoal/10">

    <!-- IMAGE -->
    <div class="relative aspect-square w-full overflow-hidden bg-ice-blue/60">
        @if ($product->main_image_url)
            <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                class="h-full w-full object-cover transition group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center text-charcoal/25">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v14H4V6z" />
                </svg>
            </div>
        @endif

        @if ($product->was_price)
            @php $discountPct = round((($product->was_price - $product->display_price) / $product->was_price) * 100); @endphp
            <span class="absolute left-1.5 top-1.5 rounded bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white">
                -{{ $discountPct }}%
            </span>
        @endif
    </div>

    <!-- DETAILS -->
    <div class="space-y-1 p-2.5">

        <p class="line-clamp-2 text-xs leading-snug text-charcoal">{{ $product->name }}</p>

        <div class="flex items-baseline gap-1.5">
            <span class="text-sm font-bold text-primary">₱{{ number_format($product->display_price) }}</span>
            @if ($product->was_price)
                <span class="text-[11px] text-charcoal/40 line-through">₱{{ number_format($product->was_price) }}</span>
            @endif
        </div>

        {{-- No reviews/per-product sales data yet — shown honestly rather than faked --}}
        <div class="flex items-center gap-1 text-[11px] text-charcoal/50">
            <span class="rounded bg-leaf-green/10 px-1.5 py-0.5 font-semibold text-leaf-green">New Arrival</span>
        </div>

        @if ($product->seller_location)
            <p class="flex items-center gap-1 text-[11px] text-charcoal/40">
                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
