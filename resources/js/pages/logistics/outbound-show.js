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
    // Only sync when printing is what unlocks Seal Bag on this page.
    const link = document.querySelector('[data-print-manifest][data-manifest-reload]');
    if (!link) return;

    const flag = 'logistics.manifest.opened';

    const reloadWhenMarked = () => {
        if (window.sessionStorage.getItem(flag) !== '1') return;

        // Remove first so focus and the fallback timer cannot reload twice.
        window.sessionStorage.removeItem(flag);
        window.location.reload();
    };

    const scheduleReload = (delay) => window.setTimeout(reloadWhenMarked, delay);

    link.addEventListener('click', () => {
        window.sessionStorage.setItem(flag, '1');

        // The manifest opens in another tab and marks the bag server-side on
        // render. Reload when the user comes back so Seal Bag reflects it. The
        // timer is only a fallback for browsers that do not fire focus.
        scheduleReload(10000);
    });

    window.addEventListener('focus', () => scheduleReload(500));
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') scheduleReload(500);
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
