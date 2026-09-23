document.addEventListener('DOMContentLoaded', () => {
    // ---------------------------------------------------
    // PASSWORD VISIBILITY
    // ---------------------------------------------------
    const EYE_OPEN_ICON = `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
              d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />
        <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
    `;
    const EYE_CLOSED_ICON = `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
              d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.7 10.7 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3 3.8M6.2 6.2C3.7 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.6-.3 3.7-.8" />
    `;

    function wirePasswordToggle(toggleId, inputId, iconId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!toggle || !input || !icon) return;

        toggle.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.innerHTML = isHidden ? EYE_CLOSED_ICON : EYE_OPEN_ICON;
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
        const hasHadBirthdayThisYear =
            today.getMonth() > birthday.getMonth() ||
            (today.getMonth() === birthday.getMonth() && today.getDate() >= birthday.getDate());
        if (!hasHadBirthdayThisYear) age--;
        ageInput.value = age >= 0 ? age : '';
    }

    birthdayInput?.addEventListener('change', calculateAge);

    // ---------------------------------------------------
    // DOCUMENT FILE SELECTION (ID + Business Permit)
    // ---------------------------------------------------
    function wireFileInput(inputId, wrapperId, nameId) {
        const input = document.getElementById(inputId);
        const wrapper = document.getElementById(wrapperId);
        const nameEl = document.getElementById(nameId);
        if (!input || !wrapper || !nameEl) return;

        input.addEventListener('change', function () {
            const file = this.files?.[0];
            nameEl.textContent = file?.name ?? '';
            wrapper.classList.toggle('hidden', !file);
        });
    }

    wireFileInput('id_upload', 'idFileSelected', 'idFileName');
    wireFileInput('business_permit', 'permitFileSelected', 'permitFileName');

    // ---------------------------------------------------
    // MIDDLE INITIAL — letters only
    // ---------------------------------------------------
    document.getElementById('middle_initial')?.addEventListener('input', function () {
        this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase();
    });

    // ---------------------------------------------------
    // CASCADING ADDRESS DROPDOWNS (PSGC API)
    // Old values (for a validation round-trip) are read from the
    // JSON data-bridge script tag the blade renders, since this
    // file itself can't access Blade's old() helper.
    // ---------------------------------------------------
    const PSGC_BASE = 'https://psgc.gitlab.io/api';

    const oldValuesEl = document.getElementById('sellerAddressOldValues');
    const OLD_VALUES = oldValuesEl ? JSON.parse(oldValuesEl.textContent) : {};

    const levels = [
        {
            select: document.getElementById('province'),
            help: document.getElementById('provinceHelp'),
            placeholder: 'Select province',
            loadingText: 'Fetching province list…',
            errorText: 'Could not load the province list. Please refresh the page.',
            resetText: 'Select province',
            url: () => `${PSGC_BASE}/provinces/`,
            old: OLD_VALUES.province,
        },
        {
            select: document.getElementById('municipality'),
            help: document.getElementById('municipalityHelp'),
            placeholder: 'Select municipality',
            loadingText: 'Fetching municipality list…',
            errorText: 'Could not load the municipality list. Please try again.',
            resetText: 'Select a province first',
            url: (code) => `${PSGC_BASE}/provinces/${code}/cities-municipalities/`,
            old: OLD_VALUES.municipality,
        },
        {
            select: document.getElementById('barangay'),
            help: document.getElementById('barangayHelp'),
            placeholder: 'Select barangay',
            loadingText: 'Fetching barangay list…',
            errorText: 'Could not load the barangay list. Please try again.',
            resetText: 'Select a municipality first',
            url: (code) => `${PSGC_BASE}/cities-municipalities/${code}/barangays/`,
            old: OLD_VALUES.barangay,
        },
    ];

    function fillOptions(select, items, placeholder) {
        select.innerHTML = '';
        const placeholderOption = new Option(placeholder, '', true, true);
        placeholderOption.disabled = true;
        select.append(placeholderOption);

        items
            .slice()
            .sort((a, b) => a.name.localeCompare(b.name))
            .forEach((item) => {
                const option = new Option(item.name, item.name);
                option.dataset.code = item.code;
                select.append(option);
            });
    }

    function resetLevel(level, text) {
        level.select.innerHTML = '';
        level.select.append(new Option(text, ''));
        level.select.disabled = true;
        if (level.help) level.help.textContent = text;
    }

    function resetFrom(index) {
        for (let i = index; i < levels.length; i++) {
            resetLevel(levels[i], levels[i].resetText);
        }
    }

    async function loadLevel(index, parentCode) {
        const level = levels[index];
        resetLevel(level, level.loadingText);
        resetFrom(index + 1);

        try {
            const response = await fetch(level.url(parentCode));
            if (!response.ok) throw new Error(`PSGC request failed: ${response.status}`);

            const items = await response.json();
            fillOptions(level.select, items, level.placeholder);
            level.select.disabled = false;

            if (level.help) {
                level.help.textContent = `Select your ${level.placeholder.replace('Select ', '')}`;
            }

            if (level.old) {
                const match = [...level.select.options].find((option) => option.value === level.old);
                if (match) {
                    level.select.value = level.old;
                    if (index + 1 < levels.length) {
                        await loadLevel(index + 1, match.dataset.code);
                    }
                }
            }
        } catch (error) {
            console.error(error);
            resetLevel(level, level.errorText);
        }
    }

    levels.forEach((level, index) => {
        const isLastLevel = index === levels.length - 1;
        if (isLastLevel) return;

        level.select.addEventListener('change', function () {
            const code = this.options[this.selectedIndex]?.dataset.code;
            if (code) loadLevel(index + 1, code);
        });
    });

    loadLevel(0);
});
