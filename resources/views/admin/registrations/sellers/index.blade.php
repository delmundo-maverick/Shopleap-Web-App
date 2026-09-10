<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Seller Management | Shopleap Admin</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal antialiased">

    <div class="flex min-h-screen">

        <x-navbars.admin active="sellers" />

        <div class="flex flex-1 flex-col min-w-0">

            <!-- TOPBAR -->
            <header
                class="sticky top-0 z-30 flex h-20 shrink-0 items-center justify-between border-b border-white/60 bg-white/70 px-6 backdrop-blur-xl">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="rounded-lg p-2 text-charcoal/60 transition hover:bg-seller-soft hover:text-seller lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Seller management</h2>
                        <p class="text-sm text-charcoal/55">{{ $sellers->total() }} seller
                            account{{ $sellers->total() === 1 ? '' : 's' }} total</p>
                    </div>
                </div>

                <div
                    class="hidden items-center gap-2 rounded-full border border-white/60 bg-white/70 px-4 py-2 text-xs font-medium text-charcoal/60 sm:flex">
                    <span class="h-2 w-2 rounded-full bg-seller"></span>
                    Sellers workspace
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
                            placeholder="Search by name, business, or email..."
                            class="w-full rounded-xl border border-white/70 bg-white/80 py-2.5 pl-10 pr-4 text-sm text-charcoal outline-none transition placeholder:text-charcoal/40 focus:border-seller focus:bg-white focus:ring-4 focus:ring-seller/10">
                    </div>

                    <button type="submit"
                        class="rounded-xl bg-seller px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-seller/30 transition hover:bg-seller-light">
                        Search
                    </button>

                    <select name="reg_status" onchange="this.form.submit()"
                        class="rounded-xl border border-white/70 bg-white/80 px-3.5 py-2.5 text-sm text-charcoal outline-none focus:border-seller focus:ring-4 focus:ring-seller/10">
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
                        class="rounded-xl border border-white/70 bg-white/80 px-3.5 py-2.5 text-sm text-charcoal outline-none focus:border-seller focus:ring-4 focus:ring-seller/10">
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

                    @if ($sellers->isEmpty())

                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <div
                                class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-seller-soft text-seller">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-charcoal">No sellers found</p>
                            <p class="mt-1 text-xs text-charcoal/50">Try adjusting your search or filters.</p>
                        </div>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-white/60 bg-white/40">
                                <tr>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Applicant</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Business</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Registration</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Account</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50">Submitted</th>
                                    <th class="px-5 py-3 font-medium text-charcoal/50"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/60">
                                @foreach ($sellers as $profile)
                                    <tr data-row-id="{{ $profile->id }}" class="transition hover:bg-white/60">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-seller-soft text-xs font-bold text-seller">
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
                                        <td class="px-5 py-4 text-charcoal/80">{{ $profile->business_name }}</td>
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
                                            <button type="button" data-view-seller="{{ $profile->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-seller px-3.5 py-2 text-xs font-semibold text-white shadow-sm shadow-seller/20 transition hover:bg-seller-light">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    @endif

                </div>

                <div class="pagination-frost flex justify-center">{{ $sellers->links() }}</div>

            </main>

        </div>

    </div>


    <!-- =====================================================
         MODAL
    ====================================================== -->

    <div id="sellerModalOverlay"
        class="fixed inset-0 z-[70] hidden items-center justify-center bg-charcoal/40 p-4 backdrop-blur-sm">

        <div id="sellerModal"
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-white/60 bg-white/90 shadow-2xl backdrop-blur-2xl">

            <div
                class="sticky top-0 z-10 flex items-center justify-between border-b border-white/60 bg-white/80 px-6 py-4 backdrop-blur-xl">
                <div>
                    <h3 id="modalBusinessName" class="text-lg font-bold text-charcoal">—</h3>
                    <p id="modalSubmittedAt" class="text-xs text-charcoal/50">—</p>
                </div>
                <button type="button" id="modalCloseBtn"
                    class="rounded-lg p-1.5 text-charcoal/50 transition hover:bg-seller-soft hover:text-seller">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="modalLoading" class="flex h-40 items-center justify-center text-sm text-charcoal/40">
                Loading...
            </div>

            <div id="modalBody" class="hidden space-y-5 p-6">

                <div class="flex flex-wrap gap-2">
                    <span id="modalRegBadge"
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize"></span>
                    <span id="modalAccBadge"
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize"></span>
                </div>

                <div id="modalRejectionReasonBox"
                    class="hidden rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                    <span class="font-semibold">Rejection reason:</span> <span id="modalRejectionReason"></span>
                </div>

                <div class="rounded-2xl border border-white/60 bg-white/60 p-4">
                    <p class="mb-3 text-xs font-semibold text-seller">Personal information</p>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-charcoal/50">Full name</dt>
                            <dd id="modalFullName" class="font-medium"></dd>
                        </div>
                        <div>
                            <dt class="text-charcoal/50">Sex</dt>
                            <dd id="modalSex" class="font-medium capitalize"></dd>
                        </div>
                        <div>
                            <dt class="text-charcoal/50">Email</dt>
                            <dd id="modalEmail" class="font-medium"></dd>
                        </div>
                        <div>
                            <dt class="text-charcoal/50">Contact no.</dt>
                            <dd id="modalContact" class="font-medium tabular-nums"></dd>
                        </div>
                        <div>
                            <dt class="text-charcoal/50">Birthday</dt>
                            <dd id="modalBirthday" class="font-medium"></dd>
                        </div>
                        <div>
                            <dt class="text-charcoal/50">Age</dt>
                            <dd id="modalAge" class="font-medium"></dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-white/60 bg-white/60 p-4">
                    <p class="mb-2 text-xs font-semibold text-seller">Address</p>
                    <p id="modalAddress" class="text-sm"></p>
                </div>

                <div class="rounded-2xl border border-white/60 bg-white/60 p-4">
                    <p class="mb-3 text-xs font-semibold text-seller">Business information</p>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-charcoal/50">Business name</dt>
                            <dd id="modalBizName" class="font-medium"></dd>
                        </div>
                        <div>
                            <dt class="text-charcoal/50">Category</dt>
                            <dd id="modalBizCategory" class="font-medium"></dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-white/60 bg-white/60 p-4">
                    <p class="mb-3 text-xs font-semibold text-seller">Documents</p>
                    <div class="grid grid-cols-2 gap-3">
                        <a id="modalIdLink" href="#" target="_blank"
                            class="block overflow-hidden rounded-xl border border-white/70">
                            <img id="modalIdImg" src="" alt="Valid ID" class="h-28 w-full object-cover">
                            <p class="bg-white/70 px-2 py-1.5 text-center text-xs font-semibold text-charcoal/70">Valid
                                ID</p>
                        </a>
                        <a id="modalPermitLink" href="#" target="_blank"
                            class="block overflow-hidden rounded-xl border border-white/70">
                            <img id="modalPermitImg" src="" alt="Business Permit"
                                class="h-28 w-full object-cover">
                            <p class="bg-white/70 px-2 py-1.5 text-center text-xs font-semibold text-charcoal/70">
                                Business permit</p>
                        </a>
                    </div>
                </div>

                <!-- PENDING ACTIONS -->
                <div id="modalPendingActions"
                    class="hidden space-y-3 rounded-2xl border border-white/60 bg-seller-bg p-4">
                    <p class="text-xs font-semibold text-seller">Registration decision</p>
                    <div class="flex gap-3">
                        <button type="button" id="modalApproveBtn"
                            class="flex-1 rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-leaf-green/30 transition hover:brightness-105">
                            Approve
                        </button>
                        <button type="button" id="modalShowRejectBtn"
                            class="flex-1 rounded-xl border border-sale-red/30 bg-sale-red/10 px-4 py-2.5 text-sm font-bold text-sale-red transition hover:bg-sale-red/20">
                            Reject
                        </button>
                    </div>
                    <div id="modalRejectForm" class="hidden space-y-2 pt-2">
                        <textarea id="modalRejectReason" rows="2" placeholder="Reason for rejection..."
                            class="w-full rounded-xl border border-white/70 bg-white/80 px-3 py-2 text-sm outline-none focus:border-sale-red focus:ring-4 focus:ring-sale-red/10"></textarea>
                        <button type="button" id="modalConfirmRejectBtn"
                            class="w-full rounded-xl bg-sale-red px-4 py-2 text-sm font-bold text-white transition hover:brightness-105">
                            Confirm rejection
                        </button>
                    </div>
                </div>

                <!-- ACCOUNT STATUS ACTIONS (only when approved) -->
                <div id="modalAccountActions"
                    class="hidden space-y-3 rounded-2xl border border-white/60 bg-seller-bg p-4">
                    <p class="text-xs font-semibold text-seller">Account status</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-set-status="active"
                            class="acc-action-btn rounded-lg border border-leaf-green/30 bg-leaf-green/10 px-3.5 py-2 text-xs font-semibold text-leaf-green transition hover:bg-leaf-green/20">
                            Activate
                        </button>
                        <button type="button" data-set-status="suspended"
                            class="acc-action-btn rounded-lg border border-orange-300 bg-orange-50 px-3.5 py-2 text-xs font-semibold text-orange-600 transition hover:bg-orange-100">
                            Suspend
                        </button>
                        <button type="button" data-set-status="deactivated"
                            class="acc-action-btn rounded-lg border border-sale-red/30 bg-sale-red/10 px-3.5 py-2 text-xs font-semibold text-sale-red transition hover:bg-sale-red/20">
                            Deactivate
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>


    <!-- TOAST -->
    <div id="toast"
        class="fixed bottom-6 right-6 z-[80] hidden max-w-sm rounded-2xl border border-white/40 px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-xl transition">
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
            background: #E6F7F5 !important;
            color: #0D9488 !important;
        }

        .pagination-frost span[aria-current="page"] span {
            background: #0D9488 !important;
            border-color: #0D9488 !important;
            color: #fff !important;
        }
    </style>


    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        const overlay = document.getElementById('sellerModalOverlay');
        const modalLoading = document.getElementById('modalLoading');
        const modalBody = document.getElementById('modalBody');
        let currentSellerId = null;

        // ---------------------------------------------------
        // TOAST
        // ---------------------------------------------------
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? 'rgba(228,87,46,0.92)' : 'rgba(76,175,125,0.92)';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3500);
        }

        // ---------------------------------------------------
        // OPEN / CLOSE MODAL
        // ---------------------------------------------------
        function openModal() {
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        }

        function closeModal() {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            modalBody.classList.add('hidden');
            modalLoading.classList.remove('hidden');
        }

        document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });

        // ---------------------------------------------------
        // FETCH & RENDER SELLER DETAILS
        // ---------------------------------------------------
        document.querySelectorAll('[data-view-seller]').forEach(btn => {
            btn.addEventListener('click', () => loadSeller(btn.dataset.viewSeller));
        });

        async function loadSeller(id) {
            currentSellerId = id;
            openModal();
            modalBody.classList.add('hidden');
            modalLoading.classList.remove('hidden');

            try {
                const res = await fetch(`/admin/registrations/sellers/${id}/details`);
                const data = await res.json();
                renderModal(data);
            } catch (err) {
                showToast('Failed to load seller details.', true);
                closeModal();
            }
        }

        function renderModal(data) {
            document.getElementById('modalBusinessName').textContent = data.business_name;
            document.getElementById('modalSubmittedAt').textContent = `Submitted ${data.submitted_at}`;
            document.getElementById('modalFullName').textContent = data.full_name;
            document.getElementById('modalSex').textContent = data.sex;
            document.getElementById('modalEmail').textContent = data.email;
            document.getElementById('modalContact').textContent = data.contact_no;
            document.getElementById('modalBirthday').textContent = data.birthday;
            document.getElementById('modalAge').textContent = `${data.age} years old`;
            document.getElementById('modalAddress').textContent = data.address;
            document.getElementById('modalBizName').textContent = data.business_name;
            document.getElementById('modalBizCategory').textContent = data.line_of_business;

            document.getElementById('modalIdImg').src = data.id_url;
            document.getElementById('modalIdLink').href = data.id_url;
            document.getElementById('modalPermitImg').src = data.permit_url;
            document.getElementById('modalPermitLink').href = data.permit_url;

            setBadges(data.registration_status, data.account_status);

            const rejectionBox = document.getElementById('modalRejectionReasonBox');
            if (data.registration_status === 'rejected' && data.rejection_reason) {
                document.getElementById('modalRejectionReason').textContent = data.rejection_reason;
                rejectionBox.classList.remove('hidden');
            } else {
                rejectionBox.classList.add('hidden');
            }

            document.getElementById('modalPendingActions').classList.toggle('hidden', data.registration_status !==
                'pending');
            document.getElementById('modalAccountActions').classList.toggle('hidden', data.registration_status !==
                'approved');
            document.getElementById('modalRejectForm').classList.add('hidden');

            modalLoading.classList.add('hidden');
            modalBody.classList.remove('hidden');
        }

        function setBadges(regStatus, accStatus) {
            const regBadge = document.getElementById('modalRegBadge');
            regBadge.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-current"></span>${regStatus}`;
            regBadge.className =
                `inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize reg-badge--${regStatus}`;

            const accBadge = document.getElementById('modalAccBadge');
            accBadge.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-current"></span>${accStatus}`;
            accBadge.className =
                `inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize acc-badge--${accStatus}`;
        }

        function updateTableRow(id, regStatus, accStatus) {
            const row = document.querySelector(`tr[data-row-id="${id}"]`);
            if (!row) return;

            const regEl = row.querySelector('[data-badge="registration"]');
            regEl.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-current"></span>${regStatus}`;
            regEl.className =
                `reg-badge reg-badge--${regStatus} inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize`;

            const accEl = row.querySelector('[data-badge="account"]');
            accEl.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-current"></span>${accStatus}`;
            accEl.className =
                `acc-badge acc-badge--${accStatus} inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium capitalize`;
        }

        // ---------------------------------------------------
        // APPROVE / REJECT
        // ---------------------------------------------------
        document.getElementById('modalApproveBtn').addEventListener('click', async () => {
            if (!confirm('Approve this seller application?')) return;
            await postAction(`/admin/registrations/sellers/${currentSellerId}/approve`, {});
        });

        document.getElementById('modalShowRejectBtn').addEventListener('click', () => {
            document.getElementById('modalRejectForm').classList.toggle('hidden');
        });

        document.getElementById('modalConfirmRejectBtn').addEventListener('click', async () => {
            const reason = document.getElementById('modalRejectReason').value.trim();
            if (!reason) {
                showToast('Please provide a rejection reason.', true);
                return;
            }
            await postAction(`/admin/registrations/sellers/${currentSellerId}/reject`, {
                rejection_reason: reason
            });
        });

        // ---------------------------------------------------
        // ACCOUNT STATUS ACTIONS
        // ---------------------------------------------------
        document.querySelectorAll('.acc-action-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const status = btn.dataset.setStatus;
                if (!confirm(`Set this seller's account to "${status}"?`)) return;
                await postAction(`/admin/registrations/sellers/${currentSellerId}/account-status`, {
                    status
                });
            });
        });

        // ---------------------------------------------------
        // SHARED POST HELPER
        // ---------------------------------------------------
        async function postAction(url, body) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    showToast(data.message || 'Something went wrong.', true);
                    return;
                }

                showToast(data.message);
                setBadges(data.registration_status, data.account_status);
                updateTableRow(currentSellerId, data.registration_status, data.account_status);

                document.getElementById('modalPendingActions').classList.toggle('hidden', data.registration_status !==
                    'pending');
                document.getElementById('modalAccountActions').classList.toggle('hidden', data.registration_status !==
                    'approved');
                document.getElementById('modalRejectForm').classList.add('hidden');
                document.getElementById('modalRejectionReasonBox').classList.toggle(
                    'hidden',
                    !(data.registration_status === 'rejected')
                );
            } catch (err) {
                showToast('Network error — please try again.', true);
            }
        }
    </script>

</body>

</html>
