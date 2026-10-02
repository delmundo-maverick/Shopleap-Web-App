@php
    $isFrosted = ($variant ?? 'frosted') !== 'flat';
    $panelClass = $isFrosted ? 'frost-panel-solid' : 'bg-white shadow-sm shadow-charcoal/5';
    $inputClass = $isFrosted ? 'frost-input' : 'border border-light-gray bg-white';
    $roleBadgeColors = [
        'buyer' => 'bg-blue-100 text-blue-600',
        'seller' => 'bg-leaf-green/10 text-leaf-green',
        'logistics' => 'bg-purple-100 text-purple-600',
        'courier' => 'bg-orange-100 text-orange-600',
    ];
@endphp

<div class="flex h-[calc(100vh-9rem)] gap-0 overflow-hidden rounded-2xl {{ $panelClass }}" id="messagingApp"
    data-current-user-id="{{ auth()->id() }}" data-current-user-role="{{ auth()->user()->role }}"
    data-buyer-mode="{{ $variant === 'flat' ? '1' : '0' }}" data-initial-conversation-id="{{ $activeConversation?->id }}">

    <!-- CONVERSATION LIST -->
    <div class="flex w-full max-w-xs flex-col border-r border-white/50 sm:max-w-sm">
        <div class="flex items-center justify-between border-b border-white/50 p-4">
            <h2 class="text-base font-bold text-charcoal">Conversations</h2>
            <button type="button" id="newMessageBtn"
                class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white transition hover:bg-sky-blue">
                + New
            </button>
        </div>

        <div id="conversationList" class="flex-1 space-y-1 overflow-y-auto p-2">
            @forelse ($conversations as $conversation)
                @php
                    $other = $conversation->otherParticipantFor(auth()->id());
                    $lastMessage = $conversation->messages->last();
                    $unread = $conversation->unreadCountFor(auth()->id());
                @endphp

                <div class="group relative">
                    <button type="button" data-conversation-id="{{ $conversation->id }}"
                        class="conversation-item flex w-full items-start gap-3 rounded-xl p-3 pr-9 text-left transition hover:bg-primary/5
                            {{ $activeConversation?->id === $conversation->id ? 'bg-primary/10' : '' }}">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                            {{ strtoupper(substr($other->messaging_name ?? '?', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex min-w-0 items-center gap-1.5">
                                    <p class="truncate text-sm font-semibold text-charcoal">
                                        {{ $other->messaging_name ?? 'Unknown' }}</p>
                                    @if ($variant === 'admin' && $other)
                                        <span
                                            class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-bold uppercase {{ $roleBadgeColors[$other->role] ?? 'bg-light-gray text-charcoal/50' }}">
                                            {{ $other->role }}
                                        </span>
                                    @endif
                                </span>
                                @if ($unread > 0)
                                    <span
                                        class="shrink-0 rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unread }}</span>
                                @endif
                            </div>
                            @if ($conversation->product)
                                <p class="truncate text-[11px] text-primary/70">Re: {{ $conversation->product->name }}
                                </p>
                            @endif
                            <p class="truncate text-xs text-charcoal/50">{{ $lastMessage?->body ?? 'No messages yet' }}
                            </p>
                            @if ($variant === 'admin' && $conversation->assignedAgent)
                                <p class="mt-0.5 truncate text-[10px] font-semibold text-primary/60">Assigned:
                                    {{ $conversation->assignedAgent->name }}</p>
                            @endif
                        </div>
                    </button>
                    <button type="button"
                        class="delete-conversation-btn absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-lg p-1.5 text-charcoal/30 transition hover:bg-sale-red/10 hover:text-sale-red group-hover:block"
                        data-conversation-id="{{ $conversation->id }}" title="Delete chat">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            @empty
                <p class="p-4 text-center text-xs text-charcoal/50">No conversations yet. Start one with "+ New".</p>
            @endforelse
        </div>
    </div>

    <!-- THREAD -->
    <div class="flex flex-1 flex-col">
        <div id="threadHeader" class="hidden items-center gap-3 border-b border-white/50 p-4">
            <div id="threadHeaderAvatar"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
            </div>
            <div class="min-w-0 flex-1">
                <p id="threadHeaderName" class="truncate text-sm font-bold text-charcoal"></p>
                @if ($variant === 'admin')
                    <p id="threadHeaderAssigned" class="text-[11px] text-charcoal/50"></p>
                @endif
            </div>

            @if ($variant === 'admin')
                <button type="button" id="threadClaimBtn"
                    class="shrink-0 rounded-lg border border-primary/30 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary/5">
                    Claim
                </button>
                <button type="button" id="threadResolveBtn"
                    class="shrink-0 rounded-lg bg-leaf-green px-3 py-1.5 text-xs font-semibold text-white transition hover:brightness-110">
                    Mark Resolved
                </button>
            @endif

            <button type="button" id="threadDeleteBtn" title="Delete chat"
                class="shrink-0 rounded-lg p-2 text-charcoal/40 transition hover:bg-sale-red/10 hover:text-sale-red">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                </svg>
            </button>
        </div>

        <div id="threadProductCard" class="hidden items-center gap-3 border-b border-white/50 bg-ice-blue/40 p-3">
            <img id="threadProductImage" src="" alt=""
                class="h-12 w-12 shrink-0 rounded-lg bg-ice-blue/60 object-cover">
            <div class="min-w-0 flex-1">
                <p id="threadProductName" class="truncate text-sm font-semibold text-charcoal"></p>
                <p id="threadProductPrice" class="text-xs font-bold text-primary"></p>
            </div>
            <a id="threadProductLink" href="#"
                class="hidden shrink-0 rounded-lg border border-primary/30 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary/5">
                View
            </a>
        </div>

        <div id="threadEmptyState" class="flex flex-1 flex-col items-center justify-center text-center">
            <svg class="h-12 w-12 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8-1.284 0-2.503-.24-3.606-.673L3 20l1.673-4.394C3.94 14.503 3.7 13.284 3.7 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="mt-3 text-sm text-charcoal/50">Select a conversation or start a new one.</p>
        </div>

        <div id="threadMessages" class="hidden flex-1 space-y-3 overflow-y-auto p-4"></div>

        <form id="messageForm" class="hidden items-center gap-2 border-t border-white/50 p-3">
            @csrf
            <textarea id="messageInput" rows="1" maxlength="2000" placeholder="Type a message…"
                class="flex-1 resize-none rounded-xl {{ $inputClass }} px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"></textarea>
            <button type="submit"
                class="shrink-0 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                Send
            </button>
        </form>
    </div>

</div>

@if ($variant === 'flat')
    <div id="buyerContactModalOverlay"
        class="fixed inset-0 z-[90] hidden items-center justify-center bg-charcoal/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="newMessageModalTitle" class="text-base font-bold text-charcoal">New Message</h3>
                <button type="button" id="buyerContactModalCloseBtn" class="text-charcoal/40 hover:text-charcoal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="buyerContactModalList" class="mt-4 max-h-80 space-y-1 overflow-y-auto"></div>
        </div>
    </div>
@endif

<!-- NEW MESSAGE MODAL -->
<div id="newMessageModalOverlay" class="fixed inset-0 z-[90] hidden items-center justify-center bg-charcoal/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-charcoal">New Message</h3>
            <button type="button" id="newMessageCloseBtn" class="text-charcoal/40 hover:text-charcoal">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-4">
            <input type="text" id="contactSearchInput" placeholder="Search by name…"
                class="w-full rounded-xl border border-light-gray px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
            <div id="contactResults" class="mt-2 max-h-48 space-y-1 overflow-y-auto"></div>
        </div>

        <div id="newMessageComposeArea" class="mt-4 hidden">
            <p class="mb-2 text-xs text-charcoal/50">To: <span id="selectedContactName"
                    class="font-semibold text-charcoal"></span></p>
            <textarea id="newMessageBody" rows="3" maxlength="2000" placeholder="Write your message…"
                class="w-full resize-none rounded-xl border border-light-gray px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"></textarea>
            <button type="button" id="newMessageSendBtn"
                class="mt-3 w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                Send Message
            </button>
        </div>
    </div>
</div>
