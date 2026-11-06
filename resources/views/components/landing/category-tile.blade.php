@props(['category', 'index'])
@php
    $imageSlug = \Illuminate\Support\Str::slug(str_replace(['&', "'"], ['and', ''], $category['name']));
@endphp
<a x-reveal="{{ $index }}" href="{{ route('home', ['category' => $category['name']]) }}#discover"
    class="landing-category landing-card group flex min-w-0 flex-col items-center gap-3 rounded-lg border border-line bg-white p-3 text-center text-xs font-medium sm:p-4">
    <span class="flex size-20 items-center justify-center overflow-hidden rounded-md bg-ice-blue sm:size-24">
        <img src="{{ asset('images/categories/'.$imageSlug.'.png') }}" alt="{{ $category['name'] }}"
            width="96" height="96" loading="{{ $index < 6 ? 'eager' : 'lazy' }}" decoding="async"
            class="size-full object-contain">
    </span>
    <span class="max-w-40 leading-5">{{ $category['name'] }}</span>
</a>
