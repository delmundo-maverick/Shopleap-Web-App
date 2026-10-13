<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/orders.js'])

    <title>My Orders | Shopleap</title>
</head>

@use('App\Models\Order')
@use('Illuminate\Support\Str')

<body class="min-h-screen bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    <main class="mx-auto max-w-5xl px-5 py-6 sm:px-8">

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                {{ session('status') }}
            </div>
        @endif

        @if ($tab === 'all')
            <form method="GET" class="mb-5">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by order # or shop name..."
                        class="w-full rounded-xl border border-light-gray bg-white py-3 pl-10 pr-4 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>
            </form>
        @endif

        <!-- TABS -->
        <div class="mb-5 rounded-lg bg-white shadow-sm shadow-charcoal/5">
            <div class="flex">
                @foreach (Order::TAB_LABELS as $key => $label)
                    <a href="{{ route('buyer.orders.index', $key === 'all' ? [] : ['tab' => $key]) }}"
                        class="flex flex-1 items-center justify-center gap-1.5 border-b-2 px-3 py-3.5 text-center text-xs font-semibold transition sm:text-sm
                            {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/50 hover:text-charcoal' }}">
                        {{ $label }}
                        @if (($counts[$key] ?? 0) > 0)
                            <span
                                class="rounded-full px-1.5 py-0.5 text-[10px] font-bold
                                {{ $tab === $key ? 'bg-primary/10 text-primary' : 'bg-light-gray text-charcoal/50' }}">
                                {{ $counts[$key] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        @if ($orders->isEmpty())

            <div
                class="flex flex-col items-center justify-center rounded-lg bg-white p-16 text-center shadow-sm shadow-charcoal/5">
                <svg class="h-14 w-14 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="mt-4 text-sm font-semibold text-charcoal">
                    {{ $tab === 'all' ? "You haven't placed any orders yet" : 'No orders in this category' }}
                </p>
                <a href="{{ route('buyer.home') }}"
                    class="mt-5 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                    Start Shopping
                </a>
            </div>
        @else
            <div class="space-y-4">
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

                        $timeline = $order->events->whereNotNull('status')->sortByDesc('id')->values();
                        $latest = $timeline->first();
                    @endphp

                    <div class="overflow-hidden rounded-lg bg-white shadow-sm shadow-charcoal/5">

                        <!-- SHOP HEADER -->
                        <div class="flex items-center justify-between gap-3 border-b border-light-gray px-5 py-3.5">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M3 9l1.5-5h15L21 9M3 9v1a3 3 0 006 0 3 3 0 006 0 3 3 0 006 0V9M3 9h18M5 13v7h14v-7" />
                                </svg>
                                <p class="truncate text-sm font-bold text-charcoal">{{ $shopName }}</p>
                                <a href="{{ route('buyer.shop.show', $order->seller_id) }}"
                                    class="shrink-0 rounded-md border border-charcoal/15 px-2.5 py-1 text-[11px] font-semibold text-charcoal/70 transition hover:border-primary hover:text-primary">
                                    View Shop
                                </a>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $order->status_color }}">
                                {{ $order->status_label }}
                            </span>
                        </div>

                        <!-- ITEMS -->
                        <div class="divide-y divide-light-gray">
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

                                <div class="flex gap-4 px-5 py-4">
                                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                                        @if ($item->product && $item->product->main_image_url)
                                            <img src="{{ $item->product->main_image_url }}"
                                                alt="{{ $item->product_name }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-semibold text-charcoal">
                                            {{ $item->product_name }}</p>
                                        @if ($options !== '')
                                            <p class="mt-0.5 text-xs text-charcoal/50">{{ $options }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-charcoal/50">x{{ $item->quantity }}</p>
                                        @if ($isCompleted && $item->review)
                                            <span
                                                class="mt-1.5 inline-block rounded-md bg-leaf-green/10 px-2 py-0.5 text-[11px] font-bold text-leaf-green">
                                                ✓ Reviewed
                                            </span>
                                        @endif
                                    </div>

                                    <p class="shrink-0 text-sm font-semibold text-charcoal">
                                        ₱{{ number_format($item->price_at_purchase, 2) }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <!-- TOTAL -->
                        <div class="flex items-center justify-between gap-3 border-t border-light-gray px-5 py-3.5">
                            <span class="text-xs text-charcoal/50">
                                Order #{{ $order->id }} · {{ $order->created_at->format('M d, Y') }}
                            </span>
                            <span class="text-sm text-charcoal">
                                {{ $order->items->sum('quantity') }} item(s) ·
                                Order Total: <span
                                    class="text-base font-bold text-primary">₱{{ number_format($order->total_amount, 2) }}</span>
                            </span>
                        </div>

                        <!-- TRACKING -->
                        @if ($latest)
                            <div x-data="{ open: false }" class="border-t border-light-gray px-5 py-3">
                                <button type="button" @click="open = !open"
                                    class="flex w-full items-center justify-between gap-3 text-left">
                                    <span class="flex min-w-0 items-center gap-2 text-xs">
                                        <svg class="h-4 w-4 shrink-0 text-leaf-green" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM17 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                                        </svg>
                                        <span class="truncate font-semibold text-leaf-green">{{ $latest->title }}</span>
                                        <span
                                            class="hidden shrink-0 text-charcoal/40 sm:inline">{{ $latest->created_at->format('M d, g:i A') }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold text-primary"
                                        x-text="open ? 'Hide' : 'Details'">Details</span>
                                </button>

                                <ol x-show="open" style="display: none;"
                                    class="mt-4 space-y-5 border-l-2 border-light-gray pl-5">
                                    @foreach ($timeline as $event)
                                        <li class="relative">
                                            <span
                                                class="absolute -left-[1.7rem] top-1 h-3 w-3 rounded-full ring-4 ring-white {{ $loop->first ? 'bg-primary' : 'bg-charcoal/25' }}"></span>
                                            <p
                                                class="text-sm {{ $loop->first ? 'font-bold text-charcoal' : 'text-charcoal/70' }}">
                                                {{ $event->title }}</p>
                                            @if ($event->note)
                                                <p class="text-xs text-charcoal/50">{{ $event->note }}</p>
                                            @endif
                                            <p class="text-[11px] text-charcoal/40">
                                                {{ $event->created_at->format('M d, Y · g:i A') }}</p>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif

                        <!-- ACTIONS -->
                        <div
                            class="flex flex-wrap items-center justify-end gap-2 border-t border-light-gray bg-ice-blue/30 px-5 py-4">

                            @if ($isDelivered)
                                <p class="mr-auto text-xs text-charcoal/60">
                                    Your parcel was delivered. Please confirm you received it in good condition.
                                </p>

                                <form method="POST" action="{{ route('buyer.orders.confirm', $order) }}">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-lg bg-primary px-5 py-2 text-xs font-bold text-white transition hover:bg-sky-blue">
                                        Confirm Order Received
                                    </button>
                                </form>
                            @endif

                            @if ($isCompleted)
                                @if ($nextToRate)
                                    <button type="button"
                                        class="write-review-btn rounded-lg bg-primary px-5 py-2 text-xs font-bold text-white transition hover:bg-sky-blue"
                                        data-action="{{ route('buyer.orders.review.store', [$order, $nextToRate]) }}"
                                        data-product-name="{{ $nextToRate->product_name }}">
                                        Rate{{ $unreviewed->count() > 1 ? ' (' . $unreviewed->count() . ')' : '' }}
                                    </button>
                                @else
                                    <span
                                        class="rounded-lg bg-leaf-green/10 px-5 py-2 text-xs font-bold text-leaf-green">
                                        ✓ Rated
                                    </span>
                                @endif
                            @endif

                            <button type="button"
                                class="contact-seller-btn rounded-lg border border-charcoal/20 bg-white px-5 py-2 text-xs font-bold text-charcoal transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-60"
                                data-open-url="{{ route('messages.open') }}"
                                data-messages-url="{{ route('messages.index') }}"
                                data-recipient-id="{{ $order->seller_id }}"
                                data-product-id="{{ $chatProductId }}">
                                Contact Seller
                            </button>

                            @if ($canBuyAgain)
                                @if ($canReorder)
                                    <form method="POST" action="{{ route('buyer.orders.buy-again', $order) }}">
                                        @csrf
                                        <button type="submit"
                                            class="rounded-lg border border-primary bg-white px-5 py-2 text-xs font-bold text-primary transition hover:bg-primary/5">
                                            Buy Again
                                        </button>
                                    </form>
                                @else
                                    <span
                                        class="cursor-not-allowed rounded-lg border border-charcoal/10 bg-white px-5 py-2 text-xs font-bold text-charcoal/30">
                                        Unavailable
                                    </span>
                                @endif
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $orders->onEachSide(1)->links() }}</div>

        @endif

    </main>


    <!-- WRITE A REVIEW MODAL -->
    <div id="reviewModalOverlay"
        class="fixed inset-0 z-[95] hidden items-end justify-center bg-charcoal/50 p-0 sm:items-center sm:p-4">
        <div
            class="max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-2xl sm:max-w-md sm:rounded-2xl">

            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Write a Review</p>
                    <p id="reviewModalProductName" class="mt-1 text-sm font-semibold text-charcoal">Product name</p>
                </div>
                <button type="button" id="reviewModalCloseBtn"
                    class="shrink-0 rounded-full p-1.5 text-charcoal/40 transition hover:bg-ice-blue hover:text-charcoal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="my-5 h-px bg-light-gray"></div>

            <input type="hidden" id="reviewRatingInput">

            <p class="mb-2 text-sm font-semibold text-charcoal">Your Rating</p>
            <div class="flex gap-1.5">
                @for ($i = 0; $i < 5; $i++)
                    <button type="button" class="star-btn rounded p-0.5">
                        <svg class="h-8 w-8" fill="none" stroke="#94A3B8" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </button>
                @endfor
            </div>

            <p class="mb-2 mt-5 text-sm font-semibold text-charcoal">Your Review</p>
            <textarea id="reviewComment" rows="4" maxlength="1000"
                placeholder="Share your experience with this product..."
                class="w-full resize-none rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"></textarea>

            <p class="mb-2 mt-5 text-sm font-semibold text-charcoal">Add Photos/Videos <span
                    class="font-normal text-charcoal/40">(optional)</span></p>
            <label for="reviewMediaInput"
                class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-light-gray bg-ice-blue/40 px-4 py-3 text-xs font-semibold text-charcoal/60 transition hover:border-primary/40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="mt-6 w-full rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-60">
                Submit Review
            </button>

        </div>
    </div>
    <x-Buyer.message-widget />
</body>

</html>
