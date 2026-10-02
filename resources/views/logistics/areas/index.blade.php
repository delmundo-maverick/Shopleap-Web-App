@php
    $inputClass =
        'frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $riderOptions = fn($selected) => $riders;
@endphp

<x-logistics.layout title="Service Areas" subtitle="Where your hub picks up and delivers" active="areas" :scripts="['resources/js/pages/logistics/areas.js']">

    <div class="frost-panel rounded-2xl p-5 text-xs leading-relaxed text-charcoal/60">
        <b>How this works.</b> Sellers in a municipality listed here can choose your hub for pickups. Parcels going to
        that municipality are sorted to the zone and rider you assign. Until you add areas, your hub only serves its own
        municipality ({{ $hub->municipality }}, {{ $hub->province }}) for pickups.
    </div>

    <!-- ADD AN AREA -->
    <form method="POST" action="{{ route('logistics.areas.store') }}" class="frost-panel-solid rounded-2xl p-5">
        @csrf
        <p class="mb-4 text-xs font-bold uppercase tracking-wide text-primary">Add a Service Area</p>

        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold">Province</label>
                <select id="area_province" name="province" required class="{{ $inputClass }}">
                    <option value="">Loading…</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">Municipality / City</label>
                <select id="area_municipality" name="municipality" required disabled class="{{ $inputClass }}">
                    <option value="">Select a province first</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">Zone name <span
                        class="font-normal text-charcoal/40">(optional)</span></label>
                <input type="text" name="zone_name" maxlength="50" placeholder="e.g. Area A"
                    value="{{ old('zone_name') }}" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold">Delivery rider <span
                        class="font-normal text-charcoal/40">(optional)</span></label>
                <select name="rider_id" class="{{ $inputClass }}">
                    <option value="">— None yet —</option>
                    @foreach ($riders as $rider)
                        <option value="{{ $rider->id }}">
                            {{ $rider->full_name }}{{ $rider->is_active ? '' : ' (inactive)' }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <p id="areaHelp" class="mt-2 text-xs text-sale-red"></p>

        <button type="submit"
            class="mt-3 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
            Add area
        </button>
    </form>

    <!-- EXISTING AREAS -->
    @if ($areas->isEmpty())
        <div class="frost-panel-solid flex h-40 flex-col items-center justify-center rounded-2xl text-center">
            <p class="text-sm font-semibold text-charcoal">No service areas yet</p>
            <p class="mt-1 text-xs text-charcoal/50">Add the municipalities you cover above.</p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($areas as $area)
                <div class="frost-panel-solid rounded-2xl p-5">
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-charcoal">{{ $area->municipality }}</p>
                            <p class="text-xs text-charcoal/50">{{ $area->province }}</p>
                        </div>
                        @if ($area->rider && !$area->rider->is_active)
                            <span
                                class="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-600">Rider
                                inactive</span>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('logistics.areas.update', $area) }}"
                        class="grid grid-cols-2 gap-3">
                        @csrf
                        <input type="text" name="zone_name" maxlength="50" value="{{ $area->zone_name }}"
                            placeholder="Zone name" class="{{ $inputClass }}">
                        <select name="rider_id" class="{{ $inputClass }}">
                            <option value="">— No rider —</option>
                            @foreach ($riders as $rider)
                                <option value="{{ $rider->id }}" @selected($area->rider_id === $rider->id)>
                                    {{ $rider->full_name }}{{ $rider->is_active ? '' : ' (inactive)' }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="col-span-2 rounded-xl bg-primary/10 px-4 py-2 text-xs font-bold text-primary transition hover:bg-primary/20">
                            Save changes
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logistics.areas.destroy', $area) }}"
                        onsubmit="return confirm('Remove {{ $area->municipality }} from your service areas?')"
                        class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="text-xs font-semibold text-sale-red/80 transition hover:text-sale-red">Remove
                            area</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

</x-logistics.layout>
