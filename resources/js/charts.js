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
        ctx.font = `bold ${centerConfig.fontSize || 22}px 'JetBrains Mono', monospace`;
        ctx.fillStyle = centerConfig.color || '#FFFFFF';
        ctx.fillText(text, width / 2, height / 2 - 6);

        // Subtext (Label)
        if (subtext) {
            ctx.font = `600 10px 'Plus Jakarta Sans', sans-serif`;
            ctx.fillStyle = '#64748B';
            ctx.fillText(subtext.toUpperCase(), width / 2, height / 2 + 14);
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

    const labels = Object.keys(statusCounts || {});
    const data = Object.values(statusCounts || {});

    return new Chart(el, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data.length ? data : [1],
                backgroundColor: [
                    '#38BDF8', // CREATED/BOOKED - Sky
                    '#818CF8', // PICKED_UP - Indigo
                    '#F59E0B', // IN_TRANSIT - Amber
                    '#10B981', // DELIVERED - Emerald
                    '#94A3B8'
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
                        font: { size: 10, family: "'Plus Jakarta Sans', sans-serif" },
                        boxWidth: 10,
                        padding: 10
                    }
                },
                centerText: {
                    display: true,
                    text: String(totalCount || data.reduce((a, b) => a + b, 0)),
                    subtext: 'Shipments'
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

    const labels = (carrierStats || []).map(c => c.name || c.code);
    const counts = (carrierStats || []).map(c => c.shipments_count || c.count || 0);

    return new Chart(el, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Bookings',
                data: counts,
                backgroundColor: '#2DD4BF',
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
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748B', font: { size: 10, family: "'JetBrains Mono', monospace" } }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: '#94A3B8', font: { size: 10, family: "'Plus Jakarta Sans', sans-serif" } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B1728',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    titleColor: '#FFFFFF',
                    bodyColor: '#2DD4BF'
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
                    label: 'On-Time',
                    data: onTime,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 3,
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
                    pointRadius: 3,
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
                    ticks: { color: '#64748B', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748B', font: { size: 10, family: "'JetBrains Mono', monospace" } }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: '#94A3B8',
                        font: { size: 10 },
                        boxWidth: 8
                    }
                }
            }
        }
    });
}

/**
 * 4. Exceptions by Type Doughnut / Polar Chart
 */
export function initExceptionsTypeChart(canvasId, exceptionCounts) {
    const el = document.getElementById(canvasId);
    if (!el) return null;

    const labels = Object.keys(exceptionCounts || {});
    const data = Object.values(exceptionCounts || {});

    return new Chart(el, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data.length ? data : [1],
                backgroundColor: [
                    '#F43F5E', // Customs hold
                    '#F59E0B', // Weather
                    '#FB7185', // Congestion
                    '#E11D48', // Damaged
                    '#64748B'  // Misrouted
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#94A3B8',
                        font: { size: 9 },
                        boxWidth: 8
                    }
                }
            }
        }
    });
}
