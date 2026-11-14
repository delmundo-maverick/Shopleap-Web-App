@props(['products'])
@php $featured = $products->take(3)->values(); @endphp
<div class="landing-hero-art">
    @foreach ($featured as $product)
        <a href="{{ route('buyer.products.show', $product) }}" class="landing-tile landing-tile-{{ $loop->iteration }}">
            @if ($product->main_image_url)
                <img src="{{ $product->main_image_url }}" alt="" width="480" height="480" @if ($loop->first) fetchpriority="high" @endif decoding="async">
            @endif
            <span>{{ $product->name }}</span>
            <strong>&#8369;{{ number_format($product->display_price) }}</strong>
        </a>
    @endforeach
    <div class="landing-tile landing-tile-brand" aria-hidden="true"><strong>s.</strong><span>shopleap</span></div>
    <div class="landing-tile landing-tile-cod"><strong>Cash on Delivery</strong><span>Pay when it arrives</span></div>
</div>
