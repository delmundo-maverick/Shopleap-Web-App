const TOAST_DURATION_MS = 3500;

const PERSONAL_FIELDS = [
    { label: 'Full name', key: 'full_name' },
    { label: 'Sex', key: 'sex', classes: 'capitalize' },
    { label: 'Email', key: 'email' },
    { label: 'Contact no.', key: 'contact_no', classes: 'tabular-nums' },
    { label: 'Birthday', key: 'birthday' },
    { label: 'Age', key: 'age', format: (age) => `${age} years old` },
];

/** @param {'reg'|'acc'} type */
const statusBadgeClass = (type, status) => `status-badge ${type}-badge--${status}`;

const csrfToken = () => document.querySelector('meta[name="csrf-token"]').content;

/**
 * @param {object} config
 * @param {string} config.baseUrl         e.g. '/admin/registrations/sellers'
 * @param {string} config.noun            'buyer' | 'seller' (used in messages)
 * @param {Array}  [config.businessFields] extra info rows: { label, key, classes?, format? }
 */
function reviewPage({ baseUrl, noun, businessFields = [] }) {
    return {
        modalOpen: false,
        loading: true,
        recordId: null,
        record: null,
        showRejectForm: false,
        rejectReason: '',
        toast: { show: false, message: '', error: false },
        toastTimer: null,

        /* ---------- Derived (used by the modal template) ---------- */

        get personalInfo() {
            return this.fieldList(PERSONAL_FIELDS);
        },

        get businessInfo() {
            return this.fieldList(businessFields);
        },

        fieldList(fields) {
            if (!this.record) return [];

            return fields.map(({ label, key, classes = '', format }) => ({
                label,
                classes,
                value: format ? format(this.record[key]) : this.record[key],
            }));
        },

        regBadgeClass(status) {
            return statusBadgeClass('reg', status);
        },

        accBadgeClass(status) {
            return statusBadgeClass('acc', status);
        },

        /* ---------- UI helpers ---------- */

        showToast(message, isError = false) {
            clearTimeout(this.toastTimer);
            this.toast = { show: true, message, error: isError };
            this.toastTimer = setTimeout(() => (this.toast.show = false), TOAST_DURATION_MS);
        },

        resetModalState() {
            this.record = null;
            this.showRejectForm = false;
            this.rejectReason = '';
        },

        /* ---------- Modal ---------- */

        async openRecord(id) {
            this.recordId = id;
            this.modalOpen = true;
            this.loading = true;
            this.resetModalState();

            try {
                const res = await fetch(`${baseUrl}/${id}/details`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const record = await res.json();

                // Ignore the response if the modal was closed or switched meanwhile.
                if (!this.modalOpen || this.recordId !== id) return;

                this.record = record;
                this.loading = false;
            } catch (err) {
                this.showToast(`Failed to load ${noun} details.`, true);
                this.closeModal();
            }
        },

        closeModal() {
            this.modalOpen = false;
            this.loading = true;
            this.resetModalState();
        },

        /* ---------- Actions ---------- */

        async approve() {
            if (!confirm(`Approve this ${noun} application?`)) return;
            await this.postAction('approve');
        },

        async confirmReject() {
            const reason = this.rejectReason.trim();
            if (!reason) {
                this.showToast('Please provide a rejection reason.', true);
                return;
            }

            const ok = await this.postAction('reject', { rejection_reason: reason });
            if (ok && this.record) this.record.rejection_reason = reason;
        },

        async setAccountStatus(status) {
            if (!confirm(`Set this ${noun}'s account to "${status}"?`)) return;
            await this.postAction('account-status', { status });
        },

        /** @returns {Promise<boolean>} true on success */
        async postAction(action, body = {}) {
            const id = this.recordId;

            try {
                const res = await fetch(`${baseUrl}/${id}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    this.showToast(data.message || 'Something went wrong.', true);
                    return false;
                }

                this.showToast(data.message);

                if (this.record) {
                    this.record.registration_status = data.registration_status;
                    this.record.account_status = data.account_status;
                }
                this.showRejectForm = false;
                this.updateTableRow(id, data.registration_status, data.account_status);

                return true;
            } catch (err) {
                this.showToast('Network error — please try again.', true);
                return false;
            }
        },

        /* ---------- Keep the table in sync without a reload ---------- */

        updateTableRow(id, regStatus, accStatus) {
            const row = document.querySelector(`tr[data-row-id="${id}"]`);
            if (!row) return;

            const update = (selector, type, status) => {
                const el = row.querySelector(selector);
                if (!el) return;
                el.textContent = status; // dot is rendered by .status-badge::before
                el.className = statusBadgeClass(type, status);
            };

            update('[data-badge="registration"]', 'reg', regStatus);
            update('[data-badge="account"]', 'acc', accStatus);
        },
    };
}

/** Registers an Alpine component (x-data="<name>()") for a review page. */
export function registerReviewPage(name, config) {
    document.addEventListener('alpine:init', () => {
        Alpine.data(name, () => reviewPage(config));
    });
}
