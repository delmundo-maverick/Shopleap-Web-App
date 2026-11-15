@php
    $peso = fn($value) => \App\Support\LedgerPresentation::peso($value);
    $date = fn($value) => $value ? \App\Support\LedgerPresentation::dateTime($value) : '—';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/' . $role . '/money.js'])
    <title>{{ $title }} | Shopleap</title>
</head>
<body class="min-h-screen bg-ice-blue text-charcoal" x-data="{}">
<div class="flex min-h-screen">
    <x-dynamic-component :component="'navbars.' . $role" />
    <div class="flex min-w-0 flex-1 flex-col">
        <x-seller.page-header :title="$title" description="Cash handover and remittance records" class="bg-surface! bg-none! shadow-none! backdrop-blur-none!" />
        <main class="space-y-5 p-4 sm:p-6">
            @if (session('status'))<p role="status" class="rounded-sm border border-line bg-surface p-3 text-sm">{{ session('status') }}</p>@endif
            @if (session('money_warning'))<p role="alert" class="rounded-sm border border-warning bg-surface p-3 text-sm text-warning">{{ session('money_warning') }}</p>@endif
            @if ($errors->any())<div role="alert" class="rounded-sm border border-danger bg-surface p-3 text-sm text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (($role === 'logistics' ? ['cash_held' => 'Cash held', 'cod_to_remit' => 'COD to remit'] : ['cod_held' => 'COD held', 'cod_receivable' => 'COD receivable']) as $key => $label)
                    <x-seller.stat-card :label="$label" :value="$balances[$key]" :display-value="$peso($balances[$key])" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!" />
                @endforeach
            </div>
            @if ($overdueHubs->isNotEmpty())
                <section class="rounded-sm border border-danger bg-surface p-4 text-sm text-danger"><h2 class="font-semibold">Overdue COD · more than {{ $dueDays }} days</h2>@foreach ($overdueHubs as $overdue)<p>{{ $overdue->business_name }} · {{ $overdue->orders }} orders · {{ $peso($overdue->amount) }}</p>@endforeach</section>
            @endif
            @include('money.movement-forms')
            @if ($rows)
                <section class="space-y-3">
                    <div class="flex flex-wrap items-end justify-between gap-3"><h2 class="font-semibold">{{ $role === 'admin' ? 'Remittance queue' : 'Transaction history' }}</h2>
                    </div>
                    <x-money.history-filters :filters="$historyFilters" :statuses="$statusOptions" />
                    <x-seller.data-table label="Cash and remittance history" responsive="scroll" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!">
                        <x-slot:head><tr>@foreach (['Reference / party', 'Amount', 'Status / timeline', 'Details', 'Actions'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></x-slot:head>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="p-3"><p class="font-semibold">{{ \App\Support\LedgerPresentation::reference($row->reference_no) }}</p><p class="text-xs">{{ \App\Support\LedgerPresentation::text($row->business_name ?? trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''))) }}</p></td>
                                <td class="whitespace-nowrap p-3 tabular-nums">{{ $peso($row->total_amount ?? $row->amount_received) }}
                                    @if ($section === 'cash')<p class="text-xs">Expected {{ $peso($row->amount_expected) }}</p><p class="text-xs {{ $row->status === 'short' ? 'text-danger' : '' }}">Outstanding shortage {{ $peso((float) $row->short_amount - (float) $row->settled_amount) }}</p>@if ((float) $row->settled_amount > 0)<p class="text-xs">Additional cash received {{ $peso($row->settled_amount) }}</p>@endif @endif
                                </td>
                                <td class="p-3"><x-status-label :status="$row->status" domain="ledger" class="rounded-sm! border border-line" /><div class="mt-2 space-y-1 text-xs">@foreach (['submitted_at' => 'Submitted', 'confirmed_at' => 'Confirmed', 'settled_at' => 'Settled', 'verified_at' => 'Verified', 'rejected_at' => 'Rejected'] as $field => $label)@if (!empty($row->$field))<p>{{ $label }} · {{ $date($row->$field) }}</p>@endif @endforeach</div></td>
                                <td class="p-3 text-xs">
                                    @if (!empty($row->notes))<p>{{ \App\Support\LedgerPresentation::text($row->notes) }}</p>@endif @if (!empty($row->settlement_note))<p>{{ \App\Support\LedgerPresentation::text($row->settlement_note) }}</p>@endif @if (!empty($row->rejection_reason))<p class="text-danger">{{ \App\Support\LedgerPresentation::text($row->rejection_reason) }}</p>@endif
                                    @if ($section === 'remittances')
                                        <details><summary class="cursor-pointer font-semibold">Orders</summary>@foreach ($recordOrders[$row->id] ?? [] as $parcel)<p>{{ $parcel->order_number }} · {{ $peso($parcel->amount) }}</p>@endforeach</details>
                                        @if ($row->proof_path)<details class="mt-2"><summary class="cursor-pointer font-semibold text-primary">Proof image</summary><a target="_blank" rel="noopener" href="{{ route($role . '.money.proof', $row->reference_no) }}"><img loading="lazy" src="{{ route($role . '.money.proof', $row->reference_no) }}" alt="Proof for {{ \App\Support\LedgerPresentation::reference($row->reference_no) }}" class="mt-2 max-h-48 max-w-64 rounded-sm border border-line object-contain"></a></details>@endif
                                    @endif
                                </td>
                                <td class="p-3"><div class="flex min-w-40 flex-col items-start gap-2">
                                    <a class="btn btn-outline rounded-sm!" href="{{ route($role === 'logistics' ? 'logistics.receipts.show' : 'money.receipts.show', ['type' => $section === 'cash' ? 'cash-handovers' : 'remittances', 'reference' => $row->reference_no]) }}">View receipt</a>
                                    @if ($role === 'logistics' && $section === 'cash' && $row->status === 'short')
                                        <form method="POST" action="{{ route('logistics.movements.cash.settle', $row->reference_no) }}" data-money-form data-confirm="Confirm receipt of the remaining shortage cash?" class="space-y-2">@csrf<label class="block text-xs">Settlement note<input name="notes" required minlength="3" maxlength="1000" class="mt-1 w-full rounded-sm border border-line p-2"></label><button class="btn btn-primary rounded-sm!">Mark shortage settled</button></form>
                                    @elseif ($role === 'admin' && $section === 'remittances' && $row->status === 'submitted')
                                        <form method="POST" action="{{ route('admin.movements.remittances.decide', ['reference' => $row->reference_no, 'decision' => 'verify']) }}" data-money-form data-confirm="Verify this remittance and transfer its COD to the platform?" data-cash-held="{{ $hubCash[$row->hub_id] ?? 0 }}" data-payment="{{ $row->total_amount }}">@csrf<button class="btn btn-primary rounded-sm!">Verify</button><p data-negative-warning hidden class="mt-2 text-xs text-warning">This remittance will make the hub cash held negative.</p></form>
                                        <form method="POST" action="{{ route('admin.movements.remittances.decide', ['reference' => $row->reference_no, 'decision' => 'reject']) }}" data-money-form data-confirm="Reject this remittance and release its orders?" class="space-y-2">@csrf<label class="block text-xs">Rejection reason<input name="reason" required minlength="3" maxlength="1000" class="mt-1 w-full rounded-sm border border-line p-2"></label><button class="btn btn-outline rounded-sm!">Reject</button></form>
                                    @else<span class="text-xs">—</span>@endif
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-6 text-center">No matching transactions.</td></tr>
                        @endforelse
                    </x-seller.data-table>
                    {{ $rows->links() }}
                </section>
            @endif
        </main>
    </div>
</div>
<x-money.action-modal />
</body>
</html>
