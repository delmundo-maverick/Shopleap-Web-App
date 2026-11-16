<x-company.layout title="Transfers" description="Read-only overview of bags between your company's hubs">
    <form method="GET" action="{{ route('company.transfers.index') }}" class="grid items-end gap-3 rounded-sm border border-line bg-surface p-4 sm:grid-cols-2 xl:grid-cols-3">
        <label class="text-sm font-medium">Status<select name="status" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All statuses</option>@foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></label>
        @foreach (['origin' => 'Origin hub', 'destination' => 'Destination hub'] as $key => $label)<label class="text-sm font-medium">{{ $label }}<select name="{{ $key }}" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All hubs</option>@foreach ($hubs as $hub)<option value="{{ $hub->public_code }}" @selected(request($key) === $hub->public_code)>{{ $hub->business_name }}</option>@endforeach</select></label>@endforeach
        <label class="text-sm font-medium">From date<input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-sm border border-line p-3"></label>
        <label class="text-sm font-medium">Through date<input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-sm border border-line p-3"></label>
        <div class="flex flex-wrap gap-4 pb-3 text-sm"><label class="flex items-center gap-2"><input type="checkbox" name="late" value="1" @checked(request()->boolean('late'))>Late only</label><label class="flex items-center gap-2"><input type="checkbox" name="missing" value="1" @checked(request()->boolean('missing'))>Missing parcels only</label></div>
        <div class="flex gap-3"><button class="btn btn-primary">Filter</button><a href="{{ route('company.transfers.index') }}" class="btn btn-outline">Reset</a></div>
    </form>
    <p class="text-xs">Dates use Philippine time and the dispatch date, or creation date for undispatched bags. A bag is late while still in transit more than 48 hours after dispatch.</p>
    @if ($hubs->isEmpty())
        <x-seller.empty-state title="No transfers yet" description="Bags between your hubs will appear here as your network grows." class="rounded-sm! bg-surface" />
    @else
        <x-seller.data-table label="Company hub transfers" responsive="scroll" class="rounded-sm! bg-surface! shadow-none!">
            <x-slot:head><tr>@foreach (['Bag number', 'Origin hub', 'Destination hub', 'Parcels', 'Status', 'Time since dispatch', 'Manifest'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></x-slot:head>
            @forelse ($bags as $bag)
                @php($missing = $bag->status === \App\Models\ShipmentBag::STATUS_DISCREPANCY ? $bag->unreceived_count : 0)
                <tr class="{{ $missing ? 'bg-landing-peach' : '' }}">
                    <td class="p-3"><a href="{{ route('company.transfers.show', $bag->code) }}" class="font-semibold text-primary">{{ $bag->code }}</a>@if ($missing)<div class="mt-2"><x-status-label status="missing" domain="company" class="rounded-sm!" /><span class="block text-xs text-danger">{{ $missing }} missing parcel{{ $missing === 1 ? '' : 's' }}</span></div>@endif</td>
                    <td class="p-3">{{ $bag->fromHub->business_name }}</td><td class="p-3">{{ $bag->toHub->business_name }}</td><td class="p-3">{{ $bag->items_count }}</td>
                    <td class="p-3"><x-status-label :status="$bag->status" domain="bag" class="rounded-sm! bg-surface!" /></td>
                    <td class="p-3"><span class="block">{{ $bag->dispatched_at ? $bag->dispatched_at->diffForHumans() : 'Not dispatched' }}</span>@if ($bag->status === \App\Models\ShipmentBag::STATUS_IN_TRANSIT && $bag->dispatched_at?->lt($lateBefore))<x-status-label status="late" domain="company" class="mt-1 rounded-sm!" />@endif</td>
                    <td class="p-3"><a href="{{ route('company.transfers.show', $bag->code) }}" class="text-primary">View manifest</a></td>
                </tr>
            @empty<tr><td colspan="7" class="p-6 text-center">No transfer bags match these filters.</td></tr>@endforelse
        </x-seller.data-table>
        {{ $bags->links() }}
    @endif
</x-company.layout>
