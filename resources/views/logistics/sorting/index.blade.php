@php
    $field = 'frost-input rounded-xl px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
@endphp

<x-logistics.layout title="Sort & Assign" subtitle="Sort parcels by destination, then assign a delivery rider"
    active="sorting">

    {{-- TABS --}}
    <div class="frost-panel-solid flex rounded-2xl">
        @foreach (['to_sort' => 'To Sort', 'to_assign' => 'To Assign'] as $key => $label)
            <a href="{{ route('logistics.sorting.index', ['tab' => $key]) }}"
                class="flex flex-1 items-center justify-center gap-1.5 border-b-2 px-3 py-3.5 text-sm font-semibold transition
                    {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/50 hover:text-charcoal' }}">
                {{ $label }}

                @if ($counts[$key] > 0)
                    <span
                        class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $tab === $key ? 'bg-primary/10 text-primary' : 'bg-light-gray text-charcoal/50' }}">
                        {{ $counts[$key] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- EMPTY STATE --}}
    @if ($orders->isEmpty())
        <div class="frost-panel-solid flex h-56 flex-col items-center justify-center rounded-2xl text-center">
            <p class="text-sm font-semibold text-charcoal">Nothing here</p>
            <p class="mt-1 text-xs text-charcoal/50">
                {{ $tab === 'to_sort'
                    ? 'Parcels appear once you confirm their arrival under Incoming Parcels.'
                    : 'Sorted parcels waiting for a delivery rider appear here.' }}
            </p>
        </div>

        {{-- TO SORT --}}
    @elseif ($tab === 'to_sort')
        <div class="flex justify-end">
            <form method="POST" action="{{ route('logistics.sorting.sort-all') }}">
                @csrf

                <button type="submit"
                    class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                    Sort all {{ $orders->count() }} parcel(s)
                </button>
            </form>
        </div>

        <div class="frost-panel-solid overflow-hidden rounded-2xl">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-white/50 bg-primary/5 text-charcoal/60">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Order</th>
                        <th class="px-5 py-3 font-semibold">Destination</th>
                        <th class="px-5 py-3 font-semibold">Zone / Rider</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/50">
                    @foreach ($orders as $order)
                        @php
                            $dest = $order->ship_to;

                            $area = $areas->get(
                                \App\Models\HubServiceArea::keyFor($dest['province'], $dest['municipality']),
                            );
                        @endphp

                        <tr>
                            <td class="px-5 py-4 font-semibold">
                                #{{ $order->id }}
                            </td>

                            <td class="px-5 py-4 text-charcoal/70">
                                {{ $dest['municipality'] ?? '—' }},
                                {{ $dest['province'] ?? '' }}

                                <span class="block text-xs text-charcoal/45">
                                    Brgy. {{ $dest['barangay'] ?? '—' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if ($area)
                                    <span class="font-medium">
                                        {{ $area->zone_name ?: $area->municipality }}
                                    </span>

                                    <span class="block text-xs text-charcoal/50">
                                        {{ $area->rider?->full_name ?? 'No rider mapped' }}
                                    </span>
                                @else
                                    <span class="text-xs text-orange-600">
                                        No service area for this destination
                                    </span>
                                @endif
                            </td>

                            {{-- UPDATED: SORT BUTTON / SERVICE AREA LINK --}}
                            <td class="px-5 py-4 text-right">
                                @if ($area)
                                    <form method="POST" action="{{ route('logistics.sorting.sort', $order) }}">
                                        @csrf

                                        <button type="submit"
                                            class="rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-blue">
                                            Sort
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('logistics.areas.index') }}"
                                        class="text-xs font-semibold text-orange-600 hover:underline">
                                        Add service area
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TO ASSIGN --}}
    @else
        <div class="space-y-5">
            @foreach ($groups as $group)
                @php
                    $area = $group['area'];
                    $dest = $group['dest'];

                    $mapped = $area?->rider && $area->rider->is_active && $area->rider->status === 'approved';
                @endphp

                <div class="frost-panel-solid rounded-2xl p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-charcoal">
                                {{ $area?->zone_name ? $area->zone_name . ' · ' : '' }}{{ $dest['municipality'] ?? 'Unknown destination' }},
                                {{ $dest['province'] }}
                            </p>

                            <p class="text-xs text-charcoal/50">
                                {{ $group['orders']->count() }} parcel(s) ·

                                @if ($area)
                                    Mapped rider:
                                    {{ $area->rider?->full_name ?? 'none' }}{{ $area->rider && !$area->rider->is_active ? ' (inactive)' : '' }}
                                @else
                                    <span class="text-orange-600">
                                        Not in your service areas. Add it so riders can be mapped automatically.
                                    </span>
                                @endif
                            </p>
                        </div>

                        @if ($mapped)
                            <form method="POST" action="{{ route('logistics.sorting.assign-area') }}">
                                @csrf

                                <input type="hidden" name="province" value="{{ $dest['province'] }}">
                                <input type="hidden" name="municipality" value="{{ $dest['municipality'] }}">
                                <input type="hidden" name="rider_id" value="{{ $area->rider_id }}">

                                <button type="submit"
                                    class="rounded-xl bg-leaf-green px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">
                                    Assign all to {{ $area->rider->first_name }}
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="divide-y divide-white/50">
                        @foreach ($group['orders'] as $order)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold">
                                        Order #{{ $order->id }}
                                    </p>

                                    <p class="text-xs text-charcoal/50">
                                        {{ $order->ship_to['name'] }}
                                        · {{ $order->items->sum('quantity') }} item(s)
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('logistics.sorting.assign', $order) }}"
                                    class="flex items-center gap-2">
                                    @csrf

                                    <select name="rider_id" required class="{{ $field }}">
                                        <option value="" disabled @selected(!$area?->rider_id)>
                                            Select rider…
                                        </option>

                                        @foreach ($riders as $rider)
                                            <option value="{{ $rider->id }}" @selected($area?->rider_id === $rider->id)>
                                                {{ $rider->full_name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="submit" @disabled($riders->isEmpty())
                                        class="rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-blue disabled:opacity-50">
                                        Assign
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-logistics.layout>
