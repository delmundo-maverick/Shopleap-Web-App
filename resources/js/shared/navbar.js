const DESKTOP_BREAKPOINT = 1024;
const DEFAULT_POLL_INTERVAL = 20000;
const DEFAULT_STORAGE_KEY = 'sidebarCollapsed';

const isDesktop = () => window.innerWidth >= DESKTOP_BREAKPOINT;

function createStorage(key) {
    return {
        get() {
            try {
                return localStorage.getItem(key) === 'true';
            } catch {
                return false;
            }
        },
        set(value) {
            try {
                localStorage.setItem(key, String(value));
            } catch {
                /* storage unavailable – ignore */
            }
        },
    };
}

function initCollapse(sidebar, storage) {
    // Apply saved state instantly without a transition flicker
    if (isDesktop() && storage.get()) {
        sidebar.classList.add('no-transition', 'is-collapsed');
        setTimeout(() => sidebar.classList.remove('no-transition'), 50);
    }

    document.getElementById('sidebarToggleBtn')?.addEventListener('click', () => {
        sidebar.classList.toggle('is-collapsed');
        if (isDesktop()) {
            storage.set(sidebar.classList.contains('is-collapsed'));
        }
    });
}

function initMobileDrawer(sidebar) {
    const overlay = document.getElementById('sidebarOverlay');

    const open = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
    };

    const close = () => {
        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    };

    const isOpen = () => !sidebar.classList.contains('-translate-x-full');

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-sidebar-toggle]')) {
            e.preventDefault();
            isOpen() ? close() : open();
        }
    });

    document.getElementById('sidebarCloseBtn')?.addEventListener('click', close);
    overlay?.addEventListener('click', close);

    return { close };
}

function initResize(sidebar, storage, drawer) {
    window.addEventListener('resize', () => {
        if (isDesktop()) {
            drawer.close(); // reset mobile drawer/overlay state
            sidebar.classList.toggle('is-collapsed', storage.get());
        } else {
            sidebar.classList.remove('is-collapsed');
        }
    });
}

function initUnreadBadge(sidebar) {
    const badge = document.querySelector('[data-message-badge]');
    const url = sidebar.dataset.unreadUrl;
    if (!badge || !url) return;

    const interval = Number(sidebar.dataset.pollInterval) || DEFAULT_POLL_INTERVAL;

    async function refresh() {
        if (document.hidden) return;

        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;

            const { count } = await res.json();

            if (count > 0) {
                badge.textContent = count > 9 ? '9+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        } catch (err) {
            console.error(err);
        }
    }

    refresh();
    setInterval(refresh, interval);
}

function initLogoutModal() {
    const form = document.getElementById('logoutForm');
    const overlay = document.getElementById('logoutModalOverlay');
    if (!form || !overlay) return;

    const open = () => {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    };

    const close = () => {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
    };

    document.getElementById('logoutTriggerBtn')?.addEventListener('click', open);
    document.getElementById('logoutCancelBtn')?.addEventListener('click', close);
    document.getElementById('logoutConfirmBtn')?.addEventListener('click', () => form.submit());

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
}

export function initSidebar() {
    const sidebar = document.getElementById('sidebarNav');
    if (!sidebar || sidebar.dataset.sidebarReady) return;
    sidebar.dataset.sidebarReady = 'true';

    const storage = createStorage(sidebar.dataset.storageKey || DEFAULT_STORAGE_KEY);

    initCollapse(sidebar, storage);
    const drawer = initMobileDrawer(sidebar);
    initResize(sidebar, storage, drawer);
    initUnreadBadge(sidebar);
    initLogoutModal();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}
