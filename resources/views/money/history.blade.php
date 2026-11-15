<section class="space-y-4" aria-label="Financial ledger">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($moneyCards as $key => $label)
            <x-seller.stat-card :label="$label" :value="$moneyBalances[$key] ?? 0" :display-value="$moneyBalances[$key] === null ? 'Unknown' : '₱' . number_format((float) $moneyBalances[$key], 2)" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!" />
        @endforeach
    </div>
    <p class="text-xs text-charcoal">Balances reflect posted transactions. Rider earnings include deliveries with a recorded pay amount.</p>
    @if ($moneyOwnerType === 'seller')
        <p class="text-xs text-charcoal">Pending becomes available on receipt confirmation. Late returns can make available funds negative; the next earnings offset that shortfall.</p>
    @endif
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-sm border border-line bg-surface p-4">
        <div><label for="money-from" class="mb-1 block text-xs font-semibold">Ledger from</label><input id="money-from" name="money_from" type="date" value="{{ $moneyFilters['money_from'] }}" class="rounded-sm border border-line px-3 py-2 text-sm"></div>
        <div><label for="money-to" class="mb-1 block text-xs font-semibold">Ledger to</label><input id="money-to" name="money_to" type="date" value="{{ $moneyFilters['money_to'] }}" class="rounded-sm border border-line px-3 py-2 text-sm"></div>
        <div><label for="money-status" class="mb-1 block text-xs font-semibold">Status</label><select id="money-status" name="money_status" class="rounded-sm border border-line bg-surface px-3 py-2 text-sm">@foreach (['all' => 'All statuses', 'posted' => 'Completed', 'reversed' => 'Reversed'] as $value => $label)<option value="{{ $value }}" @selected(($moneyFilters['money_status'] ?? 'all') === $value)>{{ $label }}</option>@endforeach</select></div>
        <button class="btn btn-primary rounded-sm!">Filter history</button>
        <a class="btn btn-outline rounded-sm!" href="{{ route($moneyRoute, array_merge($moneyFilters, ['export' => 'csv'])) }}">Export ledger CSV</a>
        @foreach ($errors->all() as $error)<p class="w-full text-sm text-danger">{{ $error }}</p>@endforeach
    </form>
    <p class="text-xs text-charcoal">In/out means an increase/decrease in the named account. Running balances are per account and include activity before the selected dates. Custody, receivables, and earnings are separate accounts; do not add them together as cash.</p>
    <x-seller.data-table label="Ledger history" responsive="scroll" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!">
        <x-slot:head><tr>
            @foreach (['Reference / date', 'Description / order', 'Account', 'In', 'Out', 'Running balance', 'Status'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach
            @if ($moneyOwnerType === 'seller')<th class="p-3">Order breakdown</th>@endif
        </tr></x-slot:head>
        @forelse ($moneyRows as $row)
            <tr>
                <td class="p-3"><span class="block break-all text-xs">{{ \App\Support\LedgerPresentation::reference($row->reference_no) }}</span><time class="text-xs">{{ \App\Support\LedgerPresentation::dateTime($row->posted_at) }}</time></td>
                <td class="p-3">{{ \App\Support\LedgerPresentation::description($row->description) }}<span class="block text-xs">{{ $row->order_number ?? '—' }}</span></td>
                <td class="p-3">{{ str($row->account_type)->replace('_', ' ')->title() }}<span class="block text-xs">{{ $row->owner_type }}@if ($moneyOwnerType === 'platform') #{{ $row->owner_id }}@endif · {{ $row->direction }}</span></td>
                <td class="whitespace-nowrap p-3 tabular-nums">₱{{ number_format(max(0, (float) $row->movement), 2) }}</td>
                <td class="whitespace-nowrap p-3 tabular-nums">₱{{ number_format(max(0, -(float) $row->movement), 2) }}</td>
                <td class="whitespace-nowrap p-3 tabular-nums">₱{{ number_format((float) $row->running_balance, 2) }}</td>
                <td class="p-3"><x-status-label :status="$row->status" domain="ledger" class="rounded-sm! bg-surface! border border-line" /></td>
                @if ($moneyOwnerType === 'seller')
                    <td class="p-3 text-xs">Items {{ $row->items_subtotal === null ? 'not recorded' : '₱' . number_format((float) $row->items_subtotal, 2) }}<br>Voucher −₱{{ number_format((float) $row->voucher_discount, 2) }}<br>Commission −₱{{ number_format((float) $row->commission_amount, 2) }}<br>Net ₱{{ number_format((float) $row->total_amount - (float) $row->commission_amount, 2) }}</td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $moneyOwnerType === 'seller' ? 8 : 7 }}" class="p-6 text-center">No transactions found for the selected dates.</td></tr>
        @endforelse
    </x-seller.data-table>
    {{ $moneyRows->links() }}
    @if ($reconciliation)
        <section class="space-y-3 rounded-sm border border-line bg-surface p-4" aria-label="Reconciliation">
            <h2 class="font-semibold">Reconciliation · all posted activity</h2>
            <p class="text-sm">Debits ₱{{ number_format((float) $reconciliation['debits'], 2) }} · Credits ₱{{ number_format((float) $reconciliation['credits'], 2) }} · {{ $reconciliation['balanced'] ? 'Balanced' : 'Mismatch' }}</p>
            <p class="text-sm">Unbalanced transactions: {{ $reconciliation['unbalanced_transactions'] }}. Flagged orders: {{ $reconciliation['flags']->total() }}. Delivered orders outside the ledger: {{ $reconciliation['unposted'] }}.</p>
            <p class="text-xs">Each posted delivery must satisfy cash = seller net + commission + shipping, match its stored amounts, and have a recorded rider pay snapshot. Unposted historical orders are excluded from ledger balances.</p>
            <x-seller.data-table label="Flagged orders" responsive="scroll" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!">
                <x-slot:head><tr>@foreach (['Order', 'Cash', 'Seller net', 'Commission', 'Shipping', 'Rider pay'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></x-slot:head>
                @forelse ($reconciliation['flags'] as $flag)
                    <tr><td class="p-3">{{ $flag->order_number }}</td>@foreach (['cash', 'seller_net', 'commission', 'shipping', 'rider_pay'] as $field)<td class="p-3">{{ $flag->$field === null ? 'Unknown snapshot' : '₱' . number_format((float) $flag->$field, 2) }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="6" class="p-4">No discrepancies among posted orders.</td></tr>
                @endforelse
            </x-seller.data-table>
            {{ $reconciliation['flags']->links() }}
            <h3 class="font-semibold">Unremitted COD by hub</h3>
            <x-seller.data-table label="Unremitted COD by hub" responsive="scroll" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!">
                <x-slot:head><tr><th class="p-3">Hub</th><th class="p-3">COD to remit</th></tr></x-slot:head>
                @forelse ($reconciliation['unremitted_hubs'] as $hub)<tr><td class="p-3">{{ $hub->business_name }}</td><td class="p-3">₱{{ number_format((float) $hub->amount, 2) }}</td></tr>@empty<tr><td colspan="2" class="p-4">No unremitted COD.</td></tr>@endforelse
            </x-seller.data-table>
        </section>
    @endif
</section>
