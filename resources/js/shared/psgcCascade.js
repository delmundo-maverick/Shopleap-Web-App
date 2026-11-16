const PSGC_BASE = 'https://psgc.gitlab.io/api';

export function initPsgcCascade({ province, municipality, barangay, helpEl = null, initial = {}, locationsUrl = null }) {
    const locationUrl = (level, code = null) => {
        const url = new URL(locationsUrl, window.location.origin);
        url.searchParams.set('level', level);
        if (code) url.searchParams.set('code', code);
        return url.toString();
    };
    const levels = [
        {
            select: province, placeholder: 'Select province', resetText: 'Select province',
            url: () => locationsUrl ? locationUrl('province') : `${PSGC_BASE}/provinces/`, initial: initial.province, token: 0,
        },
        {
            select: municipality, placeholder: 'Select municipality / city', resetText: 'Select a province first',
            url: (code) => locationsUrl ? locationUrl('municipality', code) : `${PSGC_BASE}/provinces/${code}/cities-municipalities/`, initial: initial.municipality, token: 0,
        },
        {
            select: barangay, placeholder: 'Select barangay', resetText: 'Select a municipality first',
            url: (code) => locationsUrl ? locationUrl('barangay', code) : `${PSGC_BASE}/cities-municipalities/${code}/barangays/`, initial: initial.barangay, token: 0,
        },
    ].filter((level) => level.select);

    const setHelp = (text) => { if (helpEl) helpEl.textContent = text; };

    function fill(level, items) {
        level.select.innerHTML = '';
        const placeholder = new Option(level.placeholder, '', true, true);
        placeholder.disabled = true;
        level.select.append(placeholder);

        items.slice().sort((a, b) => a.name.localeCompare(b.name)).forEach((item) => {
            const option = new Option(item.name, item.name);
            option.dataset.code = item.code;
            level.select.append(option);
        });
    }

    function reset(level) {
        level.select.innerHTML = '';
        const option = new Option(level.resetText, '', true, true);
        option.disabled = true;
        level.select.append(option);
        level.select.disabled = true;
    }

    function resetFrom(index) {
        for (let i = index; i < levels.length; i++) {
            levels[i].token++; // invalidates any in-flight request for that level
            reset(levels[i]);
        }
    }

    async function load(index, parentCode) {
        const level = levels[index];
        const token = ++level.token;

        try {
            const response = await fetch(level.url(parentCode));
            if (!response.ok) throw new Error(`PSGC request failed: ${response.status}`);

            const items = await response.json();
            if (token !== level.token) return; // a newer selection superseded this request

            fill(level, items);
            level.select.disabled = false;
            setHelp('');

            const wanted = level.initial;
            level.initial = null;
            const match = wanted ? [...level.select.options].find((o) => o.value === wanted) : null;

            if (match) {
                level.select.value = wanted;
                if (index + 1 < levels.length) await load(index + 1, match.dataset.code);
            } else {
                resetFrom(index + 1);
            }
        } catch (error) {
            console.error(error);
            if (token === level.token) {
                setHelp("Couldn't load the location list. Check your connection and re-select the field above to try again.");
            }
        }
    }

    levels.forEach((level, index) => {
        if (index === levels.length - 1) return;

        level.select.addEventListener('change', () => {
            const code = level.select.selectedOptions[0]?.dataset.code;
            resetFrom(index + 1);
            if (code) load(index + 1, code);
        });
    });

    load(0);

    return {
        reload() {
            levels.forEach(level => { level.initial = level.select.value || level.initial; });
            resetFrom(0);
            return load(0);
        },
    };
}
