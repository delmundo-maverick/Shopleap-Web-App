import { initPsgcCascade } from '../../shared/psgcCascade.js';

const PSGC_BASE = 'https://psgc.gitlab.io/api';

async function getJson(url) {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`PSGC request failed: ${response.status}`);
    return response.json();
}

async function loadBarangayNames(provinceName, municipalityName) {
    const provinces = await getJson(`${PSGC_BASE}/provinces/`);
    const province = provinces.find((p) => p.name === provinceName);
    if (!province) throw new Error('Province not found in the PSGC list.');

    const municipalities = await getJson(`${PSGC_BASE}/provinces/${province.code}/cities-municipalities/`);
    const municipality = municipalities.find((m) => m.name === municipalityName);
    if (!municipality) throw new Error('Municipality not found in the PSGC list.');

    const barangays = await getJson(`${PSGC_BASE}/cities-municipalities/${municipality.code}/barangays/`);
    return barangays.map((b) => b.name).sort((a, b) => a.localeCompare(b));
}

function initBarangayPanel(panel) {
    const list = panel.querySelector('[data-list]');
    const status = panel.querySelector('[data-status]');
    const mapped = JSON.parse(panel.dataset.mapped || '{}');
    let loaded = false;

    const render = (names) => {
        list.innerHTML = '';
        names.forEach((name) => {
            const label = document.createElement('label');
            label.className = 'flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1 text-xs hover:bg-white/50';

            const box = document.createElement('input');
            box.type = 'checkbox';
            box.name = 'barangays[]';
            box.value = name;
            box.className = 'accent-primary';

            const text = document.createElement('span');
            text.className = 'flex-1';
            text.textContent = name;

            const rider = document.createElement('span');
            rider.className = mapped[name] ? 'text-leaf-green' : 'text-charcoal/40';
            rider.textContent = mapped[name] || 'unassigned';

            label.append(box, text, rider);
            list.append(label);
        });
    };

    const setAll = (checked) => list.querySelectorAll('input[type="checkbox"]').forEach((box) => { box.checked = checked; });

    panel.querySelector('[data-select-all]')?.addEventListener('click', () => setAll(true));
    panel.querySelector('[data-clear]')?.addEventListener('click', () => setAll(false));
    panel.querySelector('[data-select-unassigned]')?.addEventListener('click', () => {
        list.querySelectorAll('input[type="checkbox"]').forEach((box) => { box.checked = !mapped[box.value]; });
    });

    panel.addEventListener('toggle', async () => {
        if (!panel.open || loaded) return;

        status.textContent = 'Loading barangays…';

        try {
            render(await loadBarangayNames(panel.dataset.province, panel.dataset.municipality));
            loaded = true;
            status.textContent = '';
        } catch (error) {
            console.error(error);
            status.textContent = "Couldn't load the barangay list. Close and reopen this section to try again.";
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const province = document.getElementById('area_province');
    const municipality = document.getElementById('area_municipality');

    if (province && municipality) {
        initPsgcCascade({ province, municipality, helpEl: document.getElementById('areaHelp') });
    }

    document.querySelectorAll('[data-barangay-panel]').forEach(initBarangayPanel);
});
