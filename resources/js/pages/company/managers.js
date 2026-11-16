document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-money-dialog]');
    if (!dialog) return;
    const message = dialog.querySelector('[data-money-dialog-message]');
    const editForm = dialog.querySelector('[data-manager-edit-form]');
    const confirm = dialog.querySelector('[data-money-confirm]');
    const cancel = dialog.querySelector('[data-money-cancel]');
    let pendingForm = null;
    let trigger = null;

    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            trigger = document.activeElement;
            pendingForm = form;
            editForm.hidden = true;
            message.textContent = form.dataset.confirm;
            confirm.textContent = 'Confirm';
            dialog.showModal();
            cancel.focus();
        });
    });
    document.querySelectorAll('[data-manager-edit]').forEach(button => {
        button.addEventListener('click', () => {
            trigger = button;
            pendingForm = editForm;
            editForm.action = button.dataset.action;
            editForm.elements.name.value = button.dataset.name;
            editForm.elements.phone.value = button.dataset.phone;
            editForm.hidden = false;
            message.textContent = `Update the name and phone for ${button.dataset.name}? Review the details, then confirm to save.`;
            confirm.textContent = 'Confirm changes';
            dialog.showModal();
            editForm.elements.name.focus();
        });
    });
    const submit = () => {
        if (!pendingForm || !pendingForm.reportValidity()) return;
        confirm.disabled = true;
        HTMLFormElement.prototype.submit.call(pendingForm);
    };
    editForm.addEventListener('submit', event => { event.preventDefault(); submit(); });
    confirm.addEventListener('click', submit);
    cancel.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        pendingForm = null;
        editForm.hidden = true;
        confirm.disabled = false;
        trigger?.focus();
    });
});
