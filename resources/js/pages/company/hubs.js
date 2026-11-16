document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-money-dialog]');
    if (!dialog) return;
    let pendingForm = null;
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            pendingForm = form;
            dialog.querySelector('[data-money-dialog-message]').textContent = form.dataset.confirm;
            dialog.showModal();
        });
    });
    dialog.querySelector('[data-money-cancel]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-money-confirm]').addEventListener('click', () => {
        const form = pendingForm;
        dialog.close();
        form?.submit();
    });
    dialog.addEventListener('close', () => { pendingForm = null; });
});
