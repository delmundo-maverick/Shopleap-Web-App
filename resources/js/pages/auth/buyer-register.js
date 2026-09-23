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
    // CASCADING ADDRESS DROPDOWNS (PSGC API)
    // ---------------------------------------------------
    const PSGC_BASE = 'https://psgc.gitlab.io/api';

    const provinceSelect = document.getElementById('province');
    const municipalitySelect = document.getElementById('municipality');
    const barangaySelect = document.getElementById('barangay');

    async function loadProvinces() {
        const oldValue = provinceSelect.dataset.oldValue;
        try {
            const res = await fetch(`${PSGC_BASE}/provinces/`);
            const data = await res.json();

            provinceSelect.innerHTML = '<option value="" disabled selected>Select province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.code;
                opt.textContent = p.name;
                if (oldValue && (oldValue === p.code || oldValue === p.name)) opt.selected = true;
                provinceSelect.appendChild(opt);
            });

            if (provinceSelect.value) {
                loadMunicipalities(provinceSelect.value);
            }
        } catch (err) {
            provinceSelect.innerHTML = '<option value="">Failed to load provinces</option>';
        }
    }

    async function loadMunicipalities(provinceCode) {
        const oldValue = municipalitySelect.dataset.oldValue;
        municipalitySelect.disabled = true;
        municipalitySelect.innerHTML = '<option value="">Loading municipalities...</option>';
        barangaySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Select municipality first</option>';

        try {
            const res = await fetch(`${PSGC_BASE}/provinces/${provinceCode}/cities-municipalities/`);
            const data = await res.json();

            municipalitySelect.innerHTML = '<option value="" disabled selected>Select municipality</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.code;
                opt.textContent = m.name;
                if (oldValue && (oldValue === m.code || oldValue === m.name)) opt.selected = true;
                municipalitySelect.appendChild(opt);
            });
            municipalitySelect.disabled = false;

            if (municipalitySelect.value) {
                loadBarangays(municipalitySelect.value);
            }
        } catch (err) {
            municipalitySelect.innerHTML = '<option value="">Failed to load municipalities</option>';
        }
    }

    async function loadBarangays(municipalityCode) {
        const oldValue = barangaySelect.dataset.oldValue;
        barangaySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Loading barangays...</option>';

        try {
            const res = await fetch(`${PSGC_BASE}/cities-municipalities/${municipalityCode}/barangays/`);
            const data = await res.json();

            barangaySelect.innerHTML = '<option value="" disabled selected>Select barangay</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.code;
                opt.textContent = b.name;
                if (oldValue && (oldValue === b.code || oldValue === b.name)) opt.selected = true;
                barangaySelect.appendChild(opt);
            });
            barangaySelect.disabled = false;
        } catch (err) {
            barangaySelect.innerHTML = '<option value="">Failed to load barangays</option>';
        }
    }

    provinceSelect?.addEventListener('change', (e) => {
        if (e.target.value) loadMunicipalities(e.target.value);
    });

    municipalitySelect?.addEventListener('change', (e) => {
        if (e.target.value) loadBarangays(e.target.value);
    });

    loadProvinces();
});
