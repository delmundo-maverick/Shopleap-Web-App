<x-notification-bell-script />
<div x-data="shopleapBell({ navDropdownId: 'buyer-notification-feed', unread: {{ auth()->user()?->unreadNotifications()->count() ?? 0 }}, feedUrl: @js(route('notifications.feed', [], false)), readUrl: @js(route('notifications.read', ['id' => '__ID__'], false)), readAllUrl: @js(route('notifications.read-all', [], false)), allUrl: @js(route('notifications.index', [], false)), csrf: @js(csrf_token()) })">
    <x-nav.dropdown id="buyer-notification-feed" label="Notifications">
        <x-slot:trigger><span class="sr-only">Notifications</span><svg class="size-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M18 8a6 6 0 00-12 0v6l-2 3h16l-2-3V8M9 20h6" /></svg><span x-show="unread > 0" x-cloak class="nav-badge absolute right-0 top-0" x-text="badge()"></span></x-slot:trigger>
        <p class="px-3 py-2 text-sm font-semibold">Notifications</p>
        <div class="max-h-72 overflow-y-auto"><template x-for="item in items" :key="item.id"><button type="button" @click="go(item)" class="flex min-h-10 w-full gap-2 px-3 py-2 text-left text-xs hover:bg-ice-blue"><span class="min-w-0"><span class="block font-semibold" x-text="item.title"></span><span class="block text-charcoal/65" x-text="item.body"></span><span class="block text-charcoal/55" x-text="item.time"></span></span></button></template></div>
        <p x-show="!items.length" class="px-3 py-4 text-xs" x-text="loading ? 'Loading...' : (failed ? 'Could not load notifications' : 'No notifications yet')"></p>
        <button type="button" x-show="unread > 0" @click="markAll()" class="btn btn-outline w-full">Mark all as read</button>
        <x-nav.link :href="route('notifications.index')">View all notifications</x-nav.link>
    </x-nav.dropdown>
</div>
