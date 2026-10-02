@props(['user' => null, 'size' => 'md', 'bg' => 'bg-primary/10'])

@php
    $sizes = [
        'xs' => 'h-6 w-6 text-[10px]',
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-14 w-14 text-lg',
        'xl' => 'h-16 w-16 text-2xl',
        '2xl' => 'h-24 w-24 text-4xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $name = $user?->messaging_name ?? '?';
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($name, 0, 1));
    $url = $user?->avatar_url;
@endphp

<span
    {{ $attributes->merge(['class' => "relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-bold text-primary {$bg} {$sizeClass}"]) }}>
    {{ $initial }}
    @if ($url)
        {{-- Sits on top of the initial; if the URL is broken the image removes itself and the initial shows. --}}
        <img src="{{ $url }}" alt="{{ $name }}" loading="lazy"
            class="absolute inset-0 h-full w-full bg-white object-cover" onerror="this.remove()">
    @endif
</span>
