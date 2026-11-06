@props(['id', 'sidebar' => false, 'label' => 'Main navigation', 'unreadUrl' => null, 'variant' => 'default'])
@once
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endonce
<div {{ $attributes->class(['nav-shell', 'nav-shell-sidebar' => $sidebar, 'nav-shell-top' => !$sidebar, 'buyer-header' => $variant === 'buyer']) }}
    x-data="navbar(@js(['id' => $id, 'unreadUrl' => $unreadUrl]))" :data-collapsed="collapsed"
    @navigation-toggle.window="if ($event.detail.id === id) toggle($event.detail.trigger)"
    @open-logout-modal.window="if ($event.detail.navigationId === id) requestLogout($event.detail.trigger)"
    @focusin.window="containLogoutFocus($event)"
    @keydown.escape.window="escape()" @keydown.tab.window="trap($event)" @resize.window="resize()" @popstate.window="reset()" @pagehide.window="reset()">
    @if ($sidebar)
        <x-nav.mobile-menu :id="$id" :sidebar="true" :label="$label">
            <div class="nav-sidebar-brand"><div class="nav-brand">{{ $brand }}</div><button type="button" class="btn btn-outline nav-toggle hidden lg:flex" @click="collapse()" :aria-expanded="!collapsed" aria-label="Collapse or expand navigation"><span aria-hidden="true">&#9776;</span></button><button type="button" class="btn btn-outline nav-toggle lg:hidden" @click="close()" aria-label="Close navigation menu">&times;</button></div>
            <nav aria-label="{{ $label }}" class="nav-sidebar-links">{{ $desktopLinks }}</nav>
            <div class="nav-sidebar-actions">{{ $actions ?? '' }}</div>
        </x-nav.mobile-menu>
    @else
        <header class="nav-bar" @click="navigate($event)">
            @if ($variant === 'buyer')
                <div class="buyer-header-utility site-container">
                    <nav aria-label="Buyer shortcuts" class="flex min-w-0 items-center gap-2">{{ $desktopLinks ?? '' }}</nav>
                    <div class="flex shrink-0 items-center gap-2">{{ $utilityActions ?? '' }}</div>
                </div>
                @isset($location)<div class="buyer-header-location site-container">{{ $location }}</div>@endisset
                <div class="nav-bar-row site-container">
                    <x-nav.toggle :target="$id" />
                    <div class="nav-brand">{{ $brand }}</div>
                    <div class="buyer-header-search">{{ $search ?? '' }}</div>
                    <div class="nav-actions">{{ $actions ?? '' }}</div>
                </div>
            @else
                <div class="nav-bar-row"><x-nav.toggle :target="$id" /><div class="nav-brand">{{ $brand }}</div><nav aria-label="{{ $label }}" class="nav-desktop-links">{{ $desktopLinks ?? '' }}</nav><div class="nav-actions">{{ $actions ?? '' }}</div></div>
                @isset($search)<div class="nav-search-row">{{ $search }}</div>@endisset
            @endif
        </header>
        <x-nav.mobile-menu :id="$id" :label="$label">
            {{ $mobileMenu ?? '' }}
            <x-slot:footer>{{ $mobileUser ?? '' }}</x-slot:footer>
        </x-nav.mobile-menu>
    @endif
    @auth
        <x-logout-modal :id="$id.'-logout'" />
    @endauth
</div>
