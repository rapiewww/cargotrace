import { createMap, initWorldMap, initShipmentRouteMap, updateAllMapsTheme } from './map';
import {
    initMaritimeGlobe,
    initMaritime2DMap,
    setMapMode,
    setMapFilter,
    zoomMap,
    focusMaritimeEntity,
    updateMaritimeGlobeTheme
} from './globe';
import {
    initStatusDoughnut,
    initCarrierBarChart,
    initTrendLineChart,
    initExceptionsTypeChart,
    initAnalyticsCarrierChart,
    initAnalyticsWeeklyChart,
    initAnalyticsExceptionChart,
    initAnalyticsLaneChart,
    updateAllChartsTheme
} from './charts';
import { initTrackingModule } from './tracking';
import { initUiModule } from './ui';

// Initialize global UI and tracking helpers
initTrackingModule();
initUiModule();

/**
 * Apply theme to document and broadcast change
 */
export function applyTheme(theme) {
    const validTheme = theme === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', validTheme);

    if (validTheme === 'light') {
        document.documentElement.classList.add('light');
        document.documentElement.classList.remove('dark');
    } else {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
    }

    localStorage.setItem('cargotrace_theme', validTheme);

    // Notify maps, globe, and charts
    updateAllMapsTheme(validTheme);
    updateMaritimeGlobeTheme(validTheme);
    updateAllChartsTheme(validTheme);

    // Update accessible switch buttons
    const isDark = validTheme === 'dark';
    document.querySelectorAll('#theme-toggle-btn, #welcome-theme-toggle, #login-theme-toggle, #register-theme-toggle, button[role="switch"]').forEach(btn => {
        btn.setAttribute('aria-checked', isDark ? 'true' : 'false');
        btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    });

    window.dispatchEvent(new CustomEvent('cargotrace:theme-change', { detail: { theme: validTheme } }));
}

/**
 * Toggle between dark and light themes
 */
export function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme')
        || (document.documentElement.classList.contains('light') ? 'light' : 'dark');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);
}

/**
 * Toggle responsive mobile sidebar drawer
 */
export function toggleSidebar() {
    const sidebar = document.getElementById('main-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!sidebar) return;
    const isClosed = sidebar.classList.contains('-translate-x-full');
    if (isClosed) {
        sidebar.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    } else {
        sidebar.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    }
}

/**
 * Close responsive mobile sidebar drawer
 */
export function closeSidebar() {
    const sidebar = document.getElementById('main-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (sidebar) sidebar.classList.add('-translate-x-full');
    if (backdrop) backdrop.classList.add('hidden');
    document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
}

/**
 * Toggle desktop sidebar between expanded (w-64) and icon rail (w-20)
 */
export function toggleSidebarCollapse() {
    const isCollapsed = document.documentElement.classList.toggle('sidebar-is-collapsed');
    localStorage.setItem('cargotrace_sidebar_collapsed', isCollapsed ? 'true' : 'false');
    window.dispatchEvent(new CustomEvent('cargotrace:sidebar-collapse', { detail: { isCollapsed } }));
}

// Export CargoTrace global namespace
window.CargoTrace = {
    createMap,
    initWorldMap,
    initShipmentRouteMap,
    initMaritimeGlobe,
    initMaritime2DMap,
    setMapMode,
    setMapFilter,
    zoomMap,
    focusMaritimeEntity,
    toggleTheme,
    applyTheme,
    toggleSidebar,
    closeSidebar,
    toggleSidebarCollapse,
    initStatusDoughnut,
    initCarrierBarChart,
    initTrendLineChart,
    initExceptionsTypeChart,
    initAnalyticsCarrierChart,
    initAnalyticsWeeklyChart,
    initAnalyticsExceptionChart,
    initAnalyticsLaneChart,
};

// Automatic initialization on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    // Theme setup from localStorage or prefers-color-scheme on first visit
    let initialTheme = localStorage.getItem('cargotrace_theme');
    if (!initialTheme) {
        initialTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
    }
    applyTheme(initialTheme);

    const payload = window.__CARGOTRACE__;
    if (!payload) return;

    // Maritime 3D Globe + 2D Map initialization
    if (payload.maritimeGlobe) {
        initMaritimeGlobe(payload.maritimeGlobe.globe3dId, payload.maritimeGlobe.options);
        initMaritime2DMap(payload.maritimeGlobe.map2dId, payload.maritimeGlobe.options);
    }

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

    if (payload.analyticsCharts) {
        const { carrierChartData, weeklyChartData, exceptionChartData, laneChartData } = payload.analyticsCharts;
        if (carrierChartData) initAnalyticsCarrierChart('analyticsCarrierChart', carrierChartData);
        if (weeklyChartData) initAnalyticsWeeklyChart('analyticsWeeklyChart', weeklyChartData);
        if (exceptionChartData) initAnalyticsExceptionChart('analyticsExceptionChart', exceptionChartData);
        if (laneChartData) initAnalyticsLaneChart('analyticsLaneChart', laneChartData);
    }
});
