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

// Plugin to draw total number in center of Doughnut Chart
const centerTextPlugin = {
    id: 'centerText',
    beforeDraw(chart) {
        if (chart.config.type !== 'doughnut') return;
        const centerConfig = chart.config.options.plugins?.centerText;
        if (!centerConfig || !centerConfig.display) return;

        const { ctx, width, height } = chart;
        ctx.save();
        const text = centerConfig.text || '';
        const subtext = centerConfig.subtext || '';

        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // Main text (Total count)
        ctx.font = `700 ${centerConfig.fontSize || 24}px 'Inter', system-ui, sans-serif`;
        ctx.fillStyle = centerConfig.color || '#FFFFFF';
        ctx.fillText(text, width / 2, height / 2 - 8);

        // Subtext (Sentence case label)
        if (subtext) {
            ctx.font = `500 11px 'Inter', system-ui, sans-serif`;
            ctx.fillStyle = '#64748B';
            ctx.fillText(subtext, width / 2, height / 2 + 14);
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
    const data = Object.values(statusCounts || {});

    // Convert raw status codes into sentence case labels
    const labels = rawLabels.map(l => {
        return l.toLowerCase().replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());
    });

    return new Chart(el, {
        type: 'doughnut',
        data: {
            labels: labels.length ? labels : ['No shipments'],
            datasets: [{
                data: data.length ? data : [1],
                backgroundColor: [
                    '#38BDF8', // Created / Booked - Sky
                    '#818CF8', // Picked up - Indigo
                    '#F59E0B', // In transit - Amber
                    '#10B981', // Delivered - Emerald
                    '#64748B'  // Other
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#94A3B8',
                        font: { size: 11, family: "'Inter', sans-serif" },
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 12
                    }
                },
                centerText: {
                    display: true,
                    text: String(totalCount || data.reduce((a, b) => a + b, 0)),
                    subtext: 'Total'
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
}

/**
 * 2. Shipments per Carrier (Horizontal Bar Chart)
 */
export function initCarrierBarChart(canvasId, carrierStats) {
    const el = document.getElementById(canvasId);
    if (!el) return null;

    const fullNames = (carrierStats || []).map(c => c.name || c.code);
    const labels = fullNames.map(name => name.length > 22 ? name.substring(0, 20) + '…' : name);
    const counts = (carrierStats || []).map(c => c.shipments_count || c.count || 0);

    return new Chart(el, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['None'],
            datasets: [{
                label: 'Shipments',
                data: counts.length ? counts : [0],
                backgroundColor: '#2DD4BF',
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
                    left: 6,
                    right: 12
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#64748B',
                        stepSize: 1,
                        font: { size: 10, family: "'Inter', sans-serif" }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        color: '#94A3B8',
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
                    bodyColor: '#2DD4BF',
                    padding: 8,
                    bodyFont: { family: "'Inter', sans-serif" },
                    titleFont: { family: "'Inter', sans-serif" },
                    callbacks: {
                        title: function(context) {
                            const index = context[0]?.dataIndex;
                            return fullNames[index] || context[0]?.label;
                        }
                    }
                }
            }
        }
    });
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

    return new Chart(el, {
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

    return new Chart(el, {
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
}
