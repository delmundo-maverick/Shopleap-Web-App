export function checkoutPage(config) {
    return {
        addressId: config.addressId, shipping: config.shipping, shippingTotal: Number(config.shippingTotal),
        grandTotal: Number(config.grandTotal), blocked: config.blocked.map(Number), discount: 0,
        quoting: false, quoteError: '', requestNumber: 0,
        async quote(id) {
            this.addressId = id;
            const number = ++this.requestNumber;
            this.quoting = true; this.quoteError = '';
            if (!id) { this.quoting = false; return; }
            const url = new URL(config.quoteUrl, location.origin);
            url.searchParams.set('items', config.items);
            url.searchParams.set('address_id', id);
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok) { throw new Error(data.message || 'Could not update shipping.'); }
                if (number !== this.requestNumber) { return; }
                this.shipping = data.shipping; this.shippingTotal = Number(data.shippingTotal);
                this.grandTotal = Number(data.grandTotal); this.blocked = data.blocked.map(Number);
            } catch (error) {
                if (number === this.requestNumber) { this.quoteError = 'Shipping could not be updated. Select the address again to retry.'; }
            } finally { if (number === this.requestNumber) { this.quoting = false; } }
        },
    };
}

export function voucherCheckout(url) {
    return {
        code: '', appliedCode: '', message: '', success: false, busy: false,
        async applyVoucher() {
            if (this.busy) { return; }
            this.message = ''; this.success = false;
            if (!this.code) { this.message = 'Please enter a voucher code.'; return; }
            this.busy = true;
            try {
                const response = await fetch(url, { method: 'POST', headers: {
                    'Content-Type': 'application/json', Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }, body: JSON.stringify({ code: this.code }) });
                const data = await response.json();
                this.success = response.ok && !!data.success;
                this.appliedCode = this.success ? data.voucher_code : '';
                this.message = data.message || (this.success ? 'Voucher applied.' : 'Could not apply voucher.');
                window.dispatchEvent(new CustomEvent('voucher-applied', { detail: { discount: this.success ? Number(data.discount) || 0 : 0 } }));
            } catch (error) { this.message = 'Could not apply voucher. Please try again.'; }
            finally { this.busy = false; }
        },
    };
}
