<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Logistics seller payments · Shopleap</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/' . ($role === 'super_admin' ? 'admin' : $role) . '/money.js'])
</head>
<body class="bg-surface text-charcoal">
@php
    $portal = $role === 'super_admin' ? 'admin' : $role;
    $peso = fn ($amount) => \App\Support\LedgerPresentation::peso($amount);
    $date = fn ($value) => \App\Support\LedgerPresentation::dateTime($value);
@endphp
<div class="workspace-shell min-h-dvh lg:flex">
    @if ($role === 'seller')<x-navbars.seller active="settlements" />
    @elseif ($role === 'logistics')<x-navbars.logistics />
    @else<x-navbars.admin />@endif
    <div class="min-w-0 flex-1">
        <x-seller.page-header title="Logistics seller payments" description="Direct payments, receipt confirmation and settlement history" />
        <main class="space-y-6 p-4 sm:p-6">
            @if (session('status'))<p role="status" class="rounded-sm border border-line p-3">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="rounded-sm border border-danger p-3 text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <p class="max-w-4xl text-sm">Logistics pays seller proceeds after order completion and receipt of the rider's full cash handover. Commission is deducted and remitted separately to Shopleap. A transfer is marked paid only after seller confirmation or an evidenced admin dispute resolution.</p>
            <p class="text-sm">Payment deadline: {{ config('money.seller_settlement_due_days') }} days after both completion and full cash receipt. Overdue payments remain visible for admin follow-up. These screens record external bank or wallet transfers; they do not initiate them.</p>
            @if ($hasLegacyPayouts)<p class="text-sm"><a class="text-primary underline" href="{{ route('admin.payouts.index') }}">Legacy platform payout records and remaining obligations</a>. New logistics-managed seller payments do not require admin payout approval.</p>@endif
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Eligible seller proceeds</h2>
                @foreach ($eligible->getCollection()->groupBy('seller_id') as $sellerId => $orders)
                    <div class="space-y-3 rounded-sm border border-line p-4">
                        <h3 class="font-semibold">{{ $orders->first()->seller_name }}</h3>
                        @if ($role === 'logistics')<form method="POST" action="{{ route('logistics.settlements.store') }}" data-money-form class="space-y-3">@csrf
                            <input type="hidden" name="seller_id" value="{{ $sellerId }}">
                            <input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                        @endif
                        @foreach ($orders as $order)
                            <div class="flex flex-wrap items-center gap-3 border-b border-line py-2 text-sm">
                                @if ($role === 'logistics')<input type="checkbox" name="orders[]" value="{{ $order->order_number }}" aria-label="Select {{ $order->order_number }}">@endif
                                <span class="font-mono">{{ $order->order_number }}</span><span>{{ $order->hub_name }}</span><strong>{{ $peso($order->amount) }}</strong>
                                <span @class(['text-danger' => $settlements->dueAt($order)->isPast()])>Due {{ $settlements->dueAt($order)->timezone(config('money.timezone'))->format('M d, Y H:i') }}</span>
                            </div>
                        @endforeach
                        @if ($role === 'logistics')
                            @if (($methods[$sellerId] ?? collect())->isNotEmpty())
                                <label class="block text-sm">Seller's verified payment destination
                                    <select name="method_id" required class="mt-1 w-full rounded-sm border border-line bg-surface p-2">
                                        @foreach ($methods[$sellerId] as $method)<option value="{{ $method->id }}">{{ $method->type }} · {{ $method->account_name }} · ending {{ $method->account_number_masked }}</option>@endforeach
                                    </select>
                                </label>
                                <p class="text-xs">Confirm full account details with the seller through your verified communication channel before sending funds.</p>
                                <button class="btn btn-primary">Prepare selected payment</button>
                            @else<p class="text-sm">The seller must add a payout method before payment can be prepared.</p>@endif
                            </form>
                        @endif
                    </div>
                @endforeach
                @if ($eligible->isEmpty())<p class="rounded-sm border border-line p-4 text-sm">No eligible unpaid orders. Orders must be completed, fully handed over, and free of open return requests.</p>@endif
                {{ $eligible->links() }}
                @if ($role === 'seller')<a class="text-sm text-primary underline" href="{{ route('seller.payouts.index') }}">Manage payout methods and legacy payouts</a>@endif
            </section>
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Payments and disputes</h2>
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <label class="text-sm">Status<select name="status" class="ml-2 rounded-sm border border-line bg-surface p-2">@foreach (['all', 'pending', 'sent', 'paid', 'disputed', 'cancelled', 'overdue'] as $option)<option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>@endforeach</select></label>
                    <button class="btn btn-outline">Filter</button>
                </form>
                @forelse ($rows as $row)
                    <article class="space-y-3 rounded-sm border border-line p-4">
                        <div class="flex flex-wrap justify-between gap-3"><h3 class="font-mono font-semibold">{{ $row->reference_no }}</h3><strong>{{ $peso($row->amount) }} · {{ \App\Support\LedgerPresentation::status($row->status) }}</strong></div>
                        <p class="text-sm">{{ $row->hub_name }} → {{ $row->seller_name }} · {{ $row->method_type }} · {{ $row->account_name }} · ending {{ $row->account_number_masked }}</p>
                        <p @class(['text-sm', 'text-danger' => $row->status !== 'paid' && $row->status !== 'cancelled' && \Carbon\CarbonImmutable::parse($row->due_at)->isPast()])>Due {{ $date($row->due_at) }}</p>
                        <div class="flex flex-wrap gap-3 text-sm">@foreach ($items[$row->id] ?? [] as $item)<span>{{ $item->order_number }} · {{ $peso($item->amount) }}</span>@endforeach</div>
                        <a class="text-sm text-primary underline" href="{{ route($portal === 'logistics' ? 'logistics.receipts.show' : ($portal === 'admin' ? 'admin.receipts.show' : 'money.receipts.show'), ['type' => 'seller-settlements', 'reference' => $row->reference_no]) }}">View payment record</a>
                        @if ($row->proof_path)<p class="text-sm">Transfer {{ $row->transfer_reference }} · <a class="text-primary underline" href="{{ route($portal.'.settlements.proof', $row->reference_no) }}">Download transfer evidence</a></p>@endif
                        @if ($role === 'logistics' && $row->status === 'pending')
                            <form method="POST" enctype="multipart/form-data" action="{{ route('logistics.settlements.send', $row->reference_no) }}" data-money-form data-confirm="Have you sent this amount to the seller's verified account?" class="space-y-3">@csrf
                                <x-money.transfer-fields />
                                <button class="btn btn-primary">Record transfer sent</button>
                            </form>
                            <form method="POST" action="{{ route('logistics.settlements.cancel', $row->reference_no) }}" data-money-form class="space-y-2">@csrf
                                <label class="block text-sm">Reason to cancel an unsent payment<input name="reason" required minlength="3" maxlength="2000" class="mt-1 w-full rounded-sm border border-line bg-surface p-2"></label>
                                <button class="btn btn-outline">Cancel unsent payment</button>
                            </form>
                        @elseif ($role === 'seller' && in_array($row->status, ['sent', 'disputed'], true))
                            <form method="POST" action="{{ route('seller.settlements.confirm', $row->reference_no) }}" data-money-form data-confirm="Have you checked your account and received the full payment?">@csrf<button class="btn btn-primary">Confirm money received</button></form>
                            @if ($row->status === 'sent')<form method="POST" action="{{ route('seller.settlements.dispute', $row->reference_no) }}" data-money-form class="space-y-2">@csrf
                                <label class="block text-sm">Payment issue<textarea name="reason" required minlength="3" maxlength="2000" class="mt-1 w-full rounded-sm border border-line bg-surface p-2"></textarea></label><button class="btn btn-outline">Dispute payment</button>
                            </form>@endif
                        @elseif ($role === 'super_admin' && ($row->status === 'disputed' || ($row->status === 'sent' && \Carbon\CarbonImmutable::parse($row->due_at)->isPast())))
                            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.settlements.resolve', $row->reference_no) }}" data-money-form class="space-y-3">@csrf
                                <label class="block text-sm">Verified outcome<select name="outcome" class="mt-1 w-full rounded-sm border border-line bg-surface p-2"><option value="paid">Seller received the payment</option><option value="returned">Transfer returned to the hub; release funds for retry</option></select></label>
                                <label class="block text-sm">Investigation and evidence notes<textarea name="reason" required minlength="3" maxlength="2000" class="mt-1 w-full rounded-sm border border-line bg-surface p-2"></textarea></label>
                                <label class="block text-sm">Verified payment or return evidence<input name="proof" type="file" required accept="image/jpeg,image/png,application/pdf" class="mt-1 block w-full"></label>
                                <button class="btn btn-primary">Record dispute resolution</button>
                            </form>
                        @endif
                        <details class="text-sm"><summary class="cursor-pointer font-semibold">Audit history</summary>
                            @foreach ($events[$row->id] ?? [] as $event)<p class="mt-2">{{ $date($event->created_at) }} · {{ $event->actor_name }} · {{ str_replace('_', ' ', $event->action) }} @if ($event->note)· {{ $event->note }}@endif
                                @if ($event->proof_path)<a class="text-primary underline" href="{{ route($portal.'.settlements.proof', ['reference' => $row->reference_no, 'event' => $event->id]) }}">Evidence</a>@endif
                            </p>@endforeach
                        </details>
                    </article>
                @empty<p class="text-sm">No matching payments.</p>@endforelse
                {{ $rows->links() }}
            </section>
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Seller funds to recover for refunds</h2>
                @forelse ($recoveries as $recovery)<div class="space-y-3 rounded-sm border border-line p-4 text-sm">
                    <p>{{ $recovery->order_number }} · {{ $recovery->seller_name }} · {{ $peso(abs($recovery->amount)) }} due back to logistics.</p>
                    @if ($role === 'logistics')<form method="POST" enctype="multipart/form-data" action="{{ route('logistics.settlements.recovery', $recovery->order_id) }}" data-money-form data-confirm="Has logistics received the full recovery amount from the seller?" class="space-y-3">@csrf<x-money.transfer-fields /><button class="btn btn-primary">Confirm recovery received</button></form>@endif
                </div>@empty<p class="text-sm">No seller recovery balances.</p>@endforelse
                {{ $recoveries->links() }}
            </section>
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Commission refund credits owed to logistics</h2>
                @forelse ($credits as $credit)<div class="space-y-3 rounded-sm border border-line p-4 text-sm">
                    <p>{{ $credit->order_number }} · {{ $peso(abs($credit->amount)) }} must be returned from Shopleap to the hub.</p>
                    @if ($role === 'super_admin')<form method="POST" enctype="multipart/form-data" action="{{ route('admin.settlements.commission-return', $credit->order_id) }}" data-money-form data-confirm="Has this commission credit been returned to logistics?" class="space-y-3">@csrf<x-money.transfer-fields /><button class="btn btn-primary">Record commission returned</button></form>@endif
                </div>@empty<p class="text-sm">No commission credits outstanding.</p>@endforelse
                {{ $credits->links() }}
            </section>
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Buyer refunds funded by logistics</h2>
                @forelse ($refunds as $refund)<div class="space-y-3 rounded-sm border border-line p-4 text-sm">
                    <p>{{ $refund->reference_no }} · {{ $refund->order_number }} · {{ $peso($refund->amount) }}</p>
                    <p>{{ $refund->refund_method }} · {{ $refund->refund_account_name }} · ending {{ substr($refund->refund_account_number, -4) }}. Confirm full payment details with the buyer through your verified channel.</p>
                    @if ($role === 'logistics')<form method="POST" enctype="multipart/form-data" action="{{ route('logistics.settlements.refund', $refund->reference_no) }}" data-money-form data-confirm="Has the buyer received this refund?" class="space-y-3">@csrf<x-money.transfer-fields /><button class="btn btn-primary">Record buyer refund paid</button></form>@endif
                </div>@empty<p class="text-sm">No pending logistics-funded refunds.</p>@endforelse
                {{ $refunds->links() }}
            </section>
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Refund and recovery audit history</h2>
                @forelse ($corrections as $correction)<p class="rounded-sm border border-line p-3 text-sm">
                    {{ $correction->order_number }} · {{ str_replace('_', ' ', $correction->action) }} · {{ $peso($correction->amount) }} · {{ $correction->actor_name }} · {{ $date($correction->created_at) }}
                    @if ($correction->proof_path)<a class="text-primary underline" href="{{ route($portal.'.settlements.evidence', $correction->id) }}">Evidence</a>@endif
                </p>@empty<p class="text-sm">No refund or recovery payments recorded.</p>@endforelse
                {{ $corrections->links() }}
            </section>
        </main>
    </div>
</div>
<x-money.action-modal />
</body>
</html>
