function initDeliveryProofs() {
    const dialog = document.querySelector('[data-proof-dialog]');
    if (dialog) {
        const frame = dialog.querySelector('[data-proof-frame]');
        let opener;
        document.querySelectorAll('[data-proof-open]').forEach(link => {
            link.addEventListener('click', event => {
                if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) { return; }
                event.preventDefault();
                opener = link;
                frame.src = link.href;
                dialog.showModal();
            });
        });
        dialog.querySelector('[data-proof-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => { frame.removeAttribute('src'); opener?.focus(); });
    }
    document.querySelectorAll('[data-proof-image]').forEach(image => {
        const unavailable = () => {
            image.hidden = true;
            document.querySelector('[data-proof-image-error]').hidden = false;
        };
        image.addEventListener('error', unavailable);
        if (image.complete && image.naturalWidth === 0) { unavailable(); }
    });
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initDeliveryProofs); }
else { initDeliveryProofs(); }
