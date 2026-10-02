document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('reportData');
    if (!dataEl || typeof window.Chart === 'undefined') return;

    const Chart = window.Chart;
    const { series, statusBreakdown } = JSON.parse(dataEl.textContent);

    const PRIMARY = '#1A5FB4';
    const SKY = '#4A90D9';
    const GREEN = '#4CAF7D';
    const PALETTE = ['#1A5FB4', '#4A90D9', '#4CAF7D', '#F59E0B', '#E4572E', '#8B5CF6', '#64748B', '#0EA5E9'];

    const peso = (v) => '₱' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const labels = series.map((s) => s.label);
    const legend = { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } };
    const grid = { color: '#E5E7EB' };

    const earningsCanvas = document.getElementById('earningsChart');
    if (earningsCanvas) {
        new Chart(earningsCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Net earnings', data: series.map((s) => s.earned_net), backgroundColor: PRIMARY, borderRadius: 4, stack: 'e' },
                    { label: 'Platform commission', data: series.map((s) => s.earned_commission), backgroundColor: SKY, borderRadius: 4, stack: 'e' },
                ],
            },
            options: {
                responsive: true,
                plugins: { legend, tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${peso(c.parsed.y)}` } } },
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, grid } },
            },
        });
    }

    const salesCanvas = document.getElementById('salesChart');
    if (salesCanvas) {
        new Chart(salesCanvas, {
            data: {
                labels,
                datasets: [
                    { type: 'bar', label: 'Gross sales', data: series.map((s) => s.sales_gross), backgroundColor: PRIMARY, borderRadius: 4, yAxisID: 'y' },
                    { type: 'line', label: 'Orders', data: series.map((s) => s.sales_orders), borderColor: GREEN, backgroundColor: GREEN, tension: 0.3, pointRadius: 3, yAxisID: 'y1' },
                ],
            },
            options: {
                responsive: true,
                plugins: { legend },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid, position: 'left' },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } },
                },
            },
        });
    }

    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas && statusBreakdown.length > 0) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: statusBreakdown.map((s) => s.label),
                datasets: [{
                    data: statusBreakdown.map((s) => s.count),
                    backgroundColor: statusBreakdown.map((_, i) => PALETTE[i % PALETTE.length]),
                    borderWidth: 0,
                }],
            },
            options: { responsive: true, plugins: { legend } },
        });
    }
});
