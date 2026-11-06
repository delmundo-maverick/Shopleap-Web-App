async function initializeAccountPanel(name, element, isCurrent) {
    if (name === 'purchases') {
        const { initializeOrders } = await import('./orders.js');
        if (isCurrent()) { initializeOrders(element); }
    } else if (name === 'messages') {
        const { initializeMessaging } = await import('../messaging.js');
        if (isCurrent()) { initializeMessaging(element); }
    }
}

export function accountTabs(config = {}, initializePanel = initializeAccountPanel) {
    const controllers = new Map();
    const versions = new Map();
    const urls = config.urls ?? {};
    const remoteTabs = Object.keys(urls);

    return {
        tab: config.initialTab ?? 'profile',
        valid: ['profile', 'address', 'security', 'following', ...remoteTabs],
        loaded: {},
        loading: Object.fromEntries(remoteTabs.map(name => [name, false])),
        errors: Object.fromEntries(remoteTabs.map(name => [name, ''])),
        hashListener: null,

        init() {
            const hash = window.location.hash.slice(1);
            if (!config.forced && this.valid.includes(hash)) {
                this.tab = hash;
            }
            this.hashListener = () => {
                const name = window.location.hash.slice(1) || 'profile';
                if (this.valid.includes(name)) {
                    this.tab = name;
                    this.load(name);
                }
            };
            window.addEventListener('hashchange', this.hashListener);
            this.load(this.tab);
        },

        destroy() {
            window.removeEventListener('hashchange', this.hashListener);
            controllers.forEach(controller => controller.abort());
        },

        go(name, url = null) {
            if (!this.valid.includes(name)) { return; }
            this.tab = name;
            window.history.replaceState(null, '', '#' + name);
            return this.load(name, url, url !== null);
        },

        async load(name, url = null, force = false, options = {}) {
            if (!remoteTabs.includes(name) || (!force && (this.loaded[name] || this.loading[name]))) { return; }
            const target = new URL(url || urls[name], window.location.href);
            const base = new URL(urls[name], window.location.href);
            if (target.origin !== base.origin) { return; }
            controllers.get(name)?.abort();
            const controller = new AbortController();
            controllers.set(name, controller);
            const version = (versions.get(name) ?? 0) + 1;
            versions.set(name, version);
            this.loading[name] = true;
            this.errors[name] = '';

            try {
                const response = await fetch(target.href, {
                    ...options,
                    signal: controller.signal,
                    headers: { 'X-Account-Panel': 'true', Accept: 'text/html' },
                });
                if (!response.ok) { throw new Error('Could not load this section. Please try again.'); }
                const html = await response.text();
                if (!html.includes(`data-account-content="${name}"`)) {
                    throw new Error('Your session may have expired. Refresh the page and try again.');
                }
                if (versions.get(name) !== version) { return; }
                const element = this.$refs[name];
                element.dispatchEvent(new CustomEvent('account-panel-destroy'));
                element.dataset.url = response.url || target.href;
                element.innerHTML = html;
                await initializePanel(name, element, () => versions.get(name) === version);
                if (versions.get(name) === version) { this.loaded[name] = true; }
            } catch (error) {
                if (error.name !== 'AbortError' && versions.get(name) === version) {
                    this.errors[name] = error.message;
                }
            } finally {
                if (versions.get(name) === version) { this.loading[name] = false; }
            }
        },

        navigate(event, name) {
            if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) { return; }
            const link = event.target.closest('a[href]');
            if (!link || link.target === '_blank' || link.hasAttribute('download')) { return; }
            const target = new URL(link.href, window.location.href);
            const base = new URL(urls[name], window.location.href);
            if (target.origin !== base.origin || target.pathname !== base.pathname) { return; }
            event.preventDefault();
            this.load(name, target.href, true);
        },

        submit(event, name) {
            const form = event.target;
            if (event.defaultPrevented || !(form instanceof HTMLFormElement)) { return; }
            const target = new URL(form.action, window.location.href);
            const base = new URL(urls[name], window.location.href);
            if (target.origin !== base.origin) { return; }
            if (form.method.toLowerCase() === 'get' && target.pathname === base.pathname) {
                event.preventDefault();
                target.search = new URLSearchParams(new FormData(form)).toString();
                this.load(name, target.href, true);
            } else if (name === 'purchases' && form.id === 'cancelModalForm') {
                event.preventDefault();
                this.load(name, target.href, true, { method: 'POST', body: new FormData(form) });
            }
        },
    };
}
