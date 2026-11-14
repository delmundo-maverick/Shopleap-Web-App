<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/css/pages/logistics/outbound-manifest.css', 'resources/js/pages/logistics/outbound-manifest.js'])

    <title>Manifest {{ $bag->code }} | Shopleap Logistics</title>
</head>

<body class="min-h-screen bg-light-gray/40 p-3 text-charcoal sm:p-6">
    <div class="mx-auto max-w-4xl space-y-4">
        <div class="no-print flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('logistics.outbound.show', $bag) }}"
                class="rounded-xl border border-charcoal/15 px-4 py-2 text-sm font-semibold text-charcoal/70 transition hover:bg-charcoal/5">
                Back to bag
            </a>
            <button type="button" id="manifest-print-button"
                data-record-url="{{ route('logistics.outbound.manifest.printed', $bag) }}"
                data-bag-id="{{ $bag->id }}" data-order-ids="{{ $bag->items->pluck('order.order_number')->values()->toJson() }}"
                class="rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white transition hover:bg-sky-blue disabled:cursor-wait disabled:opacity-60">
                Print Manifest
            </button>
        </div>
        <p id="manifest-print-status" class="no-print text-sm" role="status" aria-live="polite"></p>

        <div class="print-card rounded-2xl border border-white/60 bg-white p-4 shadow-sm sm:p-8">
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

                <table class="manifest-table mt-3 w-full table-fixed text-left text-xs sm:text-sm">
                    <thead>
                        <tr>
                            <th class="py-2 pr-3 font-semibold text-charcoal/60">Waybill / tracking number</th>
                            <th class="py-2 pr-3 font-semibold text-charcoal/60">Receiver barangay</th>
                            <th class="py-2 font-semibold text-charcoal/60">Municipality</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bag->items as $item)
                            <tr>
                                <td class="py-2 pr-3 font-mono font-bold">{{ $item->order?->waybill_code }}</td>
                                <td class="py-2 pr-3">
                                    {{ $item->order?->ship_to['barangay'] ?? '—' }}
                                </td>
                                <td class="py-2">
                                    {{ $item->order?->ship_to['municipality'] ?? '—' }}
                                </td>
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
                Manifest generated {{ now()->format('M d, Y g:i A') }} · Keep this manifest with the bag for the receiving hub.
            </p>
        </div>
    </div>
</body>

</html>
