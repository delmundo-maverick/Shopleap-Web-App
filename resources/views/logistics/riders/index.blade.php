<x-logistics.layout title="Rider Applications" :subtitle="array_sum($counts).' '.str('application')->plural(array_sum($counts)).' in your hub'" active="riders">
    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Application totals">
        @foreach (['all' => 'All applications', 'pending' => 'Pending review', 'approved' => 'Approved riders', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('logistics.riders.index', ['status' => $key]) }}" @if ($status === $key) aria-current="page" @endif
                @class(['frost-panel rounded-xl border p-4 transition hover:border-primary/40', 'border-primary ring-1 ring-primary/15' => $status === $key, 'border-line' => $status !== $key])>
                <p class="text-xs font-medium text-charcoal/65">{{ $label }}</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($key === 'all' ? array_sum($counts) : $counts[$key]) }}</p>
            </a>
        @endforeach
    </section>

    <x-seller.filter-bar>
        <form method="GET" action="{{ route('logistics.riders.index') }}" class="flex w-full flex-wrap items-end gap-3">
            <label class="min-w-0 flex-1 basis-56">
                <span class="mb-1.5 block text-xs font-semibold">Search applications</span>
                <input type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Name, email or plate number"
                    class="min-h-11 w-full rounded-xl border border-line bg-surface px-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
            </label>
            <label class="min-w-0 flex-1 basis-40 sm:flex-none">
                <span class="mb-1.5 block text-xs font-semibold">Application status</span>
                <select name="status" class="min-h-11 w-full rounded-xl border border-line bg-surface px-3 text-sm outline-none focus:border-primary">
                    @foreach (['pending' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All applications'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="min-h-11 rounded-xl bg-primary px-5 text-sm font-semibold text-white transition hover:bg-sky-blue">Apply filters</button>
            @if ($search !== '' || $status !== 'pending')
                <a href="{{ route('logistics.riders.index') }}" class="inline-flex min-h-11 items-center px-2 text-sm font-semibold text-primary hover:underline">Reset</a>
            @endif
        </form>
    </x-seller.filter-bar>

    <section class="space-y-3" aria-labelledby="applications-title">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div><h1 id="applications-title" class="text-lg font-semibold">Applications</h1><p class="mt-1 text-xs text-charcoal/60">Review each rider's form and documents before making a decision.</p></div>
            <p class="text-xs text-charcoal/60">{{ number_format($riders->total()) }} {{ str('result')->plural($riders->total()) }}</p>
        </div>
        @if ($riders->isEmpty())
            <x-seller.empty-state title="No matching applications" description="Try another search or status. Rider applications appear here after they register under your hub." />
        @else
            <x-seller.data-table label="Rider applications" responsive="scroll">
                <x-slot:head>
                    <tr class="text-xs text-charcoal/60">
                        <th scope="col" class="px-3 py-3 font-semibold">Applicant</th>
                        <th scope="col" class="px-3 py-3 font-semibold">Vehicle</th>
                        <th scope="col" class="px-3 py-3 font-semibold">Status</th>
                        <th scope="col" class="px-3 py-3 font-semibold">Submitted</th>
                        <th scope="col" class="px-3 py-3 text-right font-semibold">Review</th>
                    </tr>
                </x-slot:head>
                @foreach ($riders as $rider)
                    <tr class="transition hover:bg-ice-blue/60">
                        <td class="px-3 py-4"><p class="font-semibold">{{ $rider->full_name }}</p><p class="mt-1 break-all text-xs text-charcoal/60">{{ $rider->user?->email ?? 'Email not available' }}</p><p class="mt-1 text-xs text-charcoal/60">{{ $rider->contact_no }}</p></td>
                        <td class="px-3 py-4"><p>{{ $rider->vehicle_type }}</p><p class="mt-1 text-xs text-charcoal/60">{{ $rider->plate_number }}</p></td>
                        <td class="px-3 py-4"><x-status-label :status="$rider->status" domain="application" />@if ($rider->status === 'approved')<p class="mt-1 text-xs text-charcoal/60">{{ $rider->is_active ? 'Active' : 'Inactive' }}</p>@endif</td>
                        <td class="px-3 py-4 text-xs text-charcoal/60"><time datetime="{{ $rider->created_at->toIso8601String() }}" title="{{ $rider->created_at->format('M j, Y g:i A') }}">{{ $rider->created_at->format('M j, Y') }}</time></td>
                        <td class="px-3 py-4 text-right"><a href="{{ route('logistics.riders.show', $rider) }}" aria-label="View application for {{ $rider->full_name }}" class="inline-flex min-h-11 items-center whitespace-nowrap rounded-xl border border-line px-3 text-xs font-semibold text-primary transition hover:bg-primary/5">View application <span aria-hidden="true" class="ml-2">&rarr;</span></a></td>
                    </tr>
                @endforeach
            </x-seller.data-table>
        @endif
        {{ $riders->links() }}
    </section>
</x-logistics.layout>
