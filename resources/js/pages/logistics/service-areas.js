function serviceAreas() {
    return {
        barangays: [],
        riders: [],
        selected: [],
        riderAssignments: {},
        initial: '',
        search: '',
        filter: 'all',
        saving: false,
        error: '',
        saveUrl: '',
        csrf: '',
        chips: [
            { key: 'all', label: 'All' },
            { key: 'selected', label: 'Selected' },
            { key: 'available', label: 'Available' },
            { key: 'taken', label: 'Taken' },
        ],

        init() {
            const el = document.getElementById('serviceAreaData');
            const data = el ? JSON.parse(el.textContent || '{}') : {};

            this.barangays = Array.isArray(data.barangays) ? data.barangays : [];
            this.riders = Array.isArray(data.riders) ? data.riders : [];
            this.saveUrl = this.$el.dataset.saveUrl || '/logistics/areas';
            this.csrf =
                this.$el.dataset.csrf ||
                document.querySelector('meta[name="csrf-token"]')?.content ||
                '';

            // Every barangay gets an entry so the rider dropdowns stay reactive.
            this.barangays.forEach((b) => {
                this.riderAssignments[b.barangay_key] =
                    b.status === 'yours' && b.rider_id !== null && b.rider_id !== undefined
                        ? String(b.rider_id)
                        : '';
            });

            this.selected = this.barangays
                .filter((b) => b.status === 'yours')
                .map((b) => b.barangay_key);

            this.initial = this.signature();
        },

        get filtered() {
            const term = this.search.trim().toLowerCase();

            return this.barangays.filter((b) => {
                if (term && !b.barangay.toLowerCase().includes(term)) return false;
                if (this.filter === 'selected') return this.selected.includes(b.barangay_key);
                if (this.filter === 'available') return b.status === 'available';
                if (this.filter === 'taken') return b.status === 'taken';
                return true;
            });
        },

        get selectedCount() {
            return this.selected.length;
        },

        get availableCount() {
            return this.barangays.filter(
                (b) => b.status === 'available' && !this.selected.includes(b.barangay_key)
            ).length;
        },

        get dirty() {
            return this.signature() !== this.initial;
        },

        signature() {
            return JSON.stringify(
                [...this.selected].sort().map((k) => [k, this.riderAssignments[k] || ''])
            );
        },

        selectAllAvailable() {
            this.barangays.forEach((b) => {
                if (b.status === 'available' && !this.selected.includes(b.barangay_key)) {
                    this.selected.push(b.barangay_key);
                }
            });
        },

        async save() {
            if (this.saving || !this.dirty) return;

            this.saving = true;
            this.error = '';

            const byKey = Object.fromEntries(this.barangays.map((b) => [b.barangay_key, b]));
            const payload = { barangays: [], riders: {} };

            this.selected.forEach((key) => {
                const b = byKey[key];
                if (!b) return;

                payload.barangays.push(b.barangay);

                const riderId = this.riderAssignments[key];
                if (riderId) payload.riders[b.barangay] = String(riderId);
            });

            try {
                const res = await fetch(this.saveUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                if (res.status === 422) {
                    const body = await res.json().catch(() => ({}));
                    const first = Object.values(body.errors || {}).flat().find(Boolean);
                    this.error = first || body.message || 'Please check your selection.';
                    return;
                }

                if (res.status === 419) {
                    this.error = 'Your session expired. Reload the page and try again.';
                    return;
                }

                if (!res.ok) {
                    const body = await res.json().catch(() => ({}));
                    this.error = body.message || 'Something went wrong. Please try again.';
                    return;
                }

                // The body may be JSON or a redirected page; either way the save worked.
                window.location.reload();
            } catch (e) {
                this.error = 'Network error. Check your connection and try again.';
            } finally {
                this.saving = false;
            }
        },
    };
}

window.serviceAreas = serviceAreas;

let serviceAreasRegistered = false;

function registerServiceAreas() {
    if (serviceAreasRegistered || !window.Alpine) return;

    window.Alpine.data('serviceAreas', serviceAreas);
    serviceAreasRegistered = true;
}

if (window.Alpine) {
    registerServiceAreas();
} else {
    document.addEventListener('alpine:init', registerServiceAreas, { once: true });
}
