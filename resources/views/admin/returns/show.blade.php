<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Case #{{ $return->id }} | Shopleap Admin</title>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    @php
        $R = \App\Models\OrderReturn::class;
        $order = $return->order;
        $ship = $order->ship_to;
        $needsReturn = $return->type === $R::TYPE_RETURN_AND_REFUND && !$return->return_shipped_at;
        $inputClass =
            'frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.admin active="returns" />

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="workspace-header">
                <div class="min-w-0">
                    <a href="{{ route('admin.returns.index') }}" aria-label="Back to returns"
                        class="frost-btn rounded-full p-2 text-charcoal/50 transition hover:text-primary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="workspace-header-title">Case #{{ $return->id }} · Order
                            #{{ $return->order_id }}</h1>
                        <p class="workspace-header-subtitle">{{ $return->type_label }} · requested
                            {{ $return->created_at->format('M d, Y g:i A') }}</p>
                    </div>
                </div>

                <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $return->status_color }}">
                    {{ $return->status_label }}
                </span>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                <div class="mx-auto max-w-6xl space-y-4">
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

                        {{-- Left: the case --}}
                        <div class="min-w-0 space-y-5 xl:col-span-7">

                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-3 text-section-label">The request</h2>

                                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="text-charcoal/50">Buyer</dt>
                                        <dd class="font-medium">{{ $order->buyer_name }}</dd>
                                        <dd class="text-xs text-charcoal/50">{{ $ship['contact_no'] ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-charcoal/50">Seller</dt>
                                        <dd class="font-medium">
                                            {{ $return->seller?->sellerProfile?->business_name ?? ($return->seller?->name ?? '—') }}
                                        </dd>
                                        <dd class="text-xs text-charcoal/50">
                                            {{ $return->seller?->sellerProfile?->contact_no ?? '' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-charcoal/50">Reason</dt>
                                        <dd class="font-medium">{{ $return->reason_label }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-charcoal/50">Delivery address</dt>
                                        <dd class="font-medium">
                                            {{ $ship['street'] ?? '' }}, {{ $ship['barangay'] ?? '' }},
                                            {{ $ship['municipality'] ?? '' }}, {{ $ship['province'] ?? '' }}
                                        </dd>
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

                            {{-- Why it needs an admin --}}
                            @if ($return->escalated_at)
                                <section class="frost-panel rounded-2xl border border-warning/30 p-5">
                                    <h2 class="mb-2 text-section-label">Why this reached Shopleap</h2>
                                    <p class="text-sm text-charcoal">
                                        Sent by
                                        <b>{{ ['buyer' => 'the buyer', 'seller' => 'the seller', 'system' => 'the system'][$return->escalated_by] ?? $return->escalated_by }}</b>
                                        on {{ $return->escalated_at->format('M d, Y g:i A') }}.
                                    </p>
                                    @if ($return->escalation_reason)
                                        <p class="mt-2 rounded-lg bg-ice-blue/60 p-3 text-xs text-charcoal/70">
                                            {{ $return->escalation_reason }}</p>
                                    @endif
                                    @if ($return->seller_response)
                                        <p class="mt-2 text-xs text-charcoal/60">
                                            <b>Seller's reason for declining:</b> {{ $return->seller_response }}
                                        </p>
                                    @endif
                                    @if ($return->return_tracking_no)
                                        <p class="mt-2 text-xs text-charcoal/60">
                                            Item shipped back via {{ $return->return_courier }}
                                            ({{ $return->return_tracking_no }}) on
                                            {{ $return->return_shipped_at?->format('M d, Y') }}.
                                        </p>
                                    @endif
                                </section>
                            @endif

                            {{-- Delivery record: what the courier recorded --}}
                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-3 text-section-label">Delivery record</h2>

                                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="text-charcoal/50">Delivered</dt>
                                        <dd class="font-medium">
                                            {{ $order->delivered_at?->format('M d, Y g:i A') ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-charcoal/50">Delivery rider</dt>
                                        <dd class="font-medium">
                                            {{ $order->deliveryRider?->full_name ?? '—' }}
                                            <span class="block text-xs font-normal text-charcoal/50">
                                                {{ $order->deliveryRider?->contact_no }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-charcoal/50">Sorting center</dt>
                                        <dd class="font-medium">{{ $order->logisticsProfile?->business_name ?? '—' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-charcoal/50">COD collected</dt>
                                        <dd class="font-medium">
                                            @if ($order->cod_collected_amount !== null)
                                                ₱{{ number_format($order->cod_collected_amount, 2) }}
                                                <span class="block text-xs font-normal text-charcoal/50">
                                                    {{ $order->cod_collected_at?->format('M d, g:i A') }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </dd>
                                    </div>
                                </dl>

                                <p class="mb-2 mt-4 text-xs font-semibold text-charcoal/60">Order timeline</p>
                                <ol class="max-h-56 space-y-2 overflow-y-auto border-l border-charcoal/10 pl-4 text-xs">
                                    @foreach ($order->events->sortByDesc('id') as $event)
                                        <li>
                                            <span class="font-semibold text-charcoal">{{ $event->title }}</span>
                                            <span class="text-charcoal/40">
                                                · {{ $event->created_at->format('M d, g:i A') }}
                                                @if ($event->actor)
                                                    · {{ $event->actor->name }}
                                                @endif
                                            </span>
                                            @if ($event->note)
                                                <span class="block text-charcoal/55">{{ $event->note }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </section>

                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-3 text-section-label">Items in this case</h2>

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
                            </section>
                        </div>

                        {{-- Right: decide, pay, timeline --}}
                        <div class="min-w-0 space-y-5 xl:col-span-5">

                            {{-- Decision --}}
                            @if ($return->status === $R::STATUS_DISPUTED)
                                <section class="frost-panel rounded-2xl p-5">
                                    <h2 class="mb-3 text-section-label">Your decision</h2>

                                    <form method="POST" action="{{ route('admin.returns.decide', $return) }}"
                                        onsubmit="return confirm('Send this decision to the buyer and the seller?')"
                                        class="space-y-3">
                                        @csrf

                                        <label
                                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/60 bg-white/50 p-3 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                            <input type="radio" name="decision" value="approve" required
                                                class="mt-1 accent-primary">
                                            <span class="text-sm">
                                                <b>Approve</b>
                                                <span class="block text-xs text-charcoal/60">
                                                    @if ($needsReturn)
                                                        The buyer ships the item back first, then the refund is paid.
                                                    @else
                                                        The refund moves to "Refunds to pay".
                                                    @endif
                                                </span>
                                            </span>
                                        </label>

                                        <label
                                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/60 bg-white/50 p-3 has-[:checked]:border-sale-red has-[:checked]:bg-sale-red/5">
                                            <input type="radio" name="decision" value="reject" required
                                                class="mt-1 accent-primary">
                                            <span class="text-sm">
                                                <b>Reject</b>
                                                <span class="block text-xs text-charcoal/60">The case is closed with no
                                                    refund.</span>
                                            </span>
                                        </label>

                                        <textarea name="note" rows="3" required minlength="10" maxlength="500"
                                            placeholder="Your reasoning (the buyer and seller will see this)" class="{{ $inputClass }} resize-none">{{ old('note') }}</textarea>

                                        <button type="submit"
                                            class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                                            Send decision
                                        </button>
                                    </form>
                                </section>
                            @endif

                            {{-- Payout --}}
                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-3 text-section-label">Refund</h2>

                                <div class="space-y-1.5 text-sm">
                                    <div class="flex justify-between text-charcoal/70">
                                        <span>Items</span><span>₱{{ number_format($return->items_amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-charcoal/70">
                                        <span>Shipping
                                            fee</span><span>₱{{ number_format($return->shipping_amount, 2) }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between border-t border-white/50 pt-2 text-base font-bold">
                                        <span>Refund amount</span>
                                        <span
                                            class="text-primary">₱{{ number_format($return->refund_amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between pt-1 text-xs text-charcoal/50">
                                        <span>Seller earnings reduced by</span>
                                        <span>₱{{ number_format($return->seller_deduction, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-charcoal/50">
                                        <span>Commission handed back</span>
                                        <span>₱{{ number_format($return->commission_reversal, 2) }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-xl bg-ice-blue/60 p-3 text-sm" x-data="{ copied: false }">
                                    <p class="text-xs text-charcoal/50">Send the refund to</p>
                                    <p class="font-semibold">{{ $return->refund_method_label }} ·
                                        {{ $return->refund_account_name }}</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span
                                            class="font-mono text-base font-bold tracking-wide">{{ $return->refund_account_number }}</span>
                                        <button type="button"
                                            @click="navigator.clipboard.writeText(@js($return->refund_account_number)); copied = true; setTimeout(() => copied = false, 1500)"
                                            class="rounded-lg border border-charcoal/20 px-2.5 py-1 text-[11px] font-bold text-charcoal transition hover:border-primary hover:text-primary">
                                            <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                                        </button>
                                    </div>
                                </div>

                                @if ($return->status === $R::STATUS_REFUND_PENDING)
                                    <form method="POST" action="{{ route('admin.returns.refunded', $return) }}"
                                        onsubmit="return confirm('Confirm the refund was sent?')"
                                        class="mt-4 space-y-2">
                                        @csrf
                                        <input type="text" name="reference" required maxlength="100"
                                            placeholder="Transfer reference number" class="{{ $inputClass }}">
                                        <button type="submit"
                                            class="w-full rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-110">
                                            Mark refund as sent
                                        </button>
                                        <p class="text-[11px] text-charcoal/50">
                                            Send the money first, then record the reference. The buyer sees it.
                                        </p>
                                    </form>
                                @elseif ($return->status === $R::STATUS_REFUNDED)
                                    <p class="mt-3 text-sm text-leaf-green">
                                        Sent {{ $return->refunded_at?->format('M d, Y g:i A') }}
                                        · Ref {{ $return->refund_reference }}
                                    </p>
                                @elseif (!in_array($return->status, [$R::STATUS_DISPUTED], true))
                                    <p class="mt-3 text-xs text-charcoal/50">Nothing to pay at this stage.</p>
                                @endif
                            </section>

                            @if ($return->admin_note)
                                <section class="frost-panel rounded-2xl p-5">
                                    <h2 class="mb-2 text-section-label">Decision note</h2>
                                    <p class="text-sm text-charcoal/80">{{ $return->admin_note }}</p>
                                </section>
                            @endif

                            <section class="frost-panel rounded-2xl p-5">
                                <h2 class="mb-4 text-section-label">Case timeline</h2>

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
                                                {{ $event->created_at->format('M d, g:i A') }}
                                                @if ($event->actor)
                                                    · {{ $event->actor->name }}
                                                @endif
                                            </p>
                                        </li>
                                    @endforeach
                                </ol>
                            </section>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
