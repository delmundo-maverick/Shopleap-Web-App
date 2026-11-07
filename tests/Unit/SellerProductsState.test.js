import test from 'node:test';
import assert from 'node:assert/strict';
import { productForm } from '../../resources/js/pages/seller/product-form.js';
import { productDiscounts } from '../../resources/js/pages/seller/discounts.js';
import { productWorkspace } from '../../resources/js/pages/seller/product-workspace.js';
import { productNavigation } from '../../resources/js/pages/seller/products.js';

function formState(extra = {}) {
    return productForm({ editing: false, name: '', description: '', category: '', subcategory: '', categories: [], price: 100, stock: 10, existingImages: [], removedImages: [], options: [], variants: [], ...extra });
}

test('variant regeneration preserves entered values and refuses excessive or duplicate groups', () => {
    const state = formState({ options: [{ name: 'Size', values: 'S,L' }] });
    state.generateVariants();
    assert.equal(state.variants.length, 2);
    state.variants[0].price = 125;
    state.variants[0].preview = 'blob:photo';
    state.options[0].values = 'S,L,XL';
    state.generateVariants();
    assert.equal(state.variants[0].price, 125);
    assert.equal(state.variants[0].preview, 'blob:photo');
    state.options.push({ name: 'size', values: 'XS' });
    state.generateVariants();
    assert.match(state.variantError, /unique/);
    state.options = [{ name: 'Size', values: Array.from({ length: 101 }, (_, i) => i).join(',') }];
    state.generateVariants();
    assert.match(state.variantError, /100/);
    assert.equal(state.variants.length, 3);
});

test('buyer preview follows retained cover images and active variant pricing', () => {
    const state = formState({
        existingImages: [{ id: 1, url: 'first.jpg' }, { id: 2, url: 'second.jpg' }], removedImages: [1],
        variants: [
            { options: { Size: 'S' }, price: 50, stock_quantity: 2, is_active: 0 },
            { options: { Size: 'L' }, price: 120, stock_quantity: 3, is_active: 1 },
        ],
    });
    assert.equal(state.cover, 'second.jpg');
    assert.match(state.previewPrice, /120/);
    assert.equal(state.pricingComplete, true);
    state.variants[1].stock_quantity = '';
    assert.equal(state.pricingComplete, false);
});

test('discount previews validate every selected product and enforce the percentage cap', () => {
    const state = productDiscounts({ selected: [{ id: 1, name: 'Cheap', price: 10 }, { id: 2, name: 'Costly', price: 100 }], type: 'fixed', value: 20 });
    assert.equal(state.finalPrice(state.selected[1]), 80);
    assert.equal(state.canSave, false);
    state.value = 5;
    assert.equal(state.canSave, true);
    state.type = 'percent';
    state.value = 91;
    assert.equal(state.canSave, false);
    state.value = 90;
    assert.equal(state.finalPrice(state.selected[0]), 1);
    assert.equal(state.canSave, true);
    state.remove(1);
    assert.equal(state.selected.length, 1);
});

test('discount product pagination carries current selection without stale query keys', () => {
    let navigated;
    globalThis.window = { location: { assign(url) { navigated = String(url); } } };
    const state = productDiscounts({ selected: [{ id: 2 }], type: 'fixed', value: 1 });
    state.preserveSelection({
        target: { closest: () => ({ href: 'http://localhost/seller/products/discounts?product_page=2&product_ids%5B0%5D=1' }) },
        preventDefault() {},
    });
    const url = new URL(navigated);
    assert.equal(url.searchParams.get('product_page'), '2');
    assert.equal(url.searchParams.has('product_ids[0]'), false);
    assert.deepEqual(url.searchParams.getAll('product_ids[]'), ['2']);
});

test('the product sidebar opens on every product page and remains collapsible', () => {
    const state = productNavigation(true);
    assert.equal(state.expanded, true);
    state.toggleGroup();
    assert.equal(state.expanded, false);
    assert.equal(productNavigation(false).expanded, false);
});

function workspace() {
    const state = productWorkspace();
    state.$refs = { errors: { focus() {} }, confirmation: { close() {}, showModal() {} } };
    state.$nextTick = callback => callback();
    state.notify = function (message, error) { this.toast = message; this.toastError = error; };
    return state;
}

test('failed saves retain the form and report validation or network errors with loading reset', async () => {
    globalThis.document = { querySelector: () => ({ content: 'csrf' }) };
    const NativeFormData = globalThis.FormData;
    globalThis.FormData = class extends Map { constructor() { super(); } };
    const state = workspace();
    const form = { method: 'POST', action: '/seller/products', dataset: {} };
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ errors: { name: ['A name is required.'] } }) });
    await state.submit({ target: form, preventDefault() {} });
    assert.deepEqual(state.formErrors, ['A name is required.']);
    assert.equal(state.busy, false);
    globalThis.fetch = async () => { throw new Error('Offline'); };
    await state.submit({ target: form, preventDefault() {} });
    assert.match(state.toast, /reconnect/);
    assert.equal(state.busy, false);
    globalThis.FormData = NativeFormData;
});

test('delete submits only after a centered confirmation and preserves the submitter', async () => {
    const state = workspace();
    let shown = false;
    let submitted;
    state.$refs.confirmation.showModal = () => { shown = true; };
    const submitter = { name: 'action', value: 'delete' };
    const form = { method: 'POST', dataset: {}, querySelectorAll: () => [1, 2], requestSubmit(button) { submitted = button; } };
    globalThis.fetch = () => { throw new Error('Must not delete before confirmation'); };
    await state.submit({ target: form, submitter, preventDefault() {} });
    assert.equal(shown, true);
    assert.equal(state.confirmationCount, 2);
    assert.equal(submitted, undefined);
    state.confirmDeletion();
    assert.equal(submitted, submitter);
    assert.equal(state.confirmed, true);
});
