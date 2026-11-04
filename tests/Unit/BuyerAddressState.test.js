import test from 'node:test';
import assert from 'node:assert/strict';
import { checkoutPage } from '../../resources/js/pages/buyer/checkout.js';
import { addressBook } from '../../resources/js/shared/buyer-addresses.js';

test('a slow old shipping quote cannot overwrite the selected address totals', async () => {
    const pending = [];
    globalThis.location = { origin: 'http://localhost' };
    globalThis.fetch = () => new Promise(resolve => pending.push(resolve));
    const state = checkoutPage({ quoteUrl: '/buyer/checkout', items: '1,2', addressId: 1, shipping: {}, shippingTotal: 40, grandTotal: 240, blocked: [] });
    state.discount = 15;
    const first = state.quote(1);
    const second = state.quote(2);
    pending[1]({ ok: true, json: async () => ({ shipping: { 3: { fee: 70 } }, shippingTotal: 70, grandTotal: 270, blocked: [3] }) });
    await second;
    pending[0]({ ok: true, json: async () => ({ shipping: {}, shippingTotal: 40, grandTotal: 240, blocked: [] }) });
    await first;
    assert.equal(state.addressId, 2);
    assert.equal(state.shippingTotal, 70);
    assert.equal(state.grandTotal - state.discount, 255);
    assert.deepEqual(state.blocked, [3]);
    assert.equal(state.quoting, false);
});

test('saving an address closes the shared modal and selects the returned address', async () => {
    globalThis.document = { querySelector: () => ({ content: 'csrf-token' }) };
    globalThis.window = { dispatchEvent: () => {} };
    let posted;
    globalThis.fetch = async (url, options) => {
        posted = { url, ...options };
        return { ok: true, json: async () => ({ addresses: [{ id: 2, is_default: true, city_municipality: 'Cebu City' }], saved_id: 2 }) };
    };
    const state = addressBook({ addresses: [], checkout: true, indexUrl: '/buyer/addresses' });
    const events = [];
    state.$dispatch = (name, detail) => events.push({ name, detail });
    state.modalOpen = true;
    state.form.recipient_name = 'Recipient';
    await state.save();
    assert.equal(posted.method, 'POST');
    assert.equal(posted.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.equal(state.modalOpen, false);
    assert.equal(state.selected, 2);
    assert.deepEqual(events, [{ name: 'buyer-address-selected', detail: { id: 2 } }]);
});

test('server validation keeps the modal and entered address open with inline errors', async () => {
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ errors: { province: ['Choose a valid province.'] } }) });
    const state = addressBook({ addresses: [], checkout: true, indexUrl: '/buyer/addresses' });
    state.modalOpen = true;
    state.form.street_address = '20 Main Street';
    await state.save();
    assert.equal(state.modalOpen, true);
    assert.equal(state.form.street_address, '20 Main Street');
    assert.deepEqual(state.errors.province, ['Choose a valid province.']);
    assert.equal(state.busy, false);
});
