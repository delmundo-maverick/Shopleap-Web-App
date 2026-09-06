<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Buyer Management | Shopleap Admin</title>
</head>

<body class="min-h-screen bg-ice-blue text-charcoal">

    <div class="flex min-h-screen">

        <x-navbars.admin active="users" />

        <div class="flex flex-1 flex-col">

            <!-- TOPBAR -->
            <header class="flex h-20 items-center justify-between border-b border-light-gray bg-white px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Buyer Management</h2>
                        <p class="text-sm text-charcoal/55">{{ $buyers->total() }} buyer account(s) total</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 space-y-5 p-6">

                <!-- SEARCH & FILTERS -->
                <form method="GET"
                    class="flex flex-col gap-3 rounded-2xl border border-light-gray bg-white p-4 sm:flex-row sm:items-center">

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
                            class="w-full rounded-xl border border-light-gray bg-ice-blue/50 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary/10">
                    </div>

                    <select name="reg_status" onchange="this.form.submit()"
                        class="rounded-xl border border-light-gray bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary">
                        <option value="all" {{ request('reg_status', 'all') === 'all' ? 'selected' : '' }}>All
                            Registrations</option>
                        <option value="pending" {{ request('reg_status') === 'pending' ? 'selected' : '' }}>Pending
                        </option>
                        <option value="approved" {{ request('reg_status') === 'approved' ? 'selected' : '' }}>Approved
                        </option>
                        <option value="rejected" {{ request('reg_status') === 'rejected' ? 'selected' : '' }}>Rejected
                        </option>
                    </select>

                    <select name="acc_status" onchange="this.form.submit()"
                        class="rounded-xl border border-light-gray bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary">
                        <option value="all" {{ request('acc_status', 'all') === 'all' ? 'selected' : '' }}>All
                            Accounts</option>
                        <option value="active" {{ request('acc_status') === 'active' ? 'selected' : '' }}>Active
                        </option>
                        <option value="suspended" {{ request('acc_status') === 'suspended' ? 'selected' : '' }}>
                            Suspended</option>
                        <option value="deactivated" {{ request('acc_status') === 'deactivated' ? 'selected' : '' }}>
                            Deactivated</option>
                    </select>

                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-blue">
                        Search
                    </button>

                </form>

                <!-- TABLE -->
                <div class="overflow-hidden rounded-2xl border border-light-gray bg-white shadow-sm shadow-charcoal/5">

                    @if ($buyers->isEmpty())

                        <div class="flex h-64 flex-col items-center justify-center text-center">
                            <p class="text-sm font-semibold text-charcoal">No buyers found</p>
                            <p class="mt-1 text-xs text-charcoal/50">Try adjusting your search or filters.</p>
                        </div>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-light-gray bg-ice-blue/60">
                                <tr>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Buyer</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Contact No.</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Registration</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Account</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60">Submitted</th>
                                    <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-light-gray">
                                @foreach ($buyers as $profile)
                                    <tr data-row-id="{{ $profile->id }}" class="transition hover:bg-ice-blue/40">
                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-charcoal">{{ $profile->first_name }}
                                                {{ $profile->last_name }}</p>
                                            <p class="text-xs text-charcoal/50">{{ $profile->user->email }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-charcoal/80">{{ $profile->contact_no }}</td>
                                        <td class="px-5 py-4">
                                            <span data-badge="registration"
                                                class="reg-badge reg-badge--{{ $profile->status }} rounded-full px-2.5 py-1 text-xs font-semibold capitalize">
                                                {{ $profile->status }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span data-badge="account"
                                                class="acc-badge acc-badge--{{ $profile->user->account_status }} rounded-full px-2.5 py-1 text-xs font-semibold capitalize">
                                                {{ $profile->user->account_status }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-charcoal/60">
                                            {{ $profile->created_at->diffForHumans() }}</td>
                                        <td class="px-5 py-4 text-right">
                                            <button type="button" data-view-buyer="{{ $profile->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-blue">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    @endif

                </div>

                <div>{{ $buyers->links() }}</div>

            </main>

        </div>

    </div>


    <!-- =====================================================
         MODAL
    ====================================================== -->

    <div id="buyerModalOverlay" class="fixed inset-0 z-[70] hidden items-center justify-center bg-charcoal/50 p-4">

        <div id="buyerModal" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b border-light-gray px-6 py-4">
                <div>
                    <h3 id="modalFullNameHeader" class="text-lg font-bold text-charcoal">—</h3>
                    <p id="modalSubmittedAt" class="text-xs text-charcoal/50">—</p>
                </div>
                <button type="button" id="modalCloseBtn"
                    class="rounded-lg p-1.5 text-charcoal/50 transition hover:bg-ice-blue hover:text-primary">
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
                    <span id="modalRegBadge" class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize"></span>
                    <span id="modalAccBadge" class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize"></span>
                </div>

                <div id="modalRejectionReasonBox"
                    class="hidden rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                    <span class="font-semibold">Rejection reason:</span> <span id="modalRejectionReason"></span>
                </div>

                <div class="rounded-xl border border-light-gray p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Personal Information</p>
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
                            <dt class="text-charcoal/50">Contact No.</dt>
                            <dd id="modalContact" class="font-medium"></dd>
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

                <div class="rounded-xl border border-light-gray p-4">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-primary">Address</p>
                    <p id="modalAddress" class="text-sm"></p>
                </div>

                <div class="rounded-xl border border-light-gray p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Valid ID</p>
                    <a id="modalIdLink" href="#" target="_blank"
                        class="block max-w-xs overflow-hidden rounded-lg border border-light-gray">
                        <img id="modalIdImg" src="" alt="Valid ID" class="h-40 w-full object-cover">
                        <p class="bg-charcoal/5 px-2 py-1.5 text-center text-xs font-semibold">Valid ID — click to view
                            full</p>
                    </a>
                </div>

                <!-- PENDING ACTIONS -->
                <div id="modalPendingActions"
                    class="hidden space-y-3 rounded-xl border border-light-gray bg-ice-blue/40 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Registration Decision</p>
                    <div class="flex gap-3">
                        <button type="button" id="modalApproveBtn"
                            class="flex-1 rounded-xl bg-leaf-green px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-105">
                            Approve
                        </button>
                        <button type="button" id="modalShowRejectBtn"
                            class="flex-1 rounded-xl border border-sale-red/30 bg-sale-red/10 px-4 py-2.5 text-sm font-bold text-sale-red transition hover:bg-sale-red/20">
                            Reject
                        </button>
                    </div>
                    <div id="modalRejectForm" class="hidden space-y-2 pt-2">
                        <textarea id="modalRejectReason" rows="2" placeholder="Reason for rejection..."
                            class="w-full rounded-xl border border-light-gray px-3 py-2 text-sm outline-none focus:border-sale-red focus:ring-4 focus:ring-sale-red/10"></textarea>
                        <button type="button" id="modalConfirmRejectBtn"
                            class="w-full rounded-xl bg-sale-red px-4 py-2 text-sm font-bold text-white transition hover:brightness-105">
                            Confirm Rejection
                        </button>
                    </div>
                </div>

                <!-- ACCOUNT STATUS ACTIONS -->
                <div id="modalAccountActions"
                    class="hidden space-y-3 rounded-xl border border-light-gray bg-ice-blue/40 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Account Status</p>
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
        class="fixed bottom-6 right-6 z-[80] hidden max-w-sm rounded-xl px-5 py-3.5 text-sm font-medium text-white shadow-xl transition">
    </div>


    <style>
        .reg-badge--pending {
            background: rgb(255 237 213);
            color: rgb(234 88 12);
        }

        .reg-badge--approved {
            background: rgb(220 252 231);
            color: rgb(22 163 74);
        }

        .reg-badge--rejected {
            background: rgb(254 226 226);
            color: rgb(220 38 38);
        }

        .acc-badge--active {
            background: rgb(220 252 231);
            color: rgb(22 163 74);
        }

        .acc-badge--suspended {
            background: rgb(255 237 213);
            color: rgb(234 88 12);
        }

        .acc-badge--deactivated {
            background: rgb(229 231 235);
            color: rgb(75 85 99);
        }
    </style>


    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        const overlay = document.getElementById('buyerModalOverlay');
        const modalLoading = document.getElementById('modalLoading');
        const modalBody = document.getElementById('modalBody');
        let currentBuyerId = null;

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#DC2626' : '#16A34A';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3500);
        }

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

        document.querySelectorAll('[data-view-buyer]').forEach(btn => {
            btn.addEventListener('click', () => loadBuyer(btn.dataset.viewBuyer));
        });

        async function loadBuyer(id) {
            currentBuyerId = id;
            openModal();
            modalBody.classList.add('hidden');
            modalLoading.classList.remove('hidden');

            try {
                const res = await fetch(`/admin/registrations/buyers/${id}/details`);
                const data = await res.json();
                renderModal(data);
            } catch (err) {
                showToast('Failed to load buyer details.', true);
                closeModal();
            }
        }

        function renderModal(data) {
            document.getElementById('modalFullNameHeader').textContent = data.full_name;
            document.getElementById('modalSubmittedAt').textContent = `Submitted ${data.submitted_at}`;
            document.getElementById('modalFullName').textContent = data.full_name;
            document.getElementById('modalSex').textContent = data.sex;
            document.getElementById('modalEmail').textContent = data.email;
            document.getElementById('modalContact').textContent = data.contact_no;
            document.getElementById('modalBirthday').textContent = data.birthday;
            document.getElementById('modalAge').textContent = `${data.age} years old`;
            document.getElementById('modalAddress').textContent = data.address;

            document.getElementById('modalIdImg').src = data.id_url;
            document.getElementById('modalIdLink').href = data.id_url;

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
            regBadge.textContent = regStatus;
            regBadge.className = `rounded-full px-2.5 py-1 text-xs font-semibold capitalize reg-badge--${regStatus}`;

            const accBadge = document.getElementById('modalAccBadge');
            accBadge.textContent = accStatus;
            accBadge.className = `rounded-full px-2.5 py-1 text-xs font-semibold capitalize acc-badge--${accStatus}`;
        }

        function updateTableRow(id, regStatus, accStatus) {
            const row = document.querySelector(`tr[data-row-id="${id}"]`);
            if (!row) return;

            const regEl = row.querySelector('[data-badge="registration"]');
            regEl.textContent = regStatus;
            regEl.className = `reg-badge reg-badge--${regStatus} rounded-full px-2.5 py-1 text-xs font-semibold capitalize`;

            const accEl = row.querySelector('[data-badge="account"]');
            accEl.textContent = accStatus;
            accEl.className = `acc-badge acc-badge--${accStatus} rounded-full px-2.5 py-1 text-xs font-semibold capitalize`;
        }

        document.getElementById('modalApproveBtn').addEventListener('click', async () => {
            if (!confirm('Approve this buyer application?')) return;
            await postAction(`/admin/registrations/buyers/${currentBuyerId}/approve`, {});
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
            await postAction(`/admin/registrations/buyers/${currentBuyerId}/reject`, {
                rejection_reason: reason
            });
        });

        document.querySelectorAll('.acc-action-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const status = btn.dataset.setStatus;
                if (!confirm(`Set this buyer's account to "${status}"?`)) return;
                await postAction(`/admin/registrations/buyers/${currentBuyerId}/account-status`, {
                    status
                });
            });
        });

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
                updateTableRow(currentBuyerId, data.registration_status, data.account_status);

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
