@php
    $field = 'frost-input rounded-xl px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $cell =
        'flex items-center justify-between gap-3 px-1 py-1.5 before:text-xs before:font-medium before:text-charcoal/50 before:content-[attr(data-label)] md:table-cell md:px-5 md:py-4 md:before:hidden';
@endphp

<x-logistics.layout title="Sorting Session History" subtitle="View past sorting sessions and performance metrics"
    active="sorting-sessions">

    <div class="space-y-4 sm:space-y-5">
        {{-- FILTERS --}}
        <form method="GET" role="search"
            class="frost-panel grid grid-cols-2 gap-3 rounded-2xl p-4 sm:flex sm:items-end">
            <div class="min-w-0 sm:min-w-[200px] sm:flex-1">
                <label for="from" class="mb-1.5 block text-xs font-semibold text-charcoal">From Date</label>
                <input id="from" type="date" name="from" value="{{ request('from') }}"
                    class="{{ $field }} w-full">
            </div>

            <div class="min-w-0 sm:min-w-[200px] sm:flex-1">
                <label for="to" class="mb-1.5 block text-xs font-semibold text-charcoal">To Date</label>
                <input id="to" type="date" name="to" value="{{ request('to') }}"
                    class="{{ $field }} w-full">
            </div>

            <button type="submit" class="btn-primary col-span-2 sm:col-span-1">Apply Filters</button>

            @if (request()->hasAny(['from', 'to']))
                <a href="{{ route('logistics.sorting.sessions.index') }}"
                    class="btn-soft col-span-2 text-center sm:col-span-1">Clear</a>
            @endif
        </form>

        {{-- SESSIONS LIST (cards on mobile, table from md) --}}
        @if ($sessions->isEmpty())
            <div class="frost-panel-solid flex h-56 flex-col items-center justify-center rounded-2xl px-4 text-center">
                <svg class="h-12 w-12 text-charcoal/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                <p class="mt-3 text-sm font-semibold text-charcoal">No sessions found</p>
                <p class="mt-1 text-xs text-charcoal/50">
                    {{ request()->hasAny(['from', 'to']) ? 'Try adjusting the date filters.' : 'Start sorting to create your first session.' }}
                </p>
            </div>
        @else
            <div class="frost-panel-solid overflow-hidden rounded-2xl">
                <div class="overflow-x-auto">
                    <table class="block w-full text-left text-sm md:table">
                        <thead class="hidden border-b border-white/50 bg-primary/5 md:table-header-group">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Session</th>
                                <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Started</th>
                                <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Duration</th>
                                <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Performance</th>
                                <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>

                        {{-- Divider: on md+ every cell of every row except the last gets a bottom line.
                             Mobile rows are already separate bordered cards. --}}
                        <tbody
                            class="block space-y-3 p-3 md:table-row-group md:space-y-0 md:p-0 md:[&>tr:not(:last-child)>td]:border-b md:[&>tr:not(:last-child)>td]:border-charcoal/10">
                            @foreach ($sessions as $session)
                                @php
                                    $sorted = $session->sortedCount();
                                    $rejected = $session->rejectedCount();
                                    $needsReview = $session->needsReviewCount();
                                    $duplicates = $session->duplicateCount();
                                    $total = $sorted + $rejected + $needsReview + $duplicates;
                                    $duration = $session->elapsedSeconds();
                                    $hours = floor($duration / 3600);
                                    $minutes = floor(($duration % 3600) / 60);
                                    $seconds = $duration % 60;
                                    $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                @endphp

                                <tr
                                    class="block rounded-xl border border-white/50 bg-white/30 p-3 transition hover:bg-primary/5 md:table-row md:rounded-none md:border-0 md:bg-transparent md:p-0">

                                    <td class="block px-1 pb-1 md:table-cell md:px-5 md:py-4">
                                        <p class="text-base font-semibold text-charcoal md:text-sm">
                                            {{ $session->user->name }}</p>
                                        <p class="text-xs text-charcoal/50">Session #{{ $session->id }}</p>
                                    </td>

                                    <td data-label="Started" class="{{ $cell }} text-charcoal/70">
                                        <div class="min-w-0 text-right md:text-left">
                                            {{ $session->started_at->format('M j, Y') }}
                                            <span
                                                class="block text-xs text-charcoal/50">{{ $session->started_at->format('g:i A') }}</span>
                                        </div>
                                    </td>

                                    <td data-label="Duration" class="{{ $cell }}">
                                        <span class="font-mono text-charcoal">{{ $formattedDuration }}</span>
                                    </td>

                                    <td data-label="Performance" class="{{ $cell }}">
                                        <div class="min-w-0 space-y-1 text-right md:text-left">
                                            <div
                                                class="flex flex-wrap justify-end gap-x-3 gap-y-1 text-xs md:justify-start">
                                                <span class="font-semibold text-leaf-green">{{ $sorted }}
                                                    sorted</span>
                                                @if ($needsReview > 0)
                                                    <span class="font-semibold text-orange-600">{{ $needsReview }}
                                                        review</span>
                                                @endif
                                                @if ($rejected > 0)
                                                    <span class="font-semibold text-sale-red">{{ $rejected }}
                                                        rejected</span>
                                                @endif
                                            </div>
                                            @if ($duplicates > 0)
                                                <p class="text-xs text-charcoal/50">{{ $duplicates }} duplicates</p>
                                            @endif
                                        </div>
                                    </td>

                                    <td data-label="Status" class="{{ $cell }}">
                                        @if ($session->ended_at)
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-light-gray px-2.5 py-1 text-xs font-semibold text-charcoal">
                                                @if ($session->auto_closed)
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"
                                                        aria-hidden="true">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                    Auto-closed
                                                @else
                                                    Ended
                                                @endif
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                                                <span class="h-2 w-2 animate-pulse rounded-full bg-primary"></span>
                                                Active
                                            </span>
                                        @endif
                                    </td>

                                    <td class="block pt-2 md:table-cell md:px-5 md:py-4 md:pt-4 md:text-right">
                                        <a href="{{ route('logistics.sorting.sessions.summary', $session) }}"
                                            class="btn-soft flex w-full items-center justify-center !px-3.5 !py-2 !text-xs md:inline-flex md:w-auto">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PAGINATION --}}
            @if ($sessions->hasPages())
                <div class="pagination-frost overflow-x-auto">{{ $sessions->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
</x-logistics.layout>
