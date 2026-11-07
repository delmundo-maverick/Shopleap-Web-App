@props(['href', 'active' => false, 'icon' => null, 'badge' => null, 'message' => false])
<a href="{{ $href }}" aria-label="{{ trim(strip_tags((string) $slot)) }}" @if ($active) aria-current="page" @endif {{ $attributes->class(['nav-item', 'is-active' => $active]) }}>
    @if ($icon)<svg class="size-5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $icon }}" /></svg>@endif
    <span class="nav-label min-w-0 flex-1">{{ $slot }}</span>
    @if ($message)<span data-message-badge x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread" class="nav-badge nav-label"></span>
    @elseif ($badge)<span class="nav-badge nav-label">{{ $badge }}</span>@endif
</a>
