document.querySelector('[data-generate-voucher-code]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const error = document.querySelector('[data-voucher-code-error]');
    button.disabled = true;
    error.hidden = true;

    try {
        const response = await fetch(button.dataset.generateVoucherCode, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
            signal: AbortSignal.timeout(15000),
        });
        const data = await response.json();
        if (!response.ok || !data.code) {
            throw new Error('Could not generate a code. Please try again.');
        }
        document.getElementById('voucherCode').value = data.code;
    } catch {
        error.textContent = 'Could not generate a code. Please try again.';
        error.hidden = false;
    } finally {
        button.disabled = false;
    }
});
