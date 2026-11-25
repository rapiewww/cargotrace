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
 * Compute a great-circle style arc (SEA, AIR) or straight unwrapped segment (ROAD)
 * with continuous longitude unwrapping at the antimeridian.
 */
export function getGreatCircleArc(start, end, options = {}) {
    const lat1 = Number(start[0]);
    const lng1 = Number(start[1]);
    const lat2 = Number(end[0]);
    const rawLng2 = Number(end[1]);
    const mode = (options.mode || 'SEA').toUpperCase();
    const numPoints = options.numPoints || 64;

    const unwrappedEndLng = unwrapLongitudeNear(rawLng2, lng1);

    if (mode === 'ROAD') {
        return [
            [lat1, lng1],
            [lat2, unwrappedEndLng],
        ];
    }

    const toRad = (deg) => (deg * Math.PI) / 180;
    const toDeg = (rad) => (rad * 180) / Math.PI;

    const phi1 = toRad(lat1);
    const lam1 = toRad(lng1);
    const phi2 = toRad(lat2);
    const lam2 = toRad(unwrappedEndLng);

    const x1 = Math.cos(phi1) * Math.cos(lam1);
    const y1 = Math.cos(phi1) * Math.sin(lam1);
    const z1 = Math.sin(phi1);

    const x2 = Math.cos(phi2) * Math.cos(lam2);
    const y2 = Math.cos(phi2) * Math.sin(lam2);
    const z2 = Math.sin(phi2);

    const dot = Math.max(-1, Math.min(1, x1 * x2 + y1 * y2 + z1 * z2));
    const omega = Math.acos(dot);
    const sinOmega = Math.sin(omega);

    const chordDeg = Math.hypot(lat2 - lat1, unwrappedEndLng - lng1);
    const loftDeg = mode === 'AIR'
        ? Math.min(18, Math.max(4, chordDeg * 0.14))
        : Math.min(7, Math.max(1.5, chordDeg * 0.05));

    const points = [];
    let prevLng = lng1;

    for (let i = 0; i <= numPoints; i++) {
        const t = i / numPoints;
        let lat;
        let lng;

        if (sinOmega < 1e-6) {
            lat = lat1 + (lat2 - lat1) * t;
            lng = lng1 + (unwrappedEndLng - lng1) * t;
        } else {
            const a = Math.sin((1 - t) * omega) / sinOmega;
            const b = Math.sin(t * omega) / sinOmega;
            const x = a * x1 + b * x2;
            const y = a * y1 + b * y2;
            const z = a * z1 + b * z2;

            lat = toDeg(Math.atan2(z, Math.sqrt(x * x + y * y)));
            lng = toDeg(Math.atan2(y, x));
        }

        lng = unwrapLongitudeNear(lng, prevLng);
        prevLng = lng;

        // Add subtle mode-specific arc loft while anchoring endpoints
        const liftedLat = Math.max(-82, Math.min(82, lat + Math.sin(Math.PI * t) * loftDeg));
        points.push([liftedLat, lng]);
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
        center: options.center || [22, 18],
        zoom: options.zoom || 2,
        minZoom: 2,
        maxZoom: 18,
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
    });

    // Plot routes
    shipments.forEach(s => {
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
            // Sea: solid arc
            // Air: dashed higher curve
            // Road: straight line
            const points = mode === 'ROAD' ? [start, end] : getBezierCurve(start, end);

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

            const polyline = L.polyline(points, {
                color: strokeColor,
                weight: mode === 'ROAD' ? 2.8 : 2.4,
                dashArray: dashArray,
                opacity: 0.88,
            });

            const modeBadgeText = mode === 'AIR' ? '✈️ Air' : (mode === 'ROAD' ? '🚚 Road' : '🚢 Sea');

            polyline.bindPopup(`
                <div style="font-family: 'Inter', sans-serif; font-size: 11px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <span style="font-weight: 700; color: #0284C7;">${s.tracking_number}</span>
                        <span style="font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 9999px; background: rgba(2, 132, 199, 0.15);">${modeBadgeText}</span>
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
    L.circleMarker([origLat, origLng], {
        radius: 6,
        fillColor: '#10B981',
        color: '#059669',
        weight: 2,
        fillOpacity: 1,
    }).addTo(map).bindPopup(`<b>Origin:</b> ${orig.name} (${orig.code || orig.display_code || orig.unlocode})`);

    // Destination marker (Blue)
    L.circleMarker([destLat, destLng], {
        radius: 6,
        fillColor: '#3B82F6',
        color: '#1D4ED8',
        weight: 2,
        fillOpacity: 1,
    }).addTo(map).bindPopup(`<b>Destination:</b> ${dest.name} (${dest.code || dest.display_code || dest.unlocode})`);

    // Current position marker if available (Amber pulse)
    if (curr?.latitude && curr?.longitude) {
        L.circleMarker([parseFloat(curr.latitude), parseFloat(curr.longitude)], {
            radius: 7,
            fillColor: '#F59E0B',
            color: '#B45309',
            weight: 2,
            fillOpacity: 1,
        }).addTo(map).bindPopup(`<b>Current Location:</b> ${curr.name} (${curr.code || curr.display_code || curr.unlocode})`).openPopup();
    }

    // Route polyline per mode:
    // Sea: solid bezier arc
    // Air: dashed higher bezier arc
    // Road: straight direct line
    const mode = (shipment.transport_mode || shipment.carrier_mode || 'SEA').toUpperCase();
    const isRoad = mode === 'ROAD';
    const isAir = mode === 'AIR';

    const curvePoints = isRoad ? [[origLat, origLng], [destLat, destLng]] : getBezierCurve([origLat, origLng], [destLat, destLng]);
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

    L.polyline(curvePoints, {
        color: strokeColor,
        weight: isRoad ? 3.2 : 2.8,
        dashArray: dashArray,
        opacity: 0.9,
    }).addTo(map);

    // Fit map bounds to show full route
    map.fitBounds([[origLat, origLng], [destLat, destLng]], { padding: [50, 50] });

    return map;
}
