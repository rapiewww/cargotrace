import {
    Chart,
    ArcElement,
    LineElement,
    BarElement,
    PointElement,
    BarController,
    DoughnutController,
    LineController,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
} from 'chart.js';

Chart.register(
    ArcElement,
    LineElement,
    BarElement,
    PointElement,
    BarController,
    DoughnutController,
    LineController,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
);

const activeCharts = new Set();

function getCurrentTheme() {
    return document.documentElement.getAttribute('data-theme')
        || (document.documentElement.classList.contains('light') ? 'light' : 'dark');
}

function getStatusColors(statusKeys, isLight) {
    const palette = {
        CREATED: isLight ? '#64748B' : '#8FA3BC',
        BOOKED: '#38BDF8',
        PICKED_UP: '#2563EB',
        IN_TRANSIT: isLight ? '#0891B2' : '#22D3EE',
        DELIVERED: '#10B981',
    };

    return (statusKeys || []).map((key) => palette[String(key).toUpperCase()] || (isLight ? '#52647A' : '#8FA3BC'));
}

function getCarrierColors(carrierItems, isLight) {
    return (carrierItems || []).map((item) => {
        if (item && item.is_others) {
            return isLight ? '#64748B' : '#8FA3BC';
        }
        return isLight ? '#2563EB' : '#38BDF8';
    });
}

export function updateAllChartsTheme(theme) {
    const isLight = theme === 'light';
    const gridColor = isLight ? 'rgba(0, 0, 0, 0.08)' : 'rgba(255, 255, 255, 0.05)';
    const tickColor = isLight ? '#52647A' : '#8FA3BC';
    const headingColor = isLight ? '#0B1728' : '#FFFFFF';
    const mutedColor = isLight ? '#52647A' : '#8FA3BC';
    const tooltipBg = isLight ? '#FFFFFF' : '#0B1728';
    const tooltipBorder = isLight ? '#D5DEEA' : 'rgba(255, 255, 255, 0.1)';
    const surfaceBg = isLight ? '#FFFFFF' : '#0B1728';

    activeCharts.forEach(chart => {
        try {
            if (chart.$statusKeys && chart.data?.datasets?.[0]) {
                chart.data.datasets[0].backgroundColor = getStatusColors(chart.$statusKeys, isLight);
                chart.data.datasets[0].borderColor = surfaceBg;
            }
            if (chart.$carrierItems && chart.data?.datasets?.[0]) {
                chart.data.datasets[0].backgroundColor = getCarrierColors(chart.$carrierItems, isLight);
            }
            if (chart.options.scales) {
                if (chart.options.scales.x) {
                    if (chart.options.scales.x.grid) chart.options.scales.x.grid.color = gridColor;
                    if (chart.options.scales.x.ticks) chart.options.scales.x.ticks.color = tickColor;
                }
                if (chart.options.scales.y) {
                    if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = gridColor;
                    if (chart.options.scales.y.ticks) chart.options.scales.y.ticks.color = tickColor;
                }
            }
            if (chart.options.plugins?.legend?.labels) {
                chart.options.plugins.legend.labels.color = isLight ? '#0B1728' : '#94A3B8';
            }
            if (chart.options.plugins?.tooltip) {
                chart.options.plugins.tooltip.backgroundColor = tooltipBg;
                chart.options.plugins.tooltip.borderColor = tooltipBorder;
                chart.options.plugins.tooltip.titleColor = headingColor;
                chart.options.plugins.tooltip.bodyColor = mutedColor;
            }
            if (chart.config.options.plugins?.centerText) {
                chart.config.options.plugins.centerText.color = headingColor;
                chart.config.options.plugins.centerText.subtextColor = mutedColor;
            }
            chart.update('none');
        } catch (e) {
            // ignore if detached
        }
    });
}

// Plugin to draw total number in center of Doughnut Chart
const centerTextPlugin = {
    id: 'centerText',
    beforeDraw(chart) {
        if (chart.config.type !== 'doughnut') return;
        const centerConfig = chart.config.options.plugins?.centerText;
        if (!centerConfig || !centerConfig.display) return;

        const { ctx, chartArea, width, height } = chart;
        const cx = chartArea ? (chartArea.left + chartArea.right) / 2 : width / 2;
        const cy = chartArea ? (chartArea.top + chartArea.bottom) / 2 : height / 2;

        ctx.save();
        const text = centerConfig.text || '';
        const subtext = centerConfig.subtext || '';

        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // Main text (Total count)
        ctx.font = `700 ${centerConfig.fontSize || 24}px 'Inter', system-ui, sans-serif`;
        ctx.fillStyle = centerConfig.color || '#FFFFFF';
        ctx.fillText(text, cx, subtext ? cy - 8 : cy);

        // Subtext (Sentence case label)
        if (subtext) {
            ctx.font = `500 11px 'Inter', system-ui, sans-serif`;
            ctx.fillStyle = centerConfig.subtextColor || '#8FA3BC';
            ctx.fillText(subtext, cx, cy + 14);
        }
        ctx.restore();
    }
};

Chart.register(centerTextPlugin);

/**
 * 1. Status Doughnut Chart with center total
 */
export function initStatusDoughnut(canvasId, statusCounts, totalCount) {
    const el = document.getElementById(canvasId);
    if (!el) return null;

    const rawLabels = Object.keys(statusCounts || {});
    const data = Object.values(statusCounts || {}).map((v) => Number(v) || 0);
    const computedTotal = Number.isFinite(Number(totalCount)) ? Number(totalCount) : data.reduce((a, b) => a + b, 0);

    if (!rawLabels.length || computedTotal <= 0) {
        return null;
    }

    const isLight = getCurrentTheme() === 'light';

    // Convert raw status codes into sentence case labels
    const labels = rawLabels.map(l => {
        return l.toLowerCase().replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());
    });

    const chart = new Chart(el, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: getStatusColors(rawLabels, isLight),
                borderColor: isLight ? '#FFFFFF' : '#0B1728',
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    display: false
                },
                centerText: {
                    display: true,
                    text: String(computedTotal),
                    subtext: 'Shipments'
                },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#8FA3BC',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" },
                    callbacks: {
                        label(ctx) {
                            const value = Number(ctx.parsed) || 0;
                            const pct = computedTotal > 0 ? ((value / computedTotal) * 100).toFixed(1) : '0.0';
                            return `${ctx.label}: ${value} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });

    chart.$statusKeys = rawLabels;
    activeCharts.add(chart);
    updateAllChartsTheme(getCurrentTheme());
    return chart;
}

/**
 * 2. Shipments per Carrier (Horizontal Bar Chart)
 */
export function initCarrierBarChart(canvasId, carrierStats) {
    const el = document.getElementById(canvasId);
    if (!el || !Array.isArray(carrierStats) || !carrierStats.length) return null;

    const isLight = getCurrentTheme() === 'light';
    const fullNames = carrierStats.map(c => c.is_others && c.carrier_count ? `${c.name} (${c.carrier_count} carriers)` : (c.name || c.code));
    const labels = carrierStats.map(c => {
        const name = String(c.name || c.code || '');
        return name.length > 20 ? name.substring(0, 18) + '…' : name;
    });
    const counts = carrierStats.map(c => Number(c.shipments_count ?? c.count ?? 0));

    const chart = new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Shipments',
                data: counts,
                backgroundColor: getCarrierColors(carrierStats, isLight),
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            layout: {
                padding: {
                    left: 4,
                    right: 12
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#8FA3BC',
                        precision: 0,
                        font: { size: 10, family: "'Inter', sans-serif" }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        color: '#8FA3BC',
                        font: { size: 11, family: "'Inter', sans-serif" }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#8FA3BC',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" },
                    callbacks: {
                        title(context) {
                            const index = context[0]?.dataIndex;
                            return fullNames[index] || context[0]?.label;
                        },
                        label(context) {
                            const count = Number(context.parsed.x) || 0;
                            const item = carrierStats[context.dataIndex];
                            const share = item && item.share !== undefined ? ` (${item.share}%)` : '';
                            return `Shipments: ${count}${share}`;
                        }
                    }
                }
            }
        }
    });

    chart.$carrierItems = carrierStats;
    activeCharts.add(chart);
    updateAllChartsTheme(getCurrentTheme());
    return chart;
}

/**
 * 3. On-Time vs Delayed Trend Chart (Last 8 Weeks)
 */
export function initTrendLineChart(canvasId, trendData) {
    const el = document.getElementById(canvasId);
    if (!el) return null;

    const weeks = (trendData || []).map(d => d.week || 'Wk');
    const onTime = (trendData || []).map(d => d.on_time || 0);
    const delayed = (trendData || []).map(d => d.delayed || 0);

    const chart = new Chart(el, {
        type: 'line',
        data: {
            labels: weeks,
            datasets: [
                {
                    label: 'On-time',
                    data: onTime,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2.5,
                    pointBackgroundColor: '#10B981',
                    fill: true
                },
                {
                    label: 'Delayed',
                    data: delayed,
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2.5,
                    pointBackgroundColor: '#F59E0B',
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748B', font: { size: 10, family: "'Inter', sans-serif" } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748B', font: { size: 10, family: "'Inter', sans-serif" } }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: '#94A3B8',
                        font: { size: 11, family: "'Inter', sans-serif" },
                        boxWidth: 8,
                        boxHeight: 8
                    }
                },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#94A3B8',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" }
                }
            }
        }
    });

    activeCharts.add(chart);
    updateAllChartsTheme(getCurrentTheme());
    return chart;
}

/**
 * 4. Exceptions by Type (Bar Chart)
 */
export function initExceptionsTypeChart(canvasId, exceptionCounts) {
    const el = document.getElementById(canvasId);
    if (!el) return null;

    const rawLabels = Object.keys(exceptionCounts || {});
    const data = Object.values(exceptionCounts || {});

    // Sentence case labels for exceptions (e.g. "Customs hold", "Weather delay")
    const labels = rawLabels.map(l => {
        return l.toLowerCase().replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());
    });

    const chart = new Chart(el, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['None'],
            datasets: [{
                label: 'Exceptions',
                data: data.length ? data : [0],
                backgroundColor: '#F43F5E',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#94A3B8',
                        font: { size: 10, family: "'Inter', sans-serif" }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#64748B',
                        stepSize: 1,
                        font: { size: 10, family: "'Inter', sans-serif" }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#F43F5E',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" }
                }
            }
        }
    });

    activeCharts.add(chart);
    updateAllChartsTheme(getCurrentTheme());
    return chart;
}

/**
 * 5. Analytics Carrier Performance Chart (On-Time Rate % per Carrier)
 */
export function initAnalyticsCarrierChart(canvasId, data) {
    const el = document.getElementById(canvasId);
    if (!el || !data || !data.labels || !data.labels.length) return null;

    const chart = new Chart(el, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'On-time rate (%)',
                data: data.rates,
                backgroundColor: data.rates.map(r => r >= 85 ? '#10B981' : (r >= 70 ? '#38BDF8' : '#F59E0B')),
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#8FA3BC',
                        callback: val => val + '%',
                        font: { size: 10, family: "'Inter', sans-serif" }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        color: '#8FA3BC',
                        font: { size: 11, family: "'Inter', sans-serif" }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#E6EEF8',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" },
                    callbacks: {
                        label: function(ctx) {
                            const total = data.totals ? data.totals[ctx.dataIndex] : 0;
                            return `On-time: ${ctx.parsed.x}% (${total} total shipments)`;
                        }
                    }
                }
            }
        }
    });

    activeCharts.add(chart);
    updateAllChartsTheme(getCurrentTheme());
    return chart;
}

/**
 * 6. Analytics 12-Week Shipment Volume & On-Time Performance Chart
 */
export function initAnalyticsWeeklyChart(canvasId, data) {
    const el = document.getElementById(canvasId);
    if (!el || !data || !data.labels || !data.labels.length) return null;

    const chart = new Chart(el, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'On-time',
                    data: data.on_time,
                    backgroundColor: '#10B981',
                    borderRadius: 4,
                    stack: 'stack1'
                },
                {
                    label: 'Delayed',
                    data: data.delayed,
                    backgroundColor: '#F59E0B',
                    borderRadius: 4,
                    stack: 'stack1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        color: '#8FA3BC',
                        font: { size: 10, family: "'Inter', sans-serif" }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#8FA3BC',
                        stepSize: 1,
                        font: { size: 10, family: "'Inter', sans-serif" }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: '#8FA3BC',
                        font: { size: 11, family: "'Inter', sans-serif" },
                        boxWidth: 8,
                        boxHeight: 8
                    }
                },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#E6EEF8',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" }
                }
            }
        }
    });

    activeCharts.add(chart);
    updateAllChartsTheme(getCurrentTheme());
    return chart;
}

/**
 * 7. Analytics Exceptions by Category Doughnut Chart
 */
export function initAnalyticsExceptionChart(canvasId, data) {
    const el = document.getElementById(canvasId);
    if (!el || !data || !data.labels || !data.labels.length) return null;

    const totalCount = data.counts.reduce((a, b) => a + b, 0);

    const chart = new Chart(el, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.counts,
                backgroundColor: [
                    '#F43F5E', // Rose (Customs hold)
                    '#38BDF8', // Sky (Weather delay)
                    '#F59E0B', // Amber (Port congestion)
                    '#EC4899', // Pink (Damaged)
                    '#8B5CF6', // Purple (Misrouted)
                    '#64748B'  // Slate (Documentation)
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#8FA3BC',
                        font: { size: 11, family: "'Inter', sans-serif" },
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 10
                    }
                },
                centerText: {
                    display: true,
                    text: String(totalCount),
                    subtext: 'Exceptions'
                },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#E6EEF8',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" }
                }
            }
        }
    });

    activeCharts.add(chart);
    updateAllChartsTheme(getCurrentTheme());
    return chart;
}

/**
 * 8. Analytics Top Delayed Shipping Lanes Chart
 */
export function initAnalyticsLaneChart(canvasId, data) {
    const el = document.getElementById(canvasId);
    if (!el || !data || !data.labels || !data.labels.length) return null;

    const chart = new Chart(el, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Avg delay (hours)',
                data: data.delays,
                backgroundColor: '#F59E0B',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#8FA3BC',
                        callback: val => val + 'h',
                        font: { size: 10, family: "'Inter', sans-serif" }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        color: '#8FA3BC',
                        font: { size: 11, family: "'JetBrains Mono', monospace" }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#E6EEF8',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" },
                    callbacks: {
                        label: function(ctx) {
                            const count = data.shipments ? data.shipments[ctx.dataIndex] : 0;
                            return `Average delay: ${ctx.parsed.x}h (${count} delayed shipments)`;
                        }
                    }
                }
            }
        }
    });

    activeCharts.add(chart);
    updateAllChartsTheme(getCurrentTheme());
    return chart;
}
