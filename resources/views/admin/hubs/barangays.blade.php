<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Hub Barangays | Shopleap Admin</title>
</head>
<body class="min-h-screen text-charcoal">
    <div class="frost-surface"></div>
    <div class="flex min-h-screen">
        <x-navbars.admin active="dashboard" />
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="frost-panel-solid flex h-20 items-center justify-between border-0 border-b border-line px-6">
                <h2 class="text-page-title">Barangays Owned by Hubs</h2>
            </header>
            <main class="flex-1 overflow-y-auto p-6">
                @foreach ($hubs as $hub)
                    <section class="mb-6 rounded-2xl border border-line bg-white p-5">
                        <h3 class="mb-3 text-section-label">{{ $hub->business_name }} — {{ $hub->company_name }}</h3>
                        @foreach ($hub->serviceAreas as $area)
                            <div class="mb-3">
                                <p class="text-sm font-semibold">{{ $area->province }}, {{ $area->municipality }} ({{ $area->barangays->count() }} barangay(s))</p>
                                <p class="text-xs text-charcoal/60">{{ $area->barangays->pluck('barangay')->implode(', ') }}</p>
                            </div>
                        @endforeach
                        @if ($hub->serviceAreas->isEmpty())
                            <p class="text-xs text-charcoal/50">No service areas configured (falls back to base municipality).</p>
                        @endif
                    </section>
                @endforeach
            </main>
        </div>
    </div>
</body>
</html>