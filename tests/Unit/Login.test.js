import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

test('the shared login toggle reveals and hides the password with accessible state', () => {
    function element(hidden = false) {
        const listeners = new Map();
        const attributes = new Map();
        const classes = new Set(hidden ? ['hidden'] : []);

        return {
            listeners,
            attributes,
            classes,
            addEventListener: (name, callback) => listeners.set(name, callback),
            setAttribute: (name, value) => attributes.set(name, value),
            classList: {
                toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
                add: (name) => classes.add(name),
            },
        };
    }

    const elements = {
        loginForm: element(),
        password: { ...element(), type: 'password', value: 'my-password' },
        togglePassword: element(),
        eyeOpen: element(),
        eyeClosed: element(true),
    };

    runInNewContext(readFileSync(new URL('../../resources/js/pages/auth/login.js', import.meta.url), 'utf8'), {
        document: {
            addEventListener: (name, callback) => callback(),
            getElementById: (id) => elements[id] ?? null,
        },
        window: { addEventListener: () => {} },
    });

    const toggle = elements.togglePassword.listeners.get('click');
    toggle();

    assert.equal(elements.password.type, 'text');
    assert.equal(elements.password.value, 'my-password');
    assert.equal(elements.togglePassword.attributes.get('aria-label'), 'Hide password');
    assert.equal(elements.togglePassword.attributes.get('aria-pressed'), 'true');
    assert.equal(elements.eyeOpen.classes.has('hidden'), true);
    assert.equal(elements.eyeClosed.classes.has('hidden'), false);

    toggle();

    assert.equal(elements.password.type, 'password');
    assert.equal(elements.password.value, 'my-password');
    assert.equal(elements.togglePassword.attributes.get('aria-label'), 'Show password');
    assert.equal(elements.togglePassword.attributes.get('aria-pressed'), 'false');
    assert.equal(elements.eyeOpen.classes.has('hidden'), false);
    assert.equal(elements.eyeClosed.classes.has('hidden'), true);
});
