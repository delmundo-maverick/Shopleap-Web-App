<x-company.layout :title="$type === 'delayed' ? 'Delayed parcels' : 'Inactive managers'" description="Current issues across your company's hubs">
    <a href="{{ route('company.dashboard') }}" class="text-sm text-primary">Back to dashboard</a>
    @if ($type === 'delayed')
        <p class="text-sm">Parcels at your hubs with no recorded movement for more than {{ $staleHours }} hours. Parcels currently in a transfer bag in transit are excluded.</p>
        <x-seller.data-table label="Delayed parcels" responsive="scroll" class="rounded-sm! bg-surface! shadow-none!">
            <x-slot:head><tr><th class="p-3">Parcel</th><th class="p-3">Holding hub</th><th class="p-3">Status</th><th class="p-3">Last updated</th></tr></x-slot:head>
            @forelse ($rows as $order)<tr><td class="p-3 font-semibold">{{ $order->order_number }}</td><td class="p-3">{{ $order->currentHub?->business_name ?? $order->logisticsProfile?->business_name }}</td><td class="p-3"><x-status-label :status="$order->status" class="rounded-sm! bg-surface!" /></td><td class="p-3">{{ $order->updated_at->timezone('Asia/Manila')->format('M d, Y g:i A') }} PHT</td></tr>
            @empty<tr><td colspan="4" class="p-6 text-center">No delayed parcels currently need attention.</td></tr>@endforelse
        </x-seller.data-table>
    @else
        <p class="text-sm">Managers whose last login was at least 14 days ago, including accounts created at least 14 days ago that never logged in.</p>
        <x-seller.data-table label="Inactive hub managers" responsive="scroll" class="rounded-sm! bg-surface! shadow-none!">
            <x-slot:head><tr><th class="p-3">Hub</th><th class="p-3">Manager</th><th class="p-3">Last login</th><th class="p-3">Account</th></tr></x-slot:head>
            @forelse ($rows as $hub)<tr><td class="p-3 font-semibold">{{ $hub->business_name }}</td><td class="p-3">{{ $hub->user->name }}</td><td class="p-3">{{ $hub->user->last_login_at ? $hub->user->last_login_at->timezone('Asia/Manila')->format('M d, Y g:i A').' PHT' : 'Never' }}</td><td class="p-3"><a href="{{ route('company.managers.index', ['hub' => $hub->public_code]) }}" class="text-primary">View manager</a></td></tr>
            @empty<tr><td colspan="4" class="p-6 text-center">All managers are recently active or have new accounts.</td></tr>@endforelse
        </x-seller.data-table>
    @endif
    {{ $rows->links() }}
</x-company.layout>
