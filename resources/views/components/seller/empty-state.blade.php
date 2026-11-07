@props(['title' => 'No products yet', 'description' => 'Add your first product to start building your catalogue.'])
<div {{ $attributes->class(['rounded-xl border border-dashed border-line p-8 text-center']) }} role="status"><h2 class="font-semibold">{{ $title }}</h2><p class="mt-2 text-sm text-charcoal/60">{{ $description }}</p><div class="mt-4">{{ $slot }}</div></div>
