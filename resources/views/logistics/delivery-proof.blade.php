<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer"><title>Proof of delivery | {{ $order->order_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/pages/logistics/delivery-proofs.js'])
</head>
<body class="bg-white text-charcoal">
    <x-seller.page-header title="Proof of delivery" :description="'Order #'.$order->order_number" class="static rounded border-line bg-white shadow-none" />
    <main class="space-y-4 p-4 sm:p-6">
        <x-status-label :status="$order->status" />
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-charcoal/60">Order number</dt><dd>{{ $order->order_number }}</dd></div>
            <div><dt class="text-charcoal/60">Rider</dt><dd>{{ $proof?->courier?->full_name ?? $order->earningCourier?->full_name ?? $order->deliveryRider?->full_name ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-charcoal/60">Delivered ({{ config('app.timezone') }})</dt><dd>{{ $order->delivered_at?->format('M d, Y H:i:s') ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-charcoal/60">Photo captured ({{ config('app.timezone') }})</dt><dd>{{ $proof?->captured_at?->setTimezone(config('app.timezone'))->format('M d, Y H:i:s') ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-charcoal/60">Barangay</dt><dd>{{ $order->ship_to['barangay'] ?: 'Not recorded' }}</dd></div>
        </dl>
        @if ($proof && $proof->latitude !== null && $proof->longitude !== null)
            <a class="text-sm font-semibold text-primary underline" href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($proof->latitude.','.$proof->longitude) }}" target="_blank" rel="noopener noreferrer">Open location in map</a>
        @endif
        @if (!$proof)
            <p>No proof (before feature)</p>
        @elseif ($proof->expired_at)
            <p>Proof expired</p>
        @else
            <p data-proof-image-error hidden>Image not available</p>
            <img data-proof-image src="{{ route(request()->user()->role === 'super_admin' ? 'admin.delivery-proofs.image' : 'logistics.delivery-proofs.image', ['order' => $order->order_number]) }}" alt="Delivery proof for order {{ $order->order_number }}" class="max-h-[70dvh] w-full rounded border border-line object-contain">
        @endif
    </main>
</body>
</html>
