import Chart from 'chart.js/auto';

/*
|--------------------------------------------------------------------------
| Admin dashboard charts
|--------------------------------------------------------------------------
| Data comes from the Blade view through a JSON data island:
|   <script type="application/json" id="admin-dashboard-data">…</script>
*/

const data = JSON.parse(document.getElementById('admin-dashboard-data').textContent);

// Hex values mirror the tokens in app.css. Keep them in sync.
const COLORS = {
    primary: '#1A5FB4', // --color-primary
    sky: '#4A90D9', // --color-sky-blue
    charcoal: '#2E3A46', // --color-charcoal
    success: '#4CAF7D', // --color-success
    warning: '#F97316', // --color-warning
    danger: '#E4572E', // --color-danger
    line: '#E3E8EE', // chart gridlines (lighter than --color-line)
    muted: '#5B6672', // axis text
};

const BAR_RADIUS = 3;
const isMobile = window.matchMedia('(max-width: 639px)').matches;

// Global chart look: flat, quiet, same font as the rest of the UI.
Chart.defaults.font.family = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = COLORS.muted;
Chart.defaults.borderColor = COLORS.line;
Chart.defaults.plugins.legend.labels.boxWidth = 10;
Chart.defaults.plugins.legend.labels.boxHeight = 10;
Chart.defaults.plugins.legend.labels.padding = 14;
Chart.defaults.plugins.tooltip.backgroundColor = COLORS.charcoal;
Chart.defaults.plugins.tooltip.cornerRadius = 6;
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.boxPadding = 4;

// Charts sit inside fixed-height wrapper divs, so every chart uses
// maintainAspectRatio: false and fills its container.
const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

// Turns READY_FOR_PICKUP / ready_for_pickup into "Ready For Pickup".
const prettyStatus = (status) =>
    String(status)
        .replace(/_/g, ' ')
        .toLowerCase()
        .replace(/\b\w/g, (c) => c.toUpperCase());

const canvas = (id) => document.getElementById(id);

function registrationsChart() {
    const { labels, buyers, sellers, logistics } = data.registrationsByMonth;
    const series = (label, values, color) => ({
        label,
        data: values,
        backgroundColor: color,
        borderRadius: BAR_RADIUS,
        maxBarThickness: 28,
    });

    new Chart(canvas('registrationsChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                series('Buyers', buyers, COLORS.primary),
                series('Sellers', sellers, COLORS.sky),
                series('Logistics', logistics, COLORS.charcoal),
            ],
        },
        options: {
            ...baseOptions,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, border: { display: false } },
                x: { grid: { display: false } },
            },
        },
    });
}

// Seller / logistics / buyer application charts share one look.
function statusDoughnut(canvasId, counts) {
    new Chart(canvas(canvasId), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [
                {
                    data: [counts.pending, counts.approved, counts.rejected],
                    backgroundColor: [COLORS.warning, COLORS.success, COLORS.danger],
                    borderColor: '#FFFFFF',
                    borderWidth: 2,
                },
            ],
        },
        options: {
            ...baseOptions,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 10, font: { size: 11 } },
                },
            },
        },
    });
}

function commissionChart() {
    const { labels, totals } = data.commissionByMonth;

    new Chart(canvas('commissionChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Commission (₱)',
                    data: totals,
                    borderColor: COLORS.primary,
                    backgroundColor: 'rgba(26, 95, 180, 0.06)', // flat tint, not a gradient
                    borderWidth: 2,
                    tension: 0.25,
                    fill: true,
                    pointRadius: isMobile ? 2 : 3,
                    pointBackgroundColor: COLORS.primary,
                },
            ],
        },
        options: {
            ...baseOptions,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, border: { display: false } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true } },
            },
        },
    });
}

function orderStatusChart() {
    new Chart(canvas('orderStatusChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(data.orderStatusCounts).map(prettyStatus),
            datasets: [
                {
                    label: 'Orders',
                    data: Object.values(data.orderStatusCounts),
                    backgroundColor: COLORS.primary,
                    borderRadius: BAR_RADIUS,
                    maxBarThickness: 22,
                },
            ],
        },
        options: {
            ...baseOptions,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, border: { display: false } },
                y: { grid: { display: false }, ticks: { font: { size: isMobile ? 10 : 12 } } },
            },
        },
    });
}

registrationsChart();
statusDoughnut('sellerStatusChart', data.sellerStatusCounts);
statusDoughnut('logisticsStatusChart', data.logisticsStatusCounts);
statusDoughnut('buyerStatusChart', data.buyerStatusCounts);
commissionChart();
orderStatusChart();
