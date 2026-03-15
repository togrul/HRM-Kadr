import {
    Chart,
    Filler,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
    CategoryScale,
} from 'chart.js';

Chart.register(LineController, LineElement, LinearScale, PointElement, Tooltip, CategoryScale, Filler);

const chartRegistry = new WeakMap();
const activeGuidePlugin = {
    id: 'reportsActiveGuide',
    afterDatasetsDraw(chart) {
        const activeElements = chart.getActiveElements();

        if (!activeElements.length) {
            return;
        }

        const [{ element }] = activeElements;
        const { ctx, chartArea } = chart;

        ctx.save();
        ctx.strokeStyle = 'rgba(34, 197, 94, 0.18)';
        ctx.lineWidth = 1;
        ctx.setLineDash([4, 8]);
        ctx.beginPath();
        ctx.moveTo(element.x, chartArea.top + 6);
        ctx.lineTo(element.x, chartArea.bottom);
        ctx.stroke();

        ctx.setLineDash([]);
        ctx.beginPath();
        ctx.fillStyle = 'rgba(34, 197, 94, 0.12)';
        ctx.arc(element.x, element.y, 18, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    },
};

Chart.register(activeGuidePlugin);

function parsePoints(raw) {
    try {
        const parsed = JSON.parse(raw ?? '[]');

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function destroyChart(root) {
    const chart = chartRegistry.get(root);

    if (chart) {
        chart.destroy();
        chartRegistry.delete(root);
    }
}

function buildGradient(context, chartArea) {
    const gradient = context.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);

    gradient.addColorStop(0, 'rgba(34, 197, 94, 0.26)');
    gradient.addColorStop(0.7, 'rgba(34, 197, 94, 0.08)');
    gradient.addColorStop(1, 'rgba(34, 197, 94, 0.01)');

    return gradient;
}

function externalTooltipHandler({ chart, tooltip }) {
    const root = chart.canvas.closest('[data-reports-trend-chart]');

    if (!root) {
        return;
    }

    const tooltipEl = root.querySelector('[data-chart-tooltip]');
    const labelEl = root.querySelector('[data-chart-tooltip-label]');
    const valueEl = root.querySelector('[data-chart-tooltip-value]');

    if (!tooltipEl || !labelEl || !valueEl) {
        return;
    }

    if (tooltip.opacity === 0 || !tooltip.dataPoints?.length) {
        tooltipEl.style.opacity = '0';
        tooltipEl.style.transform = 'translate(-50%, calc(-100% + 8px))';
        return;
    }

    const point = tooltip.dataPoints[0];
    const rootRect = root.getBoundingClientRect();
    const canvasRect = chart.canvas.getBoundingClientRect();
    const left = point.element.x + (canvasRect.left - rootRect.left);
    const top = point.element.y + (canvasRect.top - rootRect.top);

    labelEl.textContent = String(point.label ?? '');
    valueEl.textContent = String(point.formattedValue ?? '');
    const clampedLeft = Math.min(
        Math.max(left, 72),
        Math.max(72, rootRect.width - 72)
    );

    tooltipEl.style.left = `${clampedLeft}px`;
    tooltipEl.style.top = `${top}px`;
    tooltipEl.style.opacity = '1';
    tooltipEl.style.transform = 'translate(-50%, calc(-100% - 14px))';
}

function createChart(canvas, points) {
    const context = canvas.getContext('2d');

    if (!context) {
        return null;
    }

    return new Chart(context, {
        type: 'line',
        data: {
            labels: points.map((point) => point.label),
            datasets: [
                {
                    data: points.map((point) => Number(point.value) || 0),
                    borderColor: '#22c55e',
                    borderWidth: 4,
                    pointRadius: 8,
                    pointHoverRadius: 9,
                    pointBorderWidth: 4,
                    pointHoverBorderWidth: 4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#22c55e',
                    tension: 0.38,
                    fill: true,
                    backgroundColor: (ctx) => {
                        const { chart } = ctx;
                        const { chartArea } = chart;

                        if (!chartArea) {
                            return 'rgba(34, 197, 94, 0.12)';
                        }

                        return buildGradient(chart.ctx, chartArea);
                    },
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            animation: {
                duration: 420,
                easing: 'easeOutQuart',
            },
            transitions: {
                active: {
                    animation: {
                        duration: 120,
                    },
                },
            },
            layout: {
                padding: {
                    top: 16,
                    right: 16,
                    bottom: 8,
                    left: 10,
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    enabled: false,
                    external: externalTooltipHandler,
                },
            },
            scales: {
                x: {
                    offset: false,
                    grid: {
                        display: false,
                        drawBorder: false,
                    },
                    border: {
                        display: false,
                    },
                    ticks: {
                        color: '#71717a',
                        font: {
                            size: 12,
                            weight: 500,
                            family: 'ui-sans-serif, system-ui, sans-serif',
                        },
                        padding: 16,
                        maxRotation: 0,
                        minRotation: 0,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        display: false,
                        maxTicksLimit: 4,
                    },
                    border: {
                        display: false,
                    },
                    grid: {
                        color: '#e4e4e7',
                        drawBorder: false,
                        lineWidth: 1,
                        borderDash: [3, 8],
                        tickLength: 0,
                    },
                },
            },
            elements: {
                line: {
                    capBezierPoints: true,
                },
                point: {
                    hoverBorderWidth: 4,
                },
            },
        },
    });
}

function initTrendChart(root) {
    const canvas = root.querySelector('[data-chart-canvas]');
    const points = parsePoints(root.dataset.points);

    if (!canvas || points.length === 0) {
        destroyChart(root);
        return;
    }

    const serialized = JSON.stringify(points);

    if (root.dataset.chartPoints === serialized && chartRegistry.has(root)) {
        return;
    }

    root.dataset.chartPoints = serialized;
    destroyChart(root);

    const chart = createChart(canvas, points);

    if (!chart) {
        return;
    }

    chartRegistry.set(root, chart);
    chart.setActiveElements([{ datasetIndex: 0, index: points.length - 1 }]);
    chart.tooltip?.setActiveElements([{ datasetIndex: 0, index: points.length - 1 }], {
        x: chart.getDatasetMeta(0).data.at(-1)?.x ?? 0,
        y: chart.getDatasetMeta(0).data.at(-1)?.y ?? 0,
    });
    chart.update('none');
}

function scanTrendCharts() {
    document.querySelectorAll('[data-reports-trend-chart]').forEach(initTrendChart);
}

export function bootReportsTrendCharts() {
    scanTrendCharts();

    const observer = new MutationObserver(() => scanTrendCharts());
    observer.observe(document.body, { childList: true, subtree: true });

    document.addEventListener('livewire:navigated', scanTrendCharts);
}
