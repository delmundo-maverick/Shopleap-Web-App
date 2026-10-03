<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>System Activity Logs | Shopleap Admin</title>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    @php
        $categoryColors = [
            'User Management' => 'bg-blue-100 text-blue-600',
            'Orders' => 'bg-primary/10 text-primary',
            'Logistics' => 'bg-leaf-green/10 text-leaf-green',
            'Finance' => 'bg-orange-100 text-orange-600',
        ];
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.admin active="logs" />

        <div class="flex flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">System Activity Logs</h2>
                        <p class="text-sm text-charcoal/55">{{ $logs->total() }} event(s) recorded</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 space-y-5 p-6">

                <form method="GET" class="frost-panel flex flex-wrap items-end gap-3 rounded-2xl p-4">
                    <div class="min-w-[14rem] flex-1">
                        <label
                            class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Description, actor, or action…"
                            class="frost-input w-full rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">Category</label>
                        <select name="category"
                            class="frost-input rounded-xl px-3.5 py-2.5 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                            <option value="all">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}"
                                    {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">From</label>
                        <input type="date" name="from" value="{{ request('from') }}"
                            class="frost-input rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">To</label>
                        <input type="date" name="to" value="{{ request('to') }}"
                            class="frost-input rounded-xl px-3 py-2.5 text-sm outline-none focus:ring-4 focus:ring-primary/10">
                    </div>
                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">Filter</button>
                </form>

                <div class="frost-panel-solid overflow-hidden rounded-2xl">
                    @if ($logs->isEmpty())
                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <p class="text-sm font-semibold text-charcoal">No activity recorded yet</p>
                            <p class="mt-1 text-xs text-charcoal/50">Platform events — approvals, order changes, rider
                                management — will appear here as they happen.</p>
                        </div>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-white/50 bg-primary/5 text-charcoal/60">
                                <tr>
                                    <th class="px-5 py-3 font-semibold">Event</th>
                                    <th class="px-5 py-3 font-semibold">Category</th>
                                    <th class="px-5 py-3 font-semibold">Actor</th>
                                    <th class="px-5 py-3 font-semibold">When</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/50">
                                @foreach ($logs as $log)
                                    <tr class="transition hover:bg-white/40">
                                        <td class="px-5 py-4">
                                            <p class="font-medium text-charcoal">{{ $log->description }}</p>
                                            <p class="text-xs text-charcoal/40">{{ $log->action }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $categoryColors[$log->category] ?? 'bg-light-gray text-charcoal/60' }}">
                                                {{ $log->category }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-charcoal/70">
                                            {{ $log->actor_name ?? 'System' }}
                                            @if ($log->actor_role)
                                                <span
                                                    class="block text-xs capitalize text-charcoal/40">{{ str_replace('_', ' ', $log->actor_role) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-charcoal/50">
                                            {{ $log->created_at->format('M d, Y g:i A') }}
                                            <span
                                                class="block text-xs text-charcoal/35">{{ $log->created_at->diffForHumans() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="p-4">{{ $logs->onEachSide(1)->links() }}</div>
                    @endif
                </div>

            </main>
        </div>
    </div>

</body>

</html>
