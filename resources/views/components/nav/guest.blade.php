@props(['landing' => false, 'links' => null])
@php
    $links = $links ?? array_merge(config('landing.nav', []), [
        ['href' => route('register.seller'), 'label' => 'Become a seller'],
        ['href' => route('register.logistics'), 'label' => 'Logistics partner'],
        ['href' => route('terms'), 'label' => 'Terms'],
        ['href' => route('privacy'), 'label' => 'Privacy'],
    ]);
    $links = array_map(fn ($link) => array_merge($link, ['href' => !$landing && str_starts_with($link['href'], '#') ? route('home').$link['href'] : $link['href']]), $links);
@endphp
<x-nav.shell id="guest-navigation" variant="buyer" label="Main navigation" {{ $attributes }}>
    <x-slot:brand><x-nav.brand :image="$landing ? 'images/Shopleap-Logo-White.png' : 'images/Shopleap-Logo.png'" /></x-slot:brand>
    <x-slot:desktopLinks><x-nav.dropdown id="guest-explore" label="Explore Shopleap">@foreach ($links as $link)<x-nav.link :href="$link['href']">{{ $link['label'] }}</x-nav.link>@endforeach</x-nav.dropdown></x-slot:desktopLinks>
    <x-slot:utilityActions><a href="{{ route('register.seller') }}" class="text-xs text-white">Become a seller</a><a href="{{ route('register.logistics') }}" class="ml-4 text-xs text-white">Logistics partner</a></x-slot:utilityActions>
    <x-slot:search><x-nav.search /></x-slot:search>
    <x-slot:actions><x-nav.link :href="route('login')">Login</x-nav.link><x-nav.link :href="route('register.buyer')">Register</x-nav.link></x-slot:actions>
    <x-slot:mobileMenu>@foreach ($links as $link)<x-nav.link :href="$link['href']">{{ $link['label'] }}</x-nav.link>@endforeach<x-nav.link :href="route('login')">Login</x-nav.link><x-nav.link :href="route('register.buyer')">Register</x-nav.link></x-slot:mobileMenu>
</x-nav.shell>
