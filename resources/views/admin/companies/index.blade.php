<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Companies | ShopLeap Admin</title>
    @vite(['resources/css/app.css', 'resources/js/pages/company/hubs.js', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ice-blue text-charcoal">
    <div class="flex min-h-screen">
        <x-navbars.admin active="companies" />
        <div class="flex min-w-0 flex-1 flex-col">
            <x-seller.page-header title="Companies" description="Review logistics company accounts and manage access" class="bg-surface! shadow-none! backdrop-blur-none!" />
            <main class="flex-1 space-y-5 p-4 sm:p-6">
                @if (session('status'))<p role="status" class="rounded-sm border border-line bg-surface p-4 text-success">{{ session('status') }}</p>@endif
                @if ($errors->any())<p role="alert" class="rounded-sm border border-danger bg-surface p-4 text-danger">{{ $errors->first() }}</p>@endif
                <form method="GET" class="flex flex-wrap items-end gap-3 rounded-sm border border-line bg-surface p-4">
                    <label class="min-w-0 flex-1 text-sm">Company or account email<input type="search" name="search" value="{{ request('search') }}" maxlength="100" class="mt-1 block w-full rounded-sm border border-line p-3"></label>
                    <label class="text-sm">Status<select name="status" class="mt-1 block rounded-sm border border-line bg-surface p-3"><option value="">All statuses</option>@foreach (['pending', 'approved', 'rejected', 'suspended'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                    <button class="btn btn-primary">Filter</button>
                </form>
                <x-seller.data-table label="Logistics company accounts" responsive="scroll" class="bg-surface! shadow-none! backdrop-blur-none!">
                    <x-slot:head><tr>@foreach (['Company', 'Account holder', 'Status', 'Hubs', 'Actions'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></x-slot:head>
                    @forelse ($companies as $company)
                        <tr>
                            <td class="p-3"><div class="flex items-center gap-3">@if ($company->logo_path)<img src="{{ Storage::disk('public')->url($company->logo_path) }}" alt="" class="size-10 rounded-sm object-contain">@endif<strong>{{ $company->name }}</strong></div><p class="mt-2 text-xs">{{ $company->address }}</p><p class="text-xs">{{ $company->contact_email }} ? {{ $company->contact_phone }}</p></td>
                            <td class="p-3">@foreach ($company->users as $account)<p>{{ $account->name }}</p><p class="text-xs">{{ $account->email }} ? {{ $account->phone }}</p><x-status-label :status="$account->account_status" domain="company" class="rounded-sm!" /><span class="block text-xs">Logistics Company</span>@if ($account->email_verified_at)<span class="block text-xs text-success">Email verified</span>@endif
                            @endforeach</td>
                            <td class="p-3"><x-status-label :status="$company->status" domain="company" class="rounded-sm!" />@if ($company->rejection_reason)<p class="mt-2 max-w-xs text-xs">{{ $company->rejection_reason }}</p>@endif</td>
                            <td class="p-3"><a href="{{ route('admin.registrations.logistics.index', ['company' => $company->public_code]) }}" class="font-semibold text-primary">{{ $company->hubs_count }} hubs</a></td>
                            <td class="p-3">
                                @if ($company->status === 'pending')
                                    <form method="POST" action="{{ route('admin.companies.approve', $company) }}" data-confirm="Approve this company and allow it to create hubs?">@csrf<button class="btn btn-primary">Approve</button></form>
                                    <details class="mt-3"><summary class="cursor-pointer text-danger">Reject application</summary><form method="POST" action="{{ route('admin.companies.reject', $company) }}" class="mt-3 space-y-2">@csrf<label class="block text-sm">Rejection reason<textarea name="rejection_reason" required maxlength="2000" class="mt-1 block w-full rounded-sm border border-line p-2">{{ old('rejection_reason') }}</textarea></label><button class="btn btn-outline text-danger">Reject and email reason</button></form></details>
                                @elseif (in_array($company->status, ['approved', 'suspended'], true))
                                    <form method="POST" action="{{ route('admin.companies.status', $company) }}" data-confirm="{{ $company->status === 'approved' ? 'Suspend this company account?' : 'Reactivate this company account?' }}">@csrf<input type="hidden" name="status" value="{{ $company->status === 'approved' ? 'suspended' : 'approved' }}"><button class="btn btn-outline">{{ $company->status === 'approved' ? 'Suspend' : 'Reactivate' }}</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center">No company accounts found.</td></tr>
                    @endforelse
                </x-seller.data-table>
                {{ $companies->links() }}
                <x-money.action-modal title="Confirm company action" />
            </main>
        </div>
    </div>
</body>
</html>
