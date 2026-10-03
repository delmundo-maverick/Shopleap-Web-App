<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/admin/transactions.js'])

    <title>All Transactions | Shopleap Admin</title>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    @php
        $peso = fn($v) => '₱' . number_format($v, 2);
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
        $chartPayload = ['series' => $report['series'], 'statusBreakdown' => $report['status_breakdown']];
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.admin active="transactions" />

        <div class="flex flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">All Transactions</h2>
                        <p class="text-sm text-charcoal/55">{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }}
                        </p>
                    </div>
                </div>

                <a href="{{ route('admin.transactions.export.csv', $rangeParams) }}"
                    class="frost-btn rounded-xl px-4 py-2 text-xs font-bold text-charcoal transition hover:text-primary">
                    Export CSV
                </a>
            </header>

            <main class="flex-1 space-y-5 p-6">

                @if ($errors->any())
                    <div
                        class="frost-panel rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- DATE RANGE -->
                <div class="frost-panel rounded-2xl p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($presets as $key => $label)
                            <a href="{{ route('admin.transactions.index', ['preset' => $key]) }}"
                                class="rounded-lg px-3.5 py-2 text-xs font-semibold transition
                                    {{ $preset === $key ? 'bg-primary text-white' : 'bg-white/60 text-charcoal/70 hover:text-primary' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3 border-t border-white/50 pt-3">
                        <input type="hidden" name="preset" value="custom">
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

                <!-- SUMMARY CARDS -->
                @php
                    $cards = [
                        [
                            'label' => 'GMV (Completed)',
                            'value' => $peso($report['realized']['gmv']),
                            'sub' => $report['realized']['count'] . ' completed order(s)',
                            'accent' => true,
                        ],
                        [
                            'label' => 'Commission Revenue (10%)',
                            'value' => $peso($report['realized']['commission']),
                            'sub' => 'Platform earnings',
                            'accent' => false,
                        ],
                        [
                            'label' => 'Seller Payouts',
                            'value' => $peso($report['realized']['payout']),
                            'sub' => 'GMV minus commission',
                            'accent' => false,
                        ],
                        [
                            'label' => 'Pending GMV',
                            'value' => $peso($report['pending']['gmv']),
                            'sub' => $report['pending']['count'] . ' order(s) in progress',
                            'accent' => false,
                        ],
                    ];
                @endphp
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($cards as $card)
                        <div class="frost-panel rounded-2xl p-5 {{ $card['accent'] ? 'ring-2 ring-primary/20' : '' }}">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">
                                {{ $card['label'] }}</p>
                            <p
                                class="mt-2 text-2xl font-bold {{ $card['accent'] ? 'text-primary' : 'text-charcoal' }}">
                                {{ $card['value'] }}</p>
                            <p class="mt-1 text-xs text-charcoal/50">{{ $card['sub'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($report['lost']['count'] > 0)
                    <div
                        class="frost-panel rounded-2xl border border-orange-200 bg-orange-50/60 px-4 py-3 text-sm text-orange-700">
                        {{ $peso($report['lost']['gmv']) }} in GMV was lost across {{ $report['lost']['count'] }}
                        cancelled or returned order(s) in this range.
                    </div>
                @endif

                <!-- CHARTS -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="frost-panel rounded-2xl p-5 lg:col-span-2">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-charcoal">GMV &amp; Commission Over Time</h3>
                            <p class="text-xs text-charcoal/50">Completed orders, grouped by order date</p>
                        </div>
                        <canvas id="gmvTrendChart" height="110"></canvas>
                    </div>
                    <div class="frost-panel rounded-2xl p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-charcoal">Orders by Status</h3>
                            <p class="text-xs text-charcoal/50">All orders placed in this range</p>
                        </div>
                        <canvas id="statusChart" height="220"></canvas>
                    </div>
                </div>

                <!-- TOP SELLERS -->
                <div class="frost-panel-solid overflow-hidden rounded-2xl">
                    <div class="border-b border-white/50 p-5">
                        <h3 class="text-sm font-bold text-charcoal">Top Sellers</h3>
                        <p class="text-xs text-charcoal/50">By GMV, completed orders only</p>
                    </div>
                    @if (empty($report['top_sellers']))
                        <p class="p-8 text-center text-sm text-charcoal/50">No completed orders in this range.</p>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="bg-primary/5 text-charcoal/60">
                                <tr>
                                    <th class="px-5 py-3 font-semibold">#</th>
                                    <th class="px-5 py-3 font-semibold">Shop</th>
                                    <th class="px-5 py-3 text-right font-semibold">Orders</th>
                                    <th class="px-5 py-3 text-right font-semibold">GMV</th>
                                    <th class="px-5 py-3 text-right font-semibold">Commission</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/50">
                                @foreach ($report['top_sellers'] as $i => $seller)
                                    <tr>
                                        <td class="px-5 py-3 text-charcoal/50">{{ $i + 1 }}</td>
                                        <td class="px-5 py-3 font-medium">{{ $seller['name'] }}</td>
                                        <td class="px-5 py-3 text-right">{{ $seller['orders'] }}</td>
                                        <td class="px-5 py-3 text-right font-semibold">{{ $peso($seller['gmv']) }}</td>
                                        <td class="px-5 py-3 text-right">{{ $peso($seller['commission']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <!-- LEDGER -->
                <div class="frost-panel-solid overflow-hidden rounded-2xl">
                    <form method="GET" class="flex flex-wrap gap-3 border-b border-white/50 p-4">
                        <input type="hidden" name="preset" value="{{ $preset }}">
                        @if ($preset === 'custom')
                            <input type="hidden" name="from" value="{{ $from->format('Y-m-d') }}">
                            <input type="hidden" name="to" value="{{ $to->format('Y-m-d') }}">
                        @endif
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search order #, buyer, or shop…"
                            class="frost-input flex-1 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                        <select name="status" onchange="this.form.submit()"
                            class="frost-input rounded-xl px-3.5 py-2.5 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                            <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All
                                Status</option>
                            @foreach (\App\Models\Order::STATUS_LABELS as $value => $label)
                                <option value="{{ $value }}"
                                    {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">Search</button>
                    </form>

                    @if ($orders->isEmpty())
                        <div class="flex h-48 flex-col items-center justify-center text-center">
                            <p class="text-sm font-semibold text-charcoal">No transactions found</p>
                        </div>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-white/50 bg-primary/5 text-charcoal/60">
                                <tr>
                                    <th class="px-5 py-3 font-semibold">Order</th>
                                    <th class="px-5 py-3 font-semibold">Buyer</th>
                                    <th class="px-5 py-3 font-semibold">Seller</th>
                                    <th class="px-5 py-3 font-semibold">Status</th>
                                    <th class="px-5 py-3 text-right font-semibold">GMV</th>
                                    <th class="px-5 py-3 text-right font-semibold">Commission</th>
                                    <th class="px-5 py-3 font-semibold">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/50">
                                @foreach ($orders as $order)
                                    <tr class="transition hover:bg-white/40">
                                        <td class="px-5 py-4 font-semibold">#{{ $order->id }}</td>
                                        <td class="px-5 py-4 text-charcoal/70">{{ $order->buyer_name }}</td>
                                        <td class="px-5 py-4 text-charcoal/70">
                                            {{ $order->seller->sellerProfile->business_name ?? ($order->seller->name ?? '—') }}
                                        </td>
                                        <td class="px-5 py-4"><span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $order->status_color }}">{{ $order->status_label }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-right font-semibold">
                                            {{ $peso($order->total_amount) }}</td>
                                        <td class="px-5 py-4 text-right text-charcoal/60">
                                            {{ $peso($order->commission_amount) }}</td>
                                        <td class="px-5 py-4 text-charcoal/50">
                                            {{ $order->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="p-4">{{ $orders->onEachSide(1)->links() }}</div>
                    @endif
                </div>

            </main>
        </div>
    </div>

    <script id="transactionsData" type="application/json">@json($chartPayload)</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

</body>

</html>
