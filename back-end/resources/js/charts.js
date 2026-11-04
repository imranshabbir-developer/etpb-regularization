import Chart from 'chart.js/auto';
import { isRtl, months, t } from './i18n.js';

/**
 * Modern Chart.js visuals for ETPB dashboards — soft gradients, rounded bars,
 * spaced doughnuts, and theme-aware ticks. Mount via:
 *   <canvas data-etpb-chart="line-dual|doughnut|bar|hbar" data-etpb-source="id">
 */
const sky = [
    '#0284C7', '#0EA5E9', '#38BDF8', '#7DD3FC',
    '#244F8C', '#2F66B0', '#3B7DD4', '#6BA0E3',
    '#C4680A', '#E8891A', '#F08A82', '#34D399',
];

const instances = new Map();

function isDark() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}

function tickColor() {
    return isDark() ? '#A7B4C8' : '#5B7399';
}

function gridColor() {
    return isDark() ? 'rgba(167, 180, 200, 0.14)' : 'rgba(148, 163, 184, 0.22)';
}

function surfaceBorder() {
    return isDark() ? '#141C2B' : '#FFFFFF';
}

const LATIN_FONT = '"Segoe UI", system-ui, -apple-system, "Noto Sans", sans-serif';
const URDU_FONT = '"ETPB Latin", "Noto Nastaliq Urdu", "Segoe UI", sans-serif';

function chartFont() {
    return isRtl() ? URDU_FONT : LATIN_FONT;
}

function applyChartDefaults() {
    const rtl = isRtl();
    Chart.defaults.font.family = chartFont();
    Chart.defaults.font.size = rtl ? 12.5 : 11.5;
    // Nastaliq rises and falls far beyond Latin text; give each line room.
    Chart.defaults.font.lineHeight = rtl ? 2 : 1.2;
    Chart.defaults.plugins.legend.rtl = rtl;
    Chart.defaults.plugins.legend.textDirection = rtl ? 'rtl' : 'ltr';
    Chart.defaults.plugins.tooltip.rtl = rtl;
    Chart.defaults.plugins.tooltip.textDirection = rtl ? 'rtl' : 'ltr';
    // Fills are canvas gradients, which Chart.js cannot tween between; a resize
    // mid-animation (e.g. when the Urdu web font lands) would otherwise throw.
    Chart.defaults.animations.colors = false;
    Chart.defaults.color = tickColor();
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.boxHeight = 10;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
    Chart.defaults.plugins.tooltip.backgroundColor = isDark()
        ? 'rgba(10, 16, 26, 0.94)'
        : 'rgba(15, 28, 48, 0.92)';
    Chart.defaults.plugins.tooltip.titleColor = '#fff';
    Chart.defaults.plugins.tooltip.bodyColor = '#E8EEF6';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxPadding = 4;
    Chart.defaults.elements.bar.borderRadius = 8;
    Chart.defaults.elements.bar.borderSkipped = false;
    Chart.defaults.elements.point.hoverBorderWidth = 2;
    Chart.defaults.animation = { duration: 650, easing: 'easeOutQuart' };
}

applyChartDefaults();

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
    const rs = t('Rs.') + ' ';
    if (v >= 10000000) return rs + (v / 10000000).toFixed(2) + ' ' + t('cr');
    if (v >= 100000) return rs + (v / 100000).toFixed(1) + ' ' + t('lac');
    return rs + Math.round(v).toLocaleString('en-PK');
}

function shortMonth(ym) {
    if (! ym || typeof ym !== 'string') return ym;
    const parts = ym.split('-');
    if (parts.length < 2) return ym;
    return months()[Number(parts[1]) - 1] || ym;
}

function verticalGradient(ctx, top, bottom) {
    const chart = ctx.chart;
    const { ctx: c, chartArea } = chart;
    if (! chartArea) return top;
    const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    g.addColorStop(0, top);
    g.addColorStop(1, bottom);
    return g;
}

function horizontalGradient(ctx, left, right) {
    const chart = ctx.chart;
    const { ctx: c, chartArea } = chart;
    if (! chartArea) return left;
    const g = c.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
    g.addColorStop(0, left);
    g.addColorStop(1, right);
    return g;
}

/** Soft drop shadow under datasets (modern dashboard look). */
const softShadow = {
    id: 'etpbSoftShadow',
    beforeDatasetsDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        ctx.shadowColor = isDark() ? 'rgba(0,0,0,.35)' : 'rgba(36, 79, 140, .18)';
        ctx.shadowBlur = 12;
        ctx.shadowOffsetY = 4;
    },
    afterDatasetsDraw(chart) {
        chart.ctx.restore();
    },
};

/** Center label for doughnut (total + caption). */
const doughnutCenter = {
    id: 'etpbDoughnutCenter',
    afterDraw(chart) {
        const meta = chart.getDatasetMeta(0);
        if (! meta || meta.type !== 'doughnut' || ! meta.data.length) return;
        const opts = chart.options.plugins?.etpbCenter;
        if (! opts) return;
        const { ctx, chartArea } = chart;
        const x = (chartArea.left + chartArea.right) / 2;
        const y = (chartArea.top + chartArea.bottom) / 2;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = isDark() ? '#E8EEF6' : '#122B4F';
        ctx.font = '700 1.35rem ' + LATIN_FONT;
        ctx.fillText(String(opts.value ?? ''), x, y - 8);
        ctx.fillStyle = isDark() ? '#A7B4C8' : '#5B7399';
        ctx.font = isRtl() ? '400 .85rem ' + URDU_FONT : '600 .68rem ' + LATIN_FONT;
        ctx.fillText(String(opts.label ?? ''), x, y + (isRtl() ? 18 : 14));
        ctx.restore();
    },
};

Chart.register(softShadow, doughnutCenter);

function mountLineDual(canvas, data) {
    const labels = (data.labels || []).map(shortMonth);
    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: data.intakeLabel || t('Submitted'),
                    data: data.intake,
                    borderColor: '#0284C7',
                    backgroundColor: (ctx) => verticalGradient(ctx, 'rgba(14, 165, 233, .28)', 'rgba(14, 165, 233, 0)'),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#0284C7',
                    pointHoverBorderColor: '#fff',
                    borderWidth: 2.75,
                },
                {
                    label: data.disposalLabel || t('Regularized'),
                    data: data.disposal,
                    borderColor: '#C4680A',
                    backgroundColor: (ctx) => verticalGradient(ctx, 'rgba(232, 137, 26, .22)', 'rgba(232, 137, 26, 0)'),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#C4680A',
                    pointHoverBorderColor: '#fff',
                    borderWidth: 2.75,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            layout: { padding: isRtl() ? { left: 14 } : 0 },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { title: (items) => items[0]?.label || '' } },
            },
            scales: {
                x: {
                    reverse: isRtl(),
                    grid: { display: false },
                    ticks: { maxRotation: 0, color: tickColor(), font: { weight: '600', size: 11 } },
                    border: { display: false },
                },
                y: {
                    position: isRtl() ? 'right' : 'left',
                    beginAtZero: true,
                    ticks: { precision: 0, color: tickColor() },
                    grid: { color: gridColor(), drawTicks: false },
                    border: { display: false },
                },
            },
        },
    });
}

function mountDoughnut(canvas, data) {
    const values = data.values || [];
    const total = values.reduce((a, b) => a + Number(b), 0);
    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: values,
                backgroundColor: (data.colors && data.colors.length)
                    ? data.colors
                    : sky.slice(0, values.length),
                borderColor: surfaceBorder(),
                borderWidth: 3,
                hoverOffset: 8,
                hoverBorderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                etpbCenter: {
                    value: data.centerValue != null ? data.centerValue : total,
                    label: data.centerLabel || t('Total'),
                },
                legend: {
                    position: window.matchMedia('(max-width: 640px)').matches
                        ? 'bottom'
                        : (isRtl() ? 'left' : 'right'),
                    labels: { color: tickColor(), padding: 12 },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const sum = ctx.dataset.data.reduce((a, b) => a + Number(b), 0) || 1;
                            const pct = Math.round((Number(ctx.raw) / sum) * 100);
                            return ` ${ctx.label}: ${Number(ctx.raw).toLocaleString('en-PK')} (${pct}%)`;
                        },
                    },
                },
            },
        },
    });
}

function mountBar(canvas, data, horizontal = false) {
    const labels = data.labels || [];
    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels.map((l) => (typeof l === 'string' && /^\d{4}-\d{2}$/.test(l) ? shortMonth(l) : l)),
            datasets: [{
                label: data.label || t('Value'),
                data: data.values,
                backgroundColor: (ctx) => horizontal
                    ? (isRtl()
                        ? horizontalGradient(ctx, '#244F8C', '#38BDF8')
                        : horizontalGradient(ctx, '#38BDF8', '#244F8C'))
                    : verticalGradient(ctx, '#38BDF8', '#1A3A6B'),
                borderRadius: horizontal
                    ? (isRtl() ? { topLeft: 8, bottomLeft: 8 } : { topRight: 8, bottomRight: 8 })
                    : { topLeft: 8, topRight: 8 },
                maxBarThickness: horizontal ? 22 : 34,
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
                    reverse: isRtl(),
                    beginAtZero: true,
                    grid: {
                        color: horizontal ? gridColor() : 'transparent',
                        display: horizontal,
                        drawTicks: false,
                    },
                    border: { display: false },
                    ticks: data.money
                        ? { callback: (v) => money(v), color: tickColor(), maxTicksLimit: 5 }
                        : { precision: 0, color: tickColor() },
                },
                y: {
                    position: isRtl() ? 'right' : 'left',
                    beginAtZero: true,
                    grid: {
                        color: horizontal ? 'transparent' : gridColor(),
                        display: ! horizontal,
                        drawTicks: false,
                    },
                    border: { display: false },
                    ticks: {
                        precision: 0,
                        color: tickColor(),
                        font: { weight: horizontal ? '600' : '400', size: 11 },
                    },
                },
            },
        },
    });
}

function mount(canvas) {
    const kind = canvas.getAttribute('data-etpb-chart');
    const data = readPayload(canvas);
    if (! data) return;

    const existing = instances.get(canvas);
    if (existing) {
        existing.destroy();
        instances.delete(canvas);
    }

    let chart = null;
    if (kind === 'line-dual') chart = mountLineDual(canvas, data);
    if (kind === 'doughnut') chart = mountDoughnut(canvas, data);
    if (kind === 'bar') chart = mountBar(canvas, data, false);
    if (kind === 'hbar') chart = mountBar(canvas, data, true);
    if (chart) instances.set(canvas, chart);
}

function remountAll() {
    applyChartDefaults();
    document.querySelectorAll('canvas[data-etpb-chart]').forEach(mount);
}

// A canvas draws with whatever font is ready at that moment, so Urdu charts
// wait for the Nastaliq face instead of drawing in a fallback and redrawing.
function start() {
    if (! isRtl() || ! document.fonts || typeof document.fonts.load !== 'function') {
        remountAll();
        return;
    }
    Promise.all([
        document.fonts.load('400 16px "Noto Nastaliq Urdu"', 'اردو'),
        document.fonts.ready,
    ]).catch(() => {}).finally(remountAll);
}

document.addEventListener('etpb:themechange', remountAll);
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
