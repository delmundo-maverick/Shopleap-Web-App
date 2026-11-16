<x-company.layout title="Hub map" description="Your hub network across the Philippines" :scripts="['resources/js/pages/company/map.js']">
    @if (empty($mapHubs))
        <x-seller.empty-state title="No hubs yet" description="Create a hub and set its location to see your network on the map." class="rounded-sm! bg-surface"><a href="{{ route('company.hubs.create') }}" class="btn btn-primary">Create hub</a></x-seller.empty-state>
    @else
        <section x-data="companyHubMap" data-hubs="{{ json_encode($mapHubs) }}" class="space-y-4">
            <div class="grid gap-3 rounded-sm border border-line bg-surface p-4 sm:grid-cols-3">
                <label class="text-sm font-medium">Search hubs<input type="search" x-model="search" @input.debounce.200ms="refresh()" placeholder="Hub name" class="mt-1 block w-full rounded-sm border border-line p-3"></label>
                <label class="text-sm font-medium">Province<select x-model="province" @change="refresh()" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All provinces</option><template x-for="name in provinces" :key="name"><option :value="name" x-text="name"></option></template></select></label>
                <label class="text-sm font-medium">Hub status<select x-model="status" @change="refresh()" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All statuses</option><option value="active">Active</option><option value="suspended">Suspended</option></select></label>
            </div>
            <div class="flex flex-wrap gap-4 text-sm"><span class="flex items-center gap-2"><span class="size-3 rounded-sm bg-success" aria-hidden="true"></span>Active</span><span class="flex items-center gap-2"><span class="size-3 rounded-sm bg-danger" aria-hidden="true"></span>Suspended</span><p role="status"><span x-text="filtered.length"></span> matching hubs</p></div>
            <p x-show="loading" role="status" class="text-sm">Loading map...</p>
            <div x-show="error" x-cloak class="rounded-sm border border-line bg-surface p-3"><p x-text="error" class="text-sm text-danger" role="alert"></p><button type="button" @click="loadMap()" class="mt-2 text-sm text-primary">Retry map</button></div>
            <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div x-ref="mapCanvas" class="company-map" aria-label="Company hub locations" role="region"></div>
                <aside class="company-map-list space-y-4" aria-label="Hub list">
                    <section class="space-y-2"><h2 class="font-semibold">On the map</h2>
                        <template x-for="hub in mapped" :key="hub.code">
                            <button type="button" @click="focusHub(hub.code)" :data-hub-code="hub.code" :aria-current="selected === hub.code ? 'true' : 'false'" class="block w-full space-y-1 rounded-sm border border-line bg-surface p-3 text-left">
                                <span class="block font-semibold" x-text="hub.name"></span><span class="block text-xs" x-text="`${hub.municipality}, ${hub.province}`"></span><span class="block text-xs" x-text="hub.manager"></span><span class="block text-xs" :class="hub.status === 'active' ? 'text-success' : 'text-danger'" x-text="hub.status === 'active' ? 'Active' : 'Suspended'"></span>
                            </button>
                        </template>
                        <p x-show="!mapped.length" x-cloak class="text-sm">No mapped hubs match these filters.</p>
                    </section>
                    <section x-show="unmapped.length" x-cloak class="space-y-2"><h2 class="font-semibold">Not on the map yet</h2>
                        <template x-for="hub in unmapped" :key="hub.code"><article class="space-y-2 rounded-sm border border-line bg-surface p-3"><h3 class="font-semibold" x-text="hub.name"></h3><p class="text-xs" x-text="`${hub.municipality}, ${hub.province}`"></p><a :href="`${hub.editUrl}#hub-location`" class="btn btn-outline">Set location</a></article></template>
                    </section>
                </aside>
            </div>
        </section>
    @endif
</x-company.layout>
