import test from 'node:test';
import assert from 'node:assert/strict';
import { dashboardChartConfigs, initDashboard } from '../../resources/js/pages/logistics/dashboard.js';

const colors = { primary: 'primary-token', sky: 'sky-token', success: 'success-token', warning: 'warning-token', danger: 'danger-token', charcoal: 'charcoal-token', line: 'line-token', surface: 'surface-token' };
const data = { daily: { labels: ['Nov 5', 'Nov 6'], values: [0, 4] }, statuses: { labels: ['Sorted', 'Delivered'], values: [3, 1] } };

test('charts keep zero days and exact recorded values using only supplied theme colors', () => {
    const charts = dashboardChartConfigs(data, colors);
    assert.deepEqual(charts[0].config.data.labels, ['Nov 5', 'Nov 6']);
    assert.deepEqual(charts[0].config.data.datasets[0].data, [0, 4]);
    assert.equal(charts[0].config.data.datasets[0].backgroundColor, colors.primary);
    assert.deepEqual(charts[1].config.data.datasets[0].data, [3, 1]);
    assert.ok(charts[1].config.data.datasets[0].backgroundColor.every(color => Object.values(colors).includes(color)));
    assert.equal(charts[0].config.options.scales.y.ticks.precision, 0);
});

test('charts resize with their containers and respect reduced motion', () => {
    for (const chart of dashboardChartConfigs(data, colors, true)) {
        assert.equal(chart.config.options.responsive, true);
        assert.equal(chart.config.options.maintainAspectRatio, false);
        assert.equal(chart.config.options.animation, false);
    }
});

test('dashboard initialization leaves unrelated pages alone', () => {
    assert.deepEqual(initDashboard({ querySelector: () => null }), []);
});

test('invalid chart data exposes the accessible error state while preserving server-rendered data', () => {
    globalThis.document = { documentElement: {} };
    globalThis.getComputedStyle = () => ({ getPropertyValue: name => name });
    globalThis.window = { matchMedia: () => ({ matches: false }), addEventListener() {} };
    const error = { hidden: true };
    const root = {
        querySelector(selector) {
            if (selector === '[data-chart-data]') return { dataset: { chartData: 'invalid-json' } };
            if (selector === '[data-dashboard-chart-error]') return error;
            return null;
        },
    };
    assert.deepEqual(initDashboard(root), []);
    assert.equal(error.hidden, false);
    delete globalThis.document;
    delete globalThis.getComputedStyle;
    delete globalThis.window;
});
