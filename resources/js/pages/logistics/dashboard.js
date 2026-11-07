import Chart from 'chart.js/auto';

export function dashboardChartConfigs(data, colors, reducedMotion = false) {
    const shared = {
        responsive: true, maintainAspectRatio: false, animation: reducedMotion ? false : undefined,
        color: colors.charcoal,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: colors.charcoal } },
    };
    return [
        {
            id: 'logistics-daily-chart',
            config: {
                type: 'bar',
                data: { labels: data.daily.labels, datasets: [{ label: 'Parcels handled', data: data.daily.values, backgroundColor: colors.primary, borderRadius: 4, maxBarThickness: 40 }] },
                options: { ...shared, scales: { x: { grid: { display: false }, ticks: { color: colors.charcoal, maxRotation: 0, autoSkip: true } }, y: { beginAtZero: true, ticks: { precision: 0, color: colors.charcoal }, grid: { color: colors.line } } } },
            },
        },
        {
            id: 'logistics-status-chart',
            config: {
                type: 'doughnut',
                data: { labels: data.statuses.labels, datasets: [{ data: data.statuses.values, backgroundColor: [colors.primary, colors.sky, colors.success, colors.warning, colors.danger, colors.charcoal], borderColor: colors.surface, borderWidth: 2 }] },
                options: { ...shared, cutout: '68%', plugins: { ...shared.plugins, legend: { display: true, position: 'bottom', labels: { color: colors.charcoal, boxWidth: 10, boxHeight: 10, padding: 14, font: { size: 11 } } } } },
            },
        },
    ];
}

export function initDashboard(root = document) {
    const source = root.querySelector('[data-chart-data]');
    if (!source) return [];
    const charts = [];
    try {
        const styles = getComputedStyle(document.documentElement);
        const token = name => styles.getPropertyValue('--color-' + name).trim();
        const colors = { primary: token('primary'), sky: token('sky-blue'), success: token('success'), warning: token('warning'), danger: token('danger'), charcoal: token('charcoal'), line: token('line'), surface: token('surface') };
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const data = JSON.parse(source.dataset.chartData);
        for (const chart of dashboardChartConfigs(data, colors, reducedMotion)) {
            const canvas = root.getElementById(chart.id);
            if (canvas) charts.push(new Chart(canvas, chart.config));
        }
    } catch {
        const error = root.querySelector('[data-dashboard-chart-error]');
        if (error) error.hidden = false;
    }
    root.querySelector('[data-dashboard-range]')?.addEventListener('submit', event => {
        event.target.setAttribute('aria-busy', 'true');
        const loading = root.querySelector('[data-dashboard-loading]');
        if (loading) loading.hidden = false;
    });
    window.addEventListener('pageshow', () => {
        root.querySelector('[data-dashboard-range]')?.removeAttribute('aria-busy');
        const loading = root.querySelector('[data-dashboard-loading]');
        if (loading) loading.hidden = true;
    });
    window.addEventListener('pagehide', event => { if (!event.persisted) charts.forEach(chart => chart.destroy()); });
    return charts;
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initDashboard());
    else initDashboard();
}
