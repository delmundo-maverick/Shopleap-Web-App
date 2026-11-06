@props(['label', 'home', 'sections', 'active' => null])
<x-nav.shell id="workspace-navigation" :sidebar="true" :label="$label" :unread-url="route('messages.unread-count')" {{ $attributes }}>
    <x-slot:brand><x-nav.brand :href="route($home)" :label="$label" /></x-slot:brand>
    <x-slot:desktopLinks>
        @foreach ($sections as $section => $links)
            <p class="nav-section px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wide text-charcoal/55">{{ $section }}</p>
            @foreach ($links as $link)
                @php
                    $pattern = preg_replace('/\.index$/', '.*', $link['route']);
                    $selected = request()->routeIs($link['route'] === 'admin.settings.index' ? $link['route'] : $pattern);
                    if (isset($link['params']['status'])) { $selected = request()->routeIs($link['route']) && (request('status', 'new') === $link['params']['status']); }
                    if ($link['route'] === 'messages.support') { $selected = $active === 'help'; }
                @endphp
                <x-nav.link :href="route($link['route'], $link['params'] ?? [])" :active="$selected" :icon="$link['icon'] ?? null" :badge="$link['count'] ?? null" :message="!empty($link['badge'])" :title="$link['label']">{{ $link['label'] }}</x-nav.link>
            @endforeach
        @endforeach
    </x-slot:desktopLinks>
    <x-slot:actions>
        <x-nav.user-menu id="workspace-user-menu" />
    </x-slot:actions>
</x-nav.shell>
