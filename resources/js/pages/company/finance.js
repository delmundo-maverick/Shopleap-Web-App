import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-finance-chart]');
    const canvas = document.getElementById('company-finance-chart');
    if (!root || !canvas) return;
    try {
        const rows = JSON.parse(root.dataset.values);
        const styles = getComputedStyle(document.documentElement);
        const color = name => styles.getPropertyValue(`--color-${name}`).trim();
        const chart = new Chart(canvas, {
            type: 'bar',
            data: { labels: rows.map(row => row.name), datasets: [
                ['shipping', 'Shipping earned', 'primary'], ['owed', 'Rider pay owed', 'warning'], ['paid', 'Rider pay paid', 'sky-blue'], ['margin', 'Net margin', 'success'],
            ].map(([key, label, token]) => ({ label, data: rows.map(row => row[key] === null ? null : Number(row[key])), backgroundColor: color(token), borderRadius: 3 })) },
            options: { responsive: true, maintainAspectRatio: false, animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
                plugins: { legend: { position: 'bottom', labels: { color: color('charcoal'), boxWidth: 12 } }, tooltip: { backgroundColor: color('charcoal'), titleColor: color('surface'), bodyColor: color('surface') } },
                scales: { x: { ticks: { color: color('charcoal') }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: color('charcoal') }, grid: { color: color('line') } } } },
        });
        window.addEventListener('pagehide', event => { if (!event.persisted) chart.destroy(); });
    } catch { root.querySelector('[data-chart-error]').hidden = false; }
});
