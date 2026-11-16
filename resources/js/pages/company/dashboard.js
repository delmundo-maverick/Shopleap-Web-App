import Chart from 'chart.js/auto';

function initDashboard() {
    const root = document.querySelector('[data-company-charts]');
    if (!root) return;
    const charts = [];
    try {
        const data = JSON.parse(root.dataset.chartData);
        const styles = getComputedStyle(document.documentElement);
        const color = name => styles.getPropertyValue(`--color-${name}`).trim();
        const charcoal = color('charcoal');
        const line = color('line');
        const options = {
            responsive: true, maintainAspectRatio: false,
            animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
            color: charcoal,
            plugins: { legend: { display: false }, tooltip: { backgroundColor: charcoal, titleColor: color('surface'), bodyColor: color('surface') } },
        };
        const numericAxis = { beginAtZero: true, ticks: { precision: 0, color: charcoal }, grid: { color: line } };
        const labelAxis = { ticks: { color: charcoal, maxRotation: 0 }, grid: { display: false } };
        const configs = [
            ['company-daily-chart', {
                type: 'line',
                data: { labels: data.daily.labels, datasets: [{ label: 'Parcels handled', data: data.daily.values, borderColor: color('primary'), backgroundColor: color('primary'), pointRadius: 3, fill: false, tension: 0 }] },
                options: { ...options, scales: { x: labelAxis, y: numericAxis } },
            }],
            ['company-hubs-chart', {
                type: 'bar',
                data: { labels: data.hubs.labels, datasets: [{ label: 'Parcels handled', data: data.hubs.values, backgroundColor: color('primary'), borderRadius: 3, maxBarThickness: 24 }] },
                options: { ...options, indexAxis: 'y', scales: { x: numericAxis, y: { ...labelAxis, ticks: { color: charcoal, autoSkip: false, callback(value) { const text = this.getLabelForValue(value); return text.length > 22 ? `${text.slice(0, 22)}...` : text; } } } } },
            }],
            ['company-success-chart', {
                type: 'bar',
                data: { labels: data.success.labels, datasets: [{ label: 'Success rate (%)', data: data.success.values, backgroundColor: color('success'), borderRadius: 3, maxBarThickness: 24 }] },
                options: { ...options, indexAxis: 'y', scales: { x: { ...numericAxis, max: 100, ticks: { color: charcoal, callback: value => `${value}%` } }, y: { ...labelAxis, ticks: { color: charcoal, autoSkip: false, callback(value) { const text = this.getLabelForValue(value); return text.length > 22 ? `${text.slice(0, 22)}...` : text; } } } },
                    plugins: { ...options.plugins, tooltip: { ...options.plugins.tooltip, callbacks: { label: context => `${context.raw}% delivered` } } } },
            }],
        ];
        for (const [id, config] of configs) {
            const canvas = document.getElementById(id);
            if (canvas) charts.push(new Chart(canvas, config));
        }
    } catch {
        root.querySelector('[data-company-chart-error]').hidden = false;
    }
    document.querySelector('[data-company-range]')?.addEventListener('submit', event => event.currentTarget.setAttribute('aria-busy', 'true'));
    window.addEventListener('pageshow', () => document.querySelector('[data-company-range]')?.removeAttribute('aria-busy'));
    window.addEventListener('pagehide', event => { if (!event.persisted) charts.forEach(chart => chart.destroy()); });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initDashboard);
else initDashboard();
