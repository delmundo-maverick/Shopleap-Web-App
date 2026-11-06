<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Rider Applications | Shopleap Logistics</title>
</head>

<body class="min-h-screen text-charcoal bg-gray-50/50" x-data="{ rejectModalOpen: false, rejectingRiderId: null }">
    <div class="frost-surface"></div>

    <div class="flex min-h-screen">
        <x-navbars.logistics active="riders" />

        <div class="flex min-w-0 flex-1 flex-col">
            <!-- HEADER -->
            <header
                class="workspace-header">
                <div class="min-w-0">
                    <x-nav.toggle target="workspace-navigation" />
                    <div>
                        <h2 class="workspace-header-title">Rider Applications</h2>
                        <p class="workspace-header-subtitle">{{ $riders->total() }} rider accounts total</p>
                    </div>
                </div>

                <div class="hidden items-center gap-2 text-xs text-charcoal/60 sm:flex">
                    <span class="h-2 w-2 rounded-full bg-primary"></span>
                    <span>Riders workspace</span>
                </div>
            </header>

            <main class="flex-1 space-y-6 p-8">

                @if (session('status'))
                    <div
                        class="rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- SEARCH & FILTER PANEL -->
                <div class="rounded-2xl border border-gray-200/60 bg-white p-4 shadow-sm">
                    <form method="GET" class="flex flex-col gap-3 md:flex-row md:items-center">
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Search by name or email..."
                                class="w-full rounded-xl border-0 bg-transparent py-2.5 pl-10 pr-4 text-sm text-charcoal outline-none placeholder:text-charcoal/40 focus:ring-0">
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit"
                                class="rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-blue">
                                Search
                            </button>

                            <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-8 text-sm font-medium text-charcoal/70 outline-none transition focus:border-primary">
                                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All registrations
                                </option>
                                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>
                                    Pending {{ isset($counts['pending']) ? '(' . $counts['pending'] . ')' : '' }}
                                </option>
                                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>
                                    Approved {{ isset($counts['approved']) ? '(' . $counts['approved'] . ')' : '' }}
                                </option>
                                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>
                                    Rejected {{ isset($counts['rejected']) ? '(' . $counts['rejected'] . ')' : '' }}
                                </option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- DATA TABLE PANEL -->
                <div class="overflow-hidden rounded-2xl border border-gray-200/60 bg-white shadow-sm">
                    @if ($riders->isEmpty())
                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <p class="text-sm font-semibold text-charcoal">No riders found</p>
                            <p class="mt-1 text-xs text-charcoal/50">Rider applications will appear here once couriers
                                register under your hub.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="app-table w-full text-left text-sm">
                                <thead>
                                    <tr
                                        class="bg-gray-50/50 text-xs font-semibold text-charcoal/50">
                                        <th class="px-6 py-4 font-normal">Rider</th>
                                        <th class="px-6 py-4 font-normal">Vehicle</th>
                                        <th class="px-6 py-4 font-normal">Contact no.</th>
                                        <th class="px-6 py-4 font-normal">Registration</th>
                                        <th class="px-6 py-4 font-normal">Submitted</th>
                                        <th class="px-6 py-4 font-normal text-right"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($riders as $rider)
                                        <tr class="transition hover:bg-gray-50/50">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                                                        {{ strtoupper(substr($rider->full_name ?? 'R', 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-charcoal">{{ $rider->full_name }}
                                                        </p>
                                                        <p class="text-xs text-charcoal/40">
                                                            {{ $rider->user->email ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-charcoal/70">
                                                <p class="text-sm text-charcoal">{{ $rider->vehicle_type }}</p>
                                                <p class="text-xs text-charcoal/40">{{ $rider->plate_number }}</p>
                                            </td>
                                            <td class="px-6 py-4 text-charcoal/70">{{ $rider->contact_no }}</td>
                                            <td class="px-6 py-4">
                                                @if ($rider->status === 'pending')
                                                    <span
                                                        class="inline-flex items-center gap-1.5 rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                                                        Pending
                                                    </span>
                                                @elseif ($rider->status === 'approved')
                                                    <span
                                                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                        Approved
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-medium text-red-600">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                        Rejected
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-xs text-charcoal/50">
                                                {{ $rider->created_at->diffForHumans() }}
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                @if ($rider->status === 'pending')
                                                    <div class="flex justify-end gap-2">
                                                        <form method="POST"
                                                            action="{{ route('logistics.riders.approve', $rider) }}">
                                                            @csrf
                                                            <button type="submit"
                                                                class="rounded-lg bg-leaf-green px-3.5 py-1.5 text-xs font-semibold text-white transition hover:brightness-110">
                                                                Approve
                                                            </button>
                                                        </form>
                                                        <button type="button"
                                                            @click="rejectModalOpen = true; rejectingRiderId = {{ $rider->id }}"
                                                            class="rounded-lg bg-sale-red px-3.5 py-1.5 text-xs font-semibold text-white transition hover:brightness-110">
                                                            Reject
                                                        </button>
                                                    </div>
                                                @elseif ($rider->status === 'approved')
                                                    <form method="POST"
                                                        action="{{ route('logistics.riders.toggle-active', $rider) }}">
                                                        @csrf
                                                        <button type="submit"
                                                            class="rounded-lg bg-primary px-4 py-1.5 text-xs font-semibold text-white transition hover:bg-sky-blue">
                                                            {{ $rider->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div>{{ $riders->links() }}</div>

            </main>
        </div>
    </div>

    <!-- REJECT REASON MODAL -->
    <div x-show="rejectModalOpen" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-charcoal/50 px-4">
        <div @click.outside="rejectModalOpen = false" x-transition
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-charcoal">Reject Application</h3>
            <p class="mt-1 text-xs text-charcoal/55">Provide a reason — this will be visible to the applicant.</p>

            <form method="POST" :action="`/logistics/riders/${rejectingRiderId}/reject`" class="mt-4">
                @csrf
                <textarea name="rejection_reason" rows="3" required maxlength="500"
                    placeholder="e.g. Documents unclear, please re-upload"
                    class="w-full resize-none rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"></textarea>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="rejectModalOpen = false"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-charcoal/60 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-xl bg-sale-red px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">
                        Confirm Reject
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>
