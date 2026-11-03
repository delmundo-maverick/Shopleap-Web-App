@php
    $hubProvince = $hub->province;
    $hubMunicipality = $hub->municipality;
    $saveUrl = route('logistics.areas.store');
    $pageData = [
        'barangays' => array_values($barangays),
        'riders' => collect($riders)->values()->all(),
    ];
    $hasYours = collect($barangays)->contains(fn($b) => ($b['status'] ?? null) === 'yours');
@endphp

<x-logistics.layout title="Service Areas" subtitle="Barangays your hub serves" active="areas" :scripts="['resources/js/pages/logistics/service-areas.js']">

    <div class="space-y-4">

        {{-- REGISTERED LOCATION (read-only) --}}
        <div class="rounded-md border border-charcoal/10 bg-white p-5">
            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-primary">Registered location</p>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span
                    class="inline-flex items-center gap-1.5 rounded-md bg-charcoal/5 px-2.5 py-1 text-xs font-medium text-charcoal">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ $hubMunicipality }}, {{ $hubProvince }}
                </span>
                <span class="text-xs text-charcoal/50">Based on your registered address. It can't be changed
                    here.</span>
            </div>
        </div>

        @if (!$locationKnown)
            {{-- BARANGAY LIST COULD NOT BE LOADED --}}
            <div class="rounded-md border border-sale-red/20 bg-sale-red/10 p-4">
                <p class="text-sm text-sale-red"><strong>We couldn't load the barangay list</strong> for
                    {{ $hubMunicipality }}, {{ $hubProvince }}. The lookup service may be unreachable, or your
                    registered location is spelled differently from the official list.</p>
                <a href="{{ url()->current() }}"
                    class="mt-3 inline-block rounded-md bg-primary px-4 py-2 text-xs font-bold text-white hover:bg-sky-blue">Retry</a>
            </div>
        @else
            @if (!$hasYours)
                <div class="rounded-md border border-orange-200 bg-orange-50 p-4">
                    <p class="text-sm text-orange-700"><strong>No barangays selected yet.</strong> Your hub won't
                        receive
                        deliveries for {{ $hubMunicipality }} until you select at least one barangay below.</p>
                </div>
            @endif

            <script type="application/json" id="serviceAreaData">{!! json_encode($pageData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>

            <div x-data="serviceAreas()" data-save-url="{{ $saveUrl }}" data-csrf="{{ csrf_token() }}"
                class="space-y-4">

                {{-- BARANGAY PICKER --}}
                <div class="rounded-md border border-charcoal/10 bg-white p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-primary">Barangays in
                                {{ $hubMunicipality }}</p>
                            <p class="mt-1 text-xs text-charcoal/50">Tick the barangays your hub serves. One barangay
                                belongs to one hub. Approved riders are matched to these barangays.</p>
                        </div>
                        <input type="search" x-model="search" placeholder="Search barangays…"
                            class="frost-input w-full rounded-md px-3 py-2 text-sm sm:w-64">
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <template x-for="chip in chips" :key="chip.key">
                            <button type="button" @click="filter = chip.key"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                                :class="filter === chip.key ? 'bg-primary text-white' :
                                    'bg-charcoal/5 text-charcoal/60 hover:bg-charcoal/10'"
                                x-text="chip.label"></button>
                        </template>

                        <button type="button" @click="selectAllAvailable()" x-show="availableCount > 0"
                            class="ml-auto text-xs font-semibold text-primary hover:underline">
                            Select all available (<span x-text="availableCount"></span>)
                        </button>
                    </div>

                    <div class="mt-4 max-h-[28rem] space-y-1.5 overflow-y-auto pr-1">
                        <template x-for="b in filtered" :key="b.barangay_key">
                            <div class="flex flex-wrap items-center gap-3 rounded-md border px-3 py-2"
                                :class="selected.includes(b.barangay_key) ? 'border-leaf-green/30 bg-leaf-green/5' :
                                    (b.status === 'taken' ? 'border-charcoal/10 bg-charcoal/5' :
                                        'border-charcoal/10 bg-white')">

                                <input type="checkbox" class="shrink-0 accent-primary" x-model="selected"
                                    :value="b.barangay_key" :disabled="b.status === 'taken'"
                                    :title="b.status === 'taken' ? 'Owned by ' + b.owner_hub_name : ''">

                                <span class="min-w-0 flex-1 text-sm"
                                    :class="b.status === 'taken' ? 'text-charcoal/40' : 'text-charcoal'">
                                    <span x-text="b.barangay"></span>
                                    <span x-show="b.status === 'yours'"
                                        class="ml-2 rounded-full bg-leaf-green/10 px-2 py-0.5 text-[10px] font-bold text-leaf-green">Yours</span>
                                    <span x-show="b.status === 'available'"
                                        class="ml-2 rounded-full bg-charcoal/10 px-2 py-0.5 text-[10px] font-bold text-charcoal/60">Available</span>
                                    <span x-show="b.status === 'taken'"
                                        class="ml-2 rounded-full bg-sale-red/10 px-2 py-0.5 text-[10px] font-bold text-sale-red">
                                        Taken by <span x-text="b.owner_hub_name"></span>
                                    </span>
                                </span>

                                <div class="flex items-center gap-2" x-show="selected.includes(b.barangay_key)">
                                    <label class="text-xs text-charcoal/50">Rider</label>
                                    <select x-model="riderAssignments[b.barangay_key]"
                                        class="frost-input w-44 rounded-md px-2 py-1.5 text-xs sm:w-56">
                                        <option value="">None</option>
                                        <template x-for="r in riders" :key="r.id">
                                            <option :value="String(r.id)"
                                                :selected="String(r.id) === riderAssignments[b.barangay_key]"
                                                x-text="r.full_name + ' (' + r.covers + ' barangay' + (r.covers === 1 ? '' : 's') + ')'">
                                            </option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>

                        <p x-show="! filtered.length" x-cloak class="py-8 text-center text-xs text-charcoal/40">
                            No barangays match your search and filter.
                        </p>
                    </div>
                </div>

                {{-- SAVE BAR --}}
                <div class="sticky bottom-0 z-10 rounded-md border border-charcoal/10 bg-white p-4 shadow-sm">
                    <p x-show="error" x-text="error" x-cloak class="mb-3 text-sm text-sale-red"></p>
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm text-charcoal">
                            <span class="font-bold" x-text="selectedCount"></span>
                            barangay<span x-text="selectedCount === 1 ? '' : 's'"></span> selected
                        </p>
                        <button type="button" @click="save()" :disabled="saving || !dirty"
                            class="rounded-md bg-primary px-6 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50"
                            x-text="saving ? 'Saving…' : 'Save barangays'"></button>
                    </div>
                </div>
            </div>
        @endif

        {{-- OTHER AREAS (READ-ONLY) --}}
        @if ($otherAreas->count())
            <div class="rounded-md border border-charcoal/10 bg-white p-5">
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-primary">Other service areas (read-only)
                </p>
                <p class="mb-4 text-xs text-charcoal/50">These areas were added before. They can't be edited. You can
                    delete one only if your hub has no active orders for it.</p>

                <div class="space-y-3">
                    @foreach ($otherAreas as $area)
                        <div
                            class="flex items-center justify-between gap-4 rounded-md border border-charcoal/10 bg-charcoal/5 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-charcoal">{{ $area->municipality }},
                                    {{ $area->province }}</p>
                                <p class="text-[11px] text-charcoal/50">
                                    {{ $area->barangays->count() }}
                                    barangay{{ $area->barangays->count() !== 1 ? 's' : '' }}
                                    · {{ $area->rider?->full_name ?? 'No rider' }}
                                </p>
                            </div>

                            <form method="POST" action="{{ route('logistics.areas.destroy', $area) }}"
                                x-data="{ confirming: false }" class="flex items-center gap-3">
                                @csrf
                                @method('DELETE')
                                <button type="button" x-show="! confirming" @click="confirming = true"
                                    class="text-xs font-semibold text-sale-red hover:underline">Delete</button>
                                <button type="submit" x-show="confirming" x-cloak
                                    class="text-xs font-semibold text-sale-red hover:underline">Yes, delete</button>
                                <button type="button" x-show="confirming" x-cloak @click="confirming = false"
                                    class="text-xs text-charcoal/60 hover:underline">Cancel</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-logistics.layout>
