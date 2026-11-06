<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Hubs by Company | Shopleap Admin</title>
</head>
<body class="min-h-screen text-charcoal">
    <div class="frost-surface"></div>
    <div class="flex min-h-screen">
        <x-navbars.admin active="dashboard" />
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="workspace-header">
                <h2 class="workspace-header-title">Hubs by Company</h2>
            </header>
            <main class="flex-1 overflow-y-auto p-6">
                @foreach ($hubs as $companyKey => $group)
                    <section class="mb-6 rounded-2xl border border-line bg-white p-5">
                        <h3 class="mb-3 text-section-label">{{ $group->first()->company_name ?: 'Unnamed company' }} ({{ $group->count() }} hub(s))</h3>
                        <ul class="space-y-2 text-sm">
                            @foreach ($group as $hub)
                                <li class="flex flex-wrap items-center gap-3 border-b border-line/50 pb-2">
                                    <span class="font-semibold">{{ $hub->business_name }}</span>
                                    <span class="text-charcoal/60">{{ $hub->municipality }}, {{ $hub->province }}</span>
                                    <span class="text-xs text-charcoal/50">status: {{ $hub->status }}</span>
                                    @if ($hub->joining_existing_company)
                                        <span class="rounded bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-600">joining existing company</span>
                                    @endif
                                    <span class="text-xs text-charcoal/50">{{ $hub->serviceAreas->count() }} service area(s), {{ $hub->serviceAreas->sum(fn($a) => $a->barangays->count()) }} barangay(s)</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </main>
        </div>
    </div>
</body>
</html>