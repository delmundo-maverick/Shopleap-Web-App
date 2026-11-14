@props(['items' => [], 'id' => 'navigation-user-menu', 'user' => null])
@php
    $user = $user ?? auth()->user();
@endphp
@if ($user)
    <div class="nav-user-section">
        <button type="button" class="nav-user-logout" aria-label="Log out" title="Log out"
            @click="$dispatch('open-logout-modal', { navigationId: id, trigger: $el })">
            <span class="nav-user-identity">
            <span class="nav-avatar relative">{{ mb_substr($user->name, 0, 1) }}@if ($user->avatar_url)<img src="{{ $user->avatar_url }}" alt="" class="absolute inset-0 size-full object-cover" onerror="this.remove()">@endif</span>
            <span class="min-w-0"><span class="block truncate">{{ $user->name }}</span><span class="nav-user-role">{{ str($user->role)->replace('_', ' ')->title() }}</span></span>
            </span>
            <svg aria-hidden="true" class="nav-logout-icon size-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H5v14h4m5-14 7 7-7 7m7-7H9" /></svg>
        </button>
        @if (count($items))
            <x-nav.dropdown :id="$id" label="More account options">
                <x-slot:trigger><span class="sr-only">More account options</span></x-slot:trigger>
                @foreach ($items as $item)<x-nav.link :href="route($item['route'])" :active="request()->routeIs($item['pattern'] ?? preg_replace('/\.index$/', '.*', $item['route']))">{{ $item['label'] }}</x-nav.link>@endforeach
            </x-nav.dropdown>
        @endif
    </div>
@endif
