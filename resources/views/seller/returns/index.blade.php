@php
    $tabs = ['needs_action' => 'Needs action', 'in_progress' => 'In progress', 'closed' => 'Closed'];

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

    <title>Returns &amp; Refunds | Shopleap Seller</title>
</head>

<body class="min-h-screen overflow-x-hidden text-charcoal">
    <div class="frost-surface pointer-events-none" aria-hidden="true"></div>

    <div class="flex min-h-screen">
        <x-navbars.seller active="returns" />

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-10 flex h-16 items-center justify-between gap-3 border-b border-white/50 px-4 sm:h-20 sm:px-6">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <x-nav.toggle target="workspace-navigation" />
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-bold text-charcoal sm:text-xl">Returns &amp; Refunds</h1>
                        <p class="truncate text-xs text-charcoal/55 sm:text-sm">{{ $returns->total() }} request(s) in
                            this view</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-4">
                    <x-notification-bell />
                </div>
            </header>

            <main class="min-w-0 flex-1 space-y-4 p-4 sm:space-y-5 sm:p-6">
                @if (session('status'))
                    <div role="status"
                        class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- TABS: scroll sideways if they ever outgrow the screen --}}
                <nav aria-label="Return status" class="frost-panel-solid overflow-x-auto rounded-2xl">
                    <div class="flex min-w-max sm:min-w-0">
                        @foreach ($tabs as $key => $label)
                            <a href="{{ route('seller.returns.index', ['tab' => $key]) }}"
                                @if ($tab === $key) aria-current="page" @endif
                                class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-3.5 text-xs font-semibold transition sm:text-sm
                                    {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/50 hover:text-charcoal' }}">
                                {{ $label }}
                                @if ($counts[$key] > 0)
                                    <span
                                        class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $tab === $key ? 'bg-primary/10 text-primary' : 'bg-light-gray text-charcoal/50' }}">
                                        {{ $counts[$key] }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </nav>

                <!-- RETURN LIST (cards on mobile, table from md) -->
                <div class="frost-panel-solid overflow-hidden rounded-2xl">
                    @if ($returns->isEmpty())
                        <div class="flex h-56 flex-col items-center justify-center px-4 text-center">
                            <p class="text-sm font-semibold text-charcoal">Nothing here</p>
                            <p class="mt-1 text-xs text-charcoal/50">Return and refund requests from buyers appear here.
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="app-table block w-full text-left text-sm md:table">
                                <thead class="hidden md:table-header-group">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Order</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Reason</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Type</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Refund</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                        <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Answer by
                                        </th>
                                        <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="block space-y-3 p-3 md:table-row-group md:space-y-0 md:p-0">
                                    @foreach ($returns as $return)
                                        <tr
                                            class="block rounded-xl border border-white/50 bg-white/30 p-3 transition hover:bg-white/40 md:table-row md:rounded-none md:border-0 md:bg-transparent md:p-0">
                                            <td
                                                class="block px-1 pb-1 text-base font-semibold md:table-cell md:px-5 md:py-4 md:text-sm">
                                                #{{ $return->order?->order_number }}</td>
                                            <td data-label="Reason" class="{{ $cell }} text-charcoal/80">
                                                <span
                                                    class="min-w-0 text-right md:text-left">{{ $return->reason_label }}</span>
                                            </td>
                                            <td data-label="Type" class="{{ $cell }} text-charcoal/70">
                                                {{ $return->type_label }}</td>
                                            <td data-label="Refund" class="{{ $cell }} font-semibold">
                                                ₱{{ number_format($return->refund_amount, 2) }}</td>
                                            <td data-label="Status" class="{{ $cell }}">
                                                <span
                                                    class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $return->status_color }}">
                                                    {{ $return->status_label }}
                                                </span>
                                            </td>
                                            <td data-label="Answer by"
                                                class="{{ $cell }} text-xs text-charcoal/60">
                                                @if ($return->status === \App\Models\OrderReturn::STATUS_REQUESTED)
                                                    {{ $return->response_due_at?->format('M d, g:i A') }}
                                                @elseif ($return->status === \App\Models\OrderReturn::STATUS_SHIPPED_BACK)
                                                    {{ $return->receipt_due_at?->format('M d, g:i A') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="block pt-2 md:table-cell md:px-5 md:py-4 md:pt-4 md:text-right">
                                                <a href="{{ route('seller.returns.show', $return) }}"
                                                    class="flex w-full justify-center rounded-lg bg-primary px-3.5 py-2.5 text-xs font-semibold text-white transition hover:bg-sky-blue md:inline-flex md:w-auto md:py-2">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto">{{ $returns->links() }}</div>
            </main>
        </div>
    </div>
</body>

</html>
