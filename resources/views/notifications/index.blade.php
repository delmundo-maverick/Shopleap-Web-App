@php $role = auth()->user()->role; @endphp

@if ($role === 'logistics')
    <x-logistics.layout title="Notifications" :subtitle="$unread . ' unread'" active="notifications">
        @include('notifications._list', ['panel' => 'frost-panel-solid rounded-2xl'])
    </x-logistics.layout>
@else
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <title>Notifications | Shopleap</title>
    </head>

    @if ($role === 'buyer')

        <body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">
            <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

            <main class="mx-auto w-full max-w-3xl flex-1 px-3 py-5 md:px-8 md:py-6">
                <h1 class="mb-4 text-xl font-bold text-charcoal">Notifications</h1>
                @include('notifications._list', [
                    'panel' => 'rounded-lg bg-white shadow-sm shadow-charcoal/5',
                ])
            </main>

            <x-Buyer.message-widget />
            <x-footer />
        </body>
    @else

        <body class="min-h-screen text-charcoal" x-data="{}">
            <div class="frost-surface"></div>

            <div class="flex min-h-screen">
                @if ($role === 'seller')
                    <x-navbars.seller active="notifications" />
                @else
                    <x-navbars.admin active="notifications" />
                @endif

                <div class="flex min-w-0 flex-1 flex-col">
                    <header
                        class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                        <div class="flex items-center gap-3">
                            <x-nav.toggle target="workspace-navigation" />
                            <div>
                                <h1 class="text-xl font-bold text-charcoal">Notifications</h1>
                                <p class="text-sm text-charcoal/55">{{ $unread }} unread</p>
                            </div>
                        </div>
                    </header>

                    <main class="flex-1 p-6">
                        <div class="mx-auto max-w-3xl">
                            @include('notifications._list', ['panel' => 'frost-panel-solid rounded-2xl'])
                        </div>
                    </main>
                </div>
            </div>
        </body>
    @endif

    </html>
@endif
