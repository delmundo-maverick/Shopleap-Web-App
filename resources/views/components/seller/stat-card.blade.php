@props(['label', 'value', 'href' => null, 'icon' => null, 'hint' => null, 'activity' => null, 'trend' => null, 'trendDirection' => 0])
<div {{ $attributes->class(['frost-panel relative min-w-0 rounded-xl border border-line p-4']) }}>
    @if ($href)<a href="{{ $href }}" class="absolute inset-0 z-[1] rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" aria-label="{{ $label }}: {{ number_format($value) }}. View matching items."></a>@endif
    <div class="flex items-start justify-between gap-3"><p class="text-xs text-charcoal/60">{{ $label }}</p>@if ($icon)<span class="rounded-lg bg-primary/10 p-2 text-primary"><x-nav.icon :name="$icon" /></span>@endif</div>
    <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($value) }}</p>
    @if ($hint)<p class="mt-1 text-xs text-charcoal/60">{{ $hint }}</p>@endif
    @if ($activity)<p class="mt-3 border-t border-line pt-3 text-xs text-charcoal/65">{{ $activity }}</p>@endif
    @if ($trend)<p class="mt-1 text-xs tabular-nums text-charcoal/55"><span aria-hidden="true">@if ($trendDirection > 0) &uarr; @elseif ($trendDirection < 0) &darr; @else &rarr; @endif</span>{{ $trend }}</p>@endif
</div>
