import Chart from 'chart.js/auto';

/**
 * Elegant, restrained Chart.js defaults for ETPB dashboards.
 * Charts only mount where a <canvas data-etpb-chart="..."> exists.
 */
const palette = {
    green:    '#166534',
    greenMid: '#15803d',
    greenSoft:'#86efac',
    gold:     '#b45309',
    goldSoft: '#fbbf24',
    danger:   '#b91c1c',
    warn:     '#c2410c',
    muted:    '#64748b',
    slate:    ['#14532d', '#166534', '#15803d', '#16a34a', '#22c55e', '#4ade80',
               '#b45309', '#c2410c', '#0f766e', '#1d4ed8', '#7c3aed', '#be185d'],
};

Chart.defaults.font.family = '"Instrument Sans", "Segoe UI", system-ui, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = '#475569';
Chart.defaults.plugins.legend.labels.boxWidth = 12;
Chart.defaults.plugins.legend.labels.boxHeight = 12;
Chart.defaults.plugins.legend.labels.padding = 14;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.92)';
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.elements.bar.borderRadius = 6;
Chart.defaults.elements.bar.borderSkipped = false;

function readPayload(el) {
    const id = el.getAttribute('data-etpb-source');
    if (! id) return null;
    const node = document.getElementById(id);
    if (! node) return null;
    try {
        return JSON.parse(node.textContent);
    } catch (e) {
        return null;
    }
}

function money(n) {
    const v = Number(n) || 0;
    if (v >= 10000000) return 'Rs. ' + (v / 10000000).toFixed(2) + ' cr';
    if (v >= 100000) return 'Rs. ' + (v / 100000).toFixed(1) + ' lac';
    return 'Rs. ' + Math.round(v).toLocaleString('en-PK');
}

function mountLineDual(canvas, data) {
    return new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: data.intakeLabel || 'Submitted',
                    data: data.intake,
                    borderColor: palette.green,
                    backgroundColor: 'rgba(22, 101, 52, 0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2.5,
                },
                {
                    label: data.disposalLabel || 'Regularized',
                    data: data.disposal,
                    borderColor: palette.gold,
                    backgroundColor: 'rgba(180, 83, 9, 0.10)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2.5,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0 } },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(148, 163, 184, 0.25)' },
                    border: { display: false },
                },
            },
        },
    });
}

function mountDoughnut(canvas, data) {
    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: (data.colors && data.colors.length)
                    ? data.colors
                    : palette.slate.slice(0, data.values.length),
                borderWidth: 0,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: window.matchMedia('(max-width: 640px)').matches ? 'bottom' : 'right',
                    labels: { usePointStyle: true, pointStyle: 'circle' },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + Number(b), 0) || 1;
                            const pct = Math.round((Number(ctx.raw) / total) * 100);
                            return ` ${ctx.label}: ${ctx.raw} (${pct}%)`;
                        },
                    },
                },
            },
        },
    });
}

function mountBar(canvas, data) {
    const horizontal = !! data.horizontal;
    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.label || 'Value',
                data: data.values,
                backgroundColor: data.color || palette.greenMid,
                maxBarThickness: horizontal ? 22 : 36,
            }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => data.money
                            ? ` ${money(ctx.raw)}`
                            : ` ${Number(ctx.raw).toLocaleString('en-PK')}`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: horizontal ? 'rgba(148, 163, 184, 0.25)' : false, display: horizontal },
                    border: { display: false },
                    ticks: data.money ? { callback: (v) => money(v) } : { precision: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: horizontal ? false : 'rgba(148, 163, 184, 0.25)', display: ! horizontal },
                    border: { display: false },
                    ticks: { precision: 0 },
                },
            },
        },
    });
}

function mount(canvas) {
    const kind = canvas.getAttribute('data-etpb-chart');
    const data = readPayload(canvas);
    if (! data) return;

    if (kind === 'line-dual') return mountLineDual(canvas, data);
    if (kind === 'doughnut') return mountDoughnut(canvas, data);
    if (kind === 'bar') return mountBar(canvas, data);
}

document.querySelectorAll('canvas[data-etpb-chart]').forEach(mount);
