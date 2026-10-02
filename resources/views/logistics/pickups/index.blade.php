<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Pickup Requests | Shopleap Logistics</title>
</head>

<body class="min-h-screen text-charcoal">
    <div class="frost-surface"></div>

    <div class="flex min-h-screen">
        <x-navbars.logistics active="pickups" />

        <div class="flex flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" @click="$store.sidebar.open = true"
                        class="frost-btn rounded-xl p-2 text-charcoal/60 transition hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Pickup Requests</h2>
                        <p class="text-sm text-charcoal/55">{{ $orders->total() }} order(s) ready for pickup</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 space-y-5 p-6">

                @if (session('status'))
                    <div
                        class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($availableRiders->isEmpty())
                    <div
                        class="frost-panel rounded-2xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
                        You have no active approved riders yet. Approve and activate riders under Rider Applications
                        before assigning pickups.
                    </div>
                @endif

                @if ($orders->isEmpty())
                    <div
                        class="frost-panel-solid flex h-64 flex-col items-center justify-center rounded-2xl text-center">
                        <p class="text-sm font-semibold text-charcoal">No pickup requests right now</p>
                        <p class="mt-1 text-xs text-charcoal/50">Orders marked "Ready for Pickup" by sellers will appear
                            here.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($orders as $order)
                            <div class="frost-panel-solid rounded-2xl p-5">

                                <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-charcoal">Order #{{ $order->id }}</p>
                                        <p class="text-xs text-charcoal/50">
                                            Seller:
                                            {{ $order->seller->sellerProfile->business_name ?? $order->seller->name }}
                                        </p>
                                        <p class="mt-1 text-xs text-charcoal/50">
                                            {{ $order->items->sum('quantity') }} item(s) ·
                                            ₱{{ number_format($order->total_amount, 2) }}
                                        </p>
                                    </div>

                                    @if ($order->logistics_profile_id)
                                        <span
                                            class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                            Claimed by your hub
                                        </span>
                                    @else
                                        <span
                                            class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-600">
                                            Unclaimed
                                        </span>
                                    @endif
                                </div>

                                @if (!$order->assigned_rider_id)
                                    <form method="POST" action="{{ route('logistics.pickups.assign-rider', $order) }}"
                                        class="flex flex-wrap items-center gap-3 border-t border-white/50 pt-3">
                                        @csrf
                                        <select name="rider_id" required
                                            class="frost-input rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                                            <option value="" disabled selected>Assign a rider…</option>
                                            @foreach ($availableRiders as $rider)
                                                <option value="{{ $rider->id }}">{{ $rider->full_name }} —
                                                    {{ $rider->vehicle_type }} ({{ $rider->plate_number }})</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" @if ($availableRiders->isEmpty()) disabled @endif
                                            class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50">
                                            Assign for Pickup
                                        </button>
                                    </form>
                                @else
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-3 border-t border-white/50 pt-3">
                                        <p class="text-sm text-charcoal/70">
                                            Assigned to <span
                                                class="font-semibold text-charcoal">{{ $order->assignedRider->full_name }}</span>
                                            — {{ $order->assignedRider->vehicle_type }}
                                            ({{ $order->assignedRider->plate_number }})
                                        </p>

                                        <form method="POST" action="{{ route('logistics.pickups.confirm', $order) }}">
                                            @csrf
                                            <button type="submit"
                                                class="rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110">
                                                Confirm Picked Up
                                            </button>
                                        </form>
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    </div>

                    <div>{{ $orders->links() }}</div>
                @endif

            </main>
        </div>
    </div>

</body>

</html>
