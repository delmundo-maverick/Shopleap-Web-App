{{-- Shared Help Center body. Needs: $topics, $orders, $recentTickets, $unreadTickets, $role --}}
@php
    $copy = [
        'buyer' =>
            'Choose a topic and answer a few quick questions. We\'ll open a ticket for the Shopleap support team.',
        'seller' => 'Tell us what you need help with. Your ticket goes straight to the Shopleap support team.',
        'logistics' =>
            'Report a parcel, pickup or account problem. Your ticket goes straight to the Shopleap support team.',
    ];

    // Same outline button style as the orders page.
    $btnOutline =
        'inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-line bg-white px-5 py-2 text-xs font-bold text-charcoal transition-colors hover:border-primary hover:text-primary';
@endphp

<div class="w-full space-y-3 sm:space-y-4">

    {{-- HEADER --}}
    <section
        class="flex flex-col gap-3 rounded-xl border border-line bg-white p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
            <h1 class="text-xl font-bold text-charcoal md:text-2xl">How can we help?</h1>
            <p class="mt-1 text-sm text-charcoal/60">{{ $copy[$role] ?? $copy['buyer'] }}</p>
        </div>
        <a href="{{ route('messages.index') }}" class="{{ $btnOutline }}">
            My messages &amp; tickets
            @if ($unreadTickets > 0)
                <span class="rounded-sm bg-primary px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadTickets }}
                    new</span>
            @endif
        </a>
    </section>

    <div class="grid gap-3 sm:gap-4 lg:grid-cols-3">

        <!-- GUIDED FORM -->
        <section class="overflow-hidden rounded-xl border border-line bg-white lg:col-span-2">
            <div class="border-b border-line px-4 py-3 sm:px-5">
                <ol class="flex items-center gap-2 text-xs font-semibold">
                    @foreach (['Topic', 'Details', 'Review'] as $i => $label)
                        <li class="flex items-center gap-2">
                            <span data-step-dot="{{ $i + 1 }}"
                                class="flex h-6 w-6 items-center justify-center rounded-full border border-line bg-ice-blue text-charcoal/50">{{ $i + 1 }}</span>
                            <span class="text-charcoal/60">{{ $label }}</span>
                        </li>
                        @if (!$loop->last)
                            <li class="h-px w-6 bg-line sm:w-10"></li>
                        @endif
                    @endforeach
                </ol>
            </div>

            <div class="p-4 sm:p-5">
                <div id="helpWizard" data-submit-url="{{ route('help.tickets.store') }}"
                    data-tickets-url="{{ route('messages.index') }}">
                    <div id="wizardBody"></div>
                </div>
            </div>

            <script type="application/json" id="helpTopics">{!! json_encode($topics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
            <script type="application/json" id="helpOrders">{!! json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        </section>

        <!-- RECENT TICKETS -->
        <aside class="h-fit overflow-hidden rounded-xl border border-line bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <h2 class="text-sm font-bold text-charcoal sm:text-base">Your recent tickets</h2>
                <a href="{{ route('messages.index') }}"
                    class="text-xs font-bold text-primary transition-colors hover:underline">View all</a>
            </div>

            <div class="divide-y divide-line">
                @forelse ($recentTickets as $ticket)
                    @php
                        [$badgeText, $badgeClass] = \App\Support\TicketPresenter::state(
                            $ticket,
                            $ticket->messages->last(),
                            auth()->user(),
                        );
                    @endphp
                    <a href="{{ route('messages.index', ['conversation' => $ticket->id]) }}"
                        class="block px-4 py-3 transition-colors hover:bg-ice-blue sm:px-5">
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 truncate text-sm font-semibold text-charcoal">{{ $ticket->subject }}</p>
                            <span
                                class="shrink-0 rounded-sm px-2 py-0.5 text-[10px] font-bold {{ $badgeClass }}">{{ $badgeText }}</span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-charcoal/60">
                            {{ \App\Support\TicketPresenter::number($ticket) }} ·
                            {{ \App\Support\TicketPresenter::topic($ticket) }}
                        </p>
                    </a>
                @empty
                    <div class="flex flex-col items-center justify-center p-8 text-center">
                        <svg class="h-12 w-12 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 10h8M8 14h5m-9 6l2.4-3.2A8 8 0 1112 20H4z" />
                        </svg>
                        <p class="mt-3 text-sm font-semibold text-charcoal">No tickets yet</p>
                        <p class="mt-1 text-xs text-charcoal/60">Your tickets will show up here.</p>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
