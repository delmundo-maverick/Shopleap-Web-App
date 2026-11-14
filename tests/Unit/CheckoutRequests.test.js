import test from 'node:test';
import assert from 'node:assert/strict';
import { checkoutPage } from '../../resources/js/pages/buyer/checkout.js';

function setup() {
    globalThis.location = { origin: 'https://shopleap.test' };
    return checkoutPage({ addressId: 1, shipping: {}, shops: {}, blocked: [], items: '1', quoteUrl: '/buyer/checkout/quote' });
}

test('serializes quote requests and keeps the latest selected address', async () => {
    const page = setup();
    const calls = [];
    let finish;
    globalThis.fetch = url => {
        calls.push(url.searchParams.get('address_id'));
        return calls.length === 1
            ? new Promise(resolve => { finish = resolve; })
            : Promise.resolve(new Response(JSON.stringify({ shipping: {}, shops: {}, blocked: [] })));
    };
    const pending = page.quote(1);
    await page.quote(2);
    await page.quote(3);
    assert.equal(calls.length, 1);
    finish(new Response(JSON.stringify({ shipping: {}, shops: {}, blocked: [] })));
    await pending;
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(calls, ['1', '3']);
    assert.equal(page.addressId, 3);
    assert.equal(page.quoting, false);
});

test('a rate limited quote blocks subsequent calls until Retry-After expires', async t => {
    const page = setup();
    t.mock.timers.enable({ apis: ['Date'], now: 1000000 });
    let calls = 0;
    globalThis.fetch = async () => {
        calls++;
        return new Response('{}', { status: 429, headers: { 'Retry-After': '120' } });
    };
    await page.quote(1);
    await page.quote(2);
    assert.equal(calls, 1);
    assert.ok(page.quoteError);
    t.mock.timers.tick(120000);
    await page.quote(2);
    assert.equal(calls, 2);
});
