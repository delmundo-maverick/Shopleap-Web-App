document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('reportData');
    if (!dataEl || typeof window.Chart === 'undefined') return;

    const Chart = window.Chart;
    const payload = JSON.parse(dataEl.textContent || '{}');
    const chartConfig = payload.chart;
    if (!chartConfig) return;

    const PRIMARY = '#1A5FB4';
    const SKY = '#4A90D9';
    const GREEN = '#4CAF7D';
    const PALETTE = ['#1A5FB4', '#4A90D9', '#4CAF7D', '#F59E0B', '#E4572E', '#8B5CF6', '#64748B', '#0EA5E9'];

    const canvas = document.getElementById('reportChart');
    if (!canvas) return;

    const type = chartConfig.type || 'bar';
    const labels = chartConfig.labels || [];
    const datasets = (chartConfig.datasets || []).map((ds, i) => ({
        label: ds.label,
        data: ds.data,
        type: ds.type || (type === 'doughnut' ? undefined : (i === 0 ? 'bar' : 'line')),
        backgroundColor: type === 'doughnut' ? PALETTE : (ds.type === 'line' ? 'transparent' : PALETTE[i % PALETTE.length]),
        borderColor: PALETTE[i % PALETTE.length],
        borderRadius: 4,
        tension: 0.3,
        pointRadius: 3,
    }));

    new Chart(canvas, {
        type: type === 'doughnut' ? 'doughnut' : 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            scales: type === 'doughnut' ? {} : {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: '#E5E7EB' } },
            },
        },
    });
});
