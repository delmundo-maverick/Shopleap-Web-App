<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Logistics Management | Shopleap Admin</title>

    {{-- Page script must come BEFORE app.js so it can listen for `alpine:init`. --}}
    @vite(['resources/js/pages/admin/logistics.js', 'resources/js/app.js', 'resources/css/app.css'])
</head>

@php
    $filters = [
        [
            'name' => 'reg_status',
            'label' => 'Filter by registration status',
            'options' => [
                'all' => 'All registrations',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
        ],
        [
            'name' => 'acc_status',
            'label' => 'Filter by account status',
            'options' => [
                'all' => 'All accounts',
                'active' => 'Active',
                'suspended' => 'Suspended',
                'deactivated' => 'Deactivated',
            ],
        ],
    ];

    $columns = ['Applicant', 'Business', 'Registration', 'Account', 'Submitted'];
@endphp

<body class="min-h-screen bg-ice-blue text-charcoal antialiased" x-data="logisticsPage()"
    @keydown.escape.window="modalOpen && closeModal()">

    <div class="flex min-h-screen">

        <x-navbars.admin active="logistics" />

        <div class="flex min-w-0 flex-1 flex-col">

            <!-- TOPBAR -->
            <header
                class="workspace-header">
                <div class="min-w-0">
                    <x-nav.toggle target="workspace-navigation" />
                    <div>
                        <h1 class="workspace-header-title">Logistics management</h1>
                        <p class="workspace-header-subtitle">{{ $logisticsPartners->total() }} sorting center(s) total
                        </p>
                    </div>
                </div>
                <x-notification-bell />
            </header>

            <main class="flex-1 space-y-5 p-6">

                <!-- SEARCH & FILTERS -->
                <form method="GET"
                    class="frost-panel flex flex-col gap-3 rounded-3xl p-4 sm:flex-row sm:items-center">

                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by name, business, or email..." aria-label="Search logistics partners"
                            class="frost-input w-full rounded-xl py-2.5 pl-10 pr-4 text-sm">
                    </div>

                    @foreach ($filters as $filter)
                        <select name="{{ $filter['name'] }}" aria-label="{{ $filter['label'] }}"
                            onchange="this.form.submit()" class="frost-input rounded-xl px-3.5 py-2.5 text-sm">
                            @foreach ($filter['options'] as $value => $label)
                                <option value="{{ $value }}" @selected(request($filter['name'], 'all') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    @endforeach

                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:bg-sky-blue">
                        Search
                    </button>
                </form>

                <!-- TABLE -->
                <div class="frost-panel overflow-hidden rounded-3xl">

                    @if ($logisticsPartners->isEmpty())
                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <p class="text-sm font-semibold text-charcoal">No logistics partners found</p>
                            <p class="mt-1 text-xs text-charcoal/50">Try adjusting your search or filters.</p>
                        </div>
                    @else
                        <table class="app-table w-full text-left text-sm">
                            <thead>
                                <tr>
                                    @foreach ($columns as $column)
                                        <th scope="col" class="px-5 py-3 font-medium text-charcoal/50">
                                            {{ $column }}</th>
                                    @endforeach
                                    <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logisticsPartners as $profile)
                                    <tr data-row-id="{{ $profile->id }}" class="transition hover:bg-white/60">
                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-charcoal">
                                                {{ $profile->first_name }} {{ $profile->last_name }}
                                            </p>
                                            <p class="text-xs text-charcoal/50">{{ $profile->user->email }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-charcoal/80">
                                            {{ $profile->business_name }}
                                            @if ($profile->joining_existing_company)
                                                <span class="ml-1 rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-600">joining existing company</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4">
                                            <span data-badge="registration"
                                                class="status-badge reg-badge--{{ $profile->status }}">{{ $profile->status }}</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span data-badge="account"
                                                class="status-badge acc-badge--{{ $profile->user->account_status }}">{{ $profile->user->account_status }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-charcoal/60">
                                            {{ $profile->created_at->diffForHumans() }}
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <button type="button" @click="openRecord({{ $profile->id }})"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white shadow-sm shadow-primary/20 transition hover:bg-sky-blue">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                </div>

                <div class="pagination-frost flex justify-center">{{ $logisticsPartners->links() }}</div>

            </main>
        </div>
    </div>


    <!-- MODAL -->
    <div x-show="modalOpen" x-cloak x-transition.opacity @click.self="closeModal()"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-charcoal/40 p-4 backdrop-blur-sm">

        <div x-show="modalOpen" role="dialog" aria-modal="true" aria-labelledby="logistics-modal-title"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-white/60 bg-white/90 shadow-2xl backdrop-blur-2xl">

            <div
                class="sticky top-0 z-10 flex items-center justify-between border-b border-white/60 bg-white/80 px-6 py-4 backdrop-blur-xl">
                <div>
                    <h3 id="logistics-modal-title" class="text-lg font-bold text-charcoal"
                        x-text="record ? record.business_name : '—'">—</h3>
                    <p class="text-xs text-charcoal/50" x-text="record ? `Submitted ${record.submitted_at}` : '—'">—
                    </p>
                </div>
                <button type="button" @click="closeModal()" aria-label="Close"
                    class="rounded-lg p-1.5 text-charcoal/50 transition hover:bg-ice-blue hover:text-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div x-show="loading" class="flex h-40 items-center justify-center text-sm text-charcoal/40">
                Loading...
            </div>

            <div x-show="!loading && record" x-cloak class="p-6">
                <template x-if="record">
                    <div class="space-y-5">

                        <!-- Statuses -->
                        <div class="flex flex-wrap gap-2">
                            <span :class="regBadgeClass(record.registration_status)"
                                x-text="record.registration_status"></span>
                            <span :class="accBadgeClass(record.account_status)" x-text="record.account_status"></span>
                        </div>

                        <div x-show="record.registration_status === 'rejected' && record.rejection_reason"
                            class="rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                            <span class="font-semibold">Rejection reason:</span>
                            <span x-text="record.rejection_reason"></span>
                        </div>

                        <!-- Personal info -->
                        <div class="modal-section">
                            <p class="modal-section__title mb-3">Personal information</p>
                            <dl class="grid grid-cols-2 gap-3 text-sm">
                                <template x-for="field in personalInfo" :key="field.label">
                                    <div>
                                        <dt class="text-charcoal/50" x-text="field.label"></dt>
                                        <dd class="font-medium" :class="field.classes" x-text="field.value"></dd>
                                    </div>
                                </template>
                            </dl>
                        </div>

                        <!-- Address -->
                        <div class="modal-section">
                            <p class="modal-section__title mb-2">Address</p>
                            <p class="text-sm" x-text="record.address"></p>
                        </div>

                        <!-- Documents -->
                        <div class="modal-section">
                            <p class="modal-section__title mb-3">Documents</p>
                            <div class="grid grid-cols-2 gap-3">
                                <a :href="record.id_url" target="_blank" rel="noopener noreferrer"
                                    class="block overflow-hidden rounded-xl border border-white/70">
                                    <img :src="record.id_url" alt="Valid ID" class="h-28 w-full object-cover">
                                    <p
                                        class="bg-white/70 px-2 py-1.5 text-center text-xs font-semibold text-charcoal/70">
                                        Valid ID
                                    </p>
                                </a>
                                <a :href="record.permit_url" target="_blank" rel="noopener noreferrer"
                                    class="block overflow-hidden rounded-xl border border-white/70">
                                    <img :src="record.permit_url" alt="Business/DTI permit"
                                        class="h-28 w-full object-cover">
                                    <p
                                        class="bg-white/70 px-2 py-1.5 text-center text-xs font-semibold text-charcoal/70">
                                        Business/DTI permit
                                    </p>
                                </a>
                            </div>
                        </div>

                        <!-- Registration decision -->
                        <div x-show="record.registration_status === 'pending'"
                            class="modal-section space-y-3 bg-ice-blue/60">
                            <p class="modal-section__title">Registration decision</p>
                            <div class="flex gap-3">
                                <button type="button" @click="approve()"
                                    class="flex-1 rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-leaf-green/30 transition hover:brightness-105">
                                    Approve
                                </button>
                                <button type="button" @click="showRejectForm = !showRejectForm"
                                    :aria-expanded="showRejectForm"
                                    class="flex-1 rounded-xl border border-sale-red/30 bg-sale-red/10 px-4 py-2.5 text-sm font-bold text-sale-red transition hover:bg-sale-red/20">
                                    Reject
                                </button>
                            </div>
                            <div x-show="showRejectForm" x-cloak class="space-y-2 pt-2">
                                <textarea x-model="rejectReason" rows="2" placeholder="Reason for rejection..."
                                    aria-label="Reason for rejection" class="frost-input w-full rounded-xl px-3 py-2 text-sm focus:!border-sale-red"></textarea>
                                <button type="button" @click="confirmReject()"
                                    class="w-full rounded-xl bg-sale-red px-4 py-2 text-sm font-bold text-white transition hover:brightness-105">
                                    Confirm rejection
                                </button>
                            </div>
                        </div>

                        <!-- Account status (only when approved) -->
                        <div x-show="record.registration_status === 'approved'"
                            class="modal-section space-y-3 bg-ice-blue/60">
                            <p class="modal-section__title">Account status</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="setAccountStatus('active')"
                                    class="rounded-lg border border-leaf-green/30 bg-leaf-green/10 px-3.5 py-2 text-xs font-semibold text-leaf-green transition hover:bg-leaf-green/20">
                                    Activate
                                </button>
                                <button type="button" @click="setAccountStatus('suspended')"
                                    class="rounded-lg border border-warning/30 bg-warning/10 px-3.5 py-2 text-xs font-semibold text-warning transition hover:bg-warning/20">
                                    Suspend
                                </button>
                                <button type="button" @click="setAccountStatus('deactivated')"
                                    class="rounded-lg border border-sale-red/30 bg-sale-red/10 px-3.5 py-2 text-xs font-semibold text-sale-red transition hover:bg-sale-red/20">
                                    Deactivate
                                </button>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

        </div>
    </div>


    <!-- TOAST -->
    <div x-show="toast.show" x-cloak x-transition role="status" aria-live="polite"
        :class="toast.error ? 'bg-sale-red/90' : 'bg-leaf-green/90'"
        class="fixed bottom-6 right-6 z-[80] max-w-sm rounded-2xl border border-white/40 px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-xl"
        x-text="toast.message">
    </div>

</body>

</html>
