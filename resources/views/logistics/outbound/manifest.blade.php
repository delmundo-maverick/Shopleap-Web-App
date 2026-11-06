<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css'])

    <title>Manifest {{ $bag->code }} | Shopleap Logistics</title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .print-card {
                box-shadow: none !important;
                border: 1px solid #d1d5db !important;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-light-gray/40 p-6 text-charcoal">
    <div class="mx-auto max-w-4xl space-y-4">
        <div class="no-print flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('logistics.outbound.show', $bag) }}"
                class="rounded-xl border border-charcoal/15 px-4 py-2 text-sm font-semibold text-charcoal/70 transition hover:bg-charcoal/5">
                Back to bag
            </a>
            <button type="button" onclick="window.print()"
                class="rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white transition hover:bg-sky-blue">
                Print Manifest
            </button>
        </div>

        <div class="print-card rounded-2xl border border-white/60 bg-white p-8 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-6 border-b border-charcoal/10 pb-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary">Shopleap Logistics</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight">Bag Manifest</h1>
                    <p class="mt-2 font-mono text-lg font-semibold">{{ $bag->code }}</p>
                    <p class="mt-1 text-sm text-charcoal/60">
                        {{ $bag->status_label }}
                        @if ($bag->sealed_at)
                            · Sealed {{ $bag->sealed_at->format('M d, Y g:i A') }}
                        @endif
                    </p>
                </div>

                <div class="text-center">
                    <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="{{ $bag->code }}" class="max-w-full">
                    <p class="mt-1 font-mono text-xs tracking-widest">{{ $bag->code }}</p>
                    <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" alt="Bag QR"
                        class="mx-auto mt-3 h-24 w-24">
                </div>
            </div>

            <div class="grid gap-4 border-b border-charcoal/10 py-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">From Hub</p>
                    <p class="mt-1 text-base font-bold">{{ $bag->fromHub?->business_name }}</p>
                    <p class="text-sm text-charcoal/60">
                        {{ $bag->fromHub?->street_address }}, {{ $bag->fromHub?->barangay }},
                        {{ $bag->fromHub?->municipality }}, {{ $bag->fromHub?->province }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">To Hub</p>
                    <p class="mt-1 text-base font-bold">{{ $bag->toHub?->business_name }}</p>
                    <p class="text-sm text-charcoal/60">
                        {{ $bag->toHub?->street_address }}, {{ $bag->toHub?->barangay }},
                        {{ $bag->toHub?->municipality }}, {{ $bag->toHub?->province }}
                    </p>
                </div>
            </div>

            <div class="py-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-charcoal/60">Parcels</h2>
                    <p class="text-sm font-semibold">{{ $bag->items->count() }} parcel(s)</p>
                </div>

                <table class="app-table mt-3 w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="py-2 font-semibold text-charcoal/60">Order</th>
                            <th class="py-2 font-semibold text-charcoal/60">Seller</th>
                            <th class="py-2 font-semibold text-charcoal/60">Destination</th>
                            <th class="py-2 text-right font-semibold text-charcoal/60">Items</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bag->items as $item)
                            <tr>
                                <td class="py-2 font-mono font-bold">#{{ $item->order_id }}</td>
                                <td class="py-2">
                                    {{ $item->order?->seller?->sellerProfile?->business_name ?? $item->order?->seller?->name ?? '—' }}
                                </td>
                                <td class="py-2">
                                    {{ $item->order?->ship_to['municipality'] ?? '—' }},
                                    {{ $item->order?->ship_to['province'] ?? '' }}
                                </td>
                                <td class="py-2 text-right">{{ $item->order?->items?->sum('quantity') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid gap-6 border-t border-charcoal/10 pt-6 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Created by</p>
                    <p class="mt-1 font-semibold">{{ $bag->creator?->name ?? '—' }}</p>
                    <p class="text-xs text-charcoal/50">{{ $bag->created_at?->format('M d, Y g:i A') }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Sealed by</p>
                    <p class="mt-1 font-semibold">{{ $bag->sealer?->name ?? '—' }}</p>
                    <p class="text-xs text-charcoal/50">{{ $bag->sealed_at?->format('M d, Y g:i A') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">Dispatched</p>
                    <p class="mt-1 font-semibold">{{ $bag->dispatched_at?->format('M d, Y g:i A') ?? '—' }}</p>
                </div>
            </div>

            <p class="mt-6 border-t border-charcoal/10 pt-4 text-center text-xs text-charcoal/50">
                Scan the bag QR/barcode at dispatch and receipt · Printed {{ now()->format('M d, Y g:i A') }}
            </p>
        </div>
    </div>
</body>

</html>
