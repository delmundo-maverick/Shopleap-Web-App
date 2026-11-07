<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard | Shopleap Logistics</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Shopleap-Logo.png') }}">
    @vite(['resources/css/app.css', 'resources/css/pages/logistics/dashboard.css', 'resources/js/app.js', 'resources/js/pages/logistics/dashboard.js'])
</head>
<body class="logistics-dashboard min-h-screen text-charcoal" data-chart-data="{{ json_encode($chartData) }}">
    <div class="frost-surface pointer-events-none" aria-hidden="true"></div>
    <div class="flex min-h-screen">
        <x-navbars.logistics active="dashboard" />
        <div class="min-w-0 flex-1">
            <x-seller.page-header :title="$hub->business_name" :description="'Logistics dashboard · '.$now->format('l, F j, Y').' · '.config('app.timezone')"><x-notification-bell /></x-seller.page-header>
            <main class="min-w-0 space-y-5 p-4 sm:space-y-6 sm:p-6">
                <section aria-labelledby="attention-title" class="rounded-2xl border border-warning/30 bg-warning/5 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="attention-title" class="flex items-center gap-2 text-sm font-semibold"><x-nav.icon name="reports" class="text-warning" />Needs attention</h2>
                        <span class="text-xs text-charcoal/60">Live across all dates</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
                        @foreach ($attention as $item)
                            <a href="{{ route('logistics.dashboard', ['range' => $range, 'queue' => $item['queue']]) }}#work-queue" class="rounded-xl border border-warning/20 bg-surface p-3 transition hover:border-warning/60">
                                <span class="block text-xl font-semibold tabular-nums">{{ number_format($item['value']) }}</span><span class="text-xs text-charcoal/65">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                    @if (array_sum(array_column($attention, 'value')) === 0)<p class="mt-3 text-sm text-charcoal/60">All clear. No stale parcels, outstanding inbound bags, or failed/returned deliveries.</p>@endif
                </section>

                <x-announcement-banner audience="logistics" />
                @unless ($hasServiceAreas)
                    <div class="rounded-xl border border-warning/25 bg-warning/5 p-4 text-sm">
                        Service areas have not been configured for {{ $hub->municipality }}, {{ $hub->province }}.
                        <a class="font-semibold text-primary underline" href="{{ route('logistics.areas.index') }}">Set up service areas</a>
                    </div>
                @endunless

                <section aria-labelledby="overview-title" class="space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div><h2 id="overview-title" class="text-lg font-semibold">Operations overview</h2><p class="mt-1 text-xs text-charcoal/60">Queues show what is waiting now. The date range controls recorded activity and deliveries.</p></div>
                        <form method="GET" action="{{ route('logistics.dashboard') }}" data-dashboard-range class="flex rounded-xl border border-line bg-surface p-1" aria-label="Dashboard date range">
                            <input type="hidden" name="queue" value="{{ $queue }}">
                            @foreach (['today' => 'Today', '7d' => '7 days', '30d' => '30 days'] as $value => $label)
                                <button type="submit" name="range" value="{{ $value }}" aria-pressed="{{ $range === $value ? 'true' : 'false' }}" @class(['min-h-10 rounded-lg px-4 text-sm font-semibold transition', 'bg-primary text-white' => $range === $value, 'text-charcoal/65 hover:bg-primary/10' => $range !== $value])>{{ $label }}</button>
                            @endforeach
                        </form>
                    </div>
                    <p data-dashboard-loading role="status" hidden class="text-sm text-primary">Updating dashboard…</p>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($cards as $card)
                            <x-seller.stat-card :label="$card['label']" :value="$card['value']" :href="$card['href']" :icon="$card['icon']"
                                :hint="$card['hint']" :activity="$card['activity']" :trend="$card['trend']" :trend-direction="$card['trend_direction']" />
                        @endforeach
                    </div>
                    <p class="text-xs text-charcoal/55">Trends compare recorded activity today with yesterday, not historical queue sizes. Today is still in progress.</p>
                </section>

                <div class="grid items-start gap-5 lg:grid-cols-2">
                    <section aria-labelledby="session-title" class="frost-panel rounded-2xl p-4 sm:p-5">
                        <div class="flex items-center gap-2"><x-nav.icon name="sorting" class="text-primary" /><h2 id="session-title" class="text-card-title">Active sorting session</h2></div>
                        @if ($activeSession)
                            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                                <div><p class="text-xs text-charcoal/60">Started</p><time datetime="{{ $activeSession->started_at->toIso8601String() }}" class="mt-1 block text-sm font-semibold">{{ $activeSession->started_at->format('M j, g:i A') }}</time></div>
                                <div><p class="text-xs text-charcoal/60">Total scans</p><p class="mt-1 text-xl font-semibold tabular-nums">{{ number_format($activeSession->scans_count) }}</p></div>
                                <div><p class="text-xs text-charcoal/60">Sorted / needs review</p><p class="mt-1 text-xl font-semibold tabular-nums">{{ number_format($activeSession->sorted_count) }} / {{ number_format($activeSession->review_count) }}</p></div>
                            </div>
                            <a class="dashboard-link dashboard-link-primary mt-4" href="{{ route('logistics.sorting.index') }}">Resume sorting <span aria-hidden="true">&rarr;</span></a>
                        @else
                            <x-seller.empty-state title="No sorting session running" description="Open Sort & Assign to start a session."><a class="dashboard-link" href="{{ route('logistics.sorting.index') }}">Open Sort &amp; Assign</a></x-seller.empty-state>
                        @endif
                    </section>
                    <section aria-labelledby="quick-actions-title" class="frost-panel rounded-2xl p-4 sm:p-5">
                        <h2 id="quick-actions-title" class="text-card-title">Quick actions</h2><p class="mt-1 text-card-hint">Go straight to your hub's workspaces.</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a class="dashboard-link dashboard-link-primary" href="{{ route('logistics.sorting.index') }}"><x-nav.icon name="sorting" />Sort &amp; Assign</a>
                            <a class="dashboard-link" href="{{ route('logistics.inbound.index') }}"><x-nav.icon name="inbound" />Inbound</a>
                            <a class="dashboard-link" href="{{ route('logistics.outbound.index') }}"><x-nav.icon name="outbound" />Outbound</a>
                        </div>
                        <div class="mt-5 border-t border-line pt-4"><p class="text-xs text-charcoal/60">{{ $periodLabel }} · unique parcels handled</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($handledTotal) }}</p>@if ($handledTotal === 0)<p class="mt-1 text-xs text-charcoal/55">No handling activity recorded in this range.</p>@endif</div>
                    </section>
                </div>

                <section aria-labelledby="activity-title" class="space-y-4">
                    <div><h2 id="activity-title" class="text-lg font-semibold">Parcel activity</h2><p class="mt-1 text-xs text-charcoal/60">{{ $from->format('M j') }}–{{ $now->format('M j, Y') }} · {{ config('app.timezone') }} · recorded hub activity</p></div>
                    <p data-dashboard-chart-error hidden role="alert" class="rounded-xl border border-danger/25 bg-danger/5 p-3 text-sm text-danger">Charts could not load. The data tables below are still available. Reload to try again.</p>
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                        <article class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                            <h3 class="text-card-title">Parcels handled per day</h3><p class="mt-1 text-card-hint">Each parcel counted once per day; automatic stale flags are excluded.</p>
                            @if (array_sum($chartData['daily']['values']) > 0)
                                <div class="dashboard-chart mt-4"><canvas id="logistics-daily-chart" role="img" aria-label="Parcels handled per day. Exact counts are in the data table below."></canvas></div>
                                <details class="mt-3 text-xs"><summary class="cursor-pointer text-primary">View daily counts</summary><table class="mt-2 w-full text-left"><caption class="sr-only">Daily handling counts</caption><thead><tr><th scope="col">Date</th><th scope="col">Parcels</th></tr></thead><tbody>@foreach ($chartData['daily']['labels'] as $index => $label)<tr><th scope="row" class="py-1 font-normal">{{ $label }}</th><td>{{ number_format($chartData['daily']['values'][$index]) }}</td></tr>@endforeach</tbody></table></details>
                            @else
                                <x-seller.empty-state title="No parcel activity" description="Recorded handling at this hub will appear here. Try a longer date range." />
                            @endif
                        </article>
                        <article class="frost-panel min-w-0 rounded-2xl p-4 sm:p-5">
                            <h3 class="text-card-title">Parcels by status</h3><p class="mt-1 text-card-hint">Current status of this hub's parcels with recorded activity in range.</p>
                            @if (array_sum($chartData['statuses']['values']) > 0)
                                <div class="dashboard-chart mt-4"><canvas id="logistics-status-chart" role="img" aria-label="Parcels by status. Exact counts are in the data table below."></canvas></div>
                                <details class="mt-3 text-xs"><summary class="cursor-pointer text-primary">View status counts</summary><table class="mt-2 w-full text-left"><caption class="sr-only">Parcel status counts</caption><thead><tr><th scope="col">Status</th><th scope="col">Parcels</th></tr></thead><tbody>@foreach ($chartData['statuses']['labels'] as $index => $label)<tr><th scope="row" class="py-1 font-normal">{{ $label }}</th><td>{{ number_format($chartData['statuses']['values'][$index]) }}</td></tr>@endforeach</tbody></table></details>
                            @else
                                <x-seller.empty-state title="No status data for this range" description="Parcels with recorded hub activity will appear here." />
                            @endif
                        </article>
                    </div>
                </section>

                <section id="work-queue" aria-labelledby="queue-title" class="scroll-mt-24 space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div><h2 id="queue-title" class="text-lg font-semibold">Work queue</h2><p class="mt-1 text-xs text-charcoal/60">Up to 10 most recently updated matches. Inbound quantities show parcels still unreceived.</p></div>
                        <form method="GET" action="{{ route('logistics.dashboard') }}#work-queue" class="flex flex-wrap items-end gap-2">
                            <input type="hidden" name="range" value="{{ $range }}">
                            <label><span class="sr-only">Work queue filter</span><select name="queue" class="min-h-10 rounded-xl border border-line bg-surface px-3 text-sm">
                                @foreach (['all' => 'All actionable work', 'sorting' => 'Waiting to sort', 'inbound' => 'Inbound bags', 'outbound' => 'Open outbound bags', 'delivery' => 'Out for delivery', 'delivered' => 'Delivered in range', 'applications' => 'Pending applications', 'stale' => 'Stale parcels', 'failed' => 'Failed deliveries', 'returned' => 'Returned parcels'] as $value => $label)<option value="{{ $value }}" @selected($queue === $value)>{{ $label }}</option>@endforeach
                            </select></label><button type="submit" class="dashboard-link">Filter</button>
                        </form>
                    </div>
                    @if ($workQueue->isEmpty())
                        <x-seller.empty-state title="Nothing in this queue" description="No matching items need attention. Choose another queue or return to Sort & Assign."><a class="dashboard-link" href="{{ route('logistics.sorting.index') }}">Sort &amp; Assign</a></x-seller.empty-state>
                    @else
                        <x-seller.data-table label="Hub work queue" responsive="scroll" class="dashboard-queue">
                            <x-slot:head><tr><th scope="col">Item</th><th scope="col">Details</th><th scope="col">Parcels</th><th scope="col">Status</th><th scope="col">Updated</th><th scope="col"><span class="sr-only">Action</span></th></tr></x-slot:head>
                            @foreach ($workQueue as $item)
                                <tr>
                                    <td><a class="font-semibold text-primary hover:underline" href="{{ $item['href'] }}">{{ $item['reference'] }}</a><p class="mt-1 text-xs text-charcoal/55">{{ $item['kind'] }}</p></td>
                                    <td class="max-w-64 text-charcoal/65">{{ $item['detail'] }}</td>
                                    <td class="tabular-nums">{{ $item['quantity'] !== null ? number_format($item['quantity']) : '—' }}</td>
                                    <td><x-status-label :status="$item['status']" :domain="$item['domain']" /></td>
                                    <td><time class="whitespace-nowrap text-xs text-charcoal/60" datetime="{{ $item['updated_at']->toIso8601String() }}" title="{{ $item['updated_at']->format('M j, Y g:i A') }}">{{ $item['updated_at']->diffForHumans() }}</time></td>
                                    <td><a class="dashboard-link whitespace-nowrap" aria-label="Open {{ $item['reference'] }}" href="{{ $item['href'] }}">Open <span aria-hidden="true">&rarr;</span></a></td>
                                </tr>
                            @endforeach
                        </x-seller.data-table>
                    @endif
                </section>

                <section aria-labelledby="riders-title" class="frost-panel rounded-2xl p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 id="riders-title" class="text-card-title">Rider overview</h2><p class="mt-1 text-card-hint">Up to six active, approved riders, lightest workload first. Open assignments include pickup, delivery and failed-delivery follow-up.</p></div><a class="text-xs font-semibold text-primary hover:underline" href="{{ route('logistics.riders.index', ['status' => 'approved']) }}">View all riders</a></div>
                    @if ($riders->isEmpty())
                        <x-seller.empty-state title="No active riders" description="Approve rider applications and activate riders to see their workloads."><a class="dashboard-link" href="{{ route('logistics.riders.index') }}">Rider applications</a></x-seller.empty-state>
                    @else
                        <ul class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($riders as $rider)
                                <li class="flex items-center justify-between gap-3 rounded-xl border border-line p-3">
                                    <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $rider->full_name }}</p><p class="mt-1 text-xs text-charcoal/60">{{ $rider->barangay ?: 'Barangay not recorded' }}</p></div>
                                    <div class="shrink-0 text-right"><p class="text-xl font-semibold tabular-nums">{{ number_format($rider->carrying_count) }}</p><p class="text-xs text-charcoal/55">open assignments</p></div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </main>
        </div>
    </div>
</body>
</html>
