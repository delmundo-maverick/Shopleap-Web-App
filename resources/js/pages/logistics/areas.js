import { initPsgcCascade } from '../../shared/psgcCascade.js';

document.addEventListener('DOMContentLoaded', () => {
    const province = document.getElementById('area_province');
    const municipality = document.getElementById('area_municipality');
    if (!province || !municipality) return;

    initPsgcCascade({ province, municipality, helpEl: document.getElementById('areaHelp') });
});
