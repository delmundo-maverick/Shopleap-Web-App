    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <title>Returns &amp; Refunds | Shopleap Admin</title>
    </head>

    <body class="min-h-screen text-charcoal" x-data="{}">
        <div class="frost-surface"></div>

        @php
            $tabs = [
                'disputes' => 'Disputes',
                'refunds' => 'Refunds to pay',
                'open' => 'In progress',
                'closed' => 'Closed',
            ];
        @endphp

        <div class="flex min-h-screen">
            <x-navbars.admin active="returns" />

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="$store.sidebar.open = true" aria-label="Open menu"
                            class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-xl font-bold text-charcoal">Returns &amp; Refunds</h1>
                            <p class="text-sm text-charcoal/55">{{ $returns->total() }} case(s) in this view</p>
                        </div>
                    </div>
                    <x-notification-bell />
                </header>

                <main class="flex-1 space-y-5 p-6">
                    @if (session('status'))
                        <div
                            class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="frost-panel-solid flex overflow-x-auto rounded-2xl">
                        @foreach ($tabs as $key => $label)
                            <a href="{{ route('admin.returns.index', ['tab' => $key]) }}"
                                class="flex min-w-max flex-1 items-center justify-center gap-1.5 border-b-2 px-4 py-3.5 text-sm font-semibold transition
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

                    <form method="GET" class="frost-panel flex gap-3 rounded-2xl p-4">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by order #, buyer or shop…"
                            class="frost-input flex-1 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                        <button type="submit"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
                            Search
                        </button>
                    </form>

                    <div class="frost-panel-solid overflow-hidden rounded-2xl">
                        @if ($returns->isEmpty())
                            <div class="flex h-56 flex-col items-center justify-center text-center">
                                <p class="text-sm font-semibold text-charcoal">Nothing here</p>
                                <p class="mt-1 text-xs text-charcoal/50">
                                    {{ $tab === 'disputes' ? 'Cases that need a Shopleap decision appear here.' : ($tab === 'refunds' ? 'Approved refunds waiting to be paid appear here.' : 'No cases in this view.') }}
                                </p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b border-white/50 bg-primary/5">
                                        <tr>
                                            <th class="px-5 py-3 font-semibold text-charcoal/60">Case</th>
                                            <th class="px-5 py-3 font-semibold text-charcoal/60">Order</th>
                                            <th class="px-5 py-3 font-semibold text-charcoal/60">Buyer</th>
                                            <th class="px-5 py-3 font-semibold text-charcoal/60">Shop</th>
                                            <th class="px-5 py-3 font-semibold text-charcoal/60">Reason</th>
                                            <th class="px-5 py-3 font-semibold text-charcoal/60">Refund</th>
                                            <th class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                            <th class="px-5 py-3 font-semibold text-charcoal/60">Waiting</th>
                                            <th class="px-5 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/50">
                                        @foreach ($returns as $return)
                                            <tr class="transition hover:bg-white/40">
                                                <td class="px-5 py-4 font-semibold">#{{ $return->id }}</td>
                                                <td class="px-5 py-4">#{{ $return->order_id }}</td>
                                                <td class="px-5 py-4 text-charcoal/80">{{ $return->order->buyer_name }}
                                                </td>
                                                <td class="px-5 py-4 text-charcoal/80">
                                                    {{ $return->seller?->sellerProfile?->business_name ?? ($return->seller?->name ?? '—') }}
                                                </td>
                                                <td class="px-5 py-4 text-charcoal/70">{{ $return->reason_label }}</td>
                                                <td class="px-5 py-4 font-semibold">
                                                    ₱{{ number_format($return->refund_amount, 2) }}</td>
                                                <td class="px-5 py-4">
                                                    <span
                                                        class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $return->status_color }}">
                                                        {{ $return->status_label }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-4 text-xs text-charcoal/60">
                                                    @if ($tab === 'disputes')
                                                        {{ ($return->escalated_at ?? $return->updated_at)->diffForHumans() }}
                                                    @elseif ($tab === 'refunds')
                                                        {{ $return->updated_at->diffForHumans() }}
                                                    @else
                                                        {{ $return->created_at->format('M d, Y') }}
                                                    @endif
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <a href="{{ route('admin.returns.show', $return) }}"
                                                        class="inline-flex rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-blue">
                                                        Open
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div>{{ $returns->links() }}</div>
                </main>
            </div>
        </div>
    </body>

    </html>
