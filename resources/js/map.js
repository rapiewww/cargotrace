import L from 'leaflet';

/**
 * Registry of all active Leaflet map instances for synchronized theme updates
 */
const activeMaps = new Set();

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
 * Normalize a longitude value into the standard [-180, 180] range.
 */
export function normalizeLongitude(lng) {
    let val = Number(lng);
    if (!Number.isFinite(val)) return 0;
    while (val > 180) val -= 360;
    while (val < -180) val += 360;
    return val;
}

/**
 * Unwrap a longitude relative to a reference longitude so shortest-path routes
 * across the +/-180 antimeridian stay continuous instead of wrapping across the whole map.
 */
export function unwrapLongitudeNear(lng, refLng) {
    let adjusted = Number(lng);
    const ref = Number(refLng);
    if (!Number.isFinite(adjusted) || !Number.isFinite(ref)) {
        return adjusted;
    }
    while (adjusted - ref > 180) adjusted -= 360;
    while (adjusted - ref < -180) adjusted += 360;
    return adjusted;
}

/**
 * Split an unwrapped [lat, lng] polyline at +/-180 antimeridian crossings so
 * Leaflet renders bounded segments inside [-180, 180] without horizontal wrap artifacts.
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
 * Compute a Mercator-bounded curved arc (SEA, AIR) or straight unwrapped segment (ROAD)
 * so long-haul routes (e.g. Indonesia to US East Coast) arc smoothly without shooting off the top pole.
 */
export function getGreatCircleArc(start, end, options = {}) {
    const lat1 = Number(start[0]);
    const lng1 = Number(start[1]);
    const lat2 = Number(end[0]);
    const rawLng2 = Number(end[1]);
    const mode = (options.mode || 'SEA').toUpperCase();
    const numPoints = options.numPoints || 64;

    const unwrappedEndLng = unwrapLongitudeNear(rawLng2, lng1);
    const dLng = unwrappedEndLng - lng1;

    if (mode === 'ROAD') {
        return [
            [lat1, lng1],
            [lat2, unwrappedEndLng],
        ];
    }

    const midLat = (lat1 + lat2) / 2;
    const chordDeg = Math.hypot(lat2 - lat1, dLng);
    const loftDeg = mode === 'AIR'
        ? Math.min(16, Math.max(2.5, chordDeg * 0.09))
        : Math.min(10, Math.max(1.5, chordDeg * 0.06));

    // Gently loft the arc without pushing high-latitude Mercator routes off the viewport top
    const direction = midLat < -12 ? -1 : 1;
    const controlLat = Math.max(-56, Math.min(64, midLat + direction * loftDeg));

    const points = [];
    for (let i = 0; i <= numPoints; i++) {
        const t = i / numPoints;
        const oneMinusT = 1 - t;
        const rawLat = oneMinusT * oneMinusT * lat1 + 2 * oneMinusT * t * controlLat + t * t * lat2;
        const boundedLat = Math.max(-64, Math.min(68, rawLat));
        const lng = lng1 + dLng * t;
        points.push([boundedLat, lng]);
    }

    return points;
}

/**
 * Calculate a curved great-circle style arc between two coordinates.
 * Preserved for existing callers (worldMap and shipment detail routeMap).
 */
export function getBezierCurve(start, end, numPoints = 50, mode = 'SEA') {
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

    // Remove existing Leaflet instance if attached
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
        worldCopyJump: leafletOptions.worldCopyJump !== undefined ? leafletOptions.worldCopyJump : false,
        scrollWheelZoom: leafletOptions.scrollWheelZoom !== undefined ? leafletOptions.scrollWheelZoom : false,
        ...leafletOptions,
    });

    el._leaflet_map = map;

    // Detect current theme
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
            // In case map was unmounted
            activeMaps.delete(map);
        }
    });
}

/**
 * Initialize Multi-Route World Map on Dashboard or Landing Page
 */
export function initWorldMap(containerId, options = {}) {
    const locations = options.locations || [];
    const shipments = options.shipments || [];
    const interactive = options.interactive !== false;

    const map = createMap(containerId, {
        center: options.center || [20, 22],
        zoom: options.zoom || 2,
        minZoom: 2,
        maxZoom: 18,
        maxBounds: [[-75, -180], [80, 180]],
        maxBoundsViscosity: 0.7,
        zoomControl: interactive,
        attributionControl: true,
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

    // Plot locations with clean markers
    locations.forEach(loc => {
        try {
            if (!loc.latitude || !loc.longitude) return;

            const marker = L.circleMarker([parseFloat(loc.latitude), parseFloat(loc.longitude)], {
                radius: 4.5,
                fillColor: '#2DD4BF',
                color: '#0D9488',
                weight: 1.5,
                opacity: 1,
                fillOpacity: 0.9,
            }).addTo(map);

            const locCode = loc.display_code || loc.code || loc.unlocode || '';
            const locType = (loc.type || '').toUpperCase();
            const locCodeType = loc.code_type_label || loc.code_type || (locType === 'AIRPORT' ? 'IATA' : ((locType === 'PORT' || locType === 'SEAPORT') ? 'UN/LOCODE' : 'Internal code'));

            marker.bindPopup(`
                <div style="font-family: 'Inter', sans-serif; font-size: 11px;">
                    <div style="font-weight: 700; color: #0D9488;">${loc.name}</div>
                    <div style="color: #64748B; margin-top: 2px;">${locCodeType}: <b>${locCode}</b> &bull; ${loc.country}</div>
                </div>
            `);
        } catch (err) {
            console.error(`[CargoTrace Map] Failed to render world location ${loc?.name || 'unknown'}:`, err);
        }
    });

    // Plot routes
    shipments.forEach(s => {
        try {
            const orig = s.origin_location || s.originLocation || s.origin;
            const dest = s.destination_location || s.destinationLocation || s.destination;

            const origLat = orig?.latitude || orig?.lat;
            const origLng = orig?.longitude || orig?.lng;
            const destLat = dest?.latitude || dest?.lat;
            const destLng = dest?.longitude || dest?.lng;

            if (origLat && destLat) {
                const start = [parseFloat(origLat), parseFloat(origLng)];
                const end = [parseFloat(destLat), parseFloat(destLng)];
                const mode = (s.transport_mode || s.carrier_mode || 'SEA').toUpperCase();

                // Route styling per mode:
                // Sea: solid curved arc
                // Air: dashed higher curve
                // Road: straight line
                const rawPoints = getBezierCurve(start, end, 64, mode);
                const polylinePoints = splitAntimeridianSegments(rawPoints);

                const isDelayed = Boolean(s.is_delayed);
                const hasException = Boolean(s.exceptions && s.exceptions.length > 0);

                let strokeColor;
                if (hasException) {
                    strokeColor = '#F43F5E';
                } else if (isDelayed) {
                    strokeColor = '#F59E0B';
                } else {
                    strokeColor = mode === 'AIR' ? '#0284C7' : (mode === 'ROAD' ? '#059669' : '#2563EB');
                }

                const dashArray = mode === 'AIR'
                    ? '6, 6'
                    : (mode === 'ROAD' ? null : (isDelayed ? '8, 6' : null));

                const polyline = L.polyline(polylinePoints, {
                    color: strokeColor,
                    weight: mode === 'ROAD' ? 2.8 : 2.4,
                    dashArray: dashArray,
                    opacity: 0.88,
                });

                const modeLabel = mode === 'AIR' ? 'Air' : (mode === 'ROAD' ? 'Road' : 'Sea');

                polyline.bindPopup(`
                    <div style="font-family: 'Inter', sans-serif; font-size: 11px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span style="font-weight: 700; color: #0284C7;">${s.tracking_number}</span>
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 9999px; background: rgba(2, 132, 199, 0.15);">${getModeSvg(mode, 11)} <span>${modeLabel}</span></span>
                        </div>
                        <div style="color: #64748B; margin-top: 4px;">${orig.name} &rarr; ${dest.name}</div>
                        <div style="margin-top: 4px; font-weight: 600; color: ${strokeColor};">
                            Status: ${s.status} ${isDelayed ? '(DELAYED)' : ''}
                        </div>
                    </div>
                `);

                polyline.addTo(layerAll);
                if (isDelayed) polyline.addTo(layerDelayed);
                if (hasException) polyline.addTo(layerExceptions);
                if (mode === 'SEA') polyline.addTo(layerSea);
                if (mode === 'AIR') polyline.addTo(layerAir);
                if (mode === 'ROAD') polyline.addTo(layerRoad);
            }
        } catch (err) {
            console.error(`[CargoTrace Map] Failed to render world route ${s?.tracking_number || 'unknown'}:`, err);
        }
    });

    return map;
}

/**
 * Initialize Single Shipment Route Map on Detail View
 */
export function initShipmentRouteMap(containerId, shipment) {
    if (!shipment) return null;

    const orig = shipment.origin_location || shipment.originLocation;
    const dest = shipment.destination_location || shipment.destinationLocation;
    const curr = shipment.current_location || shipment.currentLocation;

    const origLat = parseFloat(orig?.latitude || 0);
    const origLng = parseFloat(orig?.longitude || 0);
    const destLat = parseFloat(dest?.latitude || 0);
    const destLng = parseFloat(dest?.longitude || 0);

    if (!origLat || !destLat) return null;

    const midLat = (origLat + destLat) / 2;
    const midLng = (origLng + destLng) / 2;

    const map = createMap(containerId, {
        center: [midLat, midLng],
        zoom: 3,
        minZoom: 2,
        maxZoom: 18,
        zoomControl: true,
        attributionControl: true,
        scrollWheelZoom: false,
    });

    if (!map) return null;

    // Origin marker (Green)
    try {
        L.circleMarker([origLat, origLng], {
            radius: 6,
            fillColor: '#10B981',
            color: '#059669',
            weight: 2,
            fillOpacity: 1,
        }).addTo(map).bindPopup(`<b>Origin:</b> ${orig.name} (${orig.code || orig.display_code || orig.unlocode})`);
    } catch (err) {
        console.error('[CargoTrace Map] Failed to render shipment origin marker:', err);
    }

    // Destination marker (Blue)
    try {
        L.circleMarker([destLat, destLng], {
            radius: 6,
            fillColor: '#3B82F6',
            color: '#1D4ED8',
            weight: 2,
            fillOpacity: 1,
        }).addTo(map).bindPopup(`<b>Destination:</b> ${dest.name} (${dest.code || dest.display_code || dest.unlocode})`);
    } catch (err) {
        console.error('[CargoTrace Map] Failed to render shipment destination marker:', err);
    }

    // Current position marker if available (Amber pulse)
    if (curr?.latitude && curr?.longitude) {
        try {
            L.circleMarker([parseFloat(curr.latitude), parseFloat(curr.longitude)], {
                radius: 7,
                fillColor: '#F59E0B',
                color: '#B45309',
                weight: 2,
                fillOpacity: 1,
            }).addTo(map).bindPopup(`<b>Current Location:</b> ${curr.name} (${curr.code || curr.display_code || curr.unlocode})`).openPopup();
        } catch (err) {
            console.error('[CargoTrace Map] Failed to render shipment current marker:', err);
        }
    }

    // Route polyline per mode:
    // Sea: solid bezier arc
    // Air: dashed higher bezier arc
    // Road: straight direct line
    let routePolyline = null;
    try {
        const mode = (shipment.transport_mode || shipment.carrier_mode || 'SEA').toUpperCase();
        const isRoad = mode === 'ROAD';
        const isAir = mode === 'AIR';

        const rawPoints = getBezierCurve([origLat, origLng], [destLat, destLng], 64, mode);
        const curvePoints = splitAntimeridianSegments(rawPoints);
        const isDelayed = Boolean(shipment.is_delayed);
        const hasException = Boolean(shipment.exceptions && shipment.exceptions.length > 0);

        let strokeColor;
        if (hasException) {
            strokeColor = '#F43F5E';
        } else if (isDelayed) {
            strokeColor = '#F59E0B';
        } else {
            strokeColor = isAir ? '#0284C7' : (isRoad ? '#059669' : '#2563EB');
        }

        const dashArray = isAir ? '6, 6' : (isDelayed ? '8, 6' : null);

        routePolyline = L.polyline(curvePoints, {
            color: strokeColor,
            weight: isRoad ? 3.2 : 2.8,
            dashArray: dashArray,
            opacity: 0.9,
        }).addTo(map);
    } catch (err) {
        console.error('[CargoTrace Map] Failed to render shipment route polyline:', err);
    }

    // Fit map bounds to show full route
    const routeBounds = L.latLngBounds([[origLat, origLng], [destLat, destLng]]);
    if (routePolyline && routePolyline.getBounds().isValid()) {
        routeBounds.extend(routePolyline.getBounds());
    }
    map.fitBounds(routeBounds, { padding: [50, 50], maxZoom: 6 });

    return map;
}
