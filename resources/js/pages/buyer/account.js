import { initPsgcCascade } from '../../shared/psgcCascade.js';

document.addEventListener('DOMContentLoaded', () => {
    // PROFILE PHOTO — client-side check + live preview
    const input = document.getElementById('avatarInput');

    if (input) {
        const previewImg = document.getElementById('avatarPreviewImg');
        const saveActions = document.getElementById('avatarSaveActions');
        const cancelBtn = document.getElementById('avatarCancelBtn');
        const errorEl = document.getElementById('avatarClientError');
        const MAX_BYTES = 2 * 1024 * 1024;
        const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
        let objectUrl = null;

        function clearPreview() {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            previewImg.removeAttribute('src');
            previewImg.classList.add('hidden');
            saveActions.classList.add('hidden');
            saveActions.classList.remove('flex');
        }

        function rejectFile(message) {
            input.value = '';
            clearPreview();
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }

        input.addEventListener('change', () => {
            errorEl.classList.add('hidden');
            const file = input.files[0];

            if (!file) return clearPreview();
            if (!ALLOWED.includes(file.type)) return rejectFile('Please choose a JPG, PNG or WEBP image.');
            if (file.size > MAX_BYTES) return rejectFile('That image is larger than 2 MB. Please choose a smaller one.');

            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            previewImg.src = objectUrl;
            previewImg.classList.remove('hidden');
            saveActions.classList.remove('hidden');
            saveActions.classList.add('flex');
        });

        cancelBtn?.addEventListener('click', () => {
            input.value = '';
            errorEl.classList.add('hidden');
            clearPreview();
        });
    }

    // CONFIRM-BEFORE-SUBMIT FORMS
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    // DELIVERY ADDRESS — PSGC cascade, prefilled with the saved address
    const addressDataEl = document.getElementById('addressData');

    if (addressDataEl) {
        initPsgcCascade({
            province: document.getElementById('province'),
            municipality: document.getElementById('municipality'),
            barangay: document.getElementById('barangay'),
            helpEl: document.getElementById('addressHelp'),
            initial: JSON.parse(addressDataEl.textContent),
        });
    }
});
