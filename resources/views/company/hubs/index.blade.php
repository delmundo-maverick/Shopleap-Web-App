<x-company.layout title="Hubs" description="Manage your company's hubs and managers" :scripts="['resources/js/pages/company/hubs.js']">
    <div class="flex justify-end"><a href="{{ route('company.hubs.create') }}" class="btn btn-primary">Create hub</a></div>
    <x-seller.data-table label="Company hubs" responsive="scroll" class="bg-surface! shadow-none! backdrop-blur-none!">
        <x-slot:head><tr>@foreach (['Hub', 'Municipality / Province', 'Manager', 'Status', 'Riders', 'Actions'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></x-slot:head>
        @forelse ($hubs as $hub)
            <tr>
                <td class="p-3 font-semibold">{{ $hub->business_name }}</td><td class="p-3">{{ $hub->municipality }}, {{ $hub->province }}</td>
                <td class="p-3">{{ $hub->user->name }}<span class="block text-xs">{{ $hub->user->email }}</span>@if (!$hub->manager_password_set_at)<span class="block text-xs text-warning">Invitation pending</span>@endif</td>
                <td class="p-3"><x-status-label :status="$hub->user->account_status" domain="company" class="rounded-sm!" /></td><td class="p-3">{{ $hub->riders_count }}</td>
                <td class="p-3"><div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('company.hubs.edit', $hub->public_code) }}" class="text-primary">Edit</a>
                    @if ($hub->user->account_status === 'active')
                        <form method="POST" action="{{ route('company.hubs.suspend', $hub->public_code) }}" data-confirm="Suspend this hub? Its manager will lose access. Hubs holding active parcels cannot be suspended.">@csrf<button class="text-danger">Suspend</button></form>
                    @else
                        <form method="POST" action="{{ route('company.hubs.reactivate', $hub->public_code) }}">@csrf<button class="text-primary">Reactivate</button></form>
                    @endif
                    @if (!$hub->manager_password_set_at)
                        <form method="POST" action="{{ route('company.hubs.invite', $hub->public_code) }}">@csrf<button class="text-primary">Resend invite</button></form>
                    @endif
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="p-6 text-center">No hubs yet. Create your first hub to get started.</td></tr>
        @endforelse
    </x-seller.data-table>
    {{ $hubs->links() }}
    <x-money.action-modal title="Confirm hub action" />
</x-company.layout>
