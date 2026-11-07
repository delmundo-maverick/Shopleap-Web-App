export function productWorkspace(config = {}) {
    return {
        busy: false, formErrors: [], toast: '', toastError: false, timer: null,
        pendingForm: null, pendingSubmitter: null, confirmationCount: 1, confirmed: false,
        init() {
            if (config.error || config.message) this.notify(config.error || config.message, Boolean(config.error));
        },
        notify(message, error = false) {
            this.toast = message;
            this.toastError = error;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.toast = ''; }, error ? 12000 : 6000);
        },
        cancelConfirmation() {
            this.$refs.confirmation.close();
            this.pendingForm = null;
            this.pendingSubmitter?.focus();
            this.pendingSubmitter = null;
        },
        confirmDeletion() {
            const form = this.pendingForm;
            const button = this.pendingSubmitter;
            this.$refs.confirmation.close();
            this.confirmed = true;
            form?.requestSubmit(button);
            this.pendingForm = null;
        },
        async submit(event) {
            const form = event.target;
            if (form.method.toLowerCase() === 'get') {
                this.busy = true;
                return;
            }
            event.preventDefault();
            if (this.busy) return;
            const submitter = event.submitter;
            if ((form.dataset.confirmDelete !== undefined || submitter?.value === 'delete') && !this.confirmed) {
                this.pendingForm = form;
                this.pendingSubmitter = submitter;
                this.confirmationCount = form.querySelectorAll('[name="product_ids[]"]').length || 1;
                this.$refs.confirmation.showModal();
                return;
            }
            this.confirmed = false;
            this.busy = true;
            this.formErrors = [];
            const body = new FormData(form);
            if (submitter?.name) body.set(submitter.name, submitter.value);
            try {
                const response = await fetch(form.action, {
                    method: 'POST', body,
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    this.formErrors = Object.values(data.errors || {}).flat();
                    if (response.status === 419) this.formErrors = ['Your session expired. Reload this page and try again.'];
                    if (!this.formErrors.length) this.formErrors = [data.message || 'This action could not be completed. Please try again.'];
                    this.notify(this.formErrors[0], true);
                    this.$nextTick(() => this.$refs.errors.focus());
                    return;
                }
                if (data.redirect) window.location.assign(data.redirect);
                else this.notify(data.message || 'Saved successfully.');
            } catch {
                this.formErrors = ['Could not connect. Your form is still here; reconnect and try again.'];
                this.notify(this.formErrors[0], true);
            } finally {
                this.busy = false;
            }
        },
        destroy() { clearTimeout(this.timer); },
    };
}
