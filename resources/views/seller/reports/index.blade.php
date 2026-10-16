<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/seller/reports.js'])

    <title>Reports | Shopleap Seller</title>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    @php
        $fin = $report['financial'];
        $sales = $report['sales'];
        $rangeParams =
            $preset === 'custom'
                ? ['preset' => 'custom', 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]
                : ['preset' => $preset];
        $presets = [
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'this_year' => 'This year',
        ];
        $peso = fn($v) => '₱' . number_format($v, 2);
        $chartPayload = ['series' => $report['series'], 'statusBreakdown' => $report['status_breakdown']];
        $hasData = $sales['total_orders'] > 0;
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.seller active="reports" />

        <div class="flex flex-1 flex-col">

            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Reports</h2>
                        <p class="text-sm text-charcoal/55">{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('seller.reports.export.csv', array_merge($rangeParams, ['tab' => $tab])) }}"
                        class="frost-btn rounded-xl px-4 py-2 text-xs font-bold text-charcoal transition hover:text-primary">
                        Export CSV
                    </a>
                    <a href="{{ route('seller.reports.export.pdf', array_merge($rangeParams, ['tab' => $tab])) }}"
                        class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-sky-blue">
                        Download PDF
                    </a>
                </div>
            </header>

            <main class="flex-1 space-y-5 p-6">

                @if ($errors->any())
                    <div
                        class="frost-panel rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- DATE RANGE FILTER -->
                <div class="frost-panel rounded-2xl p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($presets as $key => $label)
                            <a href="{{ route('seller.reports.index', ['preset' => $key, 'tab' => $tab]) }}"
                                class="rounded-lg px-3.5 py-2 text-xs font-semibold transition
                                    {{ $preset === $key ? 'bg-primary text-white' : 'bg-white/60 text-charcoal/70 hover:text-primary' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3 border-t border-white/50 pt-3">
                        <input type="hidden" name="preset" value="custom">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <div>
                            <label
                                class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">From</label>
                            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" required
                                class="frost-input rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">To</label>
                            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" required
                                class="frost-input rounded-xl px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                        </div>
                        <button type="submit"
                            class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $preset === 'custom' ? 'bg-primary text-white' : 'bg-white/60 text-charcoal/70 hover:text-primary' }}">
                            Apply custom range
                        </button>
                    </form>
                </div>

                <!-- REPORT TABS -->
                <div class="frost-panel-solid flex rounded-2xl">
                    @foreach (['financial' => 'Financial Report', 'sales' => 'Sales Performance'] as $key => $label)
                        <a href="{{ route('seller.reports.index', array_merge($rangeParams, ['tab' => $key])) }}"
                            class="flex flex-1 items-center justify-center border-b-2 px-3 py-3.5 text-sm font-semibold transition
                                {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/50 hover:text-charcoal' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                @if (!$hasData)
                    <div
                        class="frost-panel-solid flex h-64 flex-col items-center justify-center rounded-2xl text-center">
                        <p class="text-sm font-semibold text-charcoal">No orders in this date range</p>
                        <p class="mt-1 text-xs text-charcoal/50">Try a wider range, such as "This year".</p>
                    </div>
                @elseif ($tab === 'financial')
                    @php
                        $financialCards = [
                            [
                                'label' => 'Net Earnings',
                                'value' => $peso($fin['realized']['net']),
                                'sub' => $fin['realized']['count'] . ' completed order(s)',
                                'accent' => true,
                            ],
                            [
                                'label' => 'Gross Sales',
                                'value' => $peso($fin['realized']['gross']),
                                'sub' => 'Completed orders only',
                                'accent' => false,
                            ],
                            [
                                'label' => 'Platform Commission',
                                'value' => $peso($fin['realized']['commission']),
                                'sub' => '10% of gross sales',
                                'accent' => false,
                            ],
                            [
                                'label' => 'Pending Earnings',
                                'value' => $peso($fin['pending']['net']),
                                'sub' => $fin['pending']['count'] . ' order(s) in progress',
                                'accent' => false,
                            ],
                        ];
                        $earnedTotals = [
                            'orders' => collect($report['series'])->sum('earned_orders'),
                            'gross' => collect($report['series'])->sum('earned_gross'),
                            'commission' => collect($report['series'])->sum('earned_commission'),
                            'net' => collect($report['series'])->sum('earned_net'),
                        ];
                    @endphp

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($financialCards as $card)
                            <div
                                class="frost-panel rounded-2xl p-5 {{ $card['accent'] ? 'ring-2 ring-primary/20' : '' }}">
                                <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">
                                    {{ $card['label'] }}</p>
                                <p
                                    class="mt-2 text-2xl font-bold {{ $card['accent'] ? 'text-primary' : 'text-charcoal' }}">
                                    {{ $card['value'] }}</p>
                                <p class="mt-1 text-xs text-charcoal/50">{{ $card['sub'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if ($fin['lost']['count'] > 0)
                        <div
                            class="frost-panel rounded-2xl border border-orange-200 bg-orange-50/60 px-4 py-3 text-sm text-orange-700">
                            {{ $peso($fin['lost']['gross']) }} in sales were lost across {{ $fin['lost']['count'] }}
                            cancelled, failed, or returned order(s). These are excluded from all earnings above.
                        </div>
                    @endif

                    <div class="frost-panel rounded-2xl p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-charcoal">Earnings Over Time</h3>
                            <p class="text-xs text-charcoal/50">Completed orders, grouped by order date</p>
                        </div>
                        <canvas id="earningsChart" height="90"></canvas>
                    </div>

                    <div class="frost-panel-solid overflow-hidden rounded-2xl">
                        <div class="border-b border-white/50 p-5">
                            <h3 class="text-sm font-bold text-charcoal">Earnings by Period</h3>
                        </div>
                        <div class="max-h-96 overflow-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="sticky top-0 bg-primary/5 text-charcoal/60">
                                    <tr>
                                        <th class="px-5 py-3 font-semibold">Period</th>
                                        <th class="px-5 py-3 text-right font-semibold">Completed Orders</th>
                                        <th class="px-5 py-3 text-right font-semibold">Gross Sales</th>
                                        <th class="px-5 py-3 text-right font-semibold">Commission</th>
                                        <th class="px-5 py-3 text-right font-semibold">Net Earnings</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/50">
                                    @foreach ($report['series'] as $row)
                                        <tr class="{{ $row['earned_orders'] === 0 ? 'text-charcoal/35' : '' }}">
                                            <td class="px-5 py-3">{{ $row['label'] }}</td>
                                            <td class="px-5 py-3 text-right">{{ $row['earned_orders'] }}</td>
                                            <td class="px-5 py-3 text-right">{{ $peso($row['earned_gross']) }}</td>
                                            <td class="px-5 py-3 text-right">{{ $peso($row['earned_commission']) }}
                                            </td>
                                            <td class="px-5 py-3 text-right font-semibold">
                                                {{ $peso($row['earned_net']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t-2 border-white/60 bg-primary/5 font-bold">
                                    <tr>
                                        <td class="px-5 py-3">Total</td>
                                        <td class="px-5 py-3 text-right">{{ $earnedTotals['orders'] }}</td>
                                        <td class="px-5 py-3 text-right">{{ $peso($earnedTotals['gross']) }}</td>
                                        <td class="px-5 py-3 text-right">{{ $peso($earnedTotals['commission']) }}</td>
                                        <td class="px-5 py-3 text-right text-primary">{{ $peso($earnedTotals['net']) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @else
                    @php
                        $salesCards = [
                            [
                                'label' => 'Total Orders',
                                'value' => number_format($sales['total_orders']),
                                'sub' => $sales['valid_orders'] . ' valid (excl. cancelled/returned)',
                            ],
                            [
                                'label' => 'Units Sold',
                                'value' => number_format($sales['units_sold']),
                                'sub' => 'Across valid orders',
                            ],
                            [
                                'label' => 'Avg. Order Value',
                                'value' => $peso($sales['average_order_value']),
                                'sub' => 'Gross sales ÷ valid orders',
                            ],
                            [
                                'label' => 'Cancellation Rate',
                                'value' => $sales['cancellation_rate'] . '%',
                                'sub' => $sales['lost_orders'] . ' cancelled/failed/returned',
                            ],
                        ];
                        $secondary = [
                            ['label' => 'Gross Sales', 'value' => $peso($sales['gross_sales'])],
                            ['label' => 'Completed Orders', 'value' => $sales['completed_orders']],
                            ['label' => 'Unique Buyers', 'value' => $sales['unique_buyers']],
                            ['label' => 'Repeat Buyers', 'value' => $sales['repeat_buyers']],
                        ];
                    @endphp

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($salesCards as $card)
                            <div class="frost-panel rounded-2xl p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">
                                    {{ $card['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold text-charcoal">{{ $card['value'] }}</p>
                                <p class="mt-1 text-xs text-charcoal/50">{{ $card['sub'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="frost-panel grid grid-cols-2 gap-4 rounded-2xl p-5 lg:grid-cols-4">
                        @foreach ($secondary as $stat)
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">
                                    {{ $stat['label'] }}</p>
                                <p class="mt-1 text-lg font-bold text-charcoal">{{ $stat['value'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <div class="frost-panel rounded-2xl p-5 lg:col-span-2">
                            <div class="mb-4">
                                <h3 class="text-sm font-bold text-charcoal">Sales Trend</h3>
                                <p class="text-xs text-charcoal/50">Gross sales and order count, excluding
                                    cancelled/returned</p>
                            </div>
                            <canvas id="salesChart" height="130"></canvas>
                        </div>

                        <div class="frost-panel rounded-2xl p-5">
                            <div class="mb-4">
                                <h3 class="text-sm font-bold text-charcoal">Orders by Status</h3>
                                <p class="text-xs text-charcoal/50">Every order placed in this range</p>
                            </div>
                            <canvas id="statusChart" height="220"></canvas>
                        </div>
                    </div>

                    <div class="frost-panel-solid overflow-hidden rounded-2xl">
                        <div class="border-b border-white/50 p-5">
                            <h3 class="text-sm font-bold text-charcoal">Top Products</h3>
                            <p class="text-xs text-charcoal/50">Top 10 by units sold. Export CSV for the full list.</p>
                        </div>

                        @if (empty($report['top_products']))
                            <p class="p-8 text-center text-sm text-charcoal/50">No product sales in this range.</p>
                        @else
                            <table class="w-full text-left text-sm">
                                <thead class="bg-primary/5 text-charcoal/60">
                                    <tr>
                                        <th class="px-5 py-3 font-semibold">#</th>
                                        <th class="px-5 py-3 font-semibold">Product</th>
                                        <th class="px-5 py-3 text-right font-semibold">Orders</th>
                                        <th class="px-5 py-3 text-right font-semibold">Units</th>
                                        <th class="px-5 py-3 text-right font-semibold">Revenue</th>
                                        <th class="px-5 py-3 font-semibold">Share</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/50">
                                    @foreach ($report['top_products'] as $i => $product)
                                        @php $share = $sales['gross_sales'] > 0 ? round($product['revenue'] / $sales['gross_sales'] * 100) : 0; @endphp
                                        <tr>
                                            <td class="px-5 py-3 text-charcoal/50">{{ $i + 1 }}</td>
                                            <td class="px-5 py-3 font-medium">{{ $product['name'] }}</td>
                                            <td class="px-5 py-3 text-right">{{ $product['orders'] }}</td>
                                            <td class="px-5 py-3 text-right">{{ $product['units'] }}</td>
                                            <td class="px-5 py-3 text-right font-semibold">
                                                {{ $peso($product['revenue']) }}</td>
                                            <td class="px-5 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="h-1.5 w-20 overflow-hidden rounded-full bg-primary/10">
                                                        <div class="h-full rounded-full bg-primary"
                                                            style="width: {{ $share }}%"></div>
                                                    </div>
                                                    <span class="text-xs text-charcoal/50">{{ $share }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                @endif

                <!-- DEFINITIONS -->
                <div class="frost-panel rounded-2xl p-5 text-xs leading-relaxed text-charcoal/60">
                    <p class="mb-1.5 font-bold uppercase tracking-wide text-charcoal/45">How these numbers are
                        calculated</p>
                    <ul class="list-inside list-disc space-y-1">
                        <li><b>Realized</b> earnings count only <b>Completed</b> orders (buyer confirmed receipt). Net
                            earnings = gross sales − the 10% platform commission.</li>
                        <li><b>Pending</b> covers orders from Placed through Delivered that the buyer hasn't confirmed
                            yet.</li>
                        <li><b>Lost</b> covers Cancelled, Delivery Failed, and Returned orders and is excluded from
                            sales and earnings.</li>
                        <li>Figures are grouped by <b>order date</b>, not completion date.</li>
                        <li><b>Shipping fees</b> are paid by the buyer to delivery and are not part of gross sales,
                            commission or earnings.</li>
                    </ul>
                </div>

            </main>
        </div>
    </div>

    <script id="reportData" type="application/json">@json($chartPayload)</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

</body>

</html>
