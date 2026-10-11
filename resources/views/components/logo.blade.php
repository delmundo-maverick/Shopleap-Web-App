<a href="{{ route('buyer.home') }}" {{ $attributes->merge(['class' => 'flex shrink-0 items-center gap-2']) }}>
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
        <img src="{{ asset('images/Shopleap-Logo-White.png') }}" alt="Shopleap"
            class="h-full w-full rounded-lg object-contain">
    </div>
    <span class="font-logo text-[1.375rem] font-medium leading-none tracking-tight">Shopleap</span>
</a>
