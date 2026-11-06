import test from 'node:test';
import assert from 'node:assert/strict';
globalThis.document = { addEventListener() {}, querySelector: () => ({ content: 'csrf' }) };
const { voucherCard } = await import('../../resources/js/pages/buyer/vouchers.js');

function card(following) {
    const state = voucherCard({ url: '/buyer/vouchers/1/claim', sellerId: 2, claimed: false, used: false, remaining: 1, valid: true });
    let toggled = 0;
    state.$store = { follow: { pending: {}, has: () => following, toggle: async () => { toggled++; }, toast() {} } };
    return { state, toggles: () => toggled };
}

test('follow to claim uses the existing follow store before making a claim', async () => {
    const { state, toggles } = card(false);
    globalThis.fetch = () => { throw new Error('Must not claim before following'); };
    await state.take();
    assert.equal(toggles(), 1);
    assert.equal(state.claimed, false);
});

test('a successful AJAX claim updates its state and cannot be submitted twice', async () => {
    const { state } = card(true);
    let calls = 0;
    globalThis.fetch = async () => { calls++; return { ok: true, json: async () => ({ remaining: 0, message: 'Claimed' }) }; };
    await state.take();
    await state.take();
    assert.equal(calls, 1);
    assert.equal(state.claimed, true);
    assert.equal(state.remaining, 0);
    assert.equal(state.busy, false);
});

test('a server quota rejection remains visible without marking the voucher claimed', async () => {
    const { state } = card(true);
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ errors: { voucher: ['Fully claimed.'] } }) });
    await state.take();
    assert.equal(state.claimed, false);
    assert.equal(state.error, 'Fully claimed.');
    assert.equal(state.busy, false);
});
