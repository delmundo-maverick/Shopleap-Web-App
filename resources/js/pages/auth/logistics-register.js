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
                ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.038 10.038 0 013.682-.863c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/>`
                : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
        });
    }

    wirePasswordToggle('togglePasswordBtn', 'password', 'password-icon');
    wirePasswordToggle('toggleConfirmPasswordBtn', 'password_confirmation', 'confirm-password-icon');

    // ---------------------------------------------------
    // FILE SELECTION BADGES
    // ---------------------------------------------------
    function wireFileInput(inputId, badgeId, nameId) {
        const input = document.getElementById(inputId);
        const badge = document.getElementById(badgeId);
        const nameSpan = document.getElementById(nameId);
        if (!input || !badge || !nameSpan) return;

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            nameSpan.textContent = file?.name ?? '';
            badge.classList.toggle('hidden', !file);
            badge.classList.toggle('flex', !!file);
        });
    }

    wireFileInput('id_upload', 'id-file-badge', 'id-file-name');
    wireFileInput('business_permit', 'permit-file-badge', 'permit-file-name');

    // ---------------------------------------------------
    // AGE AUTO-CALCULATION
    // ---------------------------------------------------
    const birthdayInput = document.getElementById('birthday');
    const ageInput = document.getElementById('age');

    function calculateAge() {
        if (!birthdayInput.value) {
            ageInput.value = '';
            return;
        }
        const birth = new Date(birthdayInput.value);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
        ageInput.value = age >= 0 ? age : '';
    }

    birthdayInput?.addEventListener('change', calculateAge);
    if (birthdayInput?.value) calculateAge();

    // ---------------------------------------------------
    // CASCADING ADDRESS DROPDOWNS (PSGC API)
    // ---------------------------------------------------
    const PSGC_BASE = 'https://psgc.gitlab.io/api';
    const provinceSelect = document.getElementById('province');
    const municipalitySelect = document.getElementById('municipality');
    const barangaySelect = document.getElementById('barangay');

    async function loadProvinces() {
        try {
            const res = await fetch(`${PSGC_BASE}/provinces/`);
            const data = await res.json();
            provinceSelect.innerHTML = '<option value="" disabled selected>Select province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.name;
                opt.dataset.code = p.code;
                opt.textContent = p.name;
                provinceSelect.appendChild(opt);
            });
        } catch (err) {
            provinceSelect.innerHTML = '<option value="">Could not load — check connection</option>';
        }
    }

    async function loadMunicipalities(provinceCode) {
        municipalitySelect.disabled = true;
        municipalitySelect.innerHTML = '<option value="">Loading...</option>';
        barangaySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Select municipality first</option>';

        try {
            const res = await fetch(`${PSGC_BASE}/provinces/${provinceCode}/cities-municipalities/`);
            const data = await res.json();
            municipalitySelect.innerHTML = '<option value="" disabled selected>Select municipality/city</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.name;
                opt.dataset.code = m.code;
                opt.textContent = m.name;
                municipalitySelect.appendChild(opt);
            });
            municipalitySelect.disabled = false;
        } catch (err) {
            municipalitySelect.innerHTML = '<option value="">Error loading municipalities</option>';
        }
    }

    async function loadBarangays(municipalityCode) {
        barangaySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Loading...</option>';

        try {
            const res = await fetch(`${PSGC_BASE}/cities-municipalities/${municipalityCode}/barangays/`);
            const data = await res.json();
            barangaySelect.innerHTML = '<option value="" disabled selected>Select barangay</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.name;
                opt.textContent = b.name;
                barangaySelect.appendChild(opt);
            });
            barangaySelect.disabled = false;
        } catch (err) {
            barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
        }
    }

    provinceSelect?.addEventListener('change', function () {
        const selectedOpt = this.options[this.selectedIndex];
        if (selectedOpt?.dataset.code) loadMunicipalities(selectedOpt.dataset.code);
    });

    municipalitySelect?.addEventListener('change', function () {
        const selectedOpt = this.options[this.selectedIndex];
        if (selectedOpt?.dataset.code) loadBarangays(selectedOpt.dataset.code);
    });

    loadProvinces();
});
