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
            <p class="text-xs text-charcoal/55">Payments are COD; prepaid payments are not supported. Dates use {{ config('app.timezone') }}. Change a date to select a custom range.</p>
        </form>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Report totals">
        @foreach ($report['summary'] as $card)
            <div class="rounded-lg border border-line bg-white p-4"><p class="text-xs font-semibold text-charcoal/60">{{ $card['label'] }}</p><p class="mt-2 text-2xl font-bold {{ $card['accent'] ? 'text-primary' : 'text-charcoal' }}">{{ $card['value'] }}</p></div>
        @endforeach
    </section>
    @if (!$report['hasData'])
        <section class="rounded-lg border border-dashed border-line bg-white px-5 py-14 text-center"><h2 class="font-semibold">No matching records</h2><p class="mt-2 text-sm text-charcoal/60">{{ $report['emptyMessage'] }}</p></section>
    @else
        @if ($report['chart'])
            <section class="rounded-lg border border-line bg-white p-4 sm:p-5"><h2 class="text-sm font-bold">{{ $report['chart']['title'] }}</h2><p class="mt-1 text-xs text-charcoal/55">{{ $report['chart']['subtitle'] }}</p><div class="relative mt-4 h-72"><canvas id="reportChart" role="img" aria-label="{{ $report['chart']['title'] }}. The same data is available in the table below."></canvas></div><p data-chart-error hidden class="mt-3 text-xs text-sale-red">Chart could not load. All report figures remain available below.</p></section>
        @endif
        <section class="overflow-hidden rounded-lg border border-line bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3"><h2 class="text-sm font-bold">Detailed breakdown</h2><span class="text-xs text-charcoal/55">{{ $rows->total() }} records</span></div>
            <div class="overflow-x-auto"><table class="app-table w-full text-left text-xs"><thead><tr>@foreach ($report['columns'] as $column)<th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold {{ $column['align'] === 'right' ? 'text-right' : '' }}">{{ $column['label'] }}</th>@endforeach</tr></thead><tbody>@foreach ($rows as $row)<tr class="hover:bg-ice-blue/60">@foreach ($report['columns'] as $column)<td class="px-4 py-3 align-top {{ $column['align'] === 'right' ? 'text-right tabular-nums' : '' }}">{{ $row[$column['key']] ?? '' }}</td>@endforeach</tr>@endforeach</tbody></table></div>
            @if ($rows->hasPages())<div class="border-t border-line p-4">{{ $rows->links() }}</div>@endif
        </section>
    @endif
    <aside class="rounded-lg border border-line bg-white p-4 text-xs leading-relaxed text-charcoal/65"><h2 class="mb-2 font-bold text-charcoal">How this report is calculated</h2><ul class="list-inside list-disc space-y-1">@foreach ($report['notes'] as $note)<li>{{ $note }}</li>@endforeach<li>All filters also apply to CSV and PDF exports. Exports include all matching rows.</li></ul></aside>
    <script id="reportData" type="application/json">@json(['chart' => $report['chart']])</script>
</x-logistics.layout>
