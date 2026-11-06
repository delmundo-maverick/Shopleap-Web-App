import test from 'node:test';
import assert from 'node:assert/strict';
globalThis.document = { readyState: 'loading', addEventListener() {} };
globalThis.window = { addEventListener() {} };
const { chartOptions, initReportFilters } = await import('../../resources/js/pages/logistics/reports.js');

test('line and bar charts preserve the actual report series type', () => {
    const line = chartOptions({ type: 'line', labels: ['Nov 4'], datasets: [{ label: 'Delivered', data: [2] }] });
    assert.equal(line.type, 'line');
    assert.equal(line.data.datasets[0].type, 'line');
    const bars = chartOptions({ type: 'bar', datasets: [{ data: [1] }, { data: [2] }] });
    assert.equal(bars.data.datasets[1].type, 'bar');
    assert.equal(line.options.maintainAspectRatio, false);
});

test('doughnut charts do not add cartesian axes', () => {
    const options = chartOptions({ type: 'doughnut', labels: ['Pending'], datasets: [{ data: [1] }] });
    assert.equal(options.options.scales, undefined);
    assert.ok(Array.isArray(options.data.datasets[0].backgroundColor));
});

test('date inputs stay editable and choosing a date selects a custom range', () => {
    const handlers = {};
    const field = (name, value) => ({ value, addEventListener: (event, callback) => { handlers[`${name}:${event}`] = callback; } });
    const elements = { preset: field('preset', '30d'), from: field('from', '2026-10-06'), to: field('to', '2026-11-04') };
    const button = {};
    initReportFilters({ elements, addEventListener: (event, callback) => { handlers[event] = callback; }, querySelector: () => button });
    assert.equal(elements.from.disabled, false);
    handlers['from:change']();
    assert.equal(elements.preset.value, 'custom');
    assert.equal(elements.to.required, true);
    handlers.submit();
    assert.equal(elements.to.disabled, false);
    assert.equal(button.disabled, true);
});

test('preset submission omits custom dates while preserving the selected period', () => {
    const handlers = {};
    const field = value => ({ value, addEventListener() {} });
    const elements = { preset: field('today'), from: field('2026-10-06'), to: field('2026-11-04') };
    initReportFilters({ elements, addEventListener: (event, callback) => { handlers[event] = callback; }, querySelector: () => ({}) });
    handlers.submit();
    assert.equal(elements.from.disabled, true);
    assert.equal(elements.to.disabled, true);
    assert.equal(elements.preset.value, 'today');
});
