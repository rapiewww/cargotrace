import { createMap, initWorldMap, initShipmentRouteMap, updateAllMapsTheme } from './map';
import {
    initMaritimeGlobe,
    initRoutes2DMap,
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
    document.querySelectorAll('#theme-toggle-btn, #welcome-theme-toggle, #login-theme-toggle, #register-theme-toggle, #tracking-theme-toggle, button[role="switch"]').forEach(btn => {
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

const DESKTOP_SIDEBAR_MEDIA = '(min-width: 1024px)';
let sidebarResizeTimer = null;
let sidebarTransitionCleanup = null;

function isDesktopSidebarViewport() {
    return window.matchMedia(DESKTOP_SIDEBAR_MEDIA).matches;
}

/**
 * Keep the single topbar hamburger button's aria-expanded in sync with desktop/mobile state
 */
function syncSidebarToggleAria() {
    const toggleBtn = document.getElementById('sidebar-toggle-btn');
    if (!toggleBtn) return;

    if (isDesktopSidebarViewport()) {
        const isCollapsed = document.documentElement.classList.contains('sidebar-is-collapsed');
        toggleBtn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        return;
    }

    const sidebar = document.getElementById('main-sidebar');
    const isClosed = !sidebar || sidebar.classList.contains('-translate-x-full');
    toggleBtn.setAttribute('aria-expanded', isClosed ? 'false' : 'true');
}

/**
 * Dispatch window resize once after the sidebar width/padding transition finishes
 * so Leaflet maps, 3D globe, and Chart.js canvases re-measure cleanly.
 */
function schedulePostTransitionResize() {
    if (sidebarResizeTimer) {
        clearTimeout(sidebarResizeTimer);
        sidebarResizeTimer = null;
    }
    if (sidebarTransitionCleanup) {
        sidebarTransitionCleanup();
        sidebarTransitionCleanup = null;
    }

    const mainContent = document.getElementById('main-content');
    const sidebar = document.getElementById('main-sidebar');
    const targetEl = mainContent || sidebar;
    let fired = false;

    const fireResize = () => {
        if (fired) return;
        fired = true;
        if (sidebarResizeTimer) {
            clearTimeout(sidebarResizeTimer);
            sidebarResizeTimer = null;
        }
        if (sidebarTransitionCleanup) {
            sidebarTransitionCleanup();
            sidebarTransitionCleanup = null;
        }
        window.dispatchEvent(new Event('resize'));
    };

    if (targetEl) {
        const onTransitionEnd = (event) => {
            if (event.target !== targetEl) return;
            if (event.propertyName === 'padding-left' || event.propertyName === 'width') {
                fireResize();
            }
        };
        targetEl.addEventListener('transitionend', onTransitionEnd);
        sidebarTransitionCleanup = () => {
            targetEl.removeEventListener('transitionend', onTransitionEnd);
        };
    }

    sidebarResizeTimer = setTimeout(fireResize, 320);
}

/**
 * Toggle sidebar: collapse/expand desktop icon rail (>= 1024px) or slide-over drawer (< 1024px)
 */
export function toggleSidebar() {
    if (isDesktopSidebarViewport()) {
        toggleSidebarCollapse();
        return;
    }

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
    syncSidebarToggleAria();
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
    syncSidebarToggleAria();
}

/**
 * Toggle desktop sidebar between expanded (w-64) and icon rail (w-20)
 */
export function toggleSidebarCollapse() {
    const isCollapsed = document.documentElement.classList.toggle('sidebar-is-collapsed');
    localStorage.setItem('cargotrace_sidebar_collapsed', isCollapsed ? 'true' : 'false');
    syncSidebarToggleAria();
    window.dispatchEvent(new CustomEvent('cargotrace:sidebar-collapse', { detail: { isCollapsed } }));
    schedulePostTransitionResize();
}

// Export CargoTrace global namespace
window.CargoTrace = {
    createMap,
    initWorldMap,
    initShipmentRouteMap,
    initMaritimeGlobe,
    initRoutes2DMap,
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
    syncSidebarToggleAria();

    // Close mobile drawer with Escape (R-32)
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || isDesktopSidebarViewport()) return;
        const sidebar = document.getElementById('main-sidebar');
        if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
            closeSidebar();
            document.getElementById('sidebar-toggle-btn')?.focus();
        }
    });

    // Clear mobile drawer backdrop/scroll lock when resizing to desktop and resync aria-expanded
    window.matchMedia(DESKTOP_SIDEBAR_MEDIA).addEventListener('change', (event) => {
        if (event.matches) {
            closeSidebar();
        } else {
            syncSidebarToggleAria();
        }
    });

    // Theme setup from localStorage or prefers-color-scheme on first visit
    let initialTheme = localStorage.getItem('cargotrace_theme');
    if (!initialTheme) {
        initialTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
    }
    applyTheme(initialTheme);

    const payload = window.__CARGOTRACE__;
    if (!payload) return;

    // Maritime 3D Globe + 2D Routes Map initialization (isolated so one view error never blocks the other)
    if (payload.maritimeGlobe) {
        try {
            initMaritimeGlobe(payload.maritimeGlobe.globe3dId, payload.maritimeGlobe.options);
        } catch (err) {
            console.error('[CargoTrace Map] Failed to initialize 3D globe:', err);
        }
        try {
            initRoutes2DMap(payload.maritimeGlobe.map2dId, payload.maritimeGlobe.options);
        } catch (err) {
            console.error('[CargoTrace Map] Failed to initialize 2D routes map:', err);
        }
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
