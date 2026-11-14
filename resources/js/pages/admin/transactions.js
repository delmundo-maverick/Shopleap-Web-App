import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('transactionsData');
    if (!dataEl) return;

    const { series, statusBreakdown } = JSON.parse(dataEl.textContent);
    const peso = (v) => '₱' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const labels = series.map((s) => s.label);
    const legend = { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } };
    const grid = { color: '#E5E7EB' };
    const PALETTE = ['#1A5FB4', '#4A90D9', '#4CAF7D', '#F59E0B', '#E4572E', '#8B5CF6', '#64748B', '#0EA5E9'];

    const trendCanvas = document.getElementById('gmvTrendChart');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            data: {
                labels,
                datasets: [
                    { type: 'bar', label: 'GMV', data: series.map((s) => s.gmv), backgroundColor: '#1A5FB4', borderRadius: 4, yAxisID: 'y' },
                    { type: 'line', label: 'Commission', data: series.map((s) => s.commission), borderColor: '#4CAF7D', backgroundColor: '#4CAF7D', tension: 0.3, pointRadius: 3, yAxisID: 'y' },
                ],
            },
            options: {
                responsive: true,
                plugins: { legend, tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${peso(c.parsed.y)}` } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid } },
            },
        });
    }

    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas && statusBreakdown.length > 0) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: statusBreakdown.map((s) => s.label),
                datasets: [{ data: statusBreakdown.map((s) => s.count), backgroundColor: statusBreakdown.map((_, i) => PALETTE[i % PALETTE.length]), borderWidth: 0 }],
            },
            options: { responsive: true, plugins: { legend } },
        });
    }
});
