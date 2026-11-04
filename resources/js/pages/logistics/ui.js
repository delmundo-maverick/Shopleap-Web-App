const spinner =
    '<svg class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">' +
    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" ' +
    'd="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>' +
    '</svg>';

/**
 * Ask for confirmation before submitting a form or following a link.
 * Markup: data-confirm="Remove this parcel from the bag?"
 */
export function initConfirmations(root = document) {
    root.querySelectorAll('[data-confirm]').forEach((element) => {
        if (element.dataset.confirmBound === '1') return;
        element.dataset.confirmBound = '1';

        const eventName = element.tagName === 'FORM' ? 'submit' : 'click';

        element.addEventListener(eventName, (event) => {
            if (!window.confirm(element.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}

/**
 * Put a submit button into a disabled loading state while a normal form POST
 * is in flight. Forms opt in with data-loading.
 */
export function initFormLoading(root = document) {
    root.querySelectorAll('form[data-loading]').forEach((form) => {
        if (form.dataset.loadingBound === '1') return;
        form.dataset.loadingBound = '1';

        form.addEventListener('submit', (event) => {
            // A cancelled confirm() sets defaultPrevented; do not show a spinner then.
            if (event.defaultPrevented) return;

            const button = form.querySelector('button[type="submit"], button:not([type])');
            if (!button || button.disabled || button.getAttribute('aria-busy') === 'true') return;

            button.dataset.originalHtml = button.innerHTML;
            button.setAttribute('aria-busy', 'true');
            button.classList.add('is-loading');
            button.disabled = true;
            button.innerHTML = `${spinner}<span>Working…</span>`;
        });
    });
}

export function initLogisticsUi(root = document) {
    initConfirmations(root);
    initFormLoading(root);
}
