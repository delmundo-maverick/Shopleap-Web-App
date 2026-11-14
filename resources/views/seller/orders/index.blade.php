@php
    // Mobile: each row becomes a card, each cell shows its label (from data-label).
    // md+: normal table. Labels are CSS pseudo-elements, so no extra markup is needed.
    $cell =
        'flex items-center justify-between gap-3 px-1 py-1.5 before:text-xs before:font-medium before:text-charcoal/50 before:content-[attr(data-label)] md:table-cell md:px-5 md:py-4 md:before:hidden';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Orders | Shopleap Seller</title>
</head>

<body class="min-h-screen overflow-x-hidden text-charcoal">

    <div class="frost-surface pointer-events-none" aria-hidden="true"></div>

    <div class="flex min-h-screen">

        <x-navbars.seller :active="request('status') === 'prepare' ? 'prepare' : 'orders'" />

        <div class="flex min-w-0 flex-1 flex-col">

            <header
                class="frost-panel-solid sticky top-0 z-10 flex h-16 items-center justify-between gap-3 border-b border-white/50 px-4 sm:h-20 sm:px-6">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <x-nav.toggle target="workspace-navigation" />

                    <div class="min-w-0">
                        <h2 class="truncate text-base font-bold text-charcoal sm:text-xl">Orders</h2>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">{{ $orders->total() }} order(s) total
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-4">
                    <x-notification-bell />
                </div>
            </header>

            <main class="min-w-0 flex-1 space-y-4 p-4 sm:space-y-5 sm:p-6">

                {{-- CANCELLATION REQUEST NOTICE --}}
                @if (($pendingCancelRequests ?? 0) > 0 && ($status ?? 'all') !== 'cancel_requests')
                    <a href="{{ route('seller.orders.index', ['status' => 'cancel_requests']) }}"
                        class="frost-panel flex items-center justify-between gap-3 rounded-2xl border border-warning/30 px-4 py-3 text-sm text-charcoal transition hover:bg-white/60">
                        <span>
                            <b>{{ $pendingCancelRequests }}</b> cancellation request(s) waiting for your response.
                        </span>
                        <span class="shrink-0 text-xs font-semibold text-primary">View</span>
                    </a>
                @endif

                <!-- SEARCH & FILTER -->
                <form method="GET" role="search"
                    class="frost-panel grid grid-cols-2 gap-3 rounded-2xl p-4 sm:flex sm:items-center">

                    <div class="relative col-span-2 sm:flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <input type="search" name="search" value="{{ request('search') }}"
                            placeholder="Search by order # or buyer name..." aria-label="Search orders"
                            class="frost-input w-full rounded-xl py-2.5 pl-10 pr-4 text-base outline-none transition focus:ring-4 focus:ring-primary/10 sm:text-sm">
                    </div>

                    <select name="status" onchange="this.form.submit()" aria-label="Filter by status"
                        class="frost-input min-w-0 rounded-xl px-3.5 py-2.5 text-base outline-none transition focus:ring-4 focus:ring-primary/10 sm:w-auto sm:text-sm">

                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>
                            All Status
                        </option>

                        {{-- CANCELLATION REQUEST FILTER --}}
                        <option value="cancel_requests" {{ request('status') === 'cancel_requests' ? 'selected' : '' }}>
                            Cancellation requests
                        </option>

                        @foreach (\App\Models\Order::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
                        Search
                    </button>
                </form>

                <!-- ORDER LIST (cards on mobile, table from md) -->
                <div class="frost-panel-solid overflow-hidden rounded-2xl">

                    @if ($orders->isEmpty())

                        <div class="flex h-64 flex-col items-center justify-center px-4 text-center">
                            <p class="text-sm font-semibold text-charcoal">No orders found</p>
                            <p class="mt-1 text-xs text-charcoal/50">
                                New orders from buyers will appear here.
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="app-table block w-full text-left text-sm md:table">
                                <thead class="hidden md:table-header-group">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Order</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Buyer</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Items</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Total</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Placed</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60"><span
                                                class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="block space-y-3 p-3 md:table-row-group md:space-y-0 md:p-0">
                                    @foreach ($orders as $order)
                                        <tr
                                            class="block rounded-xl border border-white/50 bg-white/30 p-3 transition hover:bg-white/40 md:table-row md:rounded-none md:border-0 md:bg-transparent md:p-0">

                                            <td
                                                class="block px-1 pb-1 text-base font-semibold text-charcoal md:table-cell md:px-5 md:py-4 md:text-sm">
                                                #{{ $order->order_number }}
                                            </td>

                                            <td data-label="Buyer" class="{{ $cell }} text-charcoal/80">
                                                <span class="min-w-0 truncate">{{ $order->buyer_name }}</span>
                                            </td>

                                            <td data-label="Items" class="{{ $cell }} text-charcoal/70">
                                                {{ $order->items->sum('quantity') }} item(s)
                                            </td>

                                            <td data-label="Total"
                                                class="{{ $cell }} font-semibold text-charcoal">
                                                ₱{{ number_format($order->total_amount, 2) }}
                                            </td>

                                            <td data-label="Status" class="{{ $cell }}">
                                                <div
                                                    class="flex flex-wrap items-center justify-end gap-1 md:justify-start">
                                                    <span
                                                        class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $order->status_color }}">
                                                        {{ $order->status_label }}
                                                    </span>

                                                    {{-- PENDING CANCELLATION REQUEST CHIP --}}
                                                    @if (
                                                        $order->cancel_request_status === 'pending' &&
                                                            in_array($order->status, \App\Models\Order::BUYER_CANCEL_REQUEST_FROM, true))
                                                        <span
                                                            class="inline-block rounded-full bg-warning/10 px-2 py-0.5 text-[10px] font-bold text-warning">
                                                            Cancel requested
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td data-label="Placed" class="{{ $cell }} text-charcoal/60">
                                                {{ $order->created_at->diffForHumans() }}
                                            </td>

                                            <td class="block pt-2 md:table-cell md:px-5 md:py-4 md:pt-4 md:text-right">
                                                <a href="{{ route('seller.orders.show', $order) }}"
                                                    class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 py-2.5 text-xs font-semibold text-white transition hover:bg-sky-blue md:inline-flex md:w-auto md:py-2">
                                                    View
                                                </a>
                                                @if ($order->canViewWaybill())
                                                    <a href="{{ route('seller.orders.waybill.view', $order) }}" target="_blank" rel="noopener"
                                                        class="mt-2 flex w-full items-center justify-center rounded-md border border-primary bg-surface px-3.5 py-2 text-xs font-semibold text-primary md:inline-flex md:w-auto">
                                                        View Waybill
                                                    </a>
                                                @endif
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @endif

                </div>

                <div class="overflow-x-auto">{{ $orders->links() }}</div>

            </main>

        </div>

    </div>

</body>

</html>
