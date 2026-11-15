const dialog = document.querySelector('[data-money-dialog]');
let pending = null;

function cents(value) {
    return Math.round(Number(value || 0) * 100);
}

function payment(form) {
    return cents(form.dataset.payment);
}

function update(form) {
    const warning = form.querySelector('[data-negative-warning]');
    if (warning) {
        warning.hidden = payment(form) <= cents(form.dataset.cashHeld);
        warning.textContent = 'This remittance will make the hub cash held negative.';
    }
    const amount = form.querySelector('[data-expected]');
    const notes = form.querySelector('[name="notes"]');
    if (amount && notes) notes.required = cents(amount.value) !== cents(amount.dataset.expected);
}

document.querySelectorAll('[data-money-form]').forEach(form => {
    update(form);
    form.addEventListener('input', () => update(form));
    form.querySelectorAll('[data-select-all]').forEach(toggle => toggle.addEventListener('change', () => {
        form.querySelectorAll('input[name="orders[]"]').forEach(input => { input.checked = toggle.checked; });
        update(form);
    }));
    form.addEventListener('submit', event => {
        if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
        if (dialog && form.dataset.confirm && form.dataset.confirmed !== 'true') {
            event.preventDefault();
            pending = { form, submitter: event.submitter };
            let message = form.dataset.confirm;
            if (form.hasAttribute('data-cash-held') && payment(form, event.submitter) > cents(form.dataset.cashHeld)) {
                message += ' This payment will make the hub cash held negative.';
            }
            dialog.querySelector('[data-money-dialog-message]').textContent = message;
            dialog.showModal();
            return;
        }
        form.dataset.submitting = 'true';
        if (event.submitter?.name) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = event.submitter.name; input.value = event.submitter.value; form.append(input);
        }
        form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(button => { button.disabled = true; });
    });
});

dialog?.querySelector('[data-money-cancel]').addEventListener('click', () => { pending = null; dialog.close(); });
dialog?.addEventListener('cancel', () => { pending = null; });
dialog?.querySelector('[data-money-confirm]').addEventListener('click', () => {
    const action = pending; pending = null; dialog.close();
    if (action) { action.form.dataset.confirmed = 'true'; action.form.requestSubmit(action.submitter); }
});
window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
