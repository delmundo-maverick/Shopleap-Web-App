@props(['product'])

<a href="#"
    class="group block overflow-hidden rounded-lg border border-light-gray bg-white transition hover:shadow-lg hover:shadow-charcoal/10">

    <!-- IMAGE -->
    <div class="relative aspect-square w-full overflow-hidden bg-ice-blue/60">
        @if (!empty($product->image_url))
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                class="h-full w-full object-cover transition group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center text-charcoal/25">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v14H4V6z" />
                </svg>
            </div>
        @endif

        @if (!empty($product->original_price))
            @php $discountPct = round((($product->original_price - $product->price) / $product->original_price) * 100); @endphp
            <span class="absolute left-1.5 top-1.5 rounded bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white">
                -{{ $discountPct }}%
            </span>
        @endif
    </div>

    <!-- DETAILS -->
    <div class="space-y-1 p-2.5">

        <p class="line-clamp-2 text-xs leading-snug text-charcoal">{{ $product->name }}</p>

        <div class="flex items-baseline gap-1.5">
            <span class="text-sm font-bold text-primary">₱{{ number_format($product->price) }}</span>
            @if (!empty($product->original_price))
                <span
                    class="text-[11px] text-charcoal/40 line-through">₱{{ number_format($product->original_price) }}</span>
            @endif
        </div>

        <div class="flex items-center gap-1 text-[11px] text-charcoal/50">
            <div class="flex items-center gap-0.5 text-amber-500">
                <svg class="h-3 w-3 fill-current" viewBox="0 0 20 20">
                    <path
                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.286 3.958c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.286-3.958a1 1 0 00-.363-1.118L2.063 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.951-.69l1.285-3.958z" />
                </svg>
                <span>{{ $product->rating }}</span>
            </div>
            <span>·</span>
            <span>{{ $product->sold_count >= 1000 ? round($product->sold_count / 1000, 1) . 'k' : $product->sold_count }}
                sold</span>
        </div>

        <p class="flex items-center gap-1 text-[11px] text-charcoal/40">
            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="truncate">{{ $product->location }}</span>
        </p>

    </div>

</a>
