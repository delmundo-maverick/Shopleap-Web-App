@props(['label', 'home', 'sections', 'active' => null, 'collapsibleSections' => []])
<x-nav.shell id="workspace-navigation" :sidebar="true" :label="$label" :unread-url="route('messages.unread-count')" {{ $attributes }}>
    <x-slot:brand><x-nav.brand :href="route($home)" :label="$label" /></x-slot:brand>
    <x-slot:desktopLinks>
        @foreach ($sections as $section => $links)
            @if (in_array($section, $collapsibleSections, true))
                <div x-data="productNavigation(@js(request()->routeIs('seller.products.*')))">
                    <button type="button" class="nav-item mt-3 w-full" @click="toggleGroup()" :aria-expanded="expanded" aria-controls="seller-products-navigation" title="Products">
                        <x-nav.icon name="products" />
                        <span class="nav-label flex-1 text-left">{{ $section }}</span><span class="nav-label" aria-hidden="true" x-text="expanded ? '-' : '+'"></span>
                    </button>
                    <div id="seller-products-navigation" x-show="expanded" x-cloak class="space-y-1">
            @else
            <p class="nav-section px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wide text-charcoal/55">{{ $section }}</p>
            @endif
            @foreach ($links as $link)
                @php
                    $pattern = $link['pattern'] ?? preg_replace('/\.index$/', '.*', $link['route']);
                    $selected = request()->routeIs($link['route'] === 'admin.settings.index' ? $link['route'] : $pattern);
                    if (isset($link['params']['status'])) { $selected = request()->routeIs($link['route']) && (request('status', 'new') === $link['params']['status']); }
                    if ($link['route'] === 'messages.support') { $selected = $active === 'help'; }
                    $selected = $link['selected'] ?? $selected;
                @endphp
                <x-nav.link :href="route($link['route'], $link['params'] ?? [])" :active="$selected" :icon="\App\Support\NavigationIcons::forRoute($link['route'], $link['params'] ?? []) ?? ($link['icon'] ?? null)" :badge="$link['count'] ?? null" :message="!empty($link['badge'])" :title="$link['title'] ?? $link['label']">{{ $link['label'] }}</x-nav.link>
            @endforeach
            @if (in_array($section, $collapsibleSections, true))</div></div>@endif
        @endforeach
    </x-slot:desktopLinks>
    <x-slot:actions>
        <nav aria-label="Policies" class="flex flex-wrap gap-3 px-3 py-2 text-xs text-charcoal/65">
            <a href="{{ \App\Support\Portal::route('main', 'terms') }}" class="hover:text-primary">Terms and Conditions</a>
            <a href="{{ \App\Support\Portal::route('main', 'privacy') }}" class="hover:text-primary">Privacy Policy</a>
        </nav>
        <x-nav.user-menu id="workspace-user-menu" />
    </x-slot:actions>
</x-nav.shell>
