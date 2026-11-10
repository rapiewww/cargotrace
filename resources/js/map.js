import L from 'leaflet';

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
 * Initialize Dark World Map on Dashboard or Landing Page
 */
export function initWorldMap(containerId, options = {}) {
    const el = document.getElementById(containerId);
    if (!el) return null;

    const locations = options.locations || [];
    const shipments = options.shipments || [];
    const interactive = options.interactive !== false;

    const map = L.map(containerId, {
        center: options.center || [22, 18],
        zoom: options.zoom || 2,
        minZoom: 2,
        maxZoom: 7,
        zoomControl: interactive,
        attributionControl: false,
        scrollWheelZoom: interactive ? 'center' : false,
    });

    // Dark Matter tile layer
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    const layerAll = L.layerGroup().addTo(map);
    const layerDelayed = L.layerGroup();
    const layerExceptions = L.layerGroup();

    // Plot locations with glowing teal pulse markers
    locations.forEach(loc => {
        if (!loc.latitude || !loc.longitude) return;

        const marker = L.circleMarker([loc.latitude, loc.longitude], {
            radius: 5,
            fillColor: '#2DD4BF',
            color: '#14B8A6',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.9
        }).addTo(map);

        marker.bindPopup(`
            <div style="font-family: 'Plus Jakarta Sans', sans-serif; padding: 4px; font-size: 11px; color: #0F172A;">
                <div style="font-weight: 800; color: #0F766E; font-size: 13px;">${loc.name}</div>
                <div style="font-family: monospace; color: #64748B; margin-top: 2px;">
                    UN/LOCODE: <b>${loc.unlocode}</b> &bull; ${loc.country}
                </div>
                <div style="margin-top: 4px; display: flex; justify-content: space-between; gap: 8px;">
                    <span>Type: <b>${loc.type}</b></span>
                    <span>Currency: <b>${loc.currency_code}</b></span>
                </div>
            </div>
        `);
    });

    // Plot shipment routes with Bezier arcs
    shipments.forEach(s => {
        const orig = s.origin_location || s.originLocation;
        const dest = s.destination_location || s.destinationLocation;

        if (orig && dest && orig.latitude && dest.latitude) {
            const start = [parseFloat(orig.latitude), parseFloat(orig.longitude)];
            const end = [parseFloat(dest.latitude), parseFloat(dest.longitude)];
            const curvePoints = getBezierCurve(start, end);

            const isDelayed = Boolean(s.is_delayed);
            const hasException = Boolean(s.has_open_exceptions || (s.exceptions && s.exceptions.length > 0));

            const strokeColor = hasException ? '#F43F5E' : (isDelayed ? '#F59E0B' : '#38BDF8');
            const dashArray = isDelayed ? '6, 6' : '4, 8';

            const polyline = L.polyline(curvePoints, {
                color: strokeColor,
                weight: 2.5,
                dashArray: dashArray,
                opacity: 0.85
            });

            // Hover and Click handler
            polyline.on('mouseover', function () {
                this.setStyle({ weight: 4, opacity: 1 });
            });
            polyline.on('mouseout', function () {
                this.setStyle({ weight: 2.5, opacity: 0.85 });
            });

            polyline.on('click', function () {
                if (options.onRouteClick) {
                    options.onRouteClick(s);
                } else {
                    const popupContent = `
                        <div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 11px; color: #0F172A; min-width: 180px;">
                            <div style="font-family: monospace; font-weight: 800; color: #0284C7; font-size: 12px;">${s.tracking_number}</div>
                            <div style="margin-top: 2px; color: #475569;">
                                ${orig.unlocode} &rarr; ${dest.unlocode} &bull; <b>${s.status}</b>
                            </div>
                            ${isDelayed ? `<div style="color: #D97706; font-weight: 700; margin-top: 4px;">Delayed: +${s.delay_hours || 0}h</div>` : ''}
                            <div style="margin-top: 6px;">
                                <a href="/shipments/${s.id}" style="color: #0F766E; font-weight: 700; text-decoration: underline;">View Full Manifest &rarr;</a>
                            </div>
                        </div>
                    `;
                    polyline.bindPopup(popupContent).openPopup();
                }
            });

            layerAll.addLayer(polyline);
            if (isDelayed) layerDelayed.addLayer(polyline);
            if (hasException) layerExceptions.addLayer(polyline);
        }
    });

    // Layer toggle helper
    window.__CARGOTRACE_MAP_TOGGLE__ = function(layerName) {
        map.removeLayer(layerAll);
        map.removeLayer(layerDelayed);
        map.removeLayer(layerExceptions);

        if (layerName === 'delayed') {
            map.addLayer(layerDelayed);
        } else if (layerName === 'exceptions') {
            map.addLayer(layerExceptions);
        } else {
            map.addLayer(layerAll);
        }
    };

    return map;
}

/**
 * Initialize single shipment route map (on Shipment Show view)
 */
export function initShipmentRouteMap(containerId, shipment) {
    const el = document.getElementById(containerId);
    if (!el || !shipment) return null;

    const orig = shipment.origin_location || shipment.originLocation;
    const dest = shipment.destination_location || shipment.destinationLocation;
    const curr = shipment.current_location || shipment.currentLocation;

    const map = L.map(containerId, {
        center: [20, 20],
        zoom: 3,
        zoomControl: true,
        attributionControl: false
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    const points = [];

    if (orig && orig.latitude && orig.longitude) {
        const oCoords = [parseFloat(orig.latitude), parseFloat(orig.longitude)];
        L.circleMarker(oCoords, {
            radius: 8,
            fillColor: '#2DD4BF',
            color: '#FFFFFF',
            weight: 2,
            fillOpacity: 1
        }).addTo(map).bindPopup(`<b>Origin: ${orig.name} (${orig.unlocode})</b>`);
        points.push(oCoords);
    }

    if (curr && curr.latitude && curr.longitude && curr.id !== orig?.id && curr.id !== dest?.id) {
        const cCoords = [parseFloat(curr.latitude), parseFloat(curr.longitude)];
        L.circleMarker(cCoords, {
            radius: 9,
            fillColor: '#F59E0B',
            color: '#FFFFFF',
            weight: 2,
            fillOpacity: 1
        }).addTo(map).bindPopup(`<b>Current Checkpoint: ${curr.name}</b>`);
        points.push(cCoords);
    }

    if (dest && dest.latitude && dest.longitude) {
        const dCoords = [parseFloat(dest.latitude), parseFloat(dest.longitude)];
        L.circleMarker(dCoords, {
            radius: 8,
            fillColor: '#10B981',
            color: '#FFFFFF',
            weight: 2,
            fillOpacity: 1
        }).addTo(map).bindPopup(`<b>Destination: ${dest.name} (${dest.unlocode})</b>`);
        points.push(dCoords);
    }

    if (points.length >= 2) {
        const polyline = L.polyline(points, {
            color: shipment.is_delayed ? '#F59E0B' : '#38BDF8',
            weight: 3.5,
            dashArray: '6, 8',
            opacity: 0.95
        }).addTo(map);

        map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
    }

    return map;
}
