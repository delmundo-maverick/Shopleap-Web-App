<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/logistics/reports.js'])

    <title>Reports | Shopleap Logistics</title>
</head>
<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    @php
        $rangeParams = $preset === 'custom'
            ? ['preset' => 'custom', 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'type' => $type]
            : ['preset' => $preset, 'type' => $type];
        $presets = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'this_month' => 'This month', 'last_month' => 'Last month', 'this_year' => 'This year'];
        $chartPayload = ['chart' => $report['chart']];
        $hasData = $report['hasData'] ?? false;
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.logistics active="reports" />

        <div class="flex flex-1 flex-col">

            <header class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Reports</h2>
                        <p class="text-sm text-charcoal/55">{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.reports.export.csv', array_merge($rangeParams)) }}" class="frost-btn rounded-xl px-4 py-2 text-xs font-bold text-charcoal transition hover:text-primary">Export CSV</a>
                    <a href="{{ route('logistics.reports.export.pdf', array_merge($rangeParams)) }}" class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-sky-blue">Download PDF</a>
                </div>
            </header>

            <main class="flex-1 space-y-5 p-6">

                @if ($errors->any())
                    <div class="frost-panel rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">{{ $errors->first() }}</div>
                @endif

                <div class="frost-panel rounded-2xl p-4">
                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">Report Type</label>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($types as $key => $label)
                            <a href="{{ route('logistics.reports.index', array_merge($rangeParams, ['type' => $key])) }}" class="rounded-lg px-3.5 py-2 text-xs font-semibold transition {{ $type === $key ? 'bg-primary text-white' : 'bg-white/60 text-charcoal/70 hover:text-primary' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="frost-panel rounded-2xl p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($presets as $key => $label)
                            <a href="{{ route('logistics.reports.index', ['preset' => $key, 'type' => $type]) }}" class="rounded-lg px-3.5 py-2 text-xs font-semibold transition {{ $preset === $key ? 'bg-primary text-white' : 'bg-white/60 text-charcoal/70 hover:text-primary' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3 border-t border-white/50 pt-3">
                        <input type="hidden" name="preset" value="custom">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">From</label>
                            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" required class="frost-input rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">To</label>
                            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" required class="frost-input rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                        </div>
                        <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $preset === 'custom' ? 'bg-primary text-white' : 'bg-white/60 text-charcoal/70 hover:text-primary' }}">Apply custom range</button>
                    </form>
                </div>

                <div class="frost-panel rounded-2xl p-4">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="preset" value="{{ $preset }}">
                        <input type="hidden" name="type" value="{{ $type }}">
                        @if ($preset === 'custom')
                            <input type="hidden" name="from" value="{{ $from->format('Y-m-d') }}">
                            <input type="hidden" name="to" value="{{ $to->format('Y-m-d') }}">
                        @endif
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">Shipment Status</label>
                            <select name="status" class="frost-input rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                                <option value="">All</option>
                                @foreach (['PLACED','CONFIRMED','PREPARING','READY_FOR_PICKUP','PICKED_UP','AT_SORTING_CENTER','SORTED','AWAITING_TRANSFER','ASSIGNED_TO_RIDER','OUT_FOR_DELIVERY','DELIVERED','COMPLETED','CANCELLED','DELIVERY_FAILED','RETURNED'] as $st)
                                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">Payment Type</label>
                            <select name="payment_type" class="frost-input rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                                <option value="">All</option>
                                <option value="COD" {{ request('payment_type') === 'COD' ? 'selected' : '' }}>COD</option>
                                <option value="Prepaid" {{ request('payment_type') === 'Prepaid' ? 'selected' : '' }}>Prepaid</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">Origin Area</label>
                            <input type="text" name="origin_area" value="{{ request('origin_area') }}" class="frost-input rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10" placeholder="City">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">Destination Area</label>
                            <input type="text" name="destination_area" value="{{ request('destination_area') }}" class="frost-input rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10" placeholder="Zone">
                        </div>
                        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-sky-blue">Apply filters</button>
                    </form>
                </div>

                @if (! $hasData)
                    <div class="frost-panel-solid flex h-64 flex-col items-center justify-center rounded-2xl text-center">
                        <p class="text-sm font-semibold text-charcoal">{{ $report['emptyMessage'] ?? 'No data in this date range' }}</p>
                        <p class="mt-1 text-xs text-charcoal/50">Try a wider range, such as "This year".</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($report['summary'] as $card)
                            <div class="frost-panel rounded-2xl p-5 {{ ($card['accent'] ?? false) ? 'ring-2 ring-primary/20' : '' }}">
                                <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">{{ $card['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold {{ ($card['accent'] ?? false) ? 'text-primary' : 'text-charcoal' }}">{{ $card['value'] }}</p>
                                <p class="mt-1 text-xs text-charcoal/50">{{ $card['sub'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if (! empty($report['secondary']))
                        <div class="frost-panel grid grid-cols-2 gap-4 rounded-2xl p-5 lg:grid-cols-4">
                            @foreach ($report['secondary'] as $stat)
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">{{ $stat['label'] }}</p>
                                    <p class="mt-1 text-lg font-bold text-charcoal">{{ $stat['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($report['chart'])
                        <div class="frost-panel rounded-2xl p-5">
                            <div class="mb-4">
                                <h3 class="text-sm font-bold text-charcoal">{{ $report['chart']['title'] }}</h3>
                                <p class="text-xs text-charcoal/50">{{ $report['chart']['subtitle'] }}</p>
                            </div>
                            <canvas id="reportChart" height="110"></canvas>
                        </div>
                    @endif

                    <div class="frost-panel-solid overflow-hidden rounded-2xl">
                        <div class="border-b border-white/50 p-5">
                            <h3 class="text-sm font-bold text-charcoal">{{ $report['title'] }}</h3>
                            <p class="text-xs text-charcoal/50">Detailed breakdown for the selected range.</p>
                        </div>
                        <div class="max-h-96 overflow-auto">
                            @if (empty($report['rows']))
                                <p class="p-8 text-center text-sm text-charcoal/50">No rows to display.</p>
                            @else
                                <table class="w-full text-left text-sm">
                                    <thead class="sticky top-0 bg-primary/5 text-charcoal/60">
                                        <tr>
                                            @foreach ($report['columns'] as $col)
                                                <th class="px-5 py-3 font-semibold {{ ($col['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">{{ $col['label'] }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/50">
                                        @foreach ($report['rows'] as $row)
                                            <tr>
                                                @foreach ($report['columns'] as $col)
                                                    <td class="px-5 py-3 {{ ($col['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">{{ $row[$col['key']] ?? '' }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="frost-panel rounded-2xl p-5 text-xs leading-relaxed text-charcoal/60">
                    <p class="mb-1.5 font-bold uppercase tracking-wide text-charcoal/45">How these numbers are calculated</p>
                    <ul class="list-inside list-disc space-y-1">
                        <li>Figures are scoped to the logged-in logistics hub and the selected date range.</li>
                        <li>On-time delivery is based on parcels delivered within the first attempt.</li>
                        <li>COD amounts default to order total + shipping fee when not explicitly recorded.</li>
                        <li>Reattempt surcharges are estimated at ₱50 per extra attempt.</li>
                    </ul>
                </div>

            </main>
        </div>
    </div>

    <script id="reportData" type="application/json">@json($chartPayload)</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

</body>
</html>

