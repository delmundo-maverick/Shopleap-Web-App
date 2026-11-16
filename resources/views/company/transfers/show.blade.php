<x-company.layout :title="'Manifest - '.$bag->code" description="Read-only transfer manifest and parcel receipt status">
    <a href="{{ route('company.transfers.index') }}" class="text-sm text-primary">Back to transfers</a>
    <section class="space-y-4 rounded-sm border border-line bg-surface p-4">
        <div class="flex flex-wrap items-center gap-3"><h2 class="text-lg font-semibold">{{ $bag->code }}</h2><x-status-label :status="$bag->status" domain="bag" class="rounded-sm! bg-surface!" />@if ($bag->status === \App\Models\ShipmentBag::STATUS_IN_TRANSIT && $bag->dispatched_at?->lt($lateBefore))<x-status-label status="late" domain="company" class="rounded-sm!" />@endif</div>
        <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-xs">Origin hub</dt><dd class="mt-1 font-semibold">{{ $bag->fromHub->business_name }}</dd><dd class="mt-1 text-xs">{{ $bag->fromHub->municipality }}, {{ $bag->fromHub->province }}</dd></div>
            <div><dt class="text-xs">Destination hub</dt><dd class="mt-1 font-semibold">{{ $bag->toHub->business_name }}</dd><dd class="mt-1 text-xs">{{ $bag->toHub->municipality }}, {{ $bag->toHub->province }}</dd></div>
            <div><dt class="text-xs">Dispatched</dt><dd class="mt-1">{{ $bag->dispatched_at?->timezone('Asia/Manila')->format('M d, Y g:i A') ?? 'Not dispatched' }}</dd>@if ($bag->dispatched_at)<dd class="mt-1 text-xs">Philippine time</dd>@endif</div>
            <div><dt class="text-xs">Received / closed</dt><dd class="mt-1">{{ $bag->received_at?->timezone('Asia/Manila')->format('M d, Y g:i A') ?? 'Not received' }}</dd>@if ($bag->received_at)<dd class="mt-1 text-xs">Philippine time</dd>@endif</div>
        </dl>
        <p class="text-sm">{{ number_format($bag->items_count) }} parcels in this manifest.</p>
        @if ($bag->status === \App\Models\ShipmentBag::STATUS_DISCREPANCY && $bag->unreceived_count)<p role="status" class="rounded-sm border border-danger p-3 text-sm text-danger">{{ $bag->unreceived_count }} parcel(s) were flagged missing on inbound and have not been received.</p>@endif
    </section>
    <x-seller.data-table label="Transfer manifest parcels" responsive="scroll" class="rounded-sm! bg-surface! shadow-none!">
        <x-slot:head><tr><th class="p-3">Parcel</th><th class="p-3">Delivery area</th><th class="p-3">Parcel status</th><th class="p-3">Bag receipt</th></tr></x-slot:head>
        @forelse ($items as $item)
            @php($missing = $bag->status === \App\Models\ShipmentBag::STATUS_DISCREPANCY && !$item->received_at)
            <tr class="{{ $missing ? 'bg-landing-peach' : '' }}"><td class="p-3 font-semibold">{{ $item->order?->order_number ?? 'Parcel unavailable' }}</td><td class="p-3">{{ $item->order?->ship_municipality }}@if ($item->order?->ship_province), {{ $item->order->ship_province }}@endif</td>
                <td class="p-3">@if ($item->order)<x-status-label :status="$item->order->status" class="rounded-sm! bg-surface!" />@else<span>Unavailable</span>@endif</td>
                <td class="p-3">@if ($missing)<x-status-label status="missing" domain="company" class="rounded-sm!" />@elseif ($item->received_at)<span class="block text-success">Received</span><span class="mt-1 block text-xs">{{ $item->received_at->timezone('Asia/Manila')->format('M d, Y g:i A') }} PHT</span>@else<span>Awaiting receipt</span>@endif</td>
            </tr>
        @empty<tr><td colspan="4" class="p-6 text-center">This bag has no parcels in its manifest.</td></tr>@endforelse
    </x-seller.data-table>
    {{ $items->links() }}
</x-company.layout>
