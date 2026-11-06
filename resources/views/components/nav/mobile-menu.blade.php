@props(['id', 'sidebar' => false, 'label' => 'Main navigation'])
<template x-teleport="body"><div x-show="open" x-cloak x-transition.opacity class="nav-overlay lg:hidden" @click="close()" aria-hidden="true"></div></template>
@if ($sidebar)
    <aside x-cloak id="{{ $id }}" x-ref="panel" class="nav-panel nav-panel-sidebar" :data-open="open" :inert="!open && !desktop" @click="navigate($event)">
        {{ $slot }}
    </aside>
@else
    <template x-teleport="body">
        <div id="{{ $id }}" x-ref="panel" x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="nav-panel nav-panel-mobile lg:hidden" @click.outside="if (open) close()" @click="navigate($event)">
            <div class="nav-mobile-heading"><span class="font-semibold">{{ $label }}</span><button type="button" class="btn btn-outline nav-toggle" @click="close()" aria-label="Close navigation menu">&times;</button></div>
            <nav aria-label="{{ $label }}" class="nav-mobile-links">{{ $slot }}</nav>
            @isset($footer)<div class="nav-sidebar-actions">{{ $footer }}</div>@endisset
        </div>
    </template>
@endif
