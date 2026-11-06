<div data-account-content="messages" class="flex h-[calc(100dvh-12rem)] min-h-[32rem] flex-col gap-4">

        {{-- Page Heading --}}
        <div class="flex shrink-0 items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-lg font-bold tracking-tight text-charcoal sm:text-xl">
                    Messages
                </h1>

                <p class="hidden truncate text-sm text-charcoal/60 sm:block">
                    Chats with sellers and your support tickets.
                </p>
            </div>

            <a href="{{ route('help.index') }}"
                class="shrink-0 rounded-xl border border-primary/30 bg-white/70 px-4 py-2 text-xs font-semibold text-primary transition hover:bg-primary/5">
                Help Center
            </a>
        </div>

        {{-- Messaging Panel --}}
        <div class="min-w-0 flex-1">
            @include('messaging._panel', ['variant' => 'flat'])
        </div>

</div>
