export function voucherCard(config) {
    return {
        claimed: config.claimed, used: config.used, remaining: config.remaining, valid: config.valid, reason: config.reason || 'Unavailable', busy: false, error: '',
        async take() {
            if (this.busy || this.claimed || !this.valid || this.remaining === 0) { return; }
            const follow = this.$store.follow;
            if (follow.pending[config.sellerId]) { return; }
            if (!follow.has(config.sellerId)) { await follow.toggle(config.sellerId); return; }
            this.busy = true; this.error = '';
            try {
                const response = await fetch(config.url, { method: 'POST', headers: {
                    Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                } });
                if (response.status === 401 || response.status === 419) { location.href = config.loginUrl; return; }
                const data = await response.json();
                if (!response.ok) { throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Could not claim voucher.'); }
                this.claimed = true; this.remaining = data.remaining;
                follow.toast(data.message);
            } catch (error) { this.error = error.message; follow.toast(this.error, true); }
            finally { this.busy = false; }
        },
    };
}

document.addEventListener('click', async event => {
    const button = event.target.closest('[data-copy-voucher]');
    if (!button) { return; }
    try { await navigator.clipboard.writeText(button.dataset.copyVoucher); button.textContent = 'Copied'; }
    catch { button.textContent = 'Select and copy the code above'; }
});
