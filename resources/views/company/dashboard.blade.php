<x-company.layout title="Dashboard" description="Company performance and current network activity" :scripts="['resources/js/pages/company/dashboard.js']">
    <section class="flex flex-wrap items-center justify-between gap-3 rounded-sm border border-line bg-surface p-4" aria-label="Dashboard date range">
        <div><h2 class="font-semibold">{{ $periodLabel }}</h2><p class="mt-1 text-xs">Dates use Philippine time. Hub, rider and attention counts show the current network.</p></div>
        <form method="GET" action="{{ route('company.dashboard') }}" class="flex flex-wrap gap-2" data-company-range>
            @foreach (['today' => 'Today', '7d' => '7 days', '30d' => '30 days'] as $key => $label)<button type="submit" name="range" value="{{ $key }}" class="btn {{ $range === $key ? 'btn-primary' : 'btn-outline' }}" aria-pressed="{{ $range === $key ? 'true' : 'false' }}">{{ $label }}</button>@endforeach
        </form>
    </section>
    @if (!$hasHubs)<x-seller.empty-state title="Build your hub network" description="Create your first hub to begin tracking company performance." class="rounded-sm! bg-surface"><a href="{{ route('company.hubs.create') }}" class="btn btn-primary">Create hub</a></x-seller.empty-state>@endif
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($cards as $card)<x-seller.stat-card :label="$card['label']" :value="$card['value']" :display-value="$card['display'] ?? null" :hint="$card['hint']" :href="$card['href'] ?? null" class="rounded-sm! bg-surface! shadow-none!" />@endforeach
    </div>
    <p class="text-xs">Success rate is delivered parcels divided by distinct parcels with a delivery attempt or delivery in the range. A retried parcel counts once. Hubs without attempts have no rate.</p>
    <div data-company-charts data-chart-data="{{ json_encode($chartData) }}" class="grid gap-4 xl:grid-cols-2">
        <p data-company-chart-error hidden role="alert" class="text-sm text-danger xl:col-span-2">Charts could not load. The totals and leaderboards are still available.</p>
        <section class="rounded-sm border border-line bg-surface p-4 xl:col-span-2"><h2 class="font-semibold">Parcels per day</h2><p class="mt-1 text-xs">Distinct parcels handled by the company each day.</p>
            @if ($handled)<div class="mt-4 h-64"><canvas id="company-daily-chart" role="img" aria-label="Parcels handled per day"><p>Parcels handled in {{ strtolower($periodLabel) }}: {{ $handled }}.</p></canvas></div>
            @else<x-seller.empty-state title="No parcel activity in this range" description="Recorded parcel movements will appear here." class="mt-4 rounded-sm!" />@endif
        </section>
        <section class="min-w-0 rounded-sm border border-line bg-surface p-4"><h2 class="font-semibold">Parcels per hub</h2><p class="mt-1 text-xs">A parcel can count at multiple hubs during a transfer.</p>
            @if ($handled)<div class="mt-4 max-h-96 overflow-y-auto"><div style="height: {{ max(220, count($chartData['hubs']['labels']) * 34) }}px"><canvas id="company-hubs-chart" role="img" aria-label="Parcels handled per hub"></canvas></div></div>
            @else<x-seller.empty-state title="No hub activity yet" description="Choose another range or check back after parcels move through your hubs." class="mt-4 rounded-sm!" />@endif
        </section>
        <section class="min-w-0 rounded-sm border border-line bg-surface p-4"><h2 class="font-semibold">Success rate per hub</h2><p class="mt-1 text-xs">Delivered / distinct parcels attempted in the range.</p>
            @if ($hasAttempts)<div class="mt-4 max-h-96 overflow-y-auto"><div style="height: {{ max(220, count($chartData['success']['labels']) * 34) }}px"><canvas id="company-success-chart" role="img" aria-label="Delivery success rate per hub"></canvas></div></div>
            @else<x-seller.empty-state title="No delivery attempts in this range" description="Success rates will appear once delivery activity is recorded." class="mt-4 rounded-sm!" />@endif
        </section>
    </div>
    <section class="space-y-3"><div><h2 class="text-lg font-semibold">Needs attention</h2><p class="mt-1 text-xs">Current issues across your hubs, regardless of the date range.</p></div>
        @if (!collect($attention)->sum('count'))<p role="status" class="rounded-sm border border-line bg-surface p-4 text-sm text-success">No issues currently need attention.</p>@endif
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">@foreach ($attention as $item)<a href="{{ $item['href'] }}" class="rounded-sm border border-line bg-surface p-4 focus-visible:outline-2 focus-visible:outline-primary"><span class="block text-2xl font-semibold {{ $item['count'] ? 'text-warning' : 'text-charcoal' }}">{{ number_format($item['count']) }}</span><span class="mt-1 block text-sm font-semibold">{{ $item['label'] }}</span><span class="mt-2 block text-xs">{{ $item['hint'] }}</span></a>@endforeach</div>
    </section>
    <section id="hub-leaderboard" class="space-y-3"><h2 class="text-lg font-semibold">Hub leaderboard</h2><div class="grid items-start gap-4 xl:grid-cols-2">
        @foreach (['delivered' => 'Top hubs by delivered parcels', 'success' => 'Top hubs by success rate'] as $metric => $heading)
            <section class="min-w-0 space-y-3"><h3 class="text-sm font-semibold">{{ $heading }}</h3>
                <x-seller.data-table :label="$heading" responsive="scroll" class="rounded-sm! bg-surface! shadow-none!">
                    <x-slot:head><tr><th class="p-3">Hub</th><th class="p-3">Delivered</th><th class="p-3">Attempted</th><th class="p-3">Success rate</th></tr></x-slot:head>
                    @forelse ($leaderboard[$metric] as $hub)<tr><td class="p-3"><a href="{{ route('company.hubs.edit', $hub['code']) }}" class="font-semibold text-primary">{{ $hub['name'] }}</a></td><td class="p-3">{{ number_format($hub['delivered']) }}</td><td class="p-3">{{ number_format($hub['attempted']) }}</td><td class="p-3">{{ $hub['rate'] === null ? 'No data' : number_format($hub['rate'], 1).'%' }}</td></tr>
                    @empty<tr><td colspan="4" class="p-5 text-center text-sm">{{ $metric === 'delivered' ? 'No delivered parcels in this range.' : 'No delivery attempts in this range.' }}</td></tr>@endforelse
                </x-seller.data-table>
            </section>
        @endforeach
    </div></section>
</x-company.layout>
