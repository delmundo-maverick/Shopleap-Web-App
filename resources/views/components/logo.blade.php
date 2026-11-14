@props(['href' => null, 'image' => 'images/Shopleap-Logo-White.png', 'label' => null])
<a href="{{ $href ?? route('buyer.home') }}" {{ $attributes->merge(['class' => 'flex shrink-0 items-center gap-2']) }}>
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
        <img src="{{ asset($image) }}" alt="Shopleap"
            class="h-full w-full rounded-lg object-contain">
    </div>
    <span class="font-logo text-[1.65rem] font-medium leading-none tracking-tight">Shopleap</span>
    @if ($label)<span class="public-brand-label">{{ $label }}</span>@endif
</a>
