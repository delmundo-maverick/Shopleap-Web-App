<x-company.layout title="Coverage" description="Barangay ownership and approved rider assignments" :scripts="['resources/js/pages/company/coverage.js']">
    <form method="GET" action="{{ route('company.coverage') }}" class="grid items-end gap-3 rounded-sm border border-line bg-surface p-4 sm:grid-cols-3" data-coverage-filters data-places="{{ json_encode($places->values()) }}">
        @if (request()->boolean('needs_rider'))<input type="hidden" name="needs_rider" value="1">@endif
        <label class="text-sm font-medium">Province<select name="province" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All provinces</option>@foreach ($provinces as $province)<option value="{{ $province }}" @selected(request('province') === $province)>{{ $province }}</option>@endforeach</select></label>
        <label class="text-sm font-medium">Municipality<select name="municipality" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All municipalities</option>@foreach ($municipalities as $municipality)<option value="{{ $municipality }}" @selected(request('municipality') === $municipality)>{{ $municipality }}</option>@endforeach</select></label>
        <div class="flex gap-3"><button class="btn btn-primary">Filter</button><a href="{{ route('company.coverage') }}" class="btn btn-outline">Reset</a></div>
    </form>
    @if (!$hasHubs)
        <x-seller.empty-state title="No coverage yet" description="Your company's barangay coverage appears here after you create hubs." class="rounded-sm! bg-surface"><a href="{{ route('company.hubs.create') }}" class="btn btn-primary">Create hub</a></x-seller.empty-state>
    @else
        <p class="text-sm">Read only. Change covered barangays in the hub edit page. Rider assignments remain managed by each hub.</p>
        @if (request()->boolean('needs_rider'))<p class="rounded-sm border border-line bg-surface p-3 text-sm text-warning">Showing covered barangays without an approved, active rider. <a href="{{ route('company.coverage') }}" class="font-semibold text-primary">Show all coverage</a></p>@endif
        @if ($missingReference)<p role="status" class="rounded-sm border border-line bg-surface p-3 text-sm text-warning">Some municipalities do not have stored barangay reference data yet. Their recorded coverage is shown, but uncovered barangays cannot be listed until that reference data is available.</p>@endif
        <x-seller.data-table label="Company barangay coverage" responsive="scroll" class="rounded-sm! bg-surface! shadow-none!">
            <x-slot:head><tr>@foreach (['Province', 'Municipality', 'Barangay', 'Covering hub', 'Assigned rider', 'Coverage'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></x-slot:head>
            @forelse ($rows as $row)
                <tr class="{{ !$row->hub_code || !$row->rider_id ? 'bg-landing-peach' : '' }}"><td class="p-3">{{ $row->province }}</td><td class="p-3">{{ $row->municipality }}</td><td class="p-3 font-semibold">{{ $row->barangay }}</td>
                    <td class="p-3">@if ($row->hub_code)<a href="{{ route('company.hubs.edit', $row->hub_code) }}" class="text-primary">{{ $row->hub_name }}</a>@else<span class="text-warning">No company hub</span>@endif</td>
                    <td class="p-3">{{ $row->rider_name ?: 'No rider' }}</td><td class="p-3"><x-status-label :status="!$row->hub_code ? 'uncovered' : ($row->rider_id ? 'covered' : 'no_rider')" domain="company" class="rounded-sm!" /></td>
                </tr>
            @empty<tr><td colspan="6" class="p-6 text-center">No barangays match these filters.</td></tr>@endforelse
        </x-seller.data-table>
        {{ $rows->links() }}
    @endif
</x-company.layout>
