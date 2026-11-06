@props(['products'])
<div class="landing-discover-grid grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 2xl:grid-cols-8">
    @forelse ($products as $product)
        <div x-reveal="{{ $loop->index }}" class="landing-discover-item min-w-0"><x-Buyer.product-card :product="$product" /></div>
    @empty
        <p class="col-span-full rounded border border-line bg-ice-blue p-6 text-sm text-charcoal/70">No products found. Try another category or check back as sellers add products.</p>
    @endforelse
</div>
