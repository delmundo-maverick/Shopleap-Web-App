<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Bag Transfers | Shopleap Admin</title>
</head>
<body class="min-h-screen text-charcoal">
    <div class="frost-surface"></div>
    <div class="flex min-h-screen">
        <x-navbars.admin active="dashboard" />
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="workspace-header">
                <h2 class="workspace-header-title">Bag / Transfer Overview</h2>
            </header>
            <main class="flex-1 overflow-y-auto p-6">
                <div class="mb-4 flex flex-wrap gap-3 text-sm">
                    @foreach ($counts as $status => $count)
                        <span class="rounded-lg border border-line bg-white px-3 py-1.5">{{ $status }}: <b>{{ $count }}</b></span>
                    @endforeach
                </div>
                <div class="overflow-x-auto rounded-2xl border border-line bg-white">
                    <table class="app-table w-full text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">From</th>
                                <th class="px-4 py-3">To</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Dispatched</th>
                                <th class="px-4 py-3">Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bags as $bag)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $bag->code }}</td>
                                    <td class="px-4 py-3">{{ $bag->fromHub?->business_name }}</td>
                                    <td class="px-4 py-3">{{ $bag->toHub?->business_name }}</td>
                                    <td class="px-4 py-3">{{ $bag->status }}</td>
                                    <td class="px-4 py-3 text-xs">{{ $bag->dispatched_at?->format('M d, g:i A') }}</td>
                                    <td class="px-4 py-3 text-xs">{{ $bag->received_at?->format('M d, g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $bags->links() }}</div>
            </main>
        </div>
    </div>
</body>
</html>