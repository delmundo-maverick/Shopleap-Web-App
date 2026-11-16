<x-company.layout title="Managers" description="Manage access for your hub manager accounts" :scripts="['resources/js/pages/company/managers.js']">
    <form method="GET" class="grid items-end gap-3 rounded-sm border border-line bg-surface p-4 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto]">
        <label class="text-sm font-medium">Search managers<input name="search" type="search" maxlength="100" value="{{ request('search') }}" placeholder="Name or email" class="mt-1 block w-full rounded-sm border border-line p-3"></label>
        <label class="text-sm font-medium">Hub<select name="hub" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All hubs</option>@foreach ($hubs as $hub)<option value="{{ $hub->public_code }}" @selected(request('hub') === $hub->public_code)>{{ $hub->business_name }}</option>@endforeach</select></label>
        <label class="text-sm font-medium">Login status<select name="status" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="suspended" @selected(request('status') === 'suspended')>Suspended</option></select></label>
        <button class="btn btn-primary">Filter</button>
    </form>
    <p class="text-sm">Manager login suspension is independent of hub suspension. A suspended hub also blocks manager access until the hub is reactivated.</p>
    <x-seller.data-table label="Hub manager accounts" responsive="scroll" class="bg-surface! shadow-none! backdrop-blur-none!">
        <x-slot:head><tr>@foreach (['Manager', 'Email', 'Hub', 'Login status', 'Last login', 'Invite status', 'Actions'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></x-slot:head>
        @forelse ($managers as $hub)
            @php($manager = $hub->user)
            <tr>
                <td class="p-3"><strong>{{ $manager->name }}</strong><p class="mt-1 text-xs">{{ $manager->phone }}</p></td>
                <td class="p-3">{{ $manager->email }}</td>
                <td class="p-3">{{ $hub->business_name }}@if ($manager->account_status !== 'active')<span class="mt-1 block text-xs text-danger">Hub {{ $manager->account_status }}</span>@endif</td>
                <td class="p-3"><x-status-label :status="$manager->logistics_login_suspended_at ? 'suspended' : 'active'" domain="company" class="rounded-sm!" /></td>
                <td class="p-3 text-xs">{{ $manager->last_login_at?->timezone('Asia/Manila')->format('M d, Y g:i A') ?? 'Never' }}@if ($manager->last_login_at)<span class="block">Philippine time</span>@endif</td>
                <td class="p-3"><x-status-label :status="$hub->manager_password_set_at ? 'active' : 'invited'" domain="company" class="rounded-sm!" />@if (!$hub->manager_password_set_at && $hub->manager_invite_expires_at?->isPast())<span class="mt-1 block text-xs text-warning">Invite expired</span>@endif</td>
                <td class="p-3"><div class="flex min-w-40 flex-wrap gap-x-3 gap-y-2 text-xs font-medium">
                    <button type="button" data-manager-edit data-action="{{ route('company.managers.update', $hub->public_code) }}" data-name="{{ $manager->name }}" data-phone="{{ $manager->phone }}" class="text-primary">Edit name and phone</button>
                    @if ($manager->logistics_login_suspended_at)
                        <form method="POST" action="{{ route('company.managers.reactivate', $hub->public_code) }}" data-confirm="Reactivate the login for {{ $manager->name }}? The hub status will stay as it is.">@csrf<button class="text-primary">Reactivate login</button></form>
                    @else
                        <form method="POST" action="{{ route('company.managers.suspend', $hub->public_code) }}" data-confirm="Suspend the login for {{ $manager->name }} and end their sessions? Hub operations and hub status will stay as they are.">@csrf<button class="text-danger">Suspend login</button></form>
                    @endif
                    @if ($hub->manager_password_set_at)
                        <form method="POST" action="{{ route('company.managers.reset-password', $hub->public_code) }}" data-confirm="Email a password reset link to {{ $manager->name }} at {{ $manager->email }}?">@csrf<button class="text-primary">Send password reset</button></form>
                    @else
                        <form method="POST" action="{{ route('company.managers.invite', $hub->public_code) }}" data-confirm="Resend the invitation to {{ $manager->email }}? The previous invitation link will expire.">@csrf<button class="text-primary">Resend invite</button></form>
                    @endif
                    <form method="POST" action="{{ route('company.managers.logout', $hub->public_code) }}" data-confirm="End all active sessions for {{ $manager->name }}? They can sign in again with their password if their login is active.">@csrf<button class="text-danger">Force logout</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="7" class="p-6 text-center">No managers match these filters.</td></tr>
        @endforelse
    </x-seller.data-table>
    {{ $managers->links() }}
    <x-money.action-modal title="Confirm manager action">
        <form method="POST" data-manager-edit-form hidden class="mt-4 space-y-4">
            @csrf @method('PUT')
            <label class="block text-sm font-medium">Manager name<input name="name" required maxlength="255" class="mt-1 block w-full rounded-sm border border-line p-3" autocomplete="name"></label>
            <label class="block text-sm font-medium">Phone<input name="phone" type="tel" required maxlength="20" class="mt-1 block w-full rounded-sm border border-line p-3" autocomplete="tel"></label>
        </form>
    </x-money.action-modal>
</x-company.layout>
