@props(['status', 'domain' => 'order'])
@php
    $label = match ($domain) {
        'ledger' => \App\Support\LedgerPresentation::status($status),
        'bag' => \App\Models\ShipmentBag::STATUS_LABELS[$status] ?? str($status)->replace('_', ' ')->title(),
        'application' => str($status)->replace('_', ' ')->title(),
        default => \App\Models\Order::STATUS_LABELS[$status] ?? str($status)->replace('_', ' ')->title(),
    };
    $tone = $domain === 'company' ? match ($status) {
        'approved', 'active', 'covered' => 'bg-surface text-success',
        'rejected', 'suspended', 'deactivated', 'missing' => 'bg-surface text-danger',
        'pending', 'uncovered', 'no_rider', 'late' => 'bg-surface text-warning',
        default => 'bg-surface text-charcoal',
    } : ($domain === 'ledger' ? match (strtolower($status)) {
        'posted', 'completed', 'paid', 'confirmed', 'verified', 'settled' => 'bg-surface text-success',
        'failed', 'rejected', 'short' => 'bg-surface text-danger',
        'pending', 'processing', 'submitted' => 'bg-surface text-warning',
        'reversed', 'reversal' => 'bg-surface text-charcoal',
        default => 'bg-surface text-primary',
    } : match ($status) {
        'DELIVERED', 'COMPLETED', 'received', 'approved' => 'bg-success/10 text-success',
        'DELIVERY_FAILED', 'CANCELLED', 'RETURNED', 'discrepancy', 'rejected' => 'bg-danger/10 text-danger',
        'READY_FOR_PICKUP', 'AWAITING_TRANSFER', 'open', 'pending' => 'bg-warning/10 text-warning',
        default => 'bg-primary/10 text-primary',
    });
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', $tone]) }}>{{ $label }}</span>
