@php
    $field = 'frost-input rounded-xl px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
@endphp

<x-logistics.layout title="Sorting Session History" subtitle="View past sorting sessions and performance metrics"
    active="sorting-sessions">

    <div class="space-y-5">
        {{-- FILTERS --}}
        <div class="frost-panel-solid rounded-2xl p-5">
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="mb-2 block text-xs font-semibold text-charcoal">From Date</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="{{ $field }}">
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="mb-2 block text-xs font-semibold text-charcoal">To Date</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="{{ $field }}">
                </div>

                <button type="submit"
                    class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                    Apply Filters
                </button>

                @if (request()->hasAny(['from', 'to']))
                    <a href="{{ route('logistics.sorting.sessions.index') }}"
                        class="rounded-xl bg-light-gray px-5 py-2.5 text-sm font-semibold text-charcoal transition hover:brightness-95">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        {{-- SESSIONS LIST --}}
        @if ($sessions->isEmpty())
            <div class="frost-panel-solid flex h-56 flex-col items-center justify-center rounded-2xl text-center">
                <svg class="h-12 w-12 text-charcoal/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/50 bg-primary/5 text-charcoal/60">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Session</th>
                            <th class="px-5 py-3 font-semibold">Started</th>
                            <th class="px-5 py-3 font-semibold">Duration</th>
                            <th class="px-5 py-3 font-semibold">Performance</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-white/50">
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

                            <tr class="hover:bg-primary/5 transition">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-charcoal">{{ $session->user->name }}</p>
                                    <p class="text-xs text-charcoal/50">Session #{{ $session->id }}</p>
                                </td>

                                <td class="px-5 py-4 text-charcoal/70">
                                    {{ $session->started_at->format('M j, Y') }}
                                    <span
                                        class="block text-xs text-charcoal/50">{{ $session->started_at->format('g:i A') }}</span>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="font-mono text-charcoal">{{ $formattedDuration }}</span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <div class="flex gap-3 text-xs">
                                            <span class="text-leaf-green font-semibold">{{ $sorted }}
                                                sorted</span>
                                            @if ($needsReview > 0)
                                                <span class="text-orange-600 font-semibold">{{ $needsReview }}
                                                    review</span>
                                            @endif
                                            @if ($rejected > 0)
                                                <span class="text-sale-red font-semibold">{{ $rejected }}
                                                    rejected</span>
                                            @endif
                                        </div>
                                        @if ($duplicates > 0)
                                            <p class="text-xs text-charcoal/50">{{ $duplicates }} duplicates</p>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    @if ($session->ended_at)
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full bg-light-gray px-2.5 py-1 text-xs font-semibold text-charcoal">
                                            @if ($session->auto_closed)
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
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
                                            <span class="h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                                            Active
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('logistics.sorting.sessions.summary', $session) }}"
                                        class="text-xs font-semibold text-primary hover:underline">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($sessions->hasPages())
                <div class="frost-panel-solid rounded-2xl p-4">
                    {{ $sessions->links() }}
                </div>
            @endif
        @endif
    </div>
</x-logistics.layout>
