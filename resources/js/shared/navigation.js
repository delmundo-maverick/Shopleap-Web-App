import { pollingRequest } from './polling-request.js';

const locks = new Set();
let previousOverflow = '';
let previousBodyOverflow = '';

function scrollLock(id, enabled) {
    if (!enabled && !locks.has(id)) { return; }
    if (enabled && !locks.size) {
        previousOverflow = document.documentElement.style.overflow;
        previousBodyOverflow = document.body.style.overflow;
    }
    if (enabled) { locks.add(id); } else { locks.delete(id); }
    document.documentElement.style.overflow = locks.size ? 'hidden' : previousOverflow;
    document.body.style.overflow = locks.size ? 'hidden' : previousBodyOverflow;
}

export function navbar(config = {}) {
    const fetchUnread = pollingRequest(30000);
    return {
        id: config.id, desktop: window.innerWidth >= 1024, open: false, collapsed: false, activeDropdown: null, logoutOpen: false,
        logoutSubmitting: false, logoutTrigger: null, logoutFromMenu: false, returnFocus: null, dropdownTrigger: null, unread: 0, poll: null,
        init() {
            try { this.collapsed = localStorage.getItem(`navigation:${this.id}:collapsed`) === 'true'; } catch {}
            this.$watch('open', () => this.syncLock());
            this.$watch('logoutOpen', () => this.syncLock());
            if (config.unreadUrl) {
                this.refreshUnread();
                this.poll = setInterval(() => this.refreshUnread(), 30000);
            }
        },
        destroy() { clearInterval(this.poll); scrollLock(this.id, false); },
        syncLock() { scrollLock(this.id, this.logoutOpen || (this.open && window.innerWidth < 1024)); },
        toggle(trigger = null) {
            if (this.logoutOpen) { return; }
            if (window.innerWidth >= 1024) { return; }
            if (this.open) { this.close(); return; }
            this.returnFocus = trigger || document.activeElement;
            this.activeDropdown = null; this.open = true;
            this.broadcast();
            this.$nextTick(() => this.$refs.panel?.querySelector('button, a[href], input')?.focus());
        },
        close(restoreFocus = true) {
            const wasOpen = this.open;
            this.open = false; this.activeDropdown = null; this.syncLock(); this.broadcast();
            if (wasOpen && restoreFocus) { this.returnFocus?.focus(); }
        },
        broadcast() { window.dispatchEvent(new CustomEvent('navigation-state', { detail: { id: this.id, open: this.open } })); },
        resize() { this.desktop = window.innerWidth >= 1024; if (this.desktop) { this.close(false); } },
        collapse() {
            this.collapsed = !this.collapsed;
            try { localStorage.setItem(`navigation:${this.id}:collapsed`, String(this.collapsed)); } catch {}
        },
        navigate(event) { if (event.target.closest('a[href]')) { this.close(false); } },
        escape() {
            if (this.logoutOpen) { this.cancelLogout(); return; }
            if (this.activeDropdown) { this.activeDropdown = null; this.dropdownTrigger?.focus(); return; }
            this.close();
        },
        dropdown(id, trigger) {
            this.dropdownTrigger = trigger;
            this.activeDropdown = this.activeDropdown === id ? null : id;
        },
        focusMenu(id, trigger) {
            this.dropdownTrigger = trigger; this.activeDropdown = id;
            this.$nextTick(() => trigger.closest('[data-nav-dropdown]')?.querySelector('[data-nav-dropdown-menu] a, [data-nav-dropdown-menu] button')?.focus());
        },
        moveMenu(event, direction) {
            const root = event.target.closest('[data-nav-dropdown-menu]');
            if (!root) { return; }
            const items = [...root.querySelectorAll('a[href], button:not([disabled])')];
            if (!items.length) { return; }
            event.preventDefault(); items[(items.indexOf(document.activeElement) + direction + items.length) % items.length]?.focus();
        },
        trap(event) {
            if (event.key !== 'Tab' || (!this.open && !this.logoutOpen)) { return; }
            const root = this.logoutOpen ? this.$refs.logoutDialog : this.$refs.panel;
            const items = [...(root?.querySelectorAll('a[href], button:not([disabled]), input, select, textarea') || [])].filter(el => el.getClientRects().length);
            if (!items.length) { event.preventDefault(); root?.focus(); return; }
            const first = items[0]; const last = items.at(-1);
            if (event.shiftKey && (document.activeElement === first || !items.includes(document.activeElement))) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && (document.activeElement === last || !items.includes(document.activeElement))) { event.preventDefault(); first.focus(); }
        },
        requestLogout(trigger) {
            if (this.logoutOpen) { return; }
            this.logoutTrigger = trigger; this.logoutFromMenu = this.open; this.logoutSubmitting = false;
            this.logoutOpen = true; this.close(false);
            this.$nextTick(() => this.$refs.logoutDialog?.querySelector('button')?.focus());
        },
        cancelLogout() {
            if (this.logoutSubmitting) { return; }
            if (this.logoutFromMenu && !this.desktop) { this.open = true; this.broadcast(); }
            this.logoutOpen = false;
            this.syncLock();
            this.$nextTick(() => this.logoutTrigger?.focus());
        },
        containLogoutFocus(event) {
            if (this.logoutOpen && !this.$refs.logoutDialog?.contains(event.target)) {
                const dialog = this.$refs.logoutDialog;
                (dialog?.querySelector('button:not([disabled])') || dialog)?.focus();
            }
        },
        confirmLogout(form) {
            if (!this.logoutOpen || this.logoutSubmitting) { return; }
            this.logoutSubmitting = true;
            form.submit();
        },
        reset() {
            this.logoutSubmitting = false; this.logoutOpen = false; this.close(false);
        },
        async refreshUnread() {
            if (document.hidden) { return; }
            try {
                const response = await fetchUnread(config.unreadUrl, { headers: { Accept: 'application/json' } });
                if (response?.ok) { this.unread = Number((await response.json()).count) || 0; }
            } catch { /* Retain the last known count during a temporary network failure. */ }
        },
    };
}

export function navToggle(id) {
    return { expanded: false, toggle() { window.dispatchEvent(new CustomEvent('navigation-toggle', { detail: { id, trigger: this.$el } })); } };
}
