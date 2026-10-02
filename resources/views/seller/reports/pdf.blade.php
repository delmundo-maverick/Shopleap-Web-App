<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 32px 36px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #2E3A46;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        h1 {
            font-size: 18px;
            margin: 0;
            color: #1A5FB4;
        }

        h2 {
            font-size: 12px;
            margin: 18px 0 6px 0;
        }

        .muted {
            color: #6B7280;
        }

        .right {
            text-align: right;
        }

        .metrics td {
            border: 1px solid #D1D5DB;
            padding: 8px 10px;
            width: 25%;
            vertical-align: top;
        }

        .metric-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #6B7280;
        }

        .metric-value {
            font-size: 14px;
            font-weight: bold;
            margin-top: 3px;
        }

        .grid th {
            background: #EAF2FB;
            text-align: left;
            padding: 5px 6px;
            font-size: 9px;
            border-bottom: 1px solid #2E3A46;
        }

        .grid td {
            padding: 5px 6px;
            border-bottom: 1px solid #E5E7EB;
        }

        .grid tfoot td {
            font-weight: bold;
            border-top: 2px solid #2E3A46;
            background: #F5F7FA;
        }

        .note {
            margin-top: 18px;
            font-size: 8px;
            color: #6B7280;
            line-height: 1.5;
        }
    </style>
</head>

<body>

    @php
        $fin = $report['financial'];
        $sales = $report['sales'];
        $peso = fn($v) => '₱' . number_format($v, 2);
    @endphp

    <table>
        <tr>
            <td>
                <h1>Shopleap</h1>
                <div class="muted">{{ $tab === 'sales' ? 'Sales Performance Report' : 'Financial Report' }}</div>
            </td>
            <td class="right">
                <b>{{ $shopName }}</b><br>
                <span class="muted">{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }}</span><br>
                <span class="muted">Generated {{ now()->format('M d, Y g:i A') }}</span>
            </td>
        </tr>
    </table>

    @if ($tab === 'financial')

        <h2>Summary</h2>
        <table class="metrics">
            <tr>
                <td>
                    <div class="metric-label">Net Earnings</div>
                    <div class="metric-value">{{ $peso($fin['realized']['net']) }}</div>
                </td>
                <td>
                    <div class="metric-label">Gross Sales</div>
                    <div class="metric-value">{{ $peso($fin['realized']['gross']) }}</div>
                </td>
                <td>
                    <div class="metric-label">Platform Commission</div>
                    <div class="metric-value">{{ $peso($fin['realized']['commission']) }}</div>
                </td>
                <td>
                    <div class="metric-label">Pending Earnings</div>
                    <div class="metric-value">{{ $peso($fin['pending']['net']) }}</div>
                </td>
            </tr>
        </table>
        <p class="muted" style="margin-top: 6px;">
            {{ $fin['realized']['count'] }} completed order(s) · {{ $fin['pending']['count'] }} in progress ·
            {{ $fin['lost']['count'] }} cancelled/failed/returned ({{ $peso($fin['lost']['gross']) }} lost, excluded
            above)
        </p>

        <h2>Earnings by Period</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Period</th>
                    <th class="right">Completed Orders</th>
                    <th class="right">Gross Sales</th>
                    <th class="right">Commission</th>
                    <th class="right">Net Earnings</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['series'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="right">{{ $row['earned_orders'] }}</td>
                        <td class="right">{{ $peso($row['earned_gross']) }}</td>
                        <td class="right">{{ $peso($row['earned_commission']) }}</td>
                        <td class="right">{{ $peso($row['earned_net']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="right">{{ collect($report['series'])->sum('earned_orders') }}</td>
                    <td class="right">{{ $peso(collect($report['series'])->sum('earned_gross')) }}</td>
                    <td class="right">{{ $peso(collect($report['series'])->sum('earned_commission')) }}</td>
                    <td class="right">{{ $peso(collect($report['series'])->sum('earned_net')) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <h2>Summary</h2>
        <table class="metrics">
            <tr>
                <td>
                    <div class="metric-label">Total Orders</div>
                    <div class="metric-value">{{ number_format($sales['total_orders']) }}</div>
                </td>
                <td>
                    <div class="metric-label">Units Sold</div>
                    <div class="metric-value">{{ number_format($sales['units_sold']) }}</div>
                </td>
                <td>
                    <div class="metric-label">Avg. Order Value</div>
                    <div class="metric-value">{{ $peso($sales['average_order_value']) }}</div>
                </td>
                <td>
                    <div class="metric-label">Cancellation Rate</div>
                    <div class="metric-value">{{ $sales['cancellation_rate'] }}%</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="metric-label">Gross Sales</div>
                    <div class="metric-value">{{ $peso($sales['gross_sales']) }}</div>
                </td>
                <td>
                    <div class="metric-label">Completed Orders</div>
                    <div class="metric-value">{{ $sales['completed_orders'] }}</div>
                </td>
                <td>
                    <div class="metric-label">Unique Buyers</div>
                    <div class="metric-value">{{ $sales['unique_buyers'] }}</div>
                </td>
                <td>
                    <div class="metric-label">Repeat Buyers</div>
                    <div class="metric-value">{{ $sales['repeat_buyers'] }}</div>
                </td>
            </tr>
        </table>

        <h2>Top Products</h2>
        @if (empty($report['top_products']))
            <p class="muted">No product sales in this range.</p>
        @else
            <table class="grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th class="right">Orders</th>
                        <th class="right">Units</th>
                        <th class="right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['top_products'] as $i => $product)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $product['name'] }}</td>
                            <td class="right">{{ $product['orders'] }}</td>
                            <td class="right">{{ $product['units'] }}</td>
                            <td class="right">{{ $peso($product['revenue']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2>Orders by Status</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="right">Orders</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['status_breakdown'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="right">{{ $row['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @endif

    <p class="note">
        Realized earnings count only Completed orders; net earnings = gross sales − 10% platform commission.
        Pending = Placed through Delivered (not yet confirmed by the buyer). Lost = Cancelled, Delivery Failed, or
        Returned,
        excluded from sales and earnings. Figures are grouped by order date.
    </p>

</body>

</html>
