<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 12px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #2E3A46;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
        }

        .brand {
            font-size: 13px;
            font-weight: bold;
            color: #1A5FB4;
        }

        .order-id {
            font-size: 11px;
            font-weight: bold;
        }

        .muted {
            color: #6B7280;
        }

        .section-title {
            font-size: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6B7280;
            margin: 5px 0 2px 0;
        }

        .box {
            border: 1px solid #2E3A46;
            padding: 4px 6px;
        }

        .name {
            font-size: 9px;
            font-weight: bold;
        }

        .dest {
            background: #2E3A46;
            color: #FFFFFF;
            padding: 5px 6px;
            margin-top: 5px;
        }

        .dest-label {
            font-size: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dest-main {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .divider {
            border-top: 1px dashed #2E3A46;
            margin: 5px 0;
        }

        .items th {
            text-align: left;
            border-bottom: 1px solid #2E3A46;
            padding: 2px 0;
            font-size: 6px;
            text-transform: uppercase;
        }

        .items td {
            padding: 2px 0;
            border-bottom: 1px solid #E5E7EB;
        }

        .variant {
            font-size: 7px;
            color: #6B7280;
        }

        .cod {
            border: 2px solid #2E3A46;
            padding: 3px 6px;
            margin-top: 5px;
        }

        .cod-amount {
            font-size: 12px;
            font-weight: bold;
        }

        .codes td {
            vertical-align: middle;
        }

        .footer {
            text-align: center;
            font-size: 6px;
            color: #6B7280;
            margin-top: 4px;
        }
    </style>
</head>

<body>

    @php
        $sellerProfile = $order->seller->sellerProfile;
        $ship = $order->ship_to;
        $maxItems = 5;
        $visibleItems = $order->items->take($maxItems);
        $hiddenCount = max(0, $order->items->count() - $maxItems);
    @endphp

    <table>
        <tr>
            <td width="60%">
                <span class="brand">Shopleap</span><br>
                <span class="muted">Shipping Waybill</span>
            </td>
            <td width="40%" style="text-align: right;">
                <span class="order-id">Order #{{ $order->id }}</span><br>
                <span class="muted">{{ $order->created_at->format('M d, Y') }}</span>
            </td>
        </tr>
    </table>

    <div class="dest">
        <div class="dest-label">Deliver To</div>
        @if ($ship['municipality'])
            <div class="dest-main">{{ $ship['municipality'] }}, {{ $ship['province'] }}</div>
            <div style="font-size: 8px;">Brgy. {{ $ship['barangay'] }}</div>
        @else
            <div class="dest-main">—</div>
        @endif
    </div>

    <p class="section-title">Ship To (Buyer)</p>
    <div class="box">
        <div class="name">{{ $ship['name'] ?? '—' }}</div>
        @if ($ship['municipality'])
            {{ $ship['street'] }}, {{ $ship['barangay'] }}, {{ $ship['municipality'] }}, {{ $ship['province'] }}<br>
            Contact: {{ $ship['contact_no'] }}
        @else
            —
        @endif
    </div>

    <p class="section-title">Ship From (Seller)</p>
    <div class="box">
        <div class="name">{{ $sellerProfile->business_name ?? $order->seller->name }}</div>
        @if ($sellerProfile)
            {{ $sellerProfile->street_address }}, {{ $sellerProfile->barangay }},
            {{ $sellerProfile->municipality }}, {{ $sellerProfile->province }}<br>
            Contact: {{ $sellerProfile->contact_no }}
        @endif
    </div>

    <p class="section-title">Items ({{ $order->items->sum('quantity') }} total)</p>
    <table class="items">
        <tr>
            <th>Item</th>
            <th style="text-align: right; width: 30px;">Qty</th>
        </tr>
        @foreach ($visibleItems as $item)
            <tr>
                <td>
                    {{ $item->product_name }}
                    @if ($item->selected_options)
                        <div class="variant">
                            {{ collect($item->selected_options)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ') }}
                        </div>
                    @endif
                </td>
                <td style="text-align: right;">{{ $item->quantity }}</td>
            </tr>
        @endforeach
        @if ($hiddenCount > 0)
            <tr>
                <td colspan="2" class="muted">+ {{ $hiddenCount }} more item(s) — see order details</td>
            </tr>
        @endif
    </table>

    @if (($order->total_weight_kg ?? 0) > 0)
        <p class="muted" style="font-size: 7px; margin-top: 4px;">
            Parcel weight: {{ number_format($order->total_weight_kg, 2) }} kg
        </p>
    @endif

    <div class="cod">
        <table>
            <tr>
                <td>
                    <span class="muted" style="font-size: 6px; text-transform: uppercase;">Payment</span><br>
                    <b>Cash on Delivery</b>
                </td>
                <td style="text-align: right;">
                    <span class="muted" style="font-size: 6px; text-transform: uppercase;">Amount to collect</span><br>
                    <span class="cod-amount">₱{{ number_format($order->grand_total, 2) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="codes" style="margin-top: 6px;">
        <tr>
            <td style="width: 72%; text-align: center;">
                <img src="data:image/png;base64,{{ $barcodeBase64 }}">
                <div style="font-size: 7px; letter-spacing: 1px;">{{ $orderCode }}</div>
            </td>
            <td style="width: 28%; text-align: right;">
                <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" style="width: 66px; height: 66px;">
            </td>
        </tr>
    </table>

    <div class="footer">
        Scan at pickup, sorting center &amp; delivery · Printed {{ now()->format('M d, Y g:i A') }} · Shopleap
        Logistics
    </div>

</body>

</html>
