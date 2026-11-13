import L from 'leaflet';

/**
 * Registry of all active Leaflet map instances for synchronized theme updates
 */
const activeMaps = new Set();

/**
 * Get OpenStreetMap tile layer configuration with theme adaptability and configurable tile source
 */
export function getTileLayerConfig(theme = 'dark') {
    const metaUrl = typeof document !== 'undefined' ? document.querySelector('meta[name="map-tile-url"]')?.getAttribute('content') : null;
    const metaAttribution = typeof document !== 'undefined' ? document.querySelector('meta[name="map-tile-attribution"]')?.getAttribute('content') : null;

    return {
        url: metaUrl || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        options: {
            attribution: metaAttribution || '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
            maxZoom: 18,
        }
    };
}

/**
 * Calculate a quadratic Bezier curve between two coordinates.
 * Generates an array of [lat, lng] points creating an elegant great-circle style arc.
 */
export function getBezierCurve(start, end, numPoints = 50) {
    const lat1 = start[0];
    const lon1 = start[1];
    const lat2 = end[0];
    const lon2 = end[1];

    const midLat = (lat1 + lat2) / 2;
    const midLon = (lon1 + lon2) / 2;

    const dLat = lat2 - lat1;
    const dLon = lon2 - lon1;
    const distance = Math.sqrt(dLat * dLat + dLon * dLon);

    // Dynamic curve apex displacement based on chord length
    const curvature = Math.min(0.25, Math.max(0.12, distance * 0.003));
    const controlLat = midLat + (-dLon * curvature);
    const controlLon = midLon + (dLat * curvature);

    const points = [];
    for (let i = 0; i <= numPoints; i++) {
        const t = i / numPoints;
        const lat = (1 - t) * (1 - t) * lat1 + 2 * (1 - t) * t * controlLat + t * t * lat2;
        const lon = (1 - t) * (1 - t) * lon1 + 2 * (1 - t) * t * controlLon + t * t * lon2;
        points.push([lat, lon]);
    }
    return points;
}

/**
 * Shared map factory using CartoDB Positron / Dark Matter tiles matching active theme.
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

    const map = L.map(elementId, {
        center: options.center || [22, 18],
        zoom: options.zoom || 2,
        minZoom: options.minZoom || 2,
        maxZoom: options.maxZoom || 18,
        zoomControl: options.zoomControl !== false,
        attributionControl: options.attributionControl !== false,
        scrollWheelZoom: options.scrollWheelZoom !== undefined ? options.scrollWheelZoom : false,
        ...options,
    });

    el._leaflet_map = map;

    // Detect current theme
    const currentTheme = document.documentElement.getAttribute('data-theme')
        || (document.documentElement.classList.contains('light') ? 'light' : 'dark');

    const tileConfig = getTileLayerConfig(currentTheme);
    const tileLayer = L.tileLayer(tileConfig.url, tileConfig.options).addTo(map);

    map._activeTileLayer = tileLayer;
    activeMaps.add(map);

    return map;
}

/**
 * Update all active Leaflet maps to match the newly switched theme
 */
export function updateAllMapsTheme(theme) {
    const tileConfig = getTileLayerConfig(theme);

    activeMaps.forEach(map => {
        try {
            if (map && map._activeTileLayer) {
                map.removeLayer(map._activeTileLayer);
                const newLayer = L.tileLayer(tileConfig.url, tileConfig.options).addTo(map);
                map._activeTileLayer = newLayer;
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

        marker.bindPopup(`
            <div style="font-family: 'Inter', sans-serif; font-size: 11px;">
                <div style="font-weight: 700; color: #0D9488;">${loc.name}</div>
                <div style="color: #64748B; margin-top: 2px;">UN/LOCODE: <b>${loc.unlocode}</b> &bull; ${loc.country}</div>
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
            const curvePoints = getBezierCurve(start, end);

            const isDelayed = Boolean(s.is_delayed);
            const hasException = Boolean(s.exceptions && s.exceptions.length > 0);

            const strokeColor = hasException ? '#F43F5E' : (isDelayed ? '#F59E0B' : '#0284C7');
            const polyline = L.polyline(curvePoints, {
                color: strokeColor,
                weight: 2.2,
                dashArray: isDelayed ? '6, 6' : '2, 6',
                opacity: 0.85,
            });

            polyline.bindPopup(`
                <div style="font-family: 'Inter', sans-serif; font-size: 11px;">
                    <div style="font-weight: 700; color: #0284C7;">${s.tracking_number}</div>
                    <div style="color: #64748B; margin-top: 2px;">${orig.name} &rarr; ${dest.name}</div>
                    <div style="margin-top: 4px; font-weight: 600; color: ${strokeColor};">
                        Status: ${s.status} ${isDelayed ? '(DELAYED)' : ''}
                    </div>
                </div>
            `);

            polyline.addTo(layerAll);
            if (isDelayed) polyline.addTo(layerDelayed);
            if (hasException) polyline.addTo(layerExceptions);
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
    }).addTo(map).bindPopup(`<b>Origin:</b> ${orig.name} (${orig.unlocode})`);

    // Destination marker (Blue)
    L.circleMarker([destLat, destLng], {
        radius: 6,
        fillColor: '#3B82F6',
        color: '#1D4ED8',
        weight: 2,
        fillOpacity: 1,
    }).addTo(map).bindPopup(`<b>Destination:</b> ${dest.name} (${dest.unlocode})`);

    // Current position marker if available (Amber pulse)
    if (curr?.latitude && curr?.longitude) {
        L.circleMarker([parseFloat(curr.latitude), parseFloat(curr.longitude)], {
            radius: 7,
            fillColor: '#F59E0B',
            color: '#B45309',
            weight: 2,
            fillOpacity: 1,
        }).addTo(map).bindPopup(`<b>Current Location:</b> ${curr.name} (${curr.unlocode})`).openPopup();
    }

    // Great circle bezier arc
    const curvePoints = getBezierCurve([origLat, origLng], [destLat, destLng]);
    const isDelayed = Boolean(shipment.is_delayed);
    const hasException = Boolean(shipment.exceptions && shipment.exceptions.length > 0);
    const strokeColor = hasException ? '#F43F5E' : (isDelayed ? '#F59E0B' : '#0284C7');

    L.polyline(curvePoints, {
        color: strokeColor,
        weight: 3,
        dashArray: isDelayed ? '8, 6' : null,
        opacity: 0.9,
    }).addTo(map);

    // Fit map bounds to show full route
    map.fitBounds([[origLat, origLng], [destLat, destLng]], { padding: [50, 50] });

    return map;
}
