import test from 'node:test';
import assert from 'node:assert/strict';
import { accountTabs } from '../../resources/js/pages/buyer/account-tabs.js';

function setup(config = {}) {
    const history = [];
    const listeners = new Map();
    globalThis.window = {
        location: { href: 'https://shopleap.test/buyer/account', hash: '' },
        history: { replaceState: (...args) => history.push(args[2]) },
        addEventListener: (name, callback) => listeners.set(name, callback),
        removeEventListener: name => listeners.delete(name),
    };
    const initialized = [];
    const state = accountTabs({
        urls: {
            purchases: 'https://shopleap.test/buyer/orders',
            wishlist: 'https://shopleap.test/buyer/wishlist',
            messages: 'https://shopleap.test/messages',
        },
        ...config,
    }, async name => initialized.push(name));
    state.$refs = Object.fromEntries(['purchases', 'wishlist', 'messages'].map(name => [name, {
        innerHTML: '', dataset: {}, dispatchEvent() {},
    }]));
    return { state, history, listeners, initialized };
}

function response(name, content = '') {
    return { ok: true, text: async () => `<div data-account-content="${name}">${content}</div>` };
}

test('loads sections only when opened and preserves their content when switching back', async () => {
    const { state, initialized, history } = setup();
    const requests = [];
    globalThis.fetch = async (url, options) => {
        requests.push({ url, options });
        return response(url.includes('wishlist') ? 'wishlist' : 'purchases');
    };
    state.init();
    assert.equal(requests.length, 0);
    await state.go('purchases');
    state.$refs.purchases.innerHTML = 'Preserved filter and selection';
    await state.go('profile');
    await state.go('wishlist');
    await state.go('purchases');
    assert.equal(state.tab, 'purchases');
    assert.equal(requests.length, 2);
    assert.equal(requests[0].options.headers['X-Account-Panel'], 'true');
    assert.equal(state.$refs.purchases.innerHTML, 'Preserved filter and selection');
    assert.deepEqual(initialized, ['purchases', 'wishlist']);
    assert.equal(history.at(-1), '#purchases');
});

test('reopens hash sections but keeps validation failures on their required account tab', () => {
    const { state, listeners } = setup({ initialTab: 'security', forced: true });
    window.location.hash = '#wishlist';
    state.init();
    assert.equal(state.tab, 'security');
    window.location.hash = '#address';
    listeners.get('hashchange')();
    assert.equal(state.tab, 'address');
    state.destroy();
    assert.equal(listeners.has('hashchange'), false);
});

test('a slower previous filter cannot replace the most recent purchases result', async () => {
    const { state } = setup();
    const pending = [];
    globalThis.fetch = () => new Promise(resolve => pending.push(resolve));
    const old = state.load('purchases', 'https://shopleap.test/buyer/orders?tab=all', true);
    const latest = state.load('purchases', 'https://shopleap.test/buyer/orders?tab=completed', true);
    pending[1](response('purchases', 'Latest completed orders'));
    await latest;
    pending[0](response('purchases', 'Old orders'));
    await old;
    assert.match(state.$refs.purchases.innerHTML, /Latest completed orders/);
    assert.equal(state.loading.purchases, false);
});

test('failed requests remain retryable and expired sessions cannot replace account content', async () => {
    const { state } = setup();
    globalThis.fetch = async () => { throw new Error('Network unavailable'); };
    await state.go('messages');
    assert.equal(state.errors.messages, 'Network unavailable');
    assert.equal(state.loading.messages, false);
    globalThis.fetch = async () => ({ ok: true, text: async () => '<html>Sign in</html>' });
    await state.load('messages', null, true);
    assert.equal(state.$refs.messages.innerHTML, '');
    assert.match(state.errors.messages, /session/);
    globalThis.fetch = async () => response('messages');
    await state.load('messages', null, true);
    assert.equal(state.errors.messages, '');
    assert.equal(state.loaded.messages, true);
});

test('keeps pagination inside its account section and preserves modified link clicks', () => {
    const { state } = setup();
    const calls = [];
    state.load = (...args) => calls.push(args);
    const link = { href: 'https://shopleap.test/buyer/orders?page=2', hasAttribute: () => false };
    let prevented = false;
    const event = { button: 0, target: { closest: () => link }, preventDefault: () => { prevented = true; } };
    state.navigate(event, 'purchases');
    assert.equal(prevented, true);
    assert.deepEqual(calls[0], ['purchases', link.href, true]);
    state.navigate({ ...event, ctrlKey: true }, 'purchases');
    link.href = 'https://shopleap.test/buyer/products/1';
    state.navigate(event, 'purchases');
    assert.equal(calls.length, 1);
});

test('does not leave the selected section when an older background request finishes', async () => {
    const { state } = setup();
    let finish;
    globalThis.fetch = () => new Promise(resolve => { finish = resolve; });
    const loading = state.go('messages');
    await state.go('profile');
    finish(response('messages'));
    await loading;
    assert.equal(state.tab, 'profile');
    assert.equal(state.loaded.messages, true);
});

test('submits purchase search and cancellation inside the panel', () => {
    const { state } = setup();
    const requests = [];
    state.load = (...args) => requests.push(args);
    const nativeFormData = globalThis.FormData;
    const nativeForm = globalThis.HTMLFormElement;
    globalThis.HTMLFormElement = class {};
    globalThis.FormData = class {
        constructor(form) { this.values = form.values; }
        [Symbol.iterator]() { return this.values[Symbol.iterator](); }
    };
    try {
        const form = new HTMLFormElement();
        Object.assign(form, { action: 'https://shopleap.test/buyer/orders', method: 'get', values: [['search', 'shop name']] });
        let prevented = 0;
        const event = { target: form, preventDefault: () => prevented++ };
        state.submit(event, 'purchases');
        assert.match(requests[0][1], /search=shop\+name/);
        Object.assign(form, { action: 'https://shopleap.test/buyer/orders/42/cancel', method: 'post', id: 'cancelModalForm', values: [['_token', 'csrf-token']] });
        state.submit(event, 'purchases');
        assert.equal(prevented, 2);
        assert.equal(requests[1][3].method, 'POST');
        assert.deepEqual([...requests[1][3].body], [['_token', 'csrf-token']]);
    } finally {
        globalThis.FormData = nativeFormData;
        globalThis.HTMLFormElement = nativeForm;
    }
});
