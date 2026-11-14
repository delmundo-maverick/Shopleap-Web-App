import test from 'node:test';
import assert from 'node:assert/strict';
import { pollingRequest } from '../../resources/js/shared/polling-request.js';

function setup() {
    globalThis.document = { hidden: false };
    globalThis.window = { location: { origin: 'https://shopleap.test' } };
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { onLine: true } });
}

test('deduplicates concurrent polls and changing cursors for the same thread', async () => {
    setup();
    let finish;
    let calls = 0;
    globalThis.fetch = () => { calls++; return new Promise(resolve => { finish = resolve; }); };
    const request = pollingRequest();
    const pending = request('/messages/1/messages?after=1');
    assert.equal(await request('/messages/1/messages?after=2'), null);
    finish(new Response('{}'));
    await pending;
    assert.equal(await request('/messages/1/messages?after=3'), null);
    assert.equal(calls, 1);
});

test('pauses hidden and offline tabs', async () => {
    setup();
    globalThis.fetch = () => { throw new Error('Unexpected request'); };
    const request = pollingRequest();
    document.hidden = true;
    assert.equal(await request('/feed'), null);
    document.hidden = false;
    navigator.onLine = false;
    assert.equal(await request('/feed'), null);
});

test('honors Retry-After and resumes after the cooldown', async t => {
    setup();
    t.mock.timers.enable({ apis: ['Date'], now: 1000000 });
    let calls = 0;
    globalThis.fetch = async () => { calls++; return new Response('{}', { status: 429, headers: { 'Retry-After': '120' } }); };
    const request = pollingRequest();
    await request('/feed');
    t.mock.timers.tick(119999);
    assert.equal(await request('/feed'), null);
    t.mock.timers.tick(1);
    await request('/feed');
    assert.equal(calls, 2);
});

test('backs off after server errors and stops after expired authentication', async t => {
    setup();
    t.mock.timers.enable({ apis: ['Date'], now: 1000000 });
    let calls = 0;
    globalThis.fetch = async () => { calls++; return new Response('{}', { status: calls === 1 ? 503 : 401 }); };
    const request = pollingRequest();
    await request('/feed');
    t.mock.timers.tick(19999);
    assert.equal(await request('/feed'), null);
    t.mock.timers.tick(1);
    await request('/feed');
    t.mock.timers.tick(3600000);
    assert.equal(await request('/feed'), null);
    assert.equal(calls, 2);
});
