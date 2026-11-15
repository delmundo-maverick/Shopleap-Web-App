@props(['code', 'label' => 'View proof'])
<a href="{{ route('logistics.delivery-proofs.show', ['order' => $code]) }}" data-proof-open class="inline-flex items-center gap-1 rounded border border-line bg-white px-2 py-1 text-xs font-semibold text-primary">
    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7h4l2-3h6l2 3h4v13H3z"/><circle cx="12" cy="13" r="4"/></svg>
    {{ $label }}
</a>
