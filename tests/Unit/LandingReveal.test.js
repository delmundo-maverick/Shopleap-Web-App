import { test } from 'node:test';
import assert from 'node:assert/strict';

function classes(...initial) {
    const values = new Set(initial);
    return {
        add: (...names) => names.forEach(name => values.add(name)),
        remove: (...names) => names.forEach(name => values.delete(name)),
        contains: name => values.has(name),
        toggle: (name, enabled) => enabled ? values.add(name) : values.delete(name),
    };
}

let instance = 0;
async function setup({ reduced = false, supported = true, broken = false, width = 320 } = {}) {
    let directive;
    let intersection;
    let motionChange;
    const observed = new Set();
    const motion = { matches: reduced, addEventListener: (_, fn) => { motionChange = fn; } };
    globalThis.document = {
        body: { classList: classes('landing-page') },
        documentElement: { classList: classes() },
        querySelector: () => null,
    };
    globalThis.window = {
        innerWidth: width, innerHeight: 800,
        matchMedia: () => motion,
    };
    globalThis.getComputedStyle = () => ({ gridTemplateColumns: width < 640 ? '1fr 1fr' : '1fr 1fr 1fr 1fr 1fr 1fr' });
    if (supported) {
        globalThis.IntersectionObserver = window.IntersectionObserver = class {
            constructor(callback) {
                if (broken) { throw new Error('unavailable'); }
                intersection = callback;
            }
            observe(element) { observed.add(element); }
            unobserve(element) { observed.delete(element); }
        };
    }
    const { registerLandingEffects } = await import(`../../resources/js/pages/landing/landing.js?test=${++instance}`);
    registerLandingEffects({ directive: (_, callback) => { directive = callback; } });
    function element(top) {
        const style = new Map();
        const events = new Map();
        return {
            classList: classes(), parentElement: {},
            style: { setProperty: (key, value) => style.set(key, value), removeProperty: key => style.delete(key) },
            getBoundingClientRect: () => ({ top, bottom: top + 100 }),
            addEventListener: (name, callback) => events.set(name, callback),
            removeEventListener: name => events.delete(name),
            values: style, events,
        };
    }
    function apply(target, index = '') {
        let dispose;
        directive(target, { expression: String(index) }, { cleanup: callback => { dispose = callback; } });
        return dispose;
    }
    return { element, apply, observed, enter: target => intersection([{ target, isIntersecting: true }]), reduce: () => { motion.matches = true; motionChange(); } };
}

for (const width of [320, 768, 1024, 1440, 1920]) {
    test(`first viewport stays visible immediately at ${width}px`, async () => {
        const context = await setup({ width });
        const first = context.element(150);
        context.apply(first);
        assert.equal(first.classList.contains('reveal-pending'), false);
        assert.equal(context.observed.size, 0);
    });
}

test('below-fold items reveal once, stagger by column, and release animation hints', async () => {
    const context = await setup();
    const tile = context.element(1000);
    const dispose = context.apply(tile, 3);
    assert.equal(tile.classList.contains('reveal-pending'), true);
    assert.equal(tile.classList.contains('reveal-animating'), false);
    assert.equal(tile.values.get('--reveal-delay'), '60ms');
    context.enter(tile);
    assert.equal(tile.classList.contains('reveal-pending'), false);
    assert.equal(tile.classList.contains('reveal-animating'), true);
    assert.equal(context.observed.size, 0);
    dispose();
    assert.equal(tile.classList.contains('reveal-animating'), false);
    assert.equal(tile.values.size, 0);
});

test('reduced motion and observer failures leave everything visible', async () => {
    for (const options of [{ reduced: true }, { supported: false }, { broken: true }]) {
        const context = await setup(options);
        const tile = context.element(1000);
        context.apply(tile);
        assert.equal(tile.classList.contains('reveal-pending'), false);
    }
});

test('enabling reduced motion reveals waiting elements immediately', async () => {
    const context = await setup();
    const tile = context.element(1000);
    context.apply(tile);
    context.reduce();
    assert.equal(tile.classList.contains('reveal-pending'), false);
    assert.equal(context.observed.size, 0);
});

test('keyboard focus immediately reveals a waiting card', async () => {
    const context = await setup();
    const tile = context.element(1000);
    context.apply(tile);
    tile.events.get('focusin')();
    assert.equal(tile.classList.contains('reveal-pending'), false);
    assert.equal(context.observed.size, 0);
});
