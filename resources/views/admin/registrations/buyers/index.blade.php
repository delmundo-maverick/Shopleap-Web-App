<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Buyer Management | Shopleap Admin</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal antialiased" x-data="buyersPage()">

    <div class="flex min-h-screen">

        <x-navbars.admin active="users" />

        <div class="flex flex-1 flex-col min-w-0">

            <!-- TOPBAR -->
            <header
                class="sticky top-0 z-30 flex h-20 shrink-0 items-center justify-between border-b border-white/60 bg-white/70 px-6 backdrop-blur-xl">
                <div class="flex items-center gap-3">
                    <button type="button" @click="$store.sidebar.open = true"
                        class="rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Buyer management</h2>
                        <p class="text-sm text-charcoal/55">{{ $buyers->total() }} buyer
                            account{{ $buyers->total() === 1 ? '' : 's' }} total</p>
                    </div>
                </div>

                <div
                    class="hidden items-center gap-2 rounded-full border border-white/60 bg-white/70 px-4 py-2 text-xs font-medium text-charcoal/60 sm:flex">
                    <span class="h-2 w-2 rounded-full bg-primary"></span>
                    Buyers workspace
                </div>
            </header>

            <main class="flex-1 space-y-5 p-6">

                <!-- SEARCH & FILTERS -->
                <form method="GET"
                    class="flex flex-col gap-3 rounded-3xl border border-white/60 bg-white/70 p-4 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.08)] backdrop-blur-xl sm:flex-row sm:items-center">

                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-charcoal/40" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by name or email..."
                            class="w-full rounded-xl border border-white/70 bg-white/80 py-2.5 pl-10 pr-4 text-sm text-charcoal outline-none transition placeholder:text-charcoal/40 focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary/10">
                    </div>

                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:bg-sky-blue">
                        Search
                    </button>

                    <select name="reg_status" onchange="this.form.submit()"
                        class="rounded-xl border border-white/70 bg-white/80 px-3.5 py-2.5 text-sm text-charcoal outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        <option value="all" {{ request('reg_status', 'all') === 'all' ? 'selected' : '' }}>All
                            registrations</option>
                        <option value="pending" {{ request('reg_status') === 'pending' ? 'selected' : '' }}>Pending
                        </option>
                        <option value="approved" {{ request('reg_status') === 'approved' ? 'selected' : '' }}>Approved
                        </option>
                        <option value="rejected" {{ request('reg_status') === 'rejected' ? 'selected' : '' }}>Rejected
                        </option>
                    </select>

                    <select name="acc_status" onchange="this.form.submit()"
                        class="rounded-xl border border-white/70 bg-white/80 px-3.5 py-2.5 text-sm text-charcoal outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        <option value="all" {{ request('acc_status', 'all') === 'all' ? 'selected' : '' }}>All
                            accounts</option>
                        <option value="active" {{ request('acc_status') === 'active' ? 'selected' : '' }}>Active
                        </option>
                        <option value="suspended" {{ request('acc_status') === 'suspended' ? 'selected' : '' }}>
                            Suspended</option>
                        <option value="deactivated" {{ request('acc_status') === 'deactivated' ? 'selected' : '' }}>
                            Deactivated</option>
                    </select>

                </form>

                <!-- TABLE -->
                <div
                    class="overflow-hidden rounded-3xl border border-white/60 bg-white/70 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.08)] backdrop-blur-xl">

                    @if ($buyers->isEmpty())

                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <div
                                class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-charcoal">No buyers found</p>
                            <p class="mt-1 text-xs text-charcoal/50">Try adjusting your search or filters.</p>
                        </div>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-white/60 bg-white/40">
                                <tr>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Buyer</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Contact no.</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Registration</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Account</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Submitted</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/60">
                                @foreach ($buyers as $profile)
                                    <tr data-row-id="{{ $profile->id }}" class="transition hover:bg-white/60">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                                                    {{ strtoupper(substr($profile->first_name, 0, 1) . substr($profile->last_name, 0, 1)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold text-charcoal">
                                                        {{ $profile->first_name }}
                                                        {{ $profile->last_name }}</p>
                                                    <p class="truncate text-xs text-charcoal/50">
                                                        {{ $profile->user->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 tabular-nums text-charcoal/80">
                                            {{ $profile->contact_no }}</td>
                                        <td class="px-5 py-4">
                                            <span data-badge="registration"
                                                class="reg-badge reg-badge--{{ $profile->status }} inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize">
                                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                {{ $profile->status }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span data-badge="account"
                                                class="acc-badge acc-badge--{{ $profile->user->account_status }} inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize">
                                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                {{ $profile->user->account_status }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-charcoal/60">
                                            {{ $profile->created_at->diffForHumans() }}</td>
                                        <td class="px-5 py-4 text-right">
                                            <button type="button" @click="openBuyer({{ $profile->id }})"
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

                <div class="pagination-frost flex justify-center">{{ $buyers->links() }}</div>

            </main>

        </div>

    </div>


    <!-- =====================================================
         MODAL
    ====================================================== -->

    <div x-show="modalOpen" x-cloak x-transition.opacity @click.self="closeModal()"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-charcoal/40 p-4 backdrop-blur-sm">

        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-white/60 bg-white/90 shadow-2xl backdrop-blur-2xl">

            <div
                class="sticky top-0 z-10 flex items-center justify-between border-b border-white/60 bg-white/80 px-6 py-4 backdrop-blur-xl">
                <div>
                    <h3 class="text-lg font-bold text-charcoal" x-text="buyer ? buyer.full_name : '—'">—</h3>
                    <p class="text-xs text-charcoal/50" x-text="buyer ? `Submitted ${buyer.submitted_at}` : '—'">—</p>
                </div>
                <button type="button" @click="closeModal()"
                    class="rounded-lg p-1.5 text-charcoal/50 transition hover:bg-ice-blue hover:text-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div x-show="loading" class="flex h-40 items-center justify-center text-sm text-charcoal/40">
                Loading...
            </div>

            <div x-show="!loading && buyer" x-cloak class="space-y-5 p-6">

                <template x-if="buyer">
                    <div class="space-y-5">

                        <div class="flex flex-wrap gap-2">
                            <span
                                :class="`reg-badge reg-badge--${buyer.registration_status} inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize`">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                <span x-text="buyer.registration_status"></span>
                            </span>
                            <span
                                :class="`acc-badge acc-badge--${buyer.account_status} inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize`">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                <span x-text="buyer.account_status"></span>
                            </span>
                        </div>

                        <div x-show="buyer.registration_status === 'rejected' && buyer.rejection_reason"
                            class="rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                            <span class="font-semibold">Rejection reason:</span>
                            <span x-text="buyer.rejection_reason"></span>
                        </div>

                        <div class="rounded-2xl border border-white/60 bg-white/60 p-4">
                            <p class="mb-3 text-xs font-semibold text-primary">Personal information</p>
                            <dl class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-charcoal/50">Full name</dt>
                                    <dd class="font-medium" x-text="buyer.full_name"></dd>
                                </div>
                                <div>
                                    <dt class="text-charcoal/50">Sex</dt>
                                    <dd class="font-medium capitalize" x-text="buyer.sex"></dd>
                                </div>
                                <div>
                                    <dt class="text-charcoal/50">Email</dt>
                                    <dd class="font-medium" x-text="buyer.email"></dd>
                                </div>
                                <div>
                                    <dt class="text-charcoal/50">Contact no.</dt>
                                    <dd class="font-medium tabular-nums" x-text="buyer.contact_no"></dd>
                                </div>
                                <div>
                                    <dt class="text-charcoal/50">Birthday</dt>
                                    <dd class="font-medium" x-text="buyer.birthday"></dd>
                                </div>
                                <div>
                                    <dt class="text-charcoal/50">Age</dt>
                                    <dd class="font-medium" x-text="`${buyer.age} years old`"></dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-white/60 bg-white/60 p-4">
                            <p class="mb-2 text-xs font-semibold text-primary">Address</p>
                            <p class="text-sm" x-text="buyer.address"></p>
                        </div>

                        <div class="rounded-2xl border border-white/60 bg-white/60 p-4">
                            <p class="mb-3 text-xs font-semibold text-primary">Valid ID</p>
                            <a :href="buyer.id_url" target="_blank"
                                class="block max-w-xs overflow-hidden rounded-xl border border-white/70">
                                <img :src="buyer.id_url" alt="Valid ID" class="h-40 w-full object-cover">
                                <p class="bg-white/70 px-2 py-1.5 text-center text-xs font-semibold text-charcoal/70">
                                    Valid ID — click to view full</p>
                            </a>
                        </div>

                        <!-- PENDING ACTIONS -->
                        <div x-show="buyer.registration_status === 'pending'"
                            class="space-y-3 rounded-2xl border border-white/60 bg-ice-blue/60 p-4">
                            <p class="text-xs font-semibold text-primary">Registration decision</p>
                            <div class="flex gap-3">
                                <button type="button" @click="approve()"
                                    class="flex-1 rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-leaf-green/30 transition hover:brightness-105">
                                    Approve
                                </button>
                                <button type="button" @click="showRejectForm = !showRejectForm"
                                    class="flex-1 rounded-xl border border-sale-red/30 bg-sale-red/10 px-4 py-2.5 text-sm font-bold text-sale-red transition hover:bg-sale-red/20">
                                    Reject
                                </button>
                            </div>
                            <div x-show="showRejectForm" x-cloak class="space-y-2 pt-2">
                                <textarea x-model="rejectReason" rows="2" placeholder="Reason for rejection..."
                                    class="w-full rounded-xl border border-white/70 bg-white/80 px-3 py-2 text-sm outline-none focus:border-sale-red focus:ring-4 focus:ring-sale-red/10"></textarea>
                                <button type="button" @click="confirmReject()"
                                    class="w-full rounded-xl bg-sale-red px-4 py-2 text-sm font-bold text-white transition hover:brightness-105">
                                    Confirm rejection
                                </button>
                            </div>
                        </div>

                        <!-- ACCOUNT STATUS ACTIONS -->
                        <div x-show="buyer.registration_status === 'approved'"
                            class="space-y-3 rounded-2xl border border-white/60 bg-ice-blue/60 p-4">
                            <p class="text-xs font-semibold text-primary">Account status</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="setAccountStatus('active')"
                                    class="rounded-lg border border-leaf-green/30 bg-leaf-green/10 px-3.5 py-2 text-xs font-semibold text-leaf-green transition hover:bg-leaf-green/20">
                                    Activate
                                </button>
                                <button type="button" @click="setAccountStatus('suspended')"
                                    class="rounded-lg border border-orange-300 bg-orange-50 px-3.5 py-2 text-xs font-semibold text-orange-600 transition hover:bg-orange-100">
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
    <div x-show="toast.show" x-cloak x-transition
        class="fixed bottom-6 right-6 z-[80] max-w-sm rounded-2xl border border-white/40 px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-xl"
        :style="`background: ${toast.error ? 'rgba(228,87,46,0.92)' : 'rgba(76,175,125,0.92)'}`"
        x-text="toast.message">
    </div>


    <style>
        .reg-badge--pending {
            background: rgb(255 237 213 / 0.7);
            color: rgb(194 65 12);
            border-color: rgb(251 146 60 / 0.35);
        }

        .reg-badge--approved {
            background: rgb(76 175 125 / 0.12);
            color: #2f7a56;
            border-color: rgb(76 175 125 / 0.3);
        }

        .reg-badge--rejected {
            background: rgb(228 87 46 / 0.1);
            color: #b8401f;
            border-color: rgb(228 87 46 / 0.3);
        }

        .acc-badge--active {
            background: rgb(76 175 125 / 0.12);
            color: #2f7a56;
            border-color: rgb(76 175 125 / 0.3);
        }

        .acc-badge--suspended {
            background: rgb(255 237 213 / 0.7);
            color: rgb(194 65 12);
            border-color: rgb(251 146 60 / 0.35);
        }

        .acc-badge--deactivated {
            background: rgb(46 58 70 / 0.08);
            color: rgb(46 58 70 / 0.65);
            border-color: rgb(46 58 70 / 0.15);
        }

        /* Pagination */
        .pagination-frost nav {
            display: flex;
            align-items: center;
            gap: .25rem;
        }

        .pagination-frost span,
        .pagination-frost a {
            border-radius: 0.65rem !important;
            border-color: rgba(255, 255, 255, 0.7) !important;
            background: rgba(255, 255, 255, 0.7) !important;
            color: #2E3A46 !important;
        }

        .pagination-frost a:hover {
            background: #EAF2FB !important;
            color: #1A5FB4 !important;
        }

        .pagination-frost span[aria-current="page"] span {
            background: #1A5FB4 !important;
            border-color: #1A5FB4 !important;
            color: #fff !important;
        }
    </style>


    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('buyersPage', () => ({
                modalOpen: false,
                loading: true,
                buyerId: null,
                buyer: null,
                showRejectForm: false,
                rejectReason: '',
                toast: {
                    show: false,
                    message: '',
                    error: false,
                },

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]').content;
                },

                showToast(message, isError = false) {
                    this.toast = {
                        show: true,
                        message,
                        error: isError
                    };
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3500);
                },

                async openBuyer(id) {
                    this.buyerId = id;
                    this.modalOpen = true;
                    this.loading = true;
                    this.buyer = null;
                    this.showRejectForm = false;
                    this.rejectReason = '';

                    try {
                        const res = await fetch(`/admin/registrations/buyers/${id}/details`);
                        this.buyer = await res.json();
                        this.loading = false;
                    } catch (err) {
                        this.showToast('Failed to load buyer details.', true);
                        this.closeModal();
                    }
                },

                closeModal() {
                    this.modalOpen = false;
                    this.loading = true;
                    this.buyer = null;
                    this.showRejectForm = false;
                },

                async approve() {
                    if (!confirm('Approve this buyer application?')) return;
                    await this.postAction(
                    `/admin/registrations/buyers/${this.buyerId}/approve`, {});
                },

                async confirmReject() {
                    const reason = this.rejectReason.trim();
                    if (!reason) {
                        this.showToast('Please provide a rejection reason.', true);
                        return;
                    }
                    await this.postAction(`/admin/registrations/buyers/${this.buyerId}/reject`, {
                        rejection_reason: reason
                    });
                    if (this.buyer) this.buyer.rejection_reason = reason;
                },

                async setAccountStatus(status) {
                    if (!confirm(`Set this buyer's account to "${status}"?`)) return;
                    await this.postAction(
                        `/admin/registrations/buyers/${this.buyerId}/account-status`, {
                            status
                        });
                },

                async postAction(url, body) {
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(body),
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            this.showToast(data.message || 'Something went wrong.', true);
                            return;
                        }

                        this.showToast(data.message);

                        this.buyer.registration_status = data.registration_status;
                        this.buyer.account_status = data.account_status;
                        this.showRejectForm = false;

                        this.updateTableRow(this.buyerId, data.registration_status, data
                            .account_status);
                    } catch (err) {
                        this.showToast('Network error — please try again.', true);
                    }
                },

                updateTableRow(id, regStatus, accStatus) {
                    const row = document.querySelector(`tr[data-row-id="${id}"]`);
                    if (!row) return;

                    const regEl = row.querySelector('[data-badge="registration"]');
                    regEl.innerHTML =
                        `<span class="h-1.5 w-1.5 rounded-full bg-current"></span>${regStatus}`;
                    regEl.className =
                        `reg-badge reg-badge--${regStatus} inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize`;

                    const accEl = row.querySelector('[data-badge="account"]');
                    accEl.innerHTML =
                        `<span class="h-1.5 w-1.5 rounded-full bg-current"></span>${accStatus}`;
                    accEl.className =
                        `acc-badge acc-badge--${accStatus} inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize`;
                },
            }));
        });
    </script>

</body>

</html>
