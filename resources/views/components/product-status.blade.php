@props(['status'])
@php
    [$color, $tooltip] = match ($status) {
        'active' => ['bg-success/10 text-success', 'Visible to buyers and available to purchase.'],
        'archived' => ['bg-charcoal/10 text-charcoal', 'Hidden from buyers. Activate when ready.'],
        'pending_review' => ['bg-primary/10 text-primary', 'Waiting for review. Hidden from buyers.'],
        'rejected' => ['bg-danger/10 text-danger', 'Changes are required. Edit and resubmit for review.'],
        default => ['bg-warning/10 text-warning', 'Unfinished listing. Hidden from buyers until published.'],
    };
@endphp
<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', $color]) }} title="{{ $tooltip }}" tabindex="0">{{ \App\Models\Product::STATUS_LABELS[$status] ?? ucfirst($status) }}</span>
