@php
    $peso = fn ($value) => \App\Support\LedgerPresentation::peso($value);
    $date = fn ($value) => \App\Support\LedgerPresentation::dateTime($value);
    $input = 'mt-1 w-full rounded-sm border border-line bg-surface p-2 text-sm';
    $canRequest = $role === 'seller' && !$hasActive && $methods->isNotEmpty() && (float) $balances['available'] >= (float) $minimumPayout;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/' . $role . '/money.js'])
    <title>{{ $title }} | Shopleap</title>
</head>
<body class="min-h-screen bg-ice-blue text-charcoal" x-data="{}">
<div class="flex min-h-screen">
    <x-dynamic-component :component="'navbars.' . $role" />
    <div class="flex min-w-0 flex-1 flex-col">
        <x-seller.page-header :title="$title" description="Payout records and payment history" class="bg-surface! bg-none! shadow-none! backdrop-blur-none!" />
        <main class="space-y-5 p-4 sm:p-6">
            @if ($role === 'admin' && !$refunds)<p class="rounded-sm border border-line p-3 text-sm">This page covers historical platform-managed seller funds only. New seller payments are handled directly by logistics. <a class="text-primary underline" href="{{ route('admin.settlements.index') }}">Open settlement oversight</a>.</p>@endif
            @if (session('status'))<p role="status" class="rounded-sm border border-line bg-surface p-3 text-sm">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="rounded-sm border border-danger bg-surface p-3 text-sm text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach (($role === 'seller' ? ['available' => 'Available balance', 'payout_locked' => 'Locked for payout', 'withdrawn' => 'Withdrawn'] : ($role === 'logistics' ? ['cash_held' => 'Hub cash held', 'rider_pay_owed' => 'Rider pay owed'] : ['cod_held' => 'Platform COD held'])) as $key => $label)
                    <x-seller.stat-card :label="$label" :value="$balances[$key]" :display-value="$peso($balances[$key])" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!" />
                @endforeach
            </div>
            @if ($role === 'seller')
                <div class="grid gap-5 xl:grid-cols-2">
                    <section class="space-y-4 rounded-sm border border-line bg-surface p-4">
                        <h2 class="font-semibold">Request a platform-managed payout</h2>
                        <p class="text-sm">New logistics-managed orders are paid directly by the hub after completion and cash receipt. <a class="text-primary underline" href="{{ route('seller.settlements.index') }}">View logistics payments</a>. This form covers legacy platform-managed earnings.</p>
                        <p class="text-sm">Minimum payout {{ $peso($minimumPayout) }}. Choose an account and amount.</p>
                        @if ((float) $balances['available'] <= 0)<p role="status" class="text-sm text-warning">You have no available balance to withdraw. Payout requests are disabled until your available balance is positive.</p>
                        @elseif ((float) $balances['available'] < (float) $minimumPayout)<p role="status" class="text-sm text-warning">Your available balance is below the minimum payout of {{ $peso($minimumPayout) }}.</p>@endif
                        @if ($hasActive)<p role="status" class="text-sm text-warning">You already have a Pending or Processing payout. Wait for it to be completed before requesting another.</p>@endif
                        @if ($methods->isEmpty())<p class="text-sm">Add a payout method to request a payout.</p>@endif
                        <form method="POST" action="{{ route('seller.payouts.store') }}" data-money-form data-confirm="Request this payout and lock the amount from your available balance?" class="space-y-3">
                            @csrf
                            <input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                            <fieldset @disabled(!$canRequest) class="space-y-3">
                                <label class="block text-sm">Amount (PHP)<input name="amount" required type="number" min="{{ $minimumPayout }}" max="{{ max(0, (float) $balances['available']) }}" step="0.01" value="{{ old('amount') }}" class="{{ $input }}"></label>
                                <label class="block text-sm">Payout method<select name="method_id" required class="{{ $input }}">@foreach ($methods as $method)<option value="{{ $method->id }}" @selected(old('method_id', $methods->first()?->id) == $method->id)>{{ $method->type === 'bank' ? 'Bank' : $method->type }} · {{ $method->account_name }} · •••• {{ $method->account_number_masked }}{{ $method->is_default ? ' · Default' : '' }}</option>@endforeach</select></label>
                                <button class="btn btn-primary rounded-sm!" @disabled(!$canRequest)>Request payout</button>
                            </fieldset>
                        </form>
                    </section>
                    <section class="space-y-4 rounded-sm border border-line bg-surface p-4">
                        <h2 class="font-semibold">Payout methods</h2>
                        <p class="text-xs">Only the last four account digits are retained. Arrange payment details with Shopleap through your verified payment channel.</p>
                        @foreach ($methods as $method)
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-3 text-sm">
                                <p>{{ $method->type === 'bank' ? 'Bank' : $method->type }} · {{ $method->account_name }} · <span class="font-mono">•••• {{ $method->account_number_masked }}</span>@if ($method->is_default)<span class="ml-2 text-xs text-primary">Default</span>@endif</p>
                                <form method="POST" action="{{ route('seller.payouts.methods.destroy', $method->id) }}" data-money-form data-confirm="Remove this payout method? Existing payout records retain their account snapshot.">@csrf @method('DELETE')<button class="btn btn-outline rounded-sm!" aria-label="Remove {{ $method->type }} ending {{ $method->account_number_masked }}">Remove</button></form>
                            </div>
                        @endforeach
                        <form method="POST" action="{{ route('seller.payouts.methods.store') }}" data-money-form class="grid gap-3 sm:grid-cols-2">
                            @csrf
                            <label class="block text-sm">Type<select name="type" required class="{{ $input }}"><option value="GCash">GCash</option><option value="Maya">Maya</option><option value="bank">Bank</option></select></label>
                            <label class="block text-sm">Account name<input name="account_name" required maxlength="150" value="{{ old('account_name') }}" class="{{ $input }}"></label>
                            <label class="block text-sm">Last 4 account digits<input name="account_number_masked" required minlength="4" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" autocomplete="off" class="{{ $input }}"></label>
                            <label class="flex items-center gap-2 text-sm"><input name="is_default" type="checkbox" value="1">Set as default</label>
                            <div><button class="btn btn-primary rounded-sm!">Add method</button></div>
                        </form>
                    </section>
                </div>
            @elseif ($role === 'logistics')
                <section class="space-y-3">
                    <h2 class="font-semibold">Accrued earnings by rider</h2>
                    @forelse ($riders as $rider)
                        <details class="rounded-sm border border-line bg-surface p-4">
                            <summary class="cursor-pointer font-semibold">{{ $rider->first_name }} {{ $rider->last_name }} · {{ $peso($rider->amount) }}</summary>
                            <form method="POST" action="{{ route('logistics.payouts.store') }}" data-money-form data-confirm="Confirm that the rider has received payment for the selected earnings?" class="mt-4 space-y-3">
                                @csrf
                                <input type="hidden" name="rider_id" value="{{ $rider->id }}">
                                <input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="all" value="1">Pay all unpaid parcels for this rider</label>
                                <x-seller.data-table label="Unpaid rider earnings" responsive="scroll" class="rounded-sm! bg-surface! bg-none! shadow-none!">
                                    <x-slot:head><tr><th class="p-3"><input type="checkbox" data-select-all aria-label="Select all parcels shown"></th><th class="p-3">Order</th><th class="p-3">Earnings</th><th class="p-3">Delivered</th></tr></x-slot:head>
                                    @foreach ($riderOrders[$rider->id] ?? [] as $parcel)<tr><td class="p-3"><input type="checkbox" name="orders[]" value="{{ $parcel->order_number }}" aria-label="Select {{ $parcel->order_number }}"></td><td class="p-3 font-mono">{{ $parcel->order_number }}</td><td class="p-3 tabular-nums">{{ $peso($parcel->amount) }}</td><td class="p-3 text-xs">{{ $date($parcel->delivered_at) }}</td></tr>@endforeach
                                </x-seller.data-table>
                                <label class="block text-sm">Notes (optional)<textarea name="notes" maxlength="1000" class="{{ $input }}"></textarea></label>
                                <button class="btn btn-primary rounded-sm!">Pay rider</button>
                            </form>
                        </details>
                    @empty<p class="rounded-sm border border-line bg-surface p-4 text-sm">No unpaid rider earnings.</p>@endforelse
                    {{ $riders->links() }}
                </section>
            @endif
            <section class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold">{{ $role === 'admin' ? ($refunds ? 'Refund queue and history' : 'Legacy platform payment queue and history') : 'Payout history' }}</h2>
                </div>
                <x-money.history-filters :filters="$historyFilters" :statuses="$statusOptions" />
                <x-seller.data-table label="Payout history" responsive="scroll" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!">
                    <x-slot:head><tr>@foreach (['Reference / party', 'Amount', 'Status / timeline', 'Payment details', 'Actions'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></x-slot:head>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="p-3"><p class="font-mono font-semibold">{{ \App\Support\LedgerPresentation::reference($row->reference_no) }}</p><p class="text-xs">{{ \App\Support\LedgerPresentation::text($row->party_name ?? trim($row->first_name . ' ' . $row->last_name)) }}</p>@if ($refunds)<p class="mt-1 font-mono text-xs">{{ $row->order_number }}</p>@endif</td>
                            <td class="whitespace-nowrap p-3 tabular-nums">{{ $peso($row->amount) }}</td>
                            <td class="p-3"><x-status-label :status="$row->status" domain="ledger" class="rounded-sm! border border-line" /><div class="mt-2 space-y-1 text-xs">@foreach (['requested_at' => 'Pending', 'processing_at' => 'Processing', 'paid_at' => 'Paid', 'rejected_at' => 'Rejected'] as $field => $label)@if (!empty($row->$field))<p>{{ $label }} · {{ $date($row->$field) }}</p>@endif @endforeach</div></td>
                            <td class="p-3 text-xs">
                                @if ($role === 'logistics')<details><summary class="cursor-pointer font-semibold">Parcels covered</summary>@foreach ($recordOrders[$row->id] ?? [] as $parcel)<p class="mt-1">{{ $parcel->order_number }} · {{ $peso($parcel->amount) }}</p>@endforeach</details>@if ($row->notes)<p class="mt-2">{{ \App\Support\LedgerPresentation::text($row->notes) }}</p>@endif
                                @else<p>{{ match ($row->method_type) { 'gcash' => 'GCash', 'maya' => 'Maya', 'bank' => 'Bank', default => $row->method_type } }} · {{ \App\Support\LedgerPresentation::text($row->account_name) }}</p><p class="mt-1 font-mono">•••• {{ substr(preg_replace('/\D/', '', $row->account_number_masked), -4) }}</p>@if (!empty($row->rejection_reason))<p class="mt-2 text-danger">{{ \App\Support\LedgerPresentation::text($row->rejection_reason) }}</p>@endif @endif
                            </td>
                            <td class="p-3">
                                <a class="btn btn-outline mb-2 rounded-sm!" href="{{ route($role === 'logistics' ? 'logistics.receipts.show' : ($role === 'admin' ? 'admin.receipts.show' : 'money.receipts.show'), ['type' => $refunds ? 'refunds' : ($role === 'logistics' ? 'rider-payouts' : 'seller-payouts'), 'reference' => $row->reference_no]) }}">View receipt</a>
                                @if ($role === 'admin' && $refunds && !empty($row->logistics_order_id))
                                    <a class="text-sm text-primary underline" href="{{ route('admin.settlements.index') }}">Logistics-funded refund · view oversight</a>
                                @elseif ($role === 'admin' && $refunds && $row->status === 'pending')
                                    <form method="POST" action="{{ route('admin.refunds.pay', $row->reference_no) }}" data-money-form data-confirm="Confirm the buyer has received this refund? This records the completed payment.">@csrf<button class="btn btn-primary whitespace-nowrap rounded-sm!">Mark refund paid</button></form>
                                @elseif ($role === 'admin' && !$refunds && in_array($row->status, ['pending', 'processing'], true))
                                    <div class="flex min-w-44 flex-col items-start gap-2">
                                        <form method="POST" action="{{ route('admin.payouts.decide', ['reference' => $row->reference_no, 'decision' => $row->status === 'pending' ? 'approve' : 'pay']) }}" data-money-form data-confirm="{{ $row->status === 'pending' ? 'Approve this payout for processing?' : 'Confirm the seller has received this payment?' }}">@csrf<button class="btn btn-primary rounded-sm!">{{ $row->status === 'pending' ? 'Approve' : 'Mark as paid' }}</button></form>
                                        <form method="POST" action="{{ route('admin.payouts.decide', ['reference' => $row->reference_no, 'decision' => 'reject']) }}" data-money-form data-confirm="Reject this payout and restore the seller available balance?" class="space-y-2"><label class="block text-xs">Rejection reason<input name="reason" required minlength="3" maxlength="1000" class="{{ $input }}"></label>@csrf<button class="btn btn-outline rounded-sm!">Reject</button></form>
                                    </div>
                                @else<span class="text-xs">—</span>@endif
                            </td>
                        </tr>
                    @empty<tr><td colspan="5" class="p-6 text-center">No matching payouts.</td></tr>@endforelse
                </x-seller.data-table>
                {{ $rows->links() }}
            </section>
        </main>
    </div>
</div>
<x-money.action-modal />
</body>
</html>
