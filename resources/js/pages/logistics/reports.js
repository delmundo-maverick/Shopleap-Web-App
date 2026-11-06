import Chart from 'chart.js/auto';

export function chartOptions(config) {
    const colors = ['#1A5FB4', '#4A90D9', '#4CAF7D', '#F59E0B', '#E4572E', '#8B5CF6', '#64748B'];
    const type = config.type || 'bar';
    return {
        type,
        data: { labels: config.labels || [], datasets: (config.datasets || []).map((dataset, index) => ({
            ...dataset, type: dataset.type || type,
            backgroundColor: type === 'doughnut' ? colors : colors[index % colors.length],
            borderColor: colors[index % colors.length], borderWidth: type === 'line' ? 2 : 1,
            borderRadius: type === 'bar' ? 3 : 0, tension: 0.2, pointRadius: 2,
        })) },
        options: { responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            ...(type === 'doughnut' ? {} : { scales: { x: { grid: { display: false }, ticks: { maxRotation: 45, autoSkip: true } }, y: { beginAtZero: true } } }),
        },
    };
}

export function initReportFilters(form) {
    const preset = form.elements.preset;
    const dates = [form.elements.from, form.elements.to];
    const sync = () => dates.forEach(input => { input.disabled = false; input.required = preset.value === 'custom'; });
    preset.addEventListener('change', sync);
    sync();
    dates.forEach(input => input.addEventListener('change', () => { preset.value = 'custom'; sync(); }));
    // Keep date inputs editable so choosing one can switch to a custom range.
    dates.forEach(input => input.addEventListener('focus', () => { preset.value = 'custom'; }));
    form.addEventListener('submit', () => {
        dates.forEach(input => { input.disabled = preset.value !== 'custom'; });
        const button = form.querySelector('[data-report-submit]');
        button.disabled = true; button.textContent = 'Generating...';
    });
}

function initReports() {
    const form = document.querySelector('[data-report-filters]');
    if (form) { initReportFilters(form); }
    const data = document.getElementById('reportData');
    const canvas = document.getElementById('reportChart');
    if (!data || !canvas) { return; }
    try {
        const payload = JSON.parse(data.textContent || '{}');
        if (payload.chart) { new Chart(canvas, chartOptions(payload.chart)); }
    } catch {
        const error = document.querySelector('[data-chart-error]');
        if (error) { error.hidden = false; }
    }
}

if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initReports); }
else { initReports(); }
window.addEventListener('pageshow', event => { if (event.persisted) { window.location.reload(); } });
