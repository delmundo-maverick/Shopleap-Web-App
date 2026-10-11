{{-- Shared Help Center body. Needs: $topics, $orders, $recentTickets, $unreadTickets, $role --}}
@php
    $copy = [
        'buyer' => 'Choose a topic and answer a few quick questions. We\'ll open a ticket for the Shopleap support team.',
        'seller' => 'Tell us what you need help with. Your ticket goes straight to the Shopleap support team.',
        'logistics' => 'Report a parcel, pickup or account problem. Your ticket goes straight to the Shopleap support team.',
    ];
@endphp

<div class="mx-auto w-full max-w-5xl space-y-4">

    <section class="frost-panel flex flex-col gap-3 rounded-2xl p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-charcoal sm:text-2xl">How can we help?</h1>
            <p class="mt-1 text-sm text-charcoal/60">{{ $copy[$role] ?? $copy['buyer'] }}</p>
        </div>
        <a href="{{ route('messages.index') }}"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-primary/30 bg-white/70 px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary/5">
            My messages &amp; tickets
            @if ($unreadTickets > 0)
                <span class="rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadTickets }} new</span>
            @endif
        </a>
    </section>

    <div class="grid gap-4 lg:grid-cols-3">

        <!-- GUIDED FORM -->
        <section class="frost-panel-solid rounded-2xl p-4 sm:p-6 lg:col-span-2">
            <ol class="mb-5 flex items-center gap-2 text-xs font-semibold">
                @foreach (['Topic', 'Details', 'Review'] as $i => $label)
                    <li class="flex items-center gap-2">
                        <span data-step-dot="{{ $i + 1 }}"
                            class="flex h-6 w-6 items-center justify-center rounded-full bg-light-gray text-charcoal/50">{{ $i + 1 }}</span>
                        <span class="text-charcoal/60">{{ $label }}</span>
                    </li>
                    @if (!$loop->last)
                        <li class="h-px w-6 bg-charcoal/15 sm:w-10"></li>
                    @endif
                @endforeach
            </ol>

            <div id="helpWizard" data-submit-url="{{ route('help.tickets.store') }}"
                data-tickets-url="{{ route('messages.index') }}">
                <div id="wizardBody"></div>
            </div>

            <script type="application/json" id="helpTopics">{!! json_encode($topics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
            <script type="application/json" id="helpOrders">{!! json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        </section>

        <!-- RECENT TICKETS -->
        <aside class="frost-panel-solid h-fit rounded-2xl p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-charcoal">Your recent tickets</h2>
                <a href="{{ route('messages.index') }}" class="text-xs font-semibold text-primary hover:underline">View all</a>
            </div>

            <div class="mt-3 divide-y divide-charcoal/10">
                @forelse ($recentTickets as $ticket)
                    @php
                        [$badgeText, $badgeClass] = \App\Support\TicketPresenter::state($ticket, $ticket->messages->last(), auth()->user());
                    @endphp
                    <a href="{{ route('messages.index', ['conversation' => $ticket->id]) }}"
                        class="block py-2.5 transition hover:bg-primary/5">
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 truncate text-sm font-semibold text-charcoal">{{ $ticket->subject }}</p>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badgeClass }}">{{ $badgeText }}</span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-charcoal/50">
                            {{ \App\Support\TicketPresenter::number($ticket) }} · {{ \App\Support\TicketPresenter::topic($ticket) }}
                        </p>
                    </a>
                @empty
                    <p class="py-4 text-center text-xs text-charcoal/50">No tickets yet. Your tickets will show up here.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
