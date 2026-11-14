<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waybill {{ $order->order_number }} | Shopleap Seller</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/seller/waybill.js'])
</head>
<body class="min-h-dvh bg-ice-blue text-charcoal">
    <div class="flex min-h-dvh">
        <x-navbars.seller />
        <main class="min-w-0 flex-1 p-4 sm:p-6">
            <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <x-nav.toggle target="workspace-navigation" />
                    <h1 class="break-all text-lg font-bold">Waybill {{ $order->order_number }}</h1>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('seller.orders.show', $order) }}" class="rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold">Back to order</a>
                    <button type="button" data-print-waybill class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-surface">Print Waybill</button>
                    <a href="{{ route('seller.orders.waybill', $order) }}" target="_blank" rel="noopener" class="rounded-md border border-primary bg-surface px-4 py-2 text-sm font-semibold text-primary">Open PDF</a>
                </div>
            </header>
            <iframe id="waybill-preview" src="{{ route('seller.orders.waybill', $order) }}" title="Waybill {{ $order->order_number }}" class="h-[80dvh] min-h-96 w-full rounded-md border border-line bg-surface"></iframe>
        </main>
    </div>
</body>
</html>
