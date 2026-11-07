@props(['title', 'description' => null])
<section {{ $attributes->class(['frost-panel space-y-4 rounded-xl border border-line p-4 sm:p-6']) }}>
    <div><h2 class="text-lg font-semibold">{{ $title }}</h2>@if ($description)<p class="mt-1 text-sm text-charcoal/60">{{ $description }}</p>@endif</div>
    {{ $slot }}
</section>
