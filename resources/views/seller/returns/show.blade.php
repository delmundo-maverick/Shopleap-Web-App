<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Return #{{ $return->id }} | Shopleap Seller</title>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    @php
        $R = \App\Models\OrderReturn::class;
        $inputClass =
            'frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.seller active="returns" />

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex min-h-20 flex-wrap items-center justify-between gap-3 border-b border-white/50 px-6 py-3">
                <div class="flex w-full items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('seller.returns.index') }}" aria-label="Back to returns"
                            class="frost-btn rounded-full p-2 text-charcoal/50 transition hover:text-primary">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-xl font-bold text-charcoal">{{ $return->type_label }} · Order
                                #{{ $return->order_id }}</h1>
                            <p class="text-sm text-charcoal/55">Requested
                                {{ $return->created_at->format('M d, Y \a\t g:i A') }}
                            </p>
                        </div>
                    </div>

                    <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $return->status_color }}">
                        {{ $return->status_label }}
                    </span>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                <div class="w-full space-y-4">
                    @if (session('status'))
                        <div role="status"
                            class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div role="alert"
                            class="frost-panel rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-12">

                        {{-- Left: the request --}}
                        <div class="min-w-0 space-y-5 xl:col-span-7">
                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-3 text-section-label">Request</h2>

                                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="text-charcoal/50">Buyer</dt>
                                        <dd class="font-medium">{{ $return->order->buyer_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-charcoal/50">Reason</dt>
                                        <dd class="font-medium">{{ $return->reason_label }}</dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-charcoal/50">What the buyer said</dt>
                                        <dd class="whitespace-pre-line">{{ $return->details }}</dd>
                                    </div>
                                </dl>

                                @if ($return->media->isNotEmpty())
                                    <p class="mb-2 mt-4 text-xs font-semibold text-charcoal/60">Evidence from the buyer
                                    </p>
                                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                                        @foreach ($return->media as $media)
                                            <a href="{{ $media->url }}" target="_blank" rel="noopener"
                                                class="block aspect-square overflow-hidden rounded-lg border border-white/60 bg-ice-blue/60">
                                                @if ($media->type === 'image')
                                                    <img src="{{ $media->url }}" alt="Evidence"
                                                        class="h-full w-full object-cover" loading="lazy">
                                                @else
                                                    <span
                                                        class="flex h-full w-full items-center justify-center text-[10px] font-semibold text-charcoal/50">VIDEO</span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </section>

                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-3 text-section-label">Items</h2>

                                <div class="app-table-body">
                                    @foreach ($return->items as $line)
                                        <div class="flex items-center gap-3 py-3">
                                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-ice-blue">
                                                @if ($line->orderItem?->product?->main_image_url)
                                                    <img src="{{ $line->orderItem->product->main_image_url }}"
                                                        alt="" class="h-full w-full object-cover">
                                                @endif
                                            </div>
                                            <p class="min-w-0 flex-1 truncate text-sm">
                                                {{ $line->orderItem?->product_name }}
                                                ×{{ $line->quantity }}</p>
                                            <p class="shrink-0 text-sm font-bold">
                                                ₱{{ number_format($line->amount, 2) }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-4 space-y-1.5 border-t border-white/50 pt-4 text-sm">
                                    <div class="flex justify-between text-charcoal/70">
                                        <span>Items refunded</span>
                                        <span>₱{{ number_format($return->items_amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-charcoal/50">
                                        <span>Commission handed back</span>
                                        <span>+ ₱{{ number_format($return->commission_reversal, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-white/50 pt-2 font-bold">
                                        <span>Change in your earnings</span>
                                        <span class="text-sale-red">−
                                            ₱{{ number_format($return->seller_deduction, 2) }}</span>
                                    </div>
                                    @if ($return->shipping_amount > 0)
                                        <p class="text-[11px] text-charcoal/45">
                                            The ₱{{ number_format($return->shipping_amount, 2) }} shipping refund is
                                            handled
                                            by Shopleap and doesn't affect your earnings.
                                        </p>
                                    @endif
                                </div>
                            </section>
                        </div>

                        {{-- Right: what to do --}}
                        <div class="min-w-0 space-y-5 xl:col-span-5">
                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-3 text-section-label">Your action</h2>

                                @switch($return->status)
                                    @case($R::STATUS_REQUESTED)
                                        <p class="text-sm text-charcoal">
                                            Respond by <b>{{ $return->response_due_at?->format('M d, g:i A') }}</b>. If you
                                            don't, the request is approved automatically.
                                        </p>

                                        <form method="POST" action="{{ route('seller.returns.approve', $return) }}"
                                            onsubmit="return confirm('Approve this request?')" class="mt-4">
                                            @csrf
                                            <button type="submit"
                                                class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                                                {{ $return->type === $R::TYPE_REFUND_ONLY ? 'Approve refund' : 'Approve return' }}
                                            </button>
                                        </form>

                                        <details class="mt-3">
                                            <summary class="cursor-pointer text-xs font-semibold text-sale-red hover:underline">
                                                Decline the request…
                                            </summary>
                                            <form method="POST" action="{{ route('seller.returns.decline', $return) }}"
                                                class="mt-2 space-y-2">
                                                @csrf
                                                <textarea name="reason" rows="3" required minlength="5" maxlength="255"
                                                    placeholder="Reason (the buyer will see this)" class="{{ $inputClass }} resize-none"></textarea>
                                                <button type="submit"
                                                    class="w-full rounded-xl border border-sale-red/30 px-4 py-2.5 text-sm font-bold text-sale-red transition hover:bg-sale-red/5">
                                                    Decline request
                                                </button>
                                                <p class="text-[11px] text-charcoal/50">The buyer can ask Shopleap to review a
                                                    decline.</p>
                                            </form>
                                        </details>
                                    @break

                                    @case($R::STATUS_APPROVED)
                                        <p class="text-sm text-charcoal/70">
                                            Waiting for the buyer to ship the item back (by
                                            {{ $return->ship_by_at?->format('M d, Y') }}).
                                        </p>
                                    @break

                                    @case($R::STATUS_SHIPPED_BACK)
                                        <p class="text-sm text-charcoal">
                                            The buyer shipped the item via <b>{{ $return->return_courier }}</b>
                                            ({{ $return->return_tracking_no }}). Confirm when it arrives, by
                                            <b>{{ $return->receipt_due_at?->format('M d, g:i A') }}</b>. If you don't, it's
                                            confirmed automatically.
                                        </p>

                                        <form method="POST" action="{{ route('seller.returns.received', $return) }}"
                                            onsubmit="return confirm('Confirm the item arrived in the condition described?')"
                                            class="mt-4">
                                            @csrf
                                            <button type="submit"
                                                class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                                                Confirm item received
                                            </button>
                                        </form>

                                        <details class="mt-3">
                                            <summary
                                                class="cursor-pointer text-xs font-semibold text-sale-red hover:underline">
                                                Report a problem…
                                            </summary>
                                            <form method="POST" action="{{ route('seller.returns.problem', $return) }}"
                                                class="mt-2 space-y-2">
                                                @csrf
                                                <textarea name="reason" rows="3" required minlength="10" maxlength="500"
                                                    placeholder="The item didn't arrive, or isn't in the condition described."
                                                    class="{{ $inputClass }} resize-none"></textarea>
                                                <button type="submit"
                                                    class="w-full rounded-xl border border-sale-red/30 px-4 py-2.5 text-sm font-bold text-sale-red transition hover:bg-sale-red/5">
                                                    Send to Shopleap for review
                                                </button>
                                            </form>
                                        </details>
                                    @break

                                    @case($R::STATUS_REFUND_PENDING)
                                        <p class="text-sm text-charcoal/70">Approved. Shopleap is sending the refund to the
                                            buyer. Nothing more to do.</p>
                                    @break

                                    @case($R::STATUS_REFUNDED)
                                        <p class="text-sm text-leaf-green">Refund sent
                                            {{ $return->refunded_at?->format('M d, Y') }}.</p>
                                    @break

                                    @case($R::STATUS_DECLINED)
                                        <p class="text-sm text-charcoal/70">You declined this request. The buyer can ask
                                            Shopleap to review it until
                                            {{ $return->escalate_until?->format('M d, g:i A') }}.</p>
                                    @break

                                    @case($R::STATUS_DISPUTED)
                                        <p class="text-sm text-charcoal/70">Shopleap is reviewing this case.
                                            @if ($return->escalation_reason)
                                                <span class="mt-2 block rounded-lg bg-ice-blue/60 p-3 text-xs">
                                                    {{ $return->escalation_reason }}
                                                </span>
                                            @endif
                                        </p>
                                    @break

                                    @default
                                        <p class="text-sm text-charcoal/60">This request is closed.</p>
                                        @if ($return->admin_note)
                                            <p class="mt-2 rounded-lg bg-ice-blue/60 p-3 text-xs">Shopleap's note:
                                                {{ $return->admin_note }}</p>
                                        @endif
                                @endswitch
                            </section>

                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-4 text-section-label">Timeline</h2>

                                <ol class="space-y-4 border-l border-charcoal/10 pl-5">
                                    @foreach ($return->events->sortByDesc('id') as $event)
                                        <li class="relative">
                                            <span
                                                class="absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-full {{ $loop->first ? 'bg-primary' : 'bg-charcoal/25' }}"></span>
                                            <p class="text-sm font-semibold">{{ $event->title }}</p>
                                            @if ($event->note)
                                                <p class="text-xs text-charcoal/55">{{ $event->note }}</p>
                                            @endif
                                            <p class="text-[11px] text-charcoal/40">
                                                {{ $event->created_at->format('M d, g:i A') }}</p>
                                        </li>
                                    @endforeach
                                </ol>
                            </section>

                            <a href="{{ route('seller.orders.show', $return->order_id) }}"
                                class="block text-center text-xs font-semibold text-primary hover:underline">
                                View order #{{ $return->order_id }}
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
