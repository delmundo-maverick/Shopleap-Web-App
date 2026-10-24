<div class="{{ $panel }} overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/50 px-5 py-3">
        <div class="flex gap-4 text-sm font-semibold">
            <a href="{{ route('notifications.index') }}"
                class="{{ $filter === 'all' ? 'text-primary' : 'text-charcoal/50 hover:text-charcoal' }}">All</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                class="{{ $filter === 'unread' ? 'text-primary' : 'text-charcoal/50 hover:text-charcoal' }}">
                Unread ({{ $unread }})
            </a>
        </div>

        @if ($unread > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-primary hover:underline">
                    Mark all as read
                </button>
            </form>
        @endif
    </div>

    @forelse ($notifications as $notification)
        @php
            $data = $notification->data;
            $isUnread = is_null($notification->read_at);
            $kind = $data['kind'] ?? '';

            $label = str_starts_with($kind, 'cancel')
                ? 'Cancellation'
                : (str_starts_with($kind, 'return')
                    ? 'Return / Refund'
                    : (str_starts_with($kind, 'order') || str_starts_with($kind, 'pickup')
                        ? 'Order'
                        : 'Update'));
        @endphp

        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
            @csrf
            <button type="submit"
                class="flex w-full items-start gap-3 border-b border-white/50 px-5 py-4 text-left transition hover:bg-white/40 {{ $isUnread ? 'bg-primary/5' : '' }}">
                <span
                    class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $isUnread ? 'bg-primary' : 'bg-transparent' }}"></span>

                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-sm {{ $isUnread ? 'font-bold text-charcoal' : 'font-semibold text-charcoal/80' }}">
                            {{ $data['title'] ?? 'Update' }}
                        </span>
                        <span class="rounded-full bg-light-gray px-2 py-0.5 text-[10px] font-bold text-charcoal/50">
                            {{ $label }}
                        </span>
                    </span>
                    <span class="mt-0.5 block text-xs text-charcoal/60">{{ $data['body'] ?? '' }}</span>
                    <span class="mt-1 block text-[11px] text-charcoal/40">
                        {{ $notification->created_at->diffForHumans() }}
                    </span>
                </span>
            </button>
        </form>
    @empty
        <div class="flex h-48 flex-col items-center justify-center text-center">
            <p class="text-sm font-semibold text-charcoal">
                {{ $filter === 'unread' ? "You're all caught up" : 'No notifications yet' }}
            </p>
            <p class="mt-1 text-xs text-charcoal/50">Updates about your orders, cancellations and refunds appear here.
            </p>
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $notifications->links() }}</div>
