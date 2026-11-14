import { initLogisticsUi } from './ui.js';

function outboundShow() {
    return {
        search: '',
        selectAll: false,
        selectedCount: 0,
        visibleCount: 0,

        init() {
            this.$nextTick(() => this.refreshCounts());
        },

        get rows() {
            return Array.from(this.$root.querySelectorAll('[data-parcel-row]'));
        },

        get checkboxes() {
            return Array.from(this.$root.querySelectorAll('[data-parcel-checkbox]'));
        },

        matches(text) {
            const term = this.search.trim().toLowerCase();
            return term === '' || String(text || '').toLowerCase().includes(term);
        },

        visibleCheckboxes() {
            return this.checkboxes.filter((checkbox) => {
                const row = checkbox.closest('[data-parcel-row]');
                return row && row.offsetParent !== null;
            });
        },

        refreshCounts() {
            this.selectedCount = this.checkboxes.filter((checkbox) => checkbox.checked).length;
            this.visibleCount = this.rows.filter((row) => row.offsetParent !== null).length;

            const visible = this.visibleCheckboxes();
            this.selectAll = visible.length > 0 && visible.every((checkbox) => checkbox.checked);
        },

        onSearchInput() {
            this.$nextTick(() => this.refreshCounts());
        },

        clearSearch() {
            this.search = '';
            this.$nextTick(() => this.refreshCounts());
        },

        toggleAll() {
            this.visibleCheckboxes().forEach((checkbox) => {
                checkbox.checked = this.selectAll;
            });

            this.refreshCounts();
        },

        onCheckboxChange() {
            this.refreshCounts();
        },
    };
}

function initManifestPrint() {
    const link = document.querySelector('[data-print-manifest][data-manifest-bag]');
    if (!link) return;

    const key = `logistics.manifest.printed.${link.dataset.manifestBag}`;
    window.addEventListener('storage', (event) => {
        if (event.key === key && event.newValue) { window.location.reload(); }
    });
}

function boot() {
    initLogisticsUi();
    initManifestPrint();
}

window.outboundShow = outboundShow;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
