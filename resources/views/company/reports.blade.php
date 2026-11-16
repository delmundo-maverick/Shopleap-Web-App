<x-company.layout title="Reports" description="Delivery snapshots across your hubs">
    @php($money = fn ($value) => $value === null ? 'Not recorded' : 'PHP '.number_format((float) $value, 2))
    <form method="GET" class="grid items-end gap-3 rounded-sm border border-line bg-surface p-4 sm:grid-cols-2 xl:grid-cols-4">
        <label class="text-sm">Hub<select name="hub" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All hubs</option>@foreach ($hubs as $hub)<option value="{{ $hub->public_code }}" @selected(request('hub') === $hub->public_code)>{{ $hub->business_name }}</option>@endforeach</select></label>
        <label class="text-sm">From<input type="date" name="from" value="{{ $fromDate }}" class="mt-1 block w-full rounded-sm border border-line p-3"></label><label class="text-sm">Through<input type="date" name="to" value="{{ $toDate }}" class="mt-1 block w-full rounded-sm border border-line p-3"></label><button class="btn btn-primary">Apply filters</button>
    </form>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-seller.stat-card label="Parcels delivered" :value="$totals['deliveries']" class="rounded-sm! bg-surface! shadow-none!" />
        @foreach (['shipping_fees' => 'Shipping fees collected', 'rider_pay' => 'Rider payouts owed', 'margin' => 'Net margin'] as $key => $label)<x-seller.stat-card :label="$label" :value="0" :display-value="$money($totals[$key])" class="rounded-sm! bg-surface! shadow-none!" />@endforeach
    </div>
    <p class="text-xs">Philippine dates, recorded delivery snapshots. Rider payouts owed here are pay earned on these deliveries, before payments. {{ $totals['snapshots'] }} of {{ $totals['deliveries'] }} deliveries have shipping snapshots; {{ $totals['paid_snapshots'] }} have pay recorded. Missing pay is not estimated; partial recorded totals may exclude legacy deliveries.</p>
    @foreach (['hubs' => ['Per hub', $hubRows], 'riders' => ['Per rider', $riderRows]] as $type => [$heading, $rows])
        <section class="space-y-3"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-semibold">{{ $heading }}</h2><a href="{{ route('company.reports', array_merge(request()->only('hub'), ['from' => $fromDate, 'to' => $toDate, 'export' => 'csv', 'table' => $type])) }}" class="btn btn-outline">Export CSV</a></div>
            <x-seller.data-table :label="$heading" responsive="scroll" class="rounded-sm! bg-surface! shadow-none!">
                <x-slot:head><tr><th class="p-3">Hub</th>@if ($type === 'riders')<th class="p-3">Rider</th>@endif<th class="p-3">Delivered</th><th class="p-3">Shipping fees</th><th class="p-3">Rider pay</th><th class="p-3">Net margin</th></tr></x-slot:head>
                @forelse ($rows as $row)<tr><td class="p-3 font-semibold">{{ $row->hub_name }}</td>@if ($type === 'riders')<td class="p-3">{{ $row->rider_name }}</td>@endif<td class="p-3">{{ number_format($row->delivered) }}</td><td class="p-3">{{ $money($row->shipping_fees) }}</td><td class="p-3">{{ $money($row->rider_pay) }}</td><td class="p-3">{{ $money($row->margin) }}</td></tr>
                @empty<tr><td colspan="{{ $type === 'riders' ? 6 : 5 }}" class="p-6 text-center">No deliveries match this hub and date range.</td></tr>@endforelse
            </x-seller.data-table>
            {{ $rows->links() }}
        </section>
    @endforeach
</x-company.layout>
