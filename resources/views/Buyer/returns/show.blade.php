<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Return / Refund #{{ $return->id }} | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    @php
        $R = \App\Models\OrderReturn::class;
        $shopName = $return->order->seller->sellerProfile->business_name ?? $return->order->seller->name;
        $inputClass =
            'w-full rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10';
    @endphp

    <main class="mx-auto w-full max-w-7xl flex-1 space-y-5 px-3 py-5 md:px-8 md:py-6">

        <a href="{{ route('buyer.orders.index', ['tab' => 'return_refund']) }}"
            class="inline-flex items-center gap-1 text-xs font-semibold text-charcoal/60 hover:text-primary">
            ← Back to my orders
        </a>

        @if (session('status'))
            <div class="rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert"
                class="rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Header --}}
        <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-bold text-charcoal">{{ $return->type_label }} · Order
                        #{{ $return->order_id }}</h1>
                    <p class="text-xs text-charcoal/50">{{ $shopName }} · requested
                        {{ $return->created_at->format('M d, Y g:i A') }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $return->status_color }}">
                    {{ $return->status_label }}
                </span>
            </div>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-charcoal/50">Reason</dt>
                    <dd class="font-medium">{{ $return->reason_label }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-charcoal/50">Refund amount</dt>
                    <dd class="font-bold text-primary">₱{{ number_format($return->refund_amount, 2) }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-charcoal/50">What you told us</dt>
                    <dd class="whitespace-pre-line">{{ $return->details }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-charcoal/50">Refund will be sent to</dt>
                    <dd class="font-medium">
                        {{ $return->refund_method_label }} · {{ $return->refund_account_name }}
                        <span class="block text-xs font-normal text-charcoal/50">{{ $return->masked_account }}</span>
                    </dd>
                </div>
            </dl>

            <div class="mt-4 app-table-body rounded-xl border border-light-gray">
                @foreach ($return->items as $line)
                    <div class="flex items-center gap-3 px-3 py-2.5 text-sm">
                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                            @if ($line->orderItem?->product?->main_image_url)
                                <img src="{{ $line->orderItem->product->main_image_url }}" alt=""
                                    class="h-full w-full object-cover">
                            @endif
                        </div>
                        <span class="min-w-0 flex-1 truncate">{{ $line->orderItem?->product_name }}
                            ×{{ $line->quantity }}</span>
                        <span class="shrink-0 font-semibold">₱{{ number_format($line->amount, 2) }}</span>
                    </div>
                @endforeach
            </div>

            @if ($return->media->isNotEmpty())
                <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-5">
                    @foreach ($return->media as $media)
                        <a href="{{ $media->url }}" target="_blank" rel="noopener"
                            class="relative block aspect-square overflow-hidden rounded-lg border border-light-gray bg-ice-blue/60">
                            @if ($media->type === 'image')
                                <img src="{{ $media->url }}" alt="Evidence" class="h-full w-full object-cover"
                                    loading="lazy">
                            @else
                                <span
                                    class="flex h-full w-full items-center justify-center text-[10px] font-semibold text-charcoal/50">VIDEO</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- What happens next --}}
        <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
            <h2 class="mb-3 text-sm font-bold text-charcoal">What happens next</h2>

            @switch($return->status)
                @case($R::STATUS_REQUESTED)
                    <p class="text-sm text-charcoal/70">
                        Waiting for the seller. They must answer by
                        <b>{{ $return->response_due_at?->format('M d, g:i A') }}</b>. If they don't, your request is
                        approved automatically.
                    </p>
                    <form method="POST" action="{{ route('buyer.returns.withdraw', $return) }}"
                        onsubmit="return confirm('Withdraw this request?')" class="mt-4">
                        @csrf
                        <button type="submit"
                            class="rounded-lg border border-charcoal/20 px-4 py-2 text-xs font-bold text-charcoal transition hover:border-primary hover:text-primary">
                            Withdraw request
                        </button>
                    </form>
                @break

                @case($R::STATUS_APPROVED)
                    <p class="text-sm text-charcoal/70">
                        The request was approved. Please ship the item back by
                        <b>{{ $return->ship_by_at?->format('M d, Y') }}</b>, then enter the courier and tracking number.
                        Pack it in its original condition.
                    </p>

                    <form method="POST" action="{{ route('buyer.returns.ship', $return) }}"
                        class="mt-4 grid gap-3 sm:grid-cols-3">
                        @csrf
                        <select name="courier" required class="{{ $inputClass }}">
                            <option value="" disabled selected>Courier…</option>
                            @foreach ($R::RETURN_COURIERS as $courier)
                                <option value="{{ $courier }}">{{ $courier }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="tracking_no" required maxlength="100" placeholder="Tracking number"
                            class="{{ $inputClass }}">
                        <button type="submit"
                            class="rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                            I shipped it
                        </button>
                    </form>

                    <form method="POST" action="{{ route('buyer.returns.withdraw', $return) }}"
                        onsubmit="return confirm('Withdraw this request?')" class="mt-3">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-sale-red/80 hover:text-sale-red">
                            Withdraw request
                        </button>
                    </form>
                @break

                @case($R::STATUS_SHIPPED_BACK)
                    <p class="text-sm text-charcoal/70">
                        You shipped the item via <b>{{ $return->return_courier }}</b> ({{ $return->return_tracking_no }}).
                        The seller will confirm when it arrives. If they don't by
                        <b>{{ $return->receipt_due_at?->format('M d, g:i A') }}</b>, it is confirmed automatically and your
                        refund is processed.
                    </p>
                @break

                @case($R::STATUS_REFUND_PENDING)
                    <p class="text-sm text-charcoal/70">
                        Approved. Shopleap is sending <b>₱{{ number_format($return->refund_amount, 2) }}</b> to your
                        {{ $return->refund_method_label }} ({{ $return->masked_account }}). We'll mark it here once it's sent.
                    </p>
                @break

                @case($R::STATUS_REFUNDED)
                    <p class="text-sm text-leaf-green">
                        Refund of <b>₱{{ number_format($return->refund_amount, 2) }}</b> sent
                        {{ $return->refunded_at?->format('M d, Y') }}.
                        @if ($return->refund_reference)
                            Reference: {{ $return->refund_reference }}.
                        @endif
                    </p>
                @break

                @case($R::STATUS_DECLINED)
                    <p class="text-sm text-charcoal/70">
                        The seller declined your request.
                        @if ($return->seller_response)
                            <span class="mt-1 block rounded-lg bg-ice-blue/60 p-3 text-xs">
                                Seller's reason: {{ $return->seller_response }}
                            </span>
                        @endif
                    </p>

                    @if ($return->escalate_until && $return->escalate_until->isFuture())
                        <p class="mt-3 text-xs text-charcoal/60">
                            You can ask Shopleap to review this decision until
                            <b>{{ $return->escalate_until->format('M d, g:i A') }}</b>.
                        </p>

                        <form method="POST" action="{{ route('buyer.returns.escalate', $return) }}" class="mt-3 space-y-2">
                            @csrf
                            <textarea name="reason" rows="3" required minlength="10" maxlength="500"
                                placeholder="Why do you disagree with the seller's decision?" class="{{ $inputClass }} resize-none"></textarea>
                            <button type="submit"
                                class="rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                                Ask Shopleap to review
                            </button>
                        </form>
                    @else
                        <p class="mt-3 text-xs text-charcoal/50">The time to ask for a review has ended.</p>
                    @endif
                @break

                @case($R::STATUS_DISPUTED)
                    <p class="text-sm text-charcoal/70">
                        Shopleap is reviewing this case. You don't need to do anything. We'll update this page with the
                        decision.
                    </p>
                @break

                @case($R::STATUS_REJECTED)
                    <p class="text-sm text-sale-red">This request was closed without a refund.</p>
                    @if ($return->admin_note)
                        <p class="mt-2 rounded-lg bg-ice-blue/60 p-3 text-xs text-charcoal/70">
                            Shopleap's note: {{ $return->admin_note }}
                        </p>
                    @endif
                @break

                @default
                    <p class="text-sm text-charcoal/60">This request is closed.</p>
            @endswitch
        </section>

        {{-- Timeline --}}
        <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
            <h2 class="mb-4 text-sm font-bold text-charcoal">Timeline</h2>

            <ol class="space-y-4 border-l-2 border-light-gray pl-5">
                @foreach ($return->events->sortByDesc('id') as $event)
                    <li class="relative">
                        <span
                            class="absolute -left-[1.7rem] top-1 h-3 w-3 rounded-full ring-4 ring-white {{ $loop->first ? 'bg-primary' : 'bg-charcoal/25' }}"></span>
                        <p class="text-sm {{ $loop->first ? 'font-bold' : 'text-charcoal/70' }}">{{ $event->title }}
                        </p>
                        @if ($event->note)
                            <p class="text-xs text-charcoal/50">{{ $event->note }}</p>
                        @endif
                        <p class="text-[11px] text-charcoal/40">{{ $event->created_at->format('M d, Y · g:i A') }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
    </main>

    <x-Buyer.message-widget />
    <x-footer />

</body>

</html>
