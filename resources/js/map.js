import L from 'leaflet';
import {
    computeGlobeArcAltitude,
    computeRouteGeometry,
    getGreatCircleArc,
    getGreatCircleDistanceRadians,
    hexToRgba,
    isValidCoordinatePair,
    normalizeLocation,
    normalizeLongitude,
    normalizeMapDataset,
    normalizeShipmentRoute,
    resolveContinuousTargetLng,
    unwrapLongitudeNear,
} from './geo';

export {
    computeGlobeArcAltitude,
    computeRouteGeometry,
    getGreatCircleArc,
    getGreatCircleDistanceRadians,
    hexToRgba,
    isValidCoordinatePair,
    normalizeLocation,
    normalizeLongitude,
    normalizeMapDataset,
    normalizeShipmentRoute,
    resolveContinuousTargetLng,
    unwrapLongitudeNear,
};

/**
 * Registry of all active Leaflet map instances for synchronized theme updates
 */
const activeMaps = new Set();

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Get OpenStreetMap tile layer configuration with configurable tile source and attribution.
 */
export function getTileLayerConfig(theme = 'dark', overrides = {}) {
    const metaUrl = typeof document !== 'undefined' ? document.querySelector('meta[name="map-tile-url"]')?.getAttribute('content') : null;
    const metaAttribution = typeof document !== 'undefined' ? document.querySelector('meta[name="map-tile-attribution"]')?.getAttribute('content') : null;

    return {
        url: overrides.tileUrl || metaUrl || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        options: {
            attribution: overrides.attribution || metaAttribution || '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
            maxZoom: 18,
            noWrap: false,
        }
    };
}

/**
 * Split an unwrapped [lat, lng] polyline at +/-180 antimeridian crossings.
 * Preserved for backwards compatibility; active maps render continuous world-copy-aware geometry.
 */
export function splitAntimeridianSegments(points) {
    if (!Array.isArray(points) || points.length === 0) return [];

    const segments = [];
    let currentSegment = [];

    const pushPoint = (lat, lng) => {
        const clampedLat = Math.max(-72, Math.min(72, Number(lat)));
        currentSegment.push([clampedLat, lng]);
    };

    const firstLat = Number(points[0][0]);
    const firstLng = normalizeLongitude(points[0][1]);
    pushPoint(firstLat, firstLng);

    for (let i = 1; i < points.length; i++) {
        const prevRawLat = Number(points[i - 1][0]);
        const prevRawLng = Number(points[i - 1][1]);
        const currRawLat = Number(points[i][0]);
        const currRawLng = Number(points[i][1]);

        const prevNorm = normalizeLongitude(prevRawLng);
        const currUnwrapped = unwrapLongitudeNear(currRawLng, prevNorm);

        if (currUnwrapped > 180) {
            const denom = currUnwrapped - prevNorm;
            const ratio = Math.abs(denom) > 1e-9 ? (180 - prevNorm) / denom : 0.5;
            const crossLat = prevRawLat + (currRawLat - prevRawLat) * ratio;
            pushPoint(crossLat, 180);
            segments.push(currentSegment);
            currentSegment = [];
            pushPoint(crossLat, -180);
            pushPoint(currRawLat, currUnwrapped - 360);
        } else if (currUnwrapped < -180) {
            const denom = currUnwrapped - prevNorm;
            const ratio = Math.abs(denom) > 1e-9 ? (-180 - prevNorm) / denom : 0.5;
            const crossLat = prevRawLat + (currRawLat - prevRawLat) * ratio;
            pushPoint(crossLat, -180);
            segments.push(currentSegment);
            currentSegment = [];
            pushPoint(crossLat, 180);
            pushPoint(currRawLat, currUnwrapped + 360);
        } else {
            pushPoint(currRawLat, currUnwrapped);
        }
    }

    if (currentSegment.length > 0) {
        segments.push(currentSegment);
    }

    return segments.length === 1 ? segments[0] : segments;
}

/**
 * Crisp monoline SVG icon markup for transport modes in Leaflet popups.
 */
export function getModeSvg(mode, size = 12) {
    const normalized = String(mode || 'SEA').toUpperCase();
    const common = `width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;flex-shrink:0;" aria-hidden="true"`;
    if (normalized === 'AIR') {
        return `<svg ${common}><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 00-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>`;
    }
    if (normalized === 'ROAD') {
        return `<svg ${common}><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>`;
    }
    return `<svg ${common}><path d="M3 17l1.5-5h15L21 17M5 12V8a1 1 0 011-1h5V4h2v3h4a1 1 0 011 1v4M2 20c1.5 1 3.5 1 5 0s3.5-1 5 0 3.5 1 5 0 3.5-1 5 0"/></svg>`;
}

/**
 * Calculate a curved great-circle style arc between two coordinates.
 * Preserved for existing callers (worldMap and shipment detail routeMap).
 */
export function getBezierCurve(start, end, numPoints = 64, mode = 'SEA') {
    return getGreatCircleArc(start, end, { numPoints, mode });
}

/**
 * Map delay severity and active exception status to DESIGN.md semantic colors.
 */
export function getSeverityColor(delaySeverity, hasException = false) {
    const sev = String(delaySeverity || 'NONE').toUpperCase();
    if (hasException || sev === 'HIGH') return '#F43F5E';
    if (sev === 'MEDIUM') return '#F97316';
    if (sev === 'LOW') return '#F59E0B';
    return '#10B981';
}

/**
 * Human-readable severity label for popups and screen-reader descriptions.
 */
export function getSeverityLabel(delaySeverity, hasException = false) {
    const sev = String(delaySeverity || 'NONE').toUpperCase();
    if (hasException) return 'Exception alert';
    if (sev === 'HIGH') return 'High delay';
    if (sev === 'MEDIUM') return 'Medium delay';
    if (sev === 'LOW') return 'Low delay';
    return 'On time';
}

/**
 * Shared map factory using config-driven OpenStreetMap tiles.
 */
export function createMap(elementId, options = {}) {
    const el = document.getElementById(elementId);
    if (!el) return null;

    if (el._leaflet_id && el._leaflet_map) {
        try {
            el._leaflet_map.remove();
            activeMaps.delete(el._leaflet_map);
        } catch (e) {
            // ignore
        }
    }

    const { tileOverrides, ...leafletOptions } = options;

    const map = L.map(elementId, {
        center: leafletOptions.center || [22, 18],
        zoom: leafletOptions.zoom || 2,
        minZoom: leafletOptions.minZoom || 2,
        maxZoom: leafletOptions.maxZoom || 18,
        zoomControl: leafletOptions.zoomControl !== false,
        attributionControl: leafletOptions.attributionControl !== false,
        worldCopyJump: leafletOptions.worldCopyJump !== undefined ? leafletOptions.worldCopyJump : true,
        scrollWheelZoom: leafletOptions.scrollWheelZoom !== undefined ? leafletOptions.scrollWheelZoom : false,
        ...leafletOptions,
    });

    el._leaflet_map = map;

    const currentTheme = document.documentElement.getAttribute('data-theme')
        || (document.documentElement.classList.contains('light') ? 'light' : 'dark');

    const tileConfig = getTileLayerConfig(currentTheme, tileOverrides);
    const tileLayer = L.tileLayer(tileConfig.url, tileConfig.options).addTo(map);

    map._activeTileLayer = tileLayer;
    map._tileOverrides = tileOverrides || {};
    activeMaps.add(map);

    return map;
}

/**
 * Update all active Leaflet maps to match the newly switched theme
 */
export function updateAllMapsTheme(theme) {
    activeMaps.forEach(map => {
        try {
            const tileConfig = getTileLayerConfig(theme, map._tileOverrides || {});
            if (map && map._activeTileLayer) {
                map.removeLayer(map._activeTileLayer);
                const newLayer = L.tileLayer(tileConfig.url, tileConfig.options).addTo(map);
                map._activeTileLayer = newLayer;
            }
            if (map && typeof map._onThemeChange === 'function') {
                map._onThemeChange(theme);
            }
        } catch (e) {
            activeMaps.delete(map);
        }
    });
}

function attachContainerResizeObserver(el, map) {
    if (typeof ResizeObserver === 'undefined' || !el || !map) return;

    if (el._mapResizeObserver) {
        el._mapResizeObserver.disconnect();
    }

    let rafId = null;
    const observer = new ResizeObserver(() => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
            if (el.offsetWidth > 0 && el.offsetHeight > 0) {
                map.invalidateSize({ animate: false });
            }
        });
    });

    observer.observe(el);
    el._mapResizeObserver = observer;
}

/**
 * Initialize Multi-Route World Map on Landing Page
 */
export function initWorldMap(containerId, options = {}) {
    const el = document.getElementById(containerId);
    if (!el) return null;

    const normalized = normalizeMapDataset(options);
    const interactive = options.interactive !== false;

    const map = createMap(containerId, {
        center: options.center || [20, 22],
        zoom: options.zoom || 2,
        minZoom: 2,
        maxZoom: 18,
        maxBounds: [[-82, -540], [82, 540]],
        maxBoundsViscosity: 0.7,
        zoomControl: interactive,
        attributionControl: true,
        worldCopyJump: true,
        scrollWheelZoom: interactive ? 'center' : false,
    });

    if (!map) return null;

    const layerAll = L.layerGroup().addTo(map);
    const layerDelayed = L.layerGroup();
    const layerExceptions = L.layerGroup();
    const layerSea = L.layerGroup();
    const layerAir = L.layerGroup();
    const layerRoad = L.layerGroup();

    map._modeLayers = {
        ALL: layerAll,
        DELAYED: layerDelayed,
        EXCEPTIONS: layerExceptions,
        SEA: layerSea,
        AIR: layerAir,
        ROAD: layerRoad,
    };

    const getMarkerRingColor = () => {
        const theme = document.documentElement.getAttribute('data-theme')
            || (document.documentElement.classList.contains('light') ? 'light' : 'dark');
        return theme === 'dark' ? '#0B1728' : '#FFFFFF';
    };

    // Plot normalized locations
    normalized.locations.forEach(loc => {
        try {
            const marker = L.circleMarker([loc.lat, loc.lng], {
                radius: 4,
                fillColor: '#2DD4BF',
                color: '#0D9488',
                weight: 1.5,
                opacity: 1,
                fillOpacity: 0.9,
            }).addTo(map);

            marker.bindPopup(`
                <div style="font-family: 'Inter', sans-serif; font-size: 11px;">
                    <div style="font-weight: 700; color: #0D9488;">${escapeHtml(loc.name)}</div>
                    <div style="color: #64748B; margin-top: 2px;">${escapeHtml(loc.codeType)}: <b>${escapeHtml(loc.code)}</b> &bull; ${escapeHtml(loc.country)}</div>
                </div>
            `);
        } catch (err) {
            console.error(`[CargoTrace Map] Failed to render world location ${loc?.name || 'unknown'}:`, err);
        }
    });

    // Plot normalized continuous routes with matching origin/destination markers
    normalized.routes.forEach(route => {
        try {
            const geometry = computeRouteGeometry(route, { numPoints: 64 });
            const strokeColor = getSeverityColor(route.delay_severity, route.has_exception);
            const isAlert = route.is_delayed || route.has_exception;
            const dashArray = route.mode === 'AIR' ? '7, 5' : null;
            const weight = route.mode === 'ROAD' ? 2.5 : (route.mode === 'AIR' ? 1.9 : 2.1);
            const opacity = isAlert ? 0.92 : 0.8;
            const modeLabel = route.mode === 'AIR' ? 'Air' : (route.mode === 'ROAD' ? 'Road' : 'Sea');
            const origName = route.originMeta?.short_name || route.originMeta?.name || 'Origin';
            const destName = route.destinationMeta?.short_name || route.destinationMeta?.name || 'Destination';
            const severityText = getSeverityLabel(route.delay_severity, route.has_exception);

            const popupHtml = `
                <div style="font-family: 'Inter', sans-serif; font-size: 11px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <a href="${escapeHtml(route.url)}" style="font-weight: 700; color: #0284C7; text-decoration: none; font-family: 'JetBrains Mono', monospace;">${escapeHtml(route.tracking_number)}</a>
                        <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 9999px; background: rgba(2, 132, 199, 0.15);">${getModeSvg(route.mode, 11)} <span>${modeLabel}</span></span>
                    </div>
                    <div style="color: #64748B; margin-top: 4px;">${escapeHtml(origName)} &rarr; ${escapeHtml(destName)}</div>
                    <div style="margin-top: 4px; font-weight: 600; color: ${strokeColor};">
                        ${escapeHtml(route.status_label)} (${escapeHtml(severityText)})
                    </div>
                </div>
            `;

            const addRouteLayers = (coords) => {
                const polyline = L.polyline(coords.points, {
                    color: strokeColor,
                    weight,
                    dashArray,
                    opacity,
                }).bindPopup(popupHtml);

                const ringColor = getMarkerRingColor();
                const origMarker = L.circleMarker(coords.originLatLng, {
                    radius: 4.5,
                    fillColor: '#2563EB',
                    color: ringColor,
                    weight: 1.8,
                    opacity: 1,
                    fillOpacity: 0.95,
                }).bindPopup(popupHtml);

                const destMarker = L.circleMarker(coords.destinationLatLng, {
                    radius: 4.5,
                    fillColor: '#0891B2',
                    color: ringColor,
                    weight: 1.8,
                    opacity: 1,
                    fillOpacity: 0.95,
                }).bindPopup(popupHtml);

                const items = [polyline, origMarker, destMarker];
                items.forEach(item => {
                    item.addTo(layerAll);
                    if (route.is_delayed) item.addTo(layerDelayed);
                    if (route.has_exception) item.addTo(layerExceptions);
                    if (route.mode === 'SEA') item.addTo(layerSea);
                    if (route.mode === 'AIR') item.addTo(layerAir);
                    if (route.mode === 'ROAD') item.addTo(layerRoad);
                });
            };

            addRouteLayers(geometry);
            if (geometry.mirrored) {
                addRouteLayers(geometry.mirrored);
            }
        } catch (err) {
            console.error(`[CargoTrace Map] Failed to render world route ${route?.tracking_number || 'unknown'}:`, err);
        }
    });

    attachContainerResizeObserver(el, map);

    return map;
}

/**
 * Initialize Single Shipment Route Map on Detail View
 */
export function initShipmentRouteMap(containerId, shipment) {
    if (!shipment) return null;

    const el = document.getElementById(containerId);
    if (!el) return null;

    const route = normalizeShipmentRoute(shipment);
    if (!route) return null;

    const geometry = computeRouteGeometry(route, { numPoints: 64 });
    const [origLat, origLng] = geometry.originLatLng;
    const [destLat, destLng] = geometry.destinationLatLng;

    const midLat = (origLat + destLat) / 2;
    const midLng = (origLng + destLng) / 2;

    const map = createMap(containerId, {
        center: [midLat, midLng],
        zoom: 3,
        minZoom: 2,
        maxZoom: 18,
        maxBounds: [[-82, -540], [82, 540]],
        maxBoundsViscosity: 0.7,
        worldCopyJump: true,
        zoomControl: true,
        attributionControl: true,
        scrollWheelZoom: false,
    });

    if (!map) return null;

    const orig = route.originMeta;
    const dest = route.destinationMeta;
    const curr = route.currentMeta;
    const strokeColor = getSeverityColor(route.delay_severity, route.has_exception);
    const dashArray = route.mode === 'AIR' ? '7, 5' : null;
    const weight = route.mode === 'ROAD' ? 3.0 : 2.6;

    const drawSingleCopy = (coords, openCurrentPopup = false) => {
        try {
            L.polyline(coords.points, {
                color: strokeColor,
                weight,
                dashArray,
                opacity: 0.9,
            }).addTo(map);
        } catch (err) {
            console.error('[CargoTrace Map] Failed to render shipment route polyline:', err);
        }

        try {
            L.circleMarker(coords.originLatLng, {
                radius: 6,
                fillColor: '#10B981',
                color: '#059669',
                weight: 2,
                fillOpacity: 1,
            }).addTo(map).bindPopup(`<b>Origin:</b> ${escapeHtml(orig.name || 'Origin')} (${escapeHtml(orig.code || orig.display_code || orig.unlocode || '')})`);
        } catch (err) {
            console.error('[CargoTrace Map] Failed to render shipment origin marker:', err);
        }

        try {
            L.circleMarker(coords.destinationLatLng, {
                radius: 6,
                fillColor: '#3B82F6',
                color: '#1D4ED8',
                weight: 2,
                fillOpacity: 1,
            }).addTo(map).bindPopup(`<b>Destination:</b> ${escapeHtml(dest.name || 'Destination')} (${escapeHtml(dest.code || dest.display_code || dest.unlocode || '')})`);
        } catch (err) {
            console.error('[CargoTrace Map] Failed to render shipment destination marker:', err);
        }

        if (coords.currentLatLng && curr) {
            try {
                const marker = L.circleMarker(coords.currentLatLng, {
                    radius: 7,
                    fillColor: '#F59E0B',
                    color: '#B45309',
                    weight: 2,
                    fillOpacity: 1,
                }).addTo(map).bindPopup(`<b>Current Location:</b> ${escapeHtml(curr.name || curr.location_name || 'Checkpoint')} (${escapeHtml(curr.code || curr.display_code || curr.unlocode || '')})`);
                if (openCurrentPopup) {
                    marker.openPopup();
                }
            } catch (err) {
                console.error('[CargoTrace Map] Failed to render shipment current marker:', err);
            }
        }
    };

    drawSingleCopy(geometry, true);
    if (geometry.mirrored) {
        drawSingleCopy(geometry.mirrored, false);
    }

    const routeBounds = L.latLngBounds(geometry.points);
    if (geometry.currentLatLng) {
        routeBounds.extend(geometry.currentLatLng);
    }
    if (routeBounds.isValid()) {
        map.fitBounds(routeBounds, { padding: [50, 50], maxZoom: 6 });
    }

    attachContainerResizeObserver(el, map);

    return map;
}
