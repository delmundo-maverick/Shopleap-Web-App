<x-logistics.layout title="Reports" :subtitle="$hub->business_name . ' | ' . $from->format('M d, Y') . ' - ' . $to->format('M d, Y')" active="reports" :scripts="['resources/js/pages/logistics/reports.js']">
    <section class="rounded-lg border border-line bg-white p-4 sm:p-5">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div><h1 class="text-lg font-bold">{{ $report['title'] }}</h1><p class="mt-1 text-xs text-charcoal/60">Review hub activity and export the same filtered results.</p></div>
            <div class="flex flex-wrap gap-2"><a class="btn btn-outline" href="{{ route('logistics.reports.export.csv', $params) }}">Export CSV</a><a class="btn btn-primary" href="{{ route('logistics.reports.export.pdf', $params) }}">Download PDF</a></div>
        </div>
        <form method="GET" action="{{ route('logistics.reports.index') }}" data-report-filters class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div><label for="report-type" class="mb-1 block text-xs font-semibold">Report</label><select id="report-type" name="type" class="w-full rounded border border-line px-3 py-2 text-sm">@foreach ($types as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="report-preset" class="mb-1 block text-xs font-semibold">Period</label><select id="report-preset" name="preset" class="w-full rounded border border-line px-3 py-2 text-sm">@foreach (['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'this_month' => 'This month', 'last_month' => 'Last month', 'this_year' => 'This year', 'custom' => 'Custom range'] as $key => $label)<option value="{{ $key }}" @selected($preset === $key)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="report-from" class="mb-1 block text-xs font-semibold">From (custom range)</label><input id="report-from" type="date" name="from" value="{{ old('from', $from->format('Y-m-d')) }}" class="w-full rounded border border-line px-3 py-2 text-sm"></div>
                <div><label for="report-to" class="mb-1 block text-xs font-semibold">To (custom range)</label><input id="report-to" type="date" name="to" value="{{ old('to', $to->format('Y-m-d')) }}" class="w-full rounded border border-line px-3 py-2 text-sm"></div>
            </div>
            <div class="grid gap-3 border-t border-line pt-4 sm:grid-cols-2 xl:grid-cols-3">
                <div><label for="report-status" class="mb-1 block text-xs font-semibold">Current shipment status</label><select id="report-status" name="status" class="w-full rounded border border-line px-3 py-2 text-sm"><option value="">All statuses</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(($params['status'] ?? '') === $status)>{{ ucfirst(strtolower(str_replace('_', ' ', $status))) }}</option>@endforeach</select></div>
                <div><label for="report-rider" class="mb-1 block text-xs font-semibold">Delivery rider</label><select id="report-rider" name="rider_id" class="w-full rounded border border-line px-3 py-2 text-sm"><option value="">All hub riders</option>@foreach ($options['riders'] as $rider)<option value="{{ $rider->id }}" @selected(($params['rider_id'] ?? '') == $rider->id)>{{ $rider->full_name }}</option>@endforeach</select></div>
                <div><label for="report-seller" class="mb-1 block text-xs font-semibold">Seller</label><select id="report-seller" name="seller_id" class="w-full rounded border border-line px-3 py-2 text-sm"><option value="">All sellers</option>@foreach ($options['sellers'] as $seller)<option value="{{ $seller->id }}" @selected(($params['seller_id'] ?? '') == $seller->id)>{{ $seller->sellerProfile?->business_name ?? $seller->name }}</option>@endforeach</select></div>
                @foreach (['origin_area' => ['Pickup city', $options['origins']], 'destination_area' => ['Destination city / zone', $options['destinations']]] as $name => [$label, $places])
                    <div><label for="{{ $name }}" class="mb-1 block text-xs font-semibold">{{ $label }}</label><select id="{{ $name }}" name="{{ $name }}" class="w-full rounded border border-line px-3 py-2 text-sm"><option value="">All areas</option>@foreach ($places as $place)<option value="{{ $place }}" @selected(($params[$name] ?? '') === $place)>{{ $place }}</option>@endforeach</select></div>
                @endforeach
                <div class="flex flex-wrap items-end gap-2"><button type="submit" class="btn btn-primary" data-report-submit>Generate report</button><a class="btn btn-outline" href="{{ route('logistics.reports.index') }}">Reset</a></div>
            </div>
            @if (in_array($type, ['delivery_performance', 'shipping_earnings'], true))
                <div><label for="report-proof" class="mb-1 block text-xs font-semibold">Proof of delivery</label><select id="report-proof" name="proof" class="w-full rounded border border-line px-3 py-2 text-sm sm:w-64"><option value="">All delivered parcels</option><option value="with" @selected(($params['proof'] ?? '') === 'with')>With proof</option><option value="missing" @selected(($params['proof'] ?? '') === 'missing')>Missing proof</option></select></div>
            @endif
            <p class="text-xs text-charcoal/55">Payments are COD; prepaid payments are not supported. Dates use {{ config('app.timezone') }}. Change a date to select a custom range.</p>
        </form>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Report totals">
        @foreach ($report['summary'] as $card)
            <x-seller.stat-card :label="$card['label']" :value="0" :display-value="$card['value']" class="rounded bg-white shadow-none" />
        @endforeach
    </section>
    @if ($type === 'shipping_earnings')
        <section class="space-y-3">
            <h2 class="text-sm font-bold">Per-rider earnings</h2>
            <x-seller.data-table label="Per-rider earnings" responsive="scroll" class="rounded bg-white shadow-none">
                <x-slot:head><tr><th>Rider</th><th>Barangay</th><th>Parcels delivered</th><th>Rider pay total</th></tr></x-slot:head>
                @forelse ($report['secondary'] as $rider)
                    <tr><td>{{ $rider['rider'] }}</td><td>{{ $rider['barangay'] }}</td><td>{{ $rider['parcels'] }}</td><td>{{ $rider['pay'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center">No rider deliveries in this period.</td></tr>
                @endforelse
            </x-seller.data-table>
        </section>
    @endif
    @if (!$report['hasData'])
        <section class="rounded-lg border border-dashed border-line bg-white px-5 py-14 text-center"><h2 class="font-semibold">No matching records</h2><p class="mt-2 text-sm text-charcoal/60">{{ $report['emptyMessage'] }}</p></section>
    @else
        @if ($report['chart'])
            <section class="rounded-lg border border-line bg-white p-4 sm:p-5"><h2 class="text-sm font-bold">{{ $report['chart']['title'] }}</h2><p class="mt-1 text-xs text-charcoal/55">{{ $report['chart']['subtitle'] }}</p><div class="relative mt-4 h-72"><canvas id="reportChart" role="img" aria-label="{{ $report['chart']['title'] }}. The same data is available in the table below."></canvas></div><p data-chart-error hidden class="mt-3 text-xs text-sale-red">Chart could not load. All report figures remain available below.</p></section>
        @endif
        <section class="overflow-hidden rounded-lg border border-line bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3"><h2 class="text-sm font-bold">{{ $type === 'shipping_earnings' ? 'Delivered parcels' : 'Detailed breakdown' }}</h2><span class="text-xs text-charcoal/55">{{ $rows->total() }} records</span></div>
            <x-seller.data-table label="Delivered parcels and report details" responsive="scroll" class="rounded bg-white shadow-none">
                <x-slot:head><tr>@foreach ($report['columns'] as $column)<th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold {{ $column['align'] === 'right' ? 'text-right' : '' }}">{{ $column['label'] }}</th>@endforeach</tr></x-slot:head>
                @forelse ($rows as $row)<tr>@foreach ($report['columns'] as $column)<td class="px-4 py-3 align-top {{ $column['align'] === 'right' ? 'text-right tabular-nums' : '' }}">@if ($column['key'] === 'proof' && !empty($row['proof_order']))<x-logistics.proof-action :code="$row['proof_order']" />@else{{ $row[$column['key']] ?? '' }}@endif</td>@endforeach</tr>
                @empty<tr><td colspan="{{ count($report['columns']) }}" class="py-8 text-center">No records on this page.</td></tr>@endforelse
            </x-seller.data-table>
            @if ($rows->hasPages())<div class="border-t border-line p-4">{{ $rows->links() }}</div>@endif
        </section>
    @endif
    <aside class="rounded-lg border border-line bg-white p-4 text-xs leading-relaxed text-charcoal/65"><h2 class="mb-2 font-bold text-charcoal">How this report is calculated</h2><ul class="list-inside list-disc space-y-1">@foreach ($report['notes'] as $note)<li>{{ $note }}</li>@endforeach<li>All filters also apply to CSV and PDF exports. Exports include all matching rows.</li></ul></aside>
    <script id="reportData" type="application/json">@json(['chart' => $report['chart']])</script>
    <x-logistics.proof-modal />
    @include('money.history')
</x-logistics.layout>
