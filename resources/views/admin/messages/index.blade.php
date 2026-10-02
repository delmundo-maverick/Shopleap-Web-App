<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/messaging.js'])

    <title>Messages | Shopleap Admin</title>
</head>

<body class="min-h-screen text-charcoal" x-data="{}">
    <div class="frost-surface"></div>

    <div class="flex min-h-screen">
        <x-navbars.admin active="messages" />

        <div class="flex flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Messages</h2>
                        <p class="text-sm text-charcoal/55">Help requests from Buyers, Sellers, and Logistics</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 space-y-4 p-6">

                <!-- ROLE TABS -->
                <div class="frost-panel-solid flex rounded-2xl">
                    @foreach (['all' => 'All', 'buyer' => 'Buyers', 'seller' => 'Sellers', 'logistics' => 'Logistics'] as $key => $label)
                        <a href="{{ route('messages.index', ['role' => $key, 'view' => $view]) }}"
                            class="flex flex-1 items-center justify-center gap-1.5 border-b-2 px-3 py-3 text-center text-sm font-semibold transition
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

                <!-- STATUS SEGMENTED CONTROL -->
                <div class="inline-flex rounded-xl border border-white/50 bg-white/40 p-1">
                    @foreach (['open' => 'Open', 'resolved' => 'Resolved'] as $key => $label)
                        <a href="{{ route('messages.index', ['role' => $role, 'view' => $key]) }}"
                            class="rounded-lg px-4 py-1.5 text-xs font-semibold transition
                                {{ $view === $key ? 'bg-primary text-white' : 'text-charcoal/60 hover:text-charcoal' }}">
                            {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
                        </a>
                    @endforeach
                </div>

                @include('messaging._panel', ['variant' => 'admin'])

            </main>
        </div>
    </div>
</body>

</html>
