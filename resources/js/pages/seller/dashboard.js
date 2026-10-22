import Chart from 'chart.js/auto';

const SELLER_PRIMARY = '#1A5FB4';

const ORDER_STATUS_LABELS = ['Pending', 'To Ship', 'Shipped', 'Delivered', 'Cancelled'];
const ORDER_STATUS_COLORS = ['#F59E0B', '#1A5FB4', '#4A90D9', '#93C5FD', '#DC2626'];

/** Reads the JSON the Blade view prints in <script id="seller-dashboard-data">. */
function readPageData() {
    const el = document.getElementById('seller-dashboard-data');

    if (!el) {
        console.error('Seller dashboard: #seller-dashboard-data not found.');
        return null;
    }

    try {
        return JSON.parse(el.textContent);
    } catch (error) {
        console.error('Seller dashboard: invalid page data.', error);
        return null;
    }
}

/** 12500 -> ₱12.5k (keeps the Y axis narrow on phones). */
function compactPeso(value) {
    const n = Number(value);
    if (n >= 1_000_000) return '₱' + (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1_000) return '₱' + (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'k';
    return '₱' + n;
}

function initSalesTrendChart(salesTrend, isMobile) {
    const canvas = document.getElementById('salesTrendChart');
    if (!canvas) return;

    const tickFont = { size: isMobile ? 10 : 12 };

    try {
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: salesTrend.labels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: salesTrend.totals,
                    borderColor: SELLER_PRIMARY,
                    backgroundColor: 'rgba(26, 95, 180, 0.12)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: isMobile ? 2 : 3,
                    pointHitRadius: 16, // easier to tap
                    pointBackgroundColor: SELLER_PRIMARY,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) =>
                                'Revenue: ₱' +
                                Number(context.raw).toLocaleString('en-PH', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                }),
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(20, 40, 70, 0.08)' },
                        ticks: {
                            font: tickFont,
                            maxTicksLimit: isMobile ? 5 : 8,
                            callback: (value) => compactPeso(value),
                        },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: tickFont, autoSkip: true, maxRotation: 0 },
                    },
                },
            },
        });
    } catch (error) {
        console.error('Sales trend chart failed:', error);
    }
}

function initOrderStatusChart(orderStatusCounts, isMobile) {
    const canvas = document.getElementById('orderStatusChart');
    const emptyState = document.getElementById('orderStatusEmpty');

    const showEmptyState = (show) => {
        if (emptyState) emptyState.style.display = show ? 'flex' : 'none';
    };

    const data = [
        Number(orderStatusCounts.pending ?? 0),
        Number(orderStatusCounts.to_ship ?? 0),
        Number(orderStatusCounts.shipped ?? 0),
        Number(orderStatusCounts.delivered ?? 0),
        Number(orderStatusCounts.cancelled ?? 0),
    ];

    const hasOrders = data.some((count) => Number.isFinite(count) && count > 0);

    if (!canvas || !hasOrders) {
        showEmptyState(true);
        return;
    }

    showEmptyState(false);

    try {
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ORDER_STATUS_LABELS,
                datasets: [{
                    label: 'Orders',
                    data,
                    backgroundColor: ORDER_STATUS_COLORS,
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    hoverOffset: isMobile ? 2 : 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: isMobile ? 8 : 12,
                            font: { size: isMobile ? 10 : 11 },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${context.raw} orders`,
                        },
                    },
                },
            },
        });
    } catch (error) {
        console.error('Order status chart failed:', error);
        showEmptyState(true);
    }
}

function init() {
    const pageData = readPageData();
    if (!pageData) return;

    const isMobile = window.matchMedia('(max-width: 639px)').matches;

    initSalesTrendChart(pageData.salesTrend, isMobile);
    initOrderStatusChart(pageData.orderStatusCounts, isMobile);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
