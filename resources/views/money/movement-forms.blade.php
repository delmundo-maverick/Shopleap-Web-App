@if ($role === 'logistics' && $section === 'cash')
    <section class="space-y-3">
        <h2 class="font-semibold">Cash awaiting handover</h2>
        <p class="text-xs">A short handover leaves the unpaid difference with the rider. Settle an open shortage by confirming receipt of its remaining cash before opening another handover.</p>
        <x-seller.data-table label="Rider cash balances" responsive="scroll" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!">
            <x-slot:head><tr><th class="p-3">Rider</th><th class="p-3">Cash on hand</th><th class="p-3">Orders and handover</th></tr></x-slot:head>
            @forelse ($riders as $rider)
                <tr><td class="p-3">{{ $rider->first_name }} {{ $rider->last_name }}</td><td class="whitespace-nowrap p-3 tabular-nums">{{ $peso($rider->amount) }}</td><td class="p-3">
                    <form method="POST" action="{{ route('logistics.movements.cash.store') }}" data-money-form data-confirm="Confirm the cash received from this rider?" class="space-y-3">
                        @csrf<input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="rider_id" value="{{ $rider->id }}">
                        <details><summary class="cursor-pointer text-sm font-semibold">View {{ ($riderOrders[$rider->id] ?? collect())->count() }} parcels</summary><div class="mt-2 max-h-64 space-y-2 overflow-y-auto">
                            @foreach ($riderOrders[$rider->id] ?? [] as $parcel)<p class="text-xs">{{ $parcel->order_number }} ? {{ $peso($parcel->amount) }}<span class="block">{{ $date($parcel->delivered_at) }}</span></p>@endforeach
                        </div></details>
                        <label class="block text-xs font-semibold">Amount received (PHP)<input name="amount" type="number" step="0.01" min="0" max="{{ $rider->amount }}" value="{{ $rider->amount }}" required data-expected="{{ $rider->amount }}" class="mt-1 w-full rounded-sm border border-line p-2 text-sm"></label>
                        <label class="block text-xs font-semibold">Note (required if received amount differs)<input name="notes" maxlength="1000" class="mt-1 w-full rounded-sm border border-line p-2 text-sm"></label>
                        <button class="btn btn-primary rounded-sm!">Confirm handover</button>
                    </form>
                </td></tr>
            @empty
                <tr><td colspan="3" class="p-6 text-center">No cash awaiting handover.</td></tr>
            @endforelse
        </x-seller.data-table>
        {{ $riders->links() }}
    </section>
@elseif ($role === 'logistics' && $section === 'remittances')
    <section class="space-y-3 rounded-sm border border-line bg-surface p-4">
        <h2 class="font-semibold">Submit remittance</h2><p class="text-xs">Select the orders covered by this remittance. Funds move after verification.</p>
        <form method="POST" enctype="multipart/form-data" action="{{ route('logistics.movements.remittances.store') }}" data-money-form data-confirm="Submit these orders for remittance verification?" class="space-y-4">
            @csrf<input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="all" value="1">Include all eligible orders across all pages</label>
            <x-seller.data-table label="Eligible remittance orders" responsive="scroll" class="rounded-sm! bg-surface! bg-none! shadow-none! backdrop-blur-none!">
                <x-slot:head><tr><th class="p-3"><input type="checkbox" data-select-all aria-label="Select all orders shown"></th><th class="p-3">Order</th><th class="p-3">Items after discounts</th><th class="p-3">Delivered</th></tr></x-slot:head>
                @forelse ($orders as $parcel)<tr class="{{ \Carbon\Carbon::parse($parcel->delivered_at)->lt(now()->subDays($dueDays)) ? 'text-danger' : '' }}"><td class="p-3"><input type="checkbox" name="orders[]" value="{{ $parcel->order_number }}" aria-label="Select {{ $parcel->order_number }}"></td><td class="p-3">{{ $parcel->order_number }}</td><td class="p-3">{{ $peso($parcel->amount) }}</td><td class="p-3">{{ $date($parcel->delivered_at) }}</td></tr>@empty<tr><td colspan="4" class="p-4">No eligible orders to remit.</td></tr>@endforelse
            </x-seller.data-table>
            {{ $orders->links() }}
            <label class="block text-sm">Proof image (optional)<input name="proof" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-sm border border-line p-2 text-sm"></label>
            <button class="btn btn-primary rounded-sm!" @disabled($orders->total() === 0)>Submit remittance</button>
        </form>
    </section>
@elseif ($role === 'admin' && $section === 'settings')
    <form method="POST" action="{{ route('admin.movements.settings') }}" data-money-form data-confirm="Save the remittance due period?" class="max-w-lg space-y-4 rounded-sm border border-line bg-surface p-4">@csrf
        <label class="block text-xs font-semibold">Remittance due after (days)<input name="remittance_due_days" value="{{ $dueDays }}" required type="number" min="1" max="365" step="1" class="mt-1 w-full rounded-sm border border-line p-2 text-sm"></label>
        <label class="block text-xs font-semibold">Minimum seller payout (PHP)<input name="minimum_payout" value="{{ old('minimum_payout', $minimumPayout) }}" required type="number" min="0.01" max="9999999999.99" step="0.01" class="mt-1 w-full rounded-sm border border-line p-2 text-sm"></label>
        <button class="btn btn-primary rounded-sm!">Save settings</button>
    </form>
@endif
