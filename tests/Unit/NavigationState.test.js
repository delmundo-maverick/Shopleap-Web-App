import test from 'node:test';
import assert from 'node:assert/strict';
import { navbar } from '../../resources/js/shared/navigation.js';

function state(width = 375) {
    const events = [];
    globalThis.window = { innerWidth: width, dispatchEvent: event => events.push(event.detail) };
    globalThis.document = { documentElement: { style: { overflow: 'clip' } }, body: { style: { overflow: 'auto' } }, activeElement: { focus() {} } };
    globalThis.localStorage = { getItem: () => null, setItem() {} };
    const nav = navbar({ id: 'test-navigation' });
    nav.$refs = { panel: { querySelector: () => ({ focus() {} }) } };
    nav.$nextTick = callback => callback();
    nav.$watch = (key, callback) => {
        let value = nav[key];
        Object.defineProperty(nav, key, { get: () => value, set: next => { value = next; callback(next); } });
    };
    nav.init();
    return { nav, events };
}

for (const width of [320, 375, 414, 768, 844]) {
    test(`mobile navigation state, scroll lock and closing at ${width}px`, () => {
        const { nav } = state(width);
        let focused = false;
        nav.toggle({ focus: () => { focused = true; } });
        assert.equal(nav.open, true);
        assert.equal(document.body.style.overflow, 'hidden');
        nav.escape();
        assert.equal(nav.open, false);
        assert.equal(document.body.style.overflow, 'auto');
        assert.equal(document.documentElement.style.overflow, 'clip');
        assert.equal(focused, true);
        nav.destroy();
    });
}

test('desktop resize closes the drawer and restores scrolling', () => {
    const { nav } = state();
    nav.toggle(); window.innerWidth = 1024; nav.resize();
    assert.equal(nav.open, false); assert.equal(nav.desktop, true);
    assert.equal(document.body.style.overflow, 'auto');
    nav.toggle(); assert.equal(nav.open, false);
    nav.destroy();
});

test('only one dropdown opens and Escape restores its trigger', () => {
    const { nav } = state(1024);
    let focused = false; const trigger = { focus: () => { focused = true; } };
    nav.dropdown('profile', trigger); nav.dropdown('cart', trigger);
    assert.equal(nav.activeDropdown, 'cart');
    nav.escape(); assert.equal(nav.activeDropdown, null); assert.equal(focused, true);
    nav.destroy();
});

test('a navigation link closes the menu and does not submit logout', () => {
    const { nav } = state(); nav.toggle();
    nav.navigate({ target: { closest: () => ({ href: '/orders' }) } });
    assert.equal(nav.open, false); assert.equal(nav.logoutOpen, false);
    nav.destroy();
});

test('logout requires confirmation and cleans up the scroll lock', () => {
    const { nav } = state(); let submitted = false;
    nav.$refs.logoutDialog = { querySelector: () => ({ focus() {} }) };
    nav.requestLogout({ focus() {} });
    assert.equal(nav.logoutOpen, true); assert.equal(submitted, false);
    assert.equal(document.body.style.overflow, 'hidden');
    nav.escape(); assert.equal(document.body.style.overflow, 'auto');
    nav.requestLogout({ focus() {} });
    nav.confirmLogout({ submit: () => { submitted = true; } }); assert.equal(submitted, true);
    nav.reset(); assert.equal(document.body.style.overflow, 'auto');
    nav.destroy();
});

test('mobile cancellation reopens the menu and returns focus to its logout icon', () => {
    const { nav } = state(320); let focused = false;
    nav.$refs.logoutDialog = { querySelector: () => ({ focus() {} }) };
    nav.toggle();
    nav.requestLogout({ focus: () => { focused = true; } });
    assert.equal(nav.open, false);
    assert.equal(nav.logoutOpen, true);
    nav.cancelLogout();
    assert.equal(nav.open, true);
    assert.equal(nav.logoutOpen, false);
    assert.equal(focused, true);
    nav.close(); assert.equal(document.body.style.overflow, 'auto');
    nav.destroy();
});

test('confirmation submits once and cannot submit without an open dialog', () => {
    const { nav } = state(1024); let submissions = 0;
    const form = { submit: () => { submissions++; } };
    nav.$refs.logoutDialog = { querySelector: () => ({ focus() {} }) };
    nav.confirmLogout(form); assert.equal(submissions, 0);
    nav.requestLogout({ focus() {} });
    nav.confirmLogout(form); nav.confirmLogout(form); nav.cancelLogout();
    assert.equal(submissions, 1);
    assert.equal(nav.logoutSubmitting, true);
    assert.equal(nav.logoutOpen, true);
    nav.reset(); nav.destroy();
});

test('dialog traps keyboard focus and redirects focus from background controls', () => {
    const { nav } = state(1024); let focused = null; let prevented = false;
    const cancel = { focus: () => { focused = 'cancel'; }, getClientRects: () => [1] };
    const confirm = { focus: () => { focused = 'confirm'; }, getClientRects: () => [1] };
    nav.$refs.logoutDialog = { querySelector: () => cancel, querySelectorAll: () => [cancel, confirm], contains: el => [cancel, confirm].includes(el) };
    nav.requestLogout({ focus() {} });
    document.activeElement = confirm;
    nav.trap({ key: 'Tab', shiftKey: false, preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true); assert.equal(focused, 'cancel');
    document.activeElement = cancel;
    nav.trap({ key: 'Tab', shiftKey: true, preventDefault() {} });
    assert.equal(focused, 'confirm');
    nav.containLogoutFocus({ target: {} }); assert.equal(focused, 'cancel');
    nav.reset(); nav.destroy();
});
