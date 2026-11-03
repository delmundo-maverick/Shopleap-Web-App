<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #2E3A46; }
        table { width: 100%; border-collapse: collapse; }
        h1 { font-size: 18px; margin: 0; color: #1A5FB4; }
        h2 { font-size: 12px; margin: 18px 0 6px 0; }
        .muted { color: #6B7280; }
        .right { text-align: right; }
        .metrics td { border: 1px solid #D1D5DB; padding: 8px 10px; width: 25%; vertical-align: top; }
        .metric-label { font-size: 8px; text-transform: uppercase; color: #6B7280; }
        .metric-value { font-size: 14px; font-weight: bold; margin-top: 3px; }
        .grid th { background: #EAF2FB; text-align: left; padding: 5px 6px; font-size: 9px; border-bottom: 1px solid #2E3A46; }
        .grid td { padding: 5px 6px; border-bottom: 1px solid #E5E7EB; }
        .grid tfoot td { font-weight: bold; border-top: 2px solid #2E3A46; background: #F5F7FA; }
        .note { margin-top: 18px; font-size: 8px; color: #6B7280; line-height: 1.5; }
    </style>
</head>
<body>
    @php
        $peso = fn($v) => '₱' . number_format((float) $v, 2);
    @endphp

    <table>
        <tr>
            <td>
                <h1>Shopleap</h1>
                <div class="muted">Logistics Report: {{ $report['title'] }}</div>
            </td>
            <td class="right">
                <b>{{ $hubName }}</b><br>
                <span class="muted">{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }}</span><br>
                <span class="muted">Generated {{ now()->format('M d, Y g:i A') }}</span>
            </td>
        </tr>
    </table>

    @if (! empty($report['summary']))
        <h2>Summary</h2>
        <table class="metrics">
            <tr>
                @foreach (array_slice($report['summary'], 0, 4) as $card)
                    <td>
                        <div class="metric-label">{{ $card['label'] }}</div>
                        <div class="metric-value">{{ $card['value'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @if (! empty($report['columns']) && ! empty($report['rows']))
        <h2>Details</h2>
        <table class="grid">
            <thead>
                <tr>
                    @foreach ($report['columns'] as $col)
                        <th class="{{ ($col['align'] ?? 'left') === 'right' ? 'right' : '' }}">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>
                        @foreach ($report['columns'] as $col)
                            <td class="{{ ($col['align'] ?? 'left') === 'right' ? 'right' : '' }}">{{ $row[$col['key']] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">{{ $report['emptyMessage'] ?? 'No data in this date range.' }}</p>
    @endif

    <p class="note">
        Figures are scoped to the logged-in logistics hub and the selected date range.
    </p>
</body>
</html>
