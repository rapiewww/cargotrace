import { initWorldMap, initShipmentRouteMap } from './map';
import {
    initStatusDoughnut,
    initCarrierBarChart,
    initTrendLineChart,
    initExceptionsTypeChart
} from './charts';
import { initTrackingModule } from './tracking';
import { initUiModule } from './ui';

// Initialize global UI and tracking helpers
initTrackingModule();
initUiModule();

// Export CargoTrace global namespace
window.CargoTrace = {
    initWorldMap,
    initShipmentRouteMap,
    initStatusDoughnut,
    initCarrierBarChart,
    initTrendLineChart,
    initExceptionsTypeChart,
};

// Automatic initialization if page payload is provided via window.__CARGOTRACE__
document.addEventListener('DOMContentLoaded', () => {
    const payload = window.__CARGOTRACE__;
    if (!payload) return;

    if (payload.worldMap) {
        initWorldMap(payload.worldMap.containerId, payload.worldMap.options);
    }

    if (payload.routeMap) {
        initShipmentRouteMap(payload.routeMap.containerId, payload.routeMap.shipment);
    }

    if (payload.dashboardCharts) {
        const { statusCounts, totalCount, carrierStats, trendData, exceptionCounts } = payload.dashboardCharts;
        if (statusCounts) initStatusDoughnut('shipmentsStatusChart', statusCounts, totalCount);
        if (carrierStats) initCarrierBarChart('carrierBarChart', carrierStats);
        if (trendData) initTrendLineChart('trendLineChart', trendData);
        if (exceptionCounts) initExceptionsTypeChart('exceptionTypeChart', exceptionCounts);
    }
});
