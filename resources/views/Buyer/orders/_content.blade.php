<div data-account-content="purchases">
@use('App\Models\Order')
@use('Illuminate\Support\Str')
    @php
        // One button style set for every order action (full width on phones).
        $btnBase =
            'inline-flex min-h-10 w-full items-center justify-center rounded-lg px-5 py-2 text-xs font-bold transition-colors sm:w-auto';
        $btnPrimary = "$btnBase bg-primary text-white hover:bg-primary/90";
        $btnAccent = "$btnBase border border-primary bg-white text-primary hover:bg-primary/5";
        $btnOutline = "$btnBase border border-line bg-white text-charcoal hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-60";
        $btnDanger = "$btnBase border border-sale-red/30 bg-white text-sale-red hover:bg-sale-red/5";
    @endphp

        {{-- TABS --}}
        <div
            class="mb-4 overflow-x-auto rounded-xl border border-line bg-white [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <div class="flex min-w-max sm:min-w-0">
                @foreach (Order::TAB_LABELS as $key => $label)
                    <a href="{{ route('buyer.orders.index', $key === 'all' ? [] : ['tab' => $key]) }}"
                        class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap border-b-2 px-4 py-3.5 text-center text-sm font-semibold transition-colors
                                {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/60 hover:text-charcoal' }}">
                        {{ $label }}
                        @if (($counts[$key] ?? 0) > 0)
                            <span
                                class="rounded-sm px-1.5 py-0.5 text-[10px] font-bold
                                    {{ $tab === $key ? 'bg-primary/10 text-primary' : 'bg-ice-blue text-charcoal/60' }}">
                                {{ $counts[$key] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- SEARCH: only in the All tab --}}
        @if ($tab === 'all')
            <form method="GET" action="{{ route('buyer.orders.index') }}" class="mb-4">
                <input type="hidden" name="tab" value="all">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-4 w-4 text-charcoal/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by order # or shop name..."
                        aria-label="Search orders by order number or shop name"
                        class="frost-input w-full rounded-lg py-3 pl-10 pr-4 text-sm">
                </div>
            </form>
        @endif

        {{-- EMPTY STATE --}}
        @if ($orders->isEmpty())
            <div
                class="flex flex-col items-center justify-center rounded-xl border border-line bg-white p-8 text-center sm:p-16">
                <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="mt-4 text-sm font-semibold text-charcoal">
                    @if ($tab === 'all' && request('search'))
                        No orders found matching "{{ request('search') }}"
                    @else
                        {{ $tab === 'all' ? "You haven't placed any orders yet" : 'No orders in this category' }}
                    @endif
                </p>
                @if ($tab === 'all' && request('search'))
                    <a href="{{ route('buyer.orders.index') }}" class="{{ $btnAccent }} mt-5 !w-auto">
                        Clear Search
                    </a>
                @else
                    <a href="{{ route('buyer.home') }}" class="{{ $btnPrimary }} mt-5 !w-auto">
                        Start Shopping
                    </a>
                @endif
            </div>
        @else
            {{-- ORDER LIST --}}
            <div class="space-y-3 sm:space-y-4">
                @foreach ($orders as $order)
                    @php
                        $shopName = $order->seller->sellerProfile->business_name ?? $order->seller->name;
                        $isDelivered = $order->status === Order::STATUS_DELIVERED;
                        $isCompleted = $order->status === Order::STATUS_COMPLETED;
                        $canBuyAgain = in_array($order->status, [
                            Order::STATUS_COMPLETED,
                            Order::STATUS_CANCELLED,
                            Order::STATUS_RETURNED,
                        ]);
                        $unreviewed = $order->items->filter(fn($i) => !$i->review);
                        $nextToRate = $unreviewed->first();
                        $canReorder = $order->items->contains(
                            fn($i) => $i->product && $i->product->status === 'active',
                        );
                        $chatProductId = $order->items->first(fn($i) => $i->product)?->product_id;
                        $timeline = $order->trackingTimeline();
                        $latest = $timeline->first();
                        $cancelMode = $order->buyerCancelMode();
                        $isCancelled = $order->status === Order::STATUS_CANCELLED;
                        $inCancelWindow = in_array($order->status, Order::BUYER_CANCEL_REQUEST_FROM, true);
                        $cancelPending = $order->cancel_request_status === 'pending' && $inCancelWindow;
                        $cancelDeclined = $order->cancel_request_status === 'declined' && $inCancelWindow;
                        $canReturn = $order->canRequestReturn();
                        $returnRecord = $order->returns->sortByDesc('id')->first();
                    @endphp

                    <article class="overflow-hidden rounded-xl border border-line bg-white">

                        {{-- SHOP HEADER --}}
                        <div
                            class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-line px-4 py-3 sm:px-5">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M3 9l1.5-5h15L21 9M3 9v1a3 3 0 006 0 3 3 0 006 0 3 3 0 006 0V9M3 9h18M5 13v7h14v-7" />
                                </svg>
                                <p class="truncate text-sm font-bold text-charcoal">{{ $shopName }}</p>
                                <a href="{{ route('buyer.shop.show', $order->seller_id) }}"
                                    class="shrink-0 rounded-lg border border-line px-2.5 py-1 text-[11px] font-semibold text-charcoal/70 transition-colors hover:border-primary hover:text-primary">
                                    View Shop
                                </a>
                            </div>
                            <span
                                class="shrink-0 rounded-md px-2.5 py-1 text-xs font-semibold {{ $order->status_color }}">
                                {{ $order->status_label }}
                            </span>
                        </div>

                        {{-- ITEMS --}}
                        <div class="app-table-body">
                            @foreach ($order->items as $item)
                                @php
                                    $options = collect($item->selected_options ?? [])
                                        ->map(function ($value, $key) {
                                            $value = is_array($value) ? implode(', ', $value) : $value;
                                            return is_string($key) ? "{$key}: {$value}" : $value;
                                        })
                                        ->filter()
                                        ->implode(' · ');
                                @endphp
                                <div class="flex gap-3 px-4 py-4 sm:gap-4 sm:px-5">
                                    <div
                                        class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-line bg-ice-blue">
                                        @if ($item->product && $item->product->main_image_url)
                                            <img src="{{ $item->product->main_image_url }}"
                                                alt="{{ $item->product_name }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-semibold text-charcoal">
                                            {{ $item->product_name }}
                                        </p>
                                        @if ($options !== '')
                                            <p class="mt-0.5 text-xs text-charcoal/60">{{ $options }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-charcoal/60">x{{ $item->quantity }}</p>
                                        @if ($isCompleted && $item->review)
                                            <span
                                                class="mt-1.5 inline-block rounded-sm border border-leaf-green/40 bg-leaf-green/10 px-2 py-0.5 text-[11px] font-bold text-charcoal">
                                                <span class="text-leaf-green">✓</span> Reviewed
                                            </span>
                                        @endif
                                    </div>
                                    <p class="shrink-0 text-right text-sm font-semibold tabular-nums text-charcoal">
                                        ₱{{ number_format($item->price_at_purchase, 2) }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        {{-- ORDER STATUS NOTES (part of the order, not page alerts) --}}
                        @if ($isCancelled)
                            <div class="border-t border-line bg-sale-red/5 px-4 py-3 text-xs text-charcoal/70 sm:px-5">
                                <b class="text-sale-red">
                                    Cancelled by
                                    {{ $order->cancelled_by === 'buyer' ? 'you' : Order::CANCELLED_BY_LABELS[$order->cancelled_by] ?? 'the seller' }}
                                </b>
                                @if ($order->cancel_reason)
                                    · {{ $order->cancel_reason }}
                                @endif
                                @if ($order->cancel_note)
                                    <span class="block text-charcoal/60">{{ $order->cancel_note }}</span>
                                @endif
                            </div>
                        @endif
                        @if ($cancelPending)
                            <div
                                class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-warning/10 px-4 py-3 text-xs sm:px-5">
                                <p class="min-w-0 flex-1 text-charcoal/70">
                                    <b>Cancellation requested</b> · waiting for the seller. If they don't respond by
                                    {{ $order->cancel_requested_at->copy()->addHours(Order::CANCEL_REQUEST_AUTO_APPROVE_HOURS)->format('M d, g:i A') }},
                                    your order is cancelled automatically.
                                </p>
                                <form method="POST"
                                    action="{{ route('buyer.orders.cancel-request.withdraw', $order) }}"
                                    class="w-full sm:w-auto">
                                    @csrf
                                    <button type="submit" class="{{ $btnOutline }}">
                                        Withdraw request
                                    </button>
                                </form>
                            </div>
                        @endif
                        @if ($cancelDeclined)
                            <div class="border-t border-line bg-sale-red/5 px-4 py-3 text-xs text-charcoal/70 sm:px-5">
                                <b class="text-sale-red">The seller declined your cancellation request.</b>
                                @if ($order->cancel_request_response)
                                    <span class="block">Reason: {{ $order->cancel_request_response }}</span>
                                @endif
                                <span class="block text-charcoal/60">Use Contact Seller if you still need help.</span>
                            </div>
                        @endif
                        {{-- ORDER DETAILS --}}
                        <div class="border-t border-line px-4 py-4 sm:px-5">
                            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                                <div>
                                    <p class="text-card-label">Order details</p>
                                    <p class="mt-1 text-xs text-charcoal/70">
                                        Order #{{ $order->id }} · {{ $order->created_at->format('M d, Y') }}
                                    </p>
                                </div>

                                <dl class="w-full space-y-1.5 text-xs text-charcoal/70 sm:w-72">
                                    <div class="flex justify-between gap-4">
                                        <dt>Merchandise Subtotal</dt>
                                        <dd class="tabular-nums">₱{{ number_format($order->total_amount, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt>Shipping Fee</dt>
                                        <dd class="tabular-nums">₱{{ number_format($order->shipping_fee, 2) }}</dd>
                                    </div>
                                    <div
                                        class="flex items-baseline justify-between gap-4 border-t border-line pt-2 text-sm text-charcoal">
                                        <dt>
                                            Order Total
                                            <span class="text-xs text-charcoal/60">
                                                ({{ $order->items->sum('quantity') }} item(s))
                                            </span>
                                        </dt>
                                        <dd class="text-base font-bold tabular-nums text-primary">
                                            ₱{{ number_format($order->grand_total, 2) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div
                            class="grid grid-cols-2 items-center gap-2 border-t border-line px-4 py-4 sm:flex sm:flex-wrap sm:justify-end sm:px-5">
                            @if ($isDelivered)
                                <p class="col-span-2 text-xs text-charcoal/70 sm:mr-auto sm:flex-1">
                                    Your parcel was delivered. Please confirm you received it in good condition.
                                    @if ($order->delivered_at)
                                        If you don't respond, it will be completed automatically on
                                        {{ $order->delivered_at->copy()->addDays(Order::AUTO_COMPLETE_DAYS)->format('M d') }}.
                                    @endif
                                </p>
                                <form method="POST" action="{{ route('buyer.orders.confirm', $order) }}"
                                    class="col-span-2 sm:col-span-1">
                                    @csrf
                                    <button type="submit" class="{{ $btnPrimary }}">
                                        Confirm Order Received
                                    </button>
                                </form>
                            @endif
                            @if ($isCompleted)
                                @if ($nextToRate)
                                    <button type="button" class="write-review-btn {{ $btnPrimary }}"
                                        data-action="{{ route('buyer.orders.review.store', [$order, $nextToRate]) }}"
                                        data-product-name="{{ $nextToRate->product_name }}">
                                        Rate{{ $unreviewed->count() > 1 ? ' (' . $unreviewed->count() . ')' : '' }}
                                    </button>
                                @else
                                    <span
                                        class="{{ $btnBase }} border border-leaf-green/40 bg-leaf-green/10 text-charcoal">
                                        <span class="mr-1 text-leaf-green">✓</span> Rated
                                    </span>
                                @endif
                            @endif

                            {{-- CANCEL ORDER BUTTON --}}
                            @if ($cancelMode)
                                <button type="button" class="cancel-order-btn {{ $btnDanger }}"
                                    data-action="{{ route('buyer.orders.cancel', $order) }}"
                                    data-mode="{{ $cancelMode }}" data-order-id="{{ $order->id }}">
                                    {{ $cancelMode === 'request' ? 'Request Cancellation' : 'Cancel Order' }}
                                </button>
                            @endif
                            @if ($canReturn)
                                <a href="{{ route('buyer.orders.return.create', $order) }}"
                                    class="{{ $btnDanger }}">
                                    Return / Refund
                                </a>
                            @endif
                            @if ($returnRecord)
                                <a href="{{ route('buyer.returns.show', $returnRecord) }}"
                                    class="{{ $btnOutline }} gap-2">
                                    View Return
                                    <span
                                        class="rounded-sm px-2 py-0.5 text-[10px] font-semibold {{ $returnRecord->status_color }}">
                                        {{ $returnRecord->status_label }}
                                    </span>
                                </a>
                            @endif
                            <button type="button" class="contact-seller-btn {{ $btnOutline }}"
                                data-open-url="{{ route('messages.open') }}"
                                data-messages-url="{{ route('messages.index') }}"
                                data-recipient-id="{{ $order->seller_id }}" data-product-id="{{ $chatProductId }}">
                                Contact Seller
                            </button>
                            @if ($canBuyAgain)
                                @if ($canReorder)
                                    <form method="POST" action="{{ route('buyer.orders.buy-again', $order) }}">
                                        @csrf
                                        <button type="submit" class="{{ $btnAccent }}">
                                            Buy Again
                                        </button>
                                    </form>
                                @else
                                    <span
                                        class="{{ $btnBase }} cursor-not-allowed border border-line bg-white text-charcoal/40">
                                        Unavailable
                                    </span>
                                @endif
                            @endif
                        </div>

                        {{-- TRACKING (bottom of the card) --}}
                        @if ($latest)
                            <div x-data="{ open: false }" class="border-t border-line px-4 py-3 sm:px-5">
                                <button type="button" @click="open = !open" :aria-expanded="open"
                                    class="flex w-full items-center justify-between gap-3 text-left">
                                    <span class="flex min-w-0 items-center gap-2 text-xs">
                                        <svg class="h-4 w-4 shrink-0 text-leaf-green" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM17 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                                        </svg>
                                        <span class="truncate font-semibold text-charcoal">
                                            {{ $latest['title'] }}
                                        </span>
                                        <span class="hidden shrink-0 text-charcoal/60 sm:inline">
                                            {{ $latest['created_at']->format('M d, g:i A') }}
                                        </span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold text-primary"
                                        x-text="open ? 'Hide' : 'Track'">Track</span>
                                </button>
                                <ol x-show="open" style="display: none;"
                                    class="mt-4 space-y-5 border-l-2 border-line pl-5">
                                    @foreach ($timeline as $event)
                                        <li class="relative">
                                            <span
                                                class="absolute -left-[1.7rem] top-1 h-3 w-3 rounded-full ring-4 ring-white {{ $loop->first ? 'bg-primary' : 'bg-charcoal/25' }}"></span>
                                            <p
                                                class="text-sm {{ $loop->first ? 'font-bold text-charcoal' : 'text-charcoal/70' }}">
                                                {{ $event['title'] }}
                                            </p>
                                            @if ($event['note'])
                                                <p class="text-xs text-charcoal/60">{{ $event['note'] }}</p>
                                            @endif
                                            <p class="text-[11px] text-charcoal/60">
                                                {{ $event['created_at']->format('M d, Y · g:i A') }}
                                            </p>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                        @if ($canReturn)
                            <div class="border-t border-line bg-ice-blue px-4 py-3 text-xs text-charcoal/70 sm:px-5">
                                Something wrong with your order? You can request a return or refund until
                                <b>{{ $order->returnWindowEndsAt()->format('M d') }}</b>.
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            {{-- PAGINATION --}}
            <div class="pagination-frost mt-6 flex justify-center overflow-x-auto">
                {{ $orders->onEachSide(1)->links() }}
            </div>
        @endif

    {{-- CANCEL ORDER MODAL --}}
    <div id="cancelModalOverlay" data-hours="{{ Order::CANCEL_REQUEST_AUTO_APPROVE_HOURS }}"
        class="fixed inset-0 z-[95] hidden items-end justify-center bg-charcoal/50 p-0 sm:items-center sm:p-4">
        <form id="cancelModalForm" method="POST" role="dialog" aria-modal="true" aria-label="Cancel order"
            class="max-h-[90vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-3xl sm:p-6">
            @csrf
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-sale-red">Cancel Order</p>
                    <p id="cancelModalOrder" class="mt-1 text-sm font-semibold text-charcoal">#</p>
                </div>
                <button type="button" id="cancelModalCloseBtn" aria-label="Close"
                    class="shrink-0 rounded-lg p-1.5 text-charcoal/50 transition-colors hover:bg-ice-blue hover:text-charcoal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p id="cancelModalHelp"
                class="mt-4 rounded-lg border border-line bg-ice-blue p-3 text-xs leading-relaxed text-charcoal/70">
            </p>
            <label for="cancelReason" class="mb-2 mt-5 block text-sm font-semibold text-charcoal">
                Reason <span class="text-sale-red">*</span>
            </label>
            <select id="cancelReason" name="reason" required
                class="frost-input w-full rounded-lg px-3.5 py-2.5 text-sm">
                <option value="" disabled selected>Select a reason…</option>
                @foreach (Order::BUYER_CANCEL_REASONS as $reason)
                    <option value="{{ $reason }}">{{ $reason }}</option>
                @endforeach
            </select>
            <label for="cancelNote" class="mb-2 mt-4 block text-sm font-semibold text-charcoal">
                Note <span class="font-normal text-charcoal/60">(optional)</span>
            </label>
            <textarea id="cancelNote" name="note" rows="3" maxlength="255"
                class="frost-input w-full resize-none rounded-lg px-3.5 py-2.5 text-sm"></textarea>
            <div class="mt-6 flex gap-3">
                <button type="button" id="cancelModalKeepBtn"
                    class="min-h-11 flex-1 rounded-lg border border-line px-4 py-3 text-sm font-bold text-charcoal transition-colors hover:bg-ice-blue">
                    Keep Order
                </button>
                <button type="submit" id="cancelModalSubmit"
                    class="min-h-11 flex-1 rounded-lg bg-sale-red px-4 py-3 text-sm font-bold text-white transition-colors hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-60">
                    Cancel Order
                </button>
            </div>
        </form>
    </div>

    {{-- WRITE A REVIEW MODAL --}}
    <div id="reviewModalOverlay"
        class="fixed inset-0 z-[95] hidden items-end justify-center bg-charcoal/50 p-0 sm:items-center sm:p-4">
        <div role="dialog" aria-modal="true" aria-label="Write a review"
            class="max-h-[90vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-3xl sm:p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Write a Review</p>
                    <p id="reviewModalProductName" class="mt-1 text-sm font-semibold text-charcoal">Product name</p>
                </div>
                <button type="button" id="reviewModalCloseBtn" aria-label="Close"
                    class="shrink-0 rounded-lg p-1.5 text-charcoal/50 transition-colors hover:bg-ice-blue hover:text-charcoal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="my-5 h-px bg-line"></div>
            <input type="hidden" id="reviewRatingInput">
            <p class="mb-2 text-sm font-semibold text-charcoal">Your Rating</p>
            <div class="flex gap-1.5">
                @for ($i = 0; $i < 5; $i++)
                    <button type="button" class="star-btn rounded-md p-0.5"
                        aria-label="Rate {{ $i + 1 }} out of 5">
                        <svg class="h-8 w-8" fill="none" stroke="#94A3B8" stroke-width="1.5" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </button>
                @endfor
            </div>
            <p class="mb-2 mt-5 text-sm font-semibold text-charcoal">Your Review</p>
            <textarea id="reviewComment" rows="4" maxlength="1000"
                placeholder="Share your experience with this product..."
                class="frost-input w-full resize-none rounded-lg px-3.5 py-2.5 text-sm"></textarea>
            <p class="mb-2 mt-5 text-sm font-semibold text-charcoal">
                Add Photos/Videos
                <span class="font-normal text-charcoal/60">(optional)</span>
            </p>
            <label for="reviewMediaInput"
                class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-line bg-ice-blue px-4 py-3 text-xs font-semibold text-charcoal/70 transition-colors hover:border-primary/50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M12 16V4m0 0L8 8m4-4l4 4M5 20h14" />
                </svg>
                Upload up to 5 photos or videos
                <input type="file" id="reviewMediaInput" multiple accept="image/*,video/*" class="sr-only">
            </label>
            <div id="reviewMediaPreview" class="mt-3 grid grid-cols-5 gap-2"></div>
            <p id="reviewError"
                class="mt-4 hidden rounded-lg bg-sale-red/10 px-3 py-2 text-xs font-semibold text-sale-red"></p>
            <button type="button" id="reviewSubmitBtn"
                class="mt-6 min-h-12 w-full rounded-lg bg-primary px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60">
                Submit Review
            </button>
        </div>
    </div>

    {{-- Footer and Message Widget --}}

</div>
