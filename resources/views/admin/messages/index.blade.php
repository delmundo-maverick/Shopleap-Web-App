<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/messaging.js'])

    <title>Messages | Shopleap Admin</title>
</head>

<body class="h-dvh overflow-hidden text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    <div class="flex h-dvh">
        <x-navbars.admin active="messages" />

        {{-- Content column: viewport tall. Top bar and filters keep natural height, the panel takes the rest. --}}
        <div class="flex h-dvh min-w-0 flex-1 flex-col">
            <header
                class="workspace-header">
                <div class="min-w-0">
                    <x-nav.toggle target="workspace-navigation" />
                    <div class="min-w-0">
                        <h2 class="workspace-header-title">Support Inbox</h2>
                        <p class="workspace-header-subtitle">Tickets and chats from Buyers, Sellers,
                            and
                            Logistics</p>
                    </div>
                </div>
                <x-notification-bell />
            </header>

            <main class="flex min-h-0 flex-1 flex-col gap-2 p-3 sm:p-4">

                <div class="flex shrink-0 flex-col gap-2 xl:flex-row xl:items-center">

                    <!-- ROLE TABS -->
                    <div class="frost-panel-solid flex min-w-0 flex-1 overflow-x-auto rounded-2xl">
                        @foreach (['all' => 'All', 'buyer' => 'Buyers', 'seller' => 'Sellers', 'logistics' => 'Logistics'] as $key => $label)
                            <a href="{{ route('messages.index', ['role' => $key, 'view' => $view, 'assign' => $assign]) }}"
                                class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2 text-center text-sm font-semibold transition
                                    {{ $role === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/50 hover:text-charcoal' }}">
                                {{ $label }}
                                @if (($roleCounts[$key] ?? 0) > 0)
                                    <span
                                        class="rounded-full px-1.5 py-0.5 text-[10px] font-bold
                                        {{ $role === $key ? 'bg-primary/10 text-primary' : 'bg-light-gray text-charcoal/50' }}">
                                        {{ $roleCounts[$key] }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <!-- STATUS -->
                        <div class="inline-flex shrink-0 rounded-xl border border-charcoal/10 bg-white/40 p-1">
                            @foreach (['open' => 'Open', 'resolved' => 'Resolved'] as $key => $label)
                                <a href="{{ route('messages.index', ['role' => $role, 'view' => $key, 'assign' => $assign]) }}"
                                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition
                                        {{ $view === $key ? 'bg-primary text-white' : 'text-charcoal/60 hover:text-charcoal' }}">
                                    {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
                                </a>
                            @endforeach
                        </div>

                        <!-- ASSIGNMENT QUEUE -->
                        <div class="inline-flex shrink-0 rounded-xl border border-charcoal/10 bg-white/40 p-1">
                            @foreach (['all' => 'Everyone', 'mine' => 'Mine', 'unassigned' => 'Unassigned'] as $key => $label)
                                <a href="{{ route('messages.index', ['role' => $role, 'view' => $view, 'assign' => $key]) }}"
                                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition
                                        {{ $assign === $key ? 'bg-primary text-white' : 'text-charcoal/60 hover:text-charcoal' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                @include('messaging._panel', ['variant' => 'admin'])

            </main>
        </div>
    </div>
</body>

</html>
