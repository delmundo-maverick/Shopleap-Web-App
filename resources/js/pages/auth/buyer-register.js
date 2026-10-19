import { initPsgcCascade } from '../../shared/psgcCascade.js';

document.addEventListener('DOMContentLoaded', () => {
    // ---------------------------------------------------
    // PASSWORD VISIBILITY
    // ---------------------------------------------------
    function wirePasswordToggle(toggleId, inputId, iconId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!toggle || !input || !icon) return;

        toggle.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.innerHTML = isHidden
                ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.7 10.7 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3 3.8M6.2 6.2C3.7 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.6-.3 3.7-.8" />`
                : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6z" /><circle cx="12" cy="12" r="2.5" stroke-width="1.8" />`;
        });
    }

    wirePasswordToggle('togglePasswordBtn', 'password', 'passwordEye');
    wirePasswordToggle('toggleConfirmPasswordBtn', 'password_confirmation', 'confirmPasswordEye');

    // ---------------------------------------------------
    // AUTOMATIC AGE CALCULATION
    // ---------------------------------------------------
    const birthdayInput = document.getElementById('birthday');
    const ageInput = document.getElementById('age');

    function calculateAge() {
        if (!birthdayInput || !ageInput) return;

        if (!birthdayInput.value) {
            ageInput.value = '';
            return;
        }
        const birthday = new Date(birthdayInput.value);
        const today = new Date();
        let age = today.getFullYear() - birthday.getFullYear();
        const m = today.getMonth() - birthday.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthday.getDate())) age--;
        ageInput.value = age >= 0 ? age : '';
    }

    birthdayInput?.addEventListener('change', calculateAge);
    if (birthdayInput?.value) calculateAge();

    // ---------------------------------------------------
    // ID FILE SELECTION
    // ---------------------------------------------------
    const idUpload = document.getElementById('id_upload');
    const selectedFile = document.getElementById('selectedFile');
    const fileName = document.getElementById('fileName');

    idUpload?.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            fileName.textContent = this.files[0].name;
            selectedFile.classList.remove('hidden');
        } else {
            fileName.textContent = '';
            selectedFile.classList.add('hidden');
        }
    });

    // ---------------------------------------------------
    // MIDDLE INITIAL — letters only
    // ---------------------------------------------------
    document.getElementById('middle_initial')?.addEventListener('input', function () {
        this.value = this.value.replace(/[^a-zA-Z]/g, '').slice(0, 1).toUpperCase();
    });

    // ---------------------------------------------------
    // CASCADING ADDRESS DROPDOWNS
    // Shared PSGC cascade: option VALUES are names (never codes),
    // so the same spelling is saved for buyers, sellers and hubs.
    // ---------------------------------------------------
    const provinceSelect = document.getElementById('province');
    const municipalitySelect = document.getElementById('municipality');
    const barangaySelect = document.getElementById('barangay');

    initPsgcCascade({
        province: provinceSelect,
        municipality: municipalitySelect,
        barangay: barangaySelect,
        initial: {
            province: provinceSelect?.dataset.oldValue,
            municipality: municipalitySelect?.dataset.oldValue,
            barangay: barangaySelect?.dataset.oldValue,
        },
    });
});
