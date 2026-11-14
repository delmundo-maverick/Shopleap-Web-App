export function checkoutPage(config) {
    return {
        addressId: config.addressId, shipping: config.shipping, shops: config.shops,
        blocked: config.blocked.map(Number), selections: config.selectedVouchers || {},
        messages: {}, picker: null, quoting: false, quoteError: '', requestNumber: 0,
        refreshTimer: null, refreshOnFocus: null, lastQuoteAt: 0, quoteRetryAt: 0, queuedAddressId: undefined,
        init() {
            this.revalidate();
            this.refreshOnFocus = () => {
                if (!document.hidden && this.addressId && !this.quoting && Date.now() - this.lastQuoteAt >= 60000) { this.quote(this.addressId); }
            };
            window.addEventListener('focus', this.refreshOnFocus);
            this.refreshTimer = window.setInterval(this.refreshOnFocus, 60000);
        },
        destroy() {
            window.removeEventListener('focus', this.refreshOnFocus);
            window.clearInterval(this.refreshTimer);
        },
        money(amount) {
            return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount) || 0);
        },
        round(amount) { return Math.round((amount + Number.EPSILON) * 100) / 100; },
        get subtotal() { return this.round(Object.values(this.shops).reduce((sum, shop) => sum + Number(shop.subtotal), 0)); },
        get discount() { return this.round(Object.keys(this.shops).reduce((sum, id) => sum + this.shopDiscount(id), 0)); },
        get shippingTotal() { return this.round(Object.keys(this.shops).reduce((sum, id) => sum + Number(this.shipping[id]?.fee || 0), 0)); },
        get grandTotal() { return this.round(this.subtotal - this.discount + this.shippingTotal); },
        voucherReason(voucher) {
            if (voucher.reason) { return voucher.reason; }
            if (voucher.expires_at && Date.parse(voucher.expires_at) <= Date.now()) { return 'Expired'; }
            if (voucher.starts_at && Date.parse(voucher.starts_at) > Date.now()) { return 'Not started yet'; }
            return '';
        },
        selectedVoucher(id) { return this.shops[id]?.vouchers.find(voucher => voucher.code === this.selections[id]); },
        shopDiscount(id) {
            const voucher = this.selectedVoucher(id);
            return voucher && !this.voucherReason(voucher)
                ? this.round(Math.max(0, Math.min(Number(this.shops[id].subtotal), Number(voucher.discount)))) : 0;
        },
        shopTotal(id) {
            return this.round(Number(this.shops[id]?.subtotal || 0) - this.shopDiscount(id) + Number(this.shipping[id]?.fee || 0));
        },
        revalidate() {
            for (const [id, code] of Object.entries(this.selections)) {
                if (!code) { continue; }
                const voucher = this.selectedVoucher(id);
                const reason = voucher ? this.voucherReason(voucher) : 'This voucher no longer qualifies for these items.';
                if (reason) {
                    delete this.selections[id];
                    this.messages[id] = `Voucher removed: ${reason}`;
                }
            }
        },
        openPicker(id) {
            this.picker = id;
            if (this.addressId && !this.quoting && Date.now() - this.lastQuoteAt >= 60000) { this.quote(this.addressId); }
        },
        selectVoucher(id, voucher) {
            if (this.quoting || this.quoteError || this.voucherReason(voucher)) { return; }
            this.selections[id] = voucher.code;
            this.messages[id] = '';
            this.picker = null;
        },
        removeVoucher(id) {
            delete this.selections[id];
            this.messages[id] = 'Voucher removed.';
        },
        async quote(id) {
            this.addressId = id;
            if (this.quoting) { this.queuedAddressId = id; return; }
            if (Date.now() < this.quoteRetryAt) {
                this.quoteError = 'Please wait before updating shipping again.';
                return;
            }
            const number = ++this.requestNumber;
            this.quoting = true; this.quoteError = '';
            if (!id) { this.quoting = false; return; }
            const url = new URL(config.quoteUrl, location.origin);
            url.searchParams.set('items', config.items);
            url.searchParams.set('address_id', id);
            url.searchParams.set('include_vouchers', '1');
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(15000) });
                if (response.status === 429 || response.status >= 500) {
                    const retry = response.headers.get('Retry-After');
                    const delay = retry && /^\d+$/.test(retry) ? Number(retry) * 1000 : Date.parse(retry || '') - Date.now();
                    this.quoteRetryAt = Date.now() + Math.max(60000, Number.isFinite(delay) ? delay : 0);
                }
                const data = await response.json();
                if (!response.ok) { throw new Error(data.message || 'Could not update shipping.'); }
                if (number !== this.requestNumber || id !== this.addressId) { return; }
                this.shipping = data.shipping; this.shops = data.shops;
                this.blocked = data.blocked.map(Number);
                this.lastQuoteAt = Date.now();
                this.revalidate();
            } catch (error) {
                if (number === this.requestNumber) { this.quoteError = 'Shipping could not be updated. Select the address again to retry.'; }
            } finally {
                if (number === this.requestNumber) { this.quoting = false; }
                const queued = this.queuedAddressId;
                this.queuedAddressId = undefined;
                if (queued !== undefined && queued !== id) { this.quote(queued); }
            }
        },
    };
}
