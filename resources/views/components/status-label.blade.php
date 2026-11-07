@props(['status', 'domain' => 'order'])
@php
    $label = match ($domain) {
        'bag' => \App\Models\ShipmentBag::STATUS_LABELS[$status] ?? str($status)->replace('_', ' ')->title(),
        'application' => str($status)->replace('_', ' ')->title(),
        default => \App\Models\Order::STATUS_LABELS[$status] ?? str($status)->replace('_', ' ')->title(),
    };
    $tone = match ($status) {
        'DELIVERED', 'COMPLETED', 'received', 'approved' => 'bg-success/10 text-success',
        'DELIVERY_FAILED', 'CANCELLED', 'RETURNED', 'discrepancy', 'rejected' => 'bg-danger/10 text-danger',
        'READY_FOR_PICKUP', 'AWAITING_TRANSFER', 'open', 'pending' => 'bg-warning/10 text-warning',
        default => 'bg-primary/10 text-primary',
    };
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', $tone]) }}>{{ $label }}</span>
