@vite('resources/js/pages/buyer/message-widget.js')

<button type="button" id="buyerMessageWidgetToggle"
    class="fixed bottom-6 right-6 z-[70] flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30 transition hover:bg-sky-blue">
    <svg id="buyerMessageWidgetToggleIcon" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8-1.284 0-2.503-.24-3.606-.673L3 20l1.673-4.394C3.94 14.503 3.7 13.284 3.7 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
    <span id="buyerMessageWidgetBadge"
        class="absolute -top-1 -right-1 hidden h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-sale-red px-1 text-[10px] font-bold text-white"></span>
</button>

<div id="buyerMessageWidgetPanel"
    class="fixed bottom-24 right-6 z-[70] hidden h-[32rem] max-h-[70vh] w-96 max-w-[calc(100vw-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">

    <!-- LIST VIEW -->
    <div id="widgetListView" class="flex h-full flex-col">
        <div class="flex shrink-0 items-center justify-between border-b border-light-gray px-4 py-3">
            <p class="text-sm font-bold text-charcoal">Messages</p>
            <button type="button" id="buyerMessageWidgetClose" class="text-charcoal/40 hover:text-charcoal">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="widgetListContent" class="flex-1 overflow-y-auto p-2">
            <p class="p-4 text-center text-xs text-charcoal/50">Loading…</p>
        </div>

        <a href="{{ route('messages.index') }}"
            class="block shrink-0 border-t border-light-gray py-2.5 text-center text-xs font-semibold text-primary hover:underline">
            Open full inbox
        </a>
    </div>

    <!-- THREAD VIEW -->
    <div id="widgetThreadView" class="hidden h-full flex-col">
        <div class="flex shrink-0 items-center gap-2 border-b border-light-gray px-3 py-3">
            <button type="button" id="widgetThreadBackBtn"
                class="rounded-lg p-1.5 text-charcoal/50 hover:bg-ice-blue hover:text-primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary"
                id="widgetThreadAvatar"></div>
            <p id="widgetThreadName" class="truncate text-sm font-bold text-charcoal"></p>
            <div class="ml-auto flex items-center gap-1">
                <button type="button" id="widgetThreadDeleteBtn" title="Delete chat"
                    class="rounded-lg p-1.5 text-charcoal/40 transition hover:bg-sale-red/10 hover:text-sale-red">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                    </svg>
                </button>
                <button type="button" id="widgetThreadCloseBtn"
                    class="rounded-lg p-1.5 text-charcoal/40 hover:text-charcoal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div id="widgetThreadProductCard"
            class="hidden shrink-0 items-center gap-2.5 border-b border-light-gray bg-ice-blue/40 px-3 py-2">
            <img id="widgetThreadProductImage" src="" alt=""
                class="h-9 w-9 shrink-0 rounded-lg bg-ice-blue/60 object-cover">
            <div class="min-w-0 flex-1">
                <p id="widgetThreadProductName" class="truncate text-xs font-semibold text-charcoal"></p>
                <p id="widgetThreadProductPrice" class="text-[11px] font-bold text-primary"></p>
            </div>
        </div>

        <div id="widgetThreadMessages" class="flex-1 space-y-2.5 overflow-y-auto p-3"></div>

        <form id="widgetThreadForm" class="flex shrink-0 items-center gap-2 border-t border-light-gray p-2.5">
            <input type="text" id="widgetThreadInput" maxlength="2000" placeholder="Type a message here"
                class="flex-1 rounded-full border border-light-gray px-3.5 py-2 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
            <button type="submit" class="shrink-0 rounded-full bg-primary p-2 text-white transition hover:bg-sky-blue">
                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6" />
                </svg>
            </button>
        </form>
    </div>

</div>
