import Globe from 'globe.gl';
import L from 'leaflet';
import {
    createMap,
    getGreatCircleArc,
    getModeSvg,
    getSeverityColor,
    getSeverityLabel,
    normalizeLongitude,
    splitAntimeridianSegments,
    unwrapLongitudeNear,
} from './map';

/**
 * Shared State for 3D Globe & 2D Leaflet Map
 */
let globeInstance = null;
let globeContainerEl = null;
let leafletMapInstance = null;
let leafletContainerEl = null;
let leafletRouteLayer = null;
let currentMode = '3D'; // '3D' or '2D'
let activeFilter = 'ALL';
let mapDataUrl = null;
let initialDataset = { shipments: [], locations: [], meta: {} };
let currentDataset = { shipments: [], locations: [], meta: {} };
let routeRegistry = new Map();
let pendingFocusTracking = null;
let activeFetchController = null;
let windowListenersAttached = false;
let resizeTimer = null;

function isFiniteCoord(value) {
    return value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value));
}

function runLayerSafely(layerLabel, fn) {
    try {
        return fn();
    } catch (err) {
        console.error(`[CargoTrace Map] Failed to render ${layerLabel}:`, err);
        return null;
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function prefersReducedMotion() {
    return typeof window !== 'undefined'
        && window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function getCurrentTheme() {
    if (typeof document === 'undefined') return 'dark';
    return document.documentElement.getAttribute('data-theme')
        || (document.documentElement.classList.contains('light') ? 'light' : 'dark');
}

function normalizeDataset(options = {}) {
    if (options.initialData && Array.isArray(options.initialData.shipments)) {
        return {
            shipments: options.initialData.shipments || [],
            locations: options.initialData.locations || [],
            meta: options.initialData.meta || {},
        };
    }

    return {
        shipments: options.shipments || [],
        locations: options.locations || [],
        meta: options.meta || {},
    };
}

function getModeDisplay(mode) {
    const normalized = String(mode || 'SEA').toUpperCase();
    if (normalized === 'AIR') return { code: 'AIR', svg: getModeSvg('AIR', 11), label: 'Air' };
    if (normalized === 'ROAD') return { code: 'ROAD', svg: getModeSvg('ROAD', 11), label: 'Road' };
    return { code: 'SEA', svg: getModeSvg('SEA', 11), label: 'Sea' };
}

function getLocCode(loc) {
    return loc?.display_code || loc?.code || loc?.unlocode || '';
}

function getLocCodeType(loc) {
    if (loc?.code_type_label || loc?.code_type) {
        return loc.code_type_label || loc.code_type;
    }
    const type = (loc?.type || '').toUpperCase();
    if (type === 'AIRPORT') return 'IATA';
    if (type === 'PORT' || type === 'SEAPORT') return 'UN/LOCODE';
    return 'Internal code';
}

function updateEmptyState(isEmpty) {
    const emptyEl = document.getElementById('map-empty-state');
    if (!emptyEl) return;
    if (isEmpty) {
        emptyEl.classList.remove('hidden');
    } else {
        emptyEl.classList.add('hidden');
    }
}

function setLoadingState(isLoading) {
    const loadingEl = document.getElementById('map-loading-state');
    if (!loadingEl) return;
    if (isLoading) {
        loadingEl.classList.remove('hidden');
    } else {
        loadingEl.classList.add('hidden');
    }
}

function setErrorState(hasError) {
    const errorEl = document.getElementById('map-error-state');
    if (!errorEl) return;
    if (hasError) {
        errorEl.classList.remove('hidden');
    } else {
        errorEl.classList.add('hidden');
    }
}

function buildShipmentPopupHtml(shipment, pointRole, locationName, locationCode = '') {
    const modeInfo = getModeDisplay(shipment.mode || shipment.transport_mode);
    const hasException = Boolean(shipment.has_exception || (shipment.exceptions && shipment.exceptions.length > 0));
    const severityColor = getSeverityColor(shipment.delay_severity, hasException);
    const severityText = getSeverityLabel(shipment.delay_severity, hasException);
    const statusLabel = shipment.status_label || shipment.status || 'IN_TRANSIT';
    const lastEvent = shipment.latest_tracking;
    const lastEventTime = lastEvent?.occurred_at_formatted || lastEvent?.occurred_at || 'No events recorded';
    const lastEventLocation = lastEvent?.location_name || shipment.current?.name || '';
    const codeSuffix = locationCode ? ` (${escapeHtml(locationCode)})` : '';
    const detailUrl = shipment.url ? escapeHtml(shipment.url) : '#';

    return `
        <div style="font-family: 'Inter', sans-serif; font-size: 11px; min-width: 210px; max-width: 260px; line-height: 1.45;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;">
                <a href="${detailUrl}" style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #38BDF8; text-decoration: none;">
                    ${escapeHtml(shipment.tracking_number)}
                </a>
                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 9999px; background: rgba(37, 99, 235, 0.16); border: 1px solid rgba(56, 189, 248, 0.25); white-space: nowrap;">
                    ${modeInfo.svg} <span>${modeInfo.label}</span>
                </span>
            </div>
            <div style="font-weight: 600; margin-bottom: 4px;">
                <span style="opacity: 0.72;">${escapeHtml(pointRole)}:</span> ${escapeHtml(locationName)}${codeSuffix}
            </div>
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 4px;">
                <span style="font-weight: 600;">Status: ${escapeHtml(statusLabel)}</span>
                <span style="font-size: 10px; font-weight: 700; color: ${severityColor};">(${escapeHtml(severityText)})</span>
            </div>
            <div style="font-size: 10px; opacity: 0.8;">
                Last event: <span style="font-family: 'JetBrains Mono', monospace;">${escapeHtml(lastEventTime)}</span>
                ${lastEventLocation && pointRole !== 'Current location' ? `<div style="margin-top: 2px;">Checkpoint: ${escapeHtml(lastEventLocation)}</div>` : ''}
            </div>
        </div>
    `;
}

/**
 * Get image textures and atmosphere colors according to theme
 */
function getGlobeThemeConfig(theme) {
    if (theme === 'light') {
        return {
            globeImage: '/images/globe/earth-blue-marble.jpg',
            bumpImage: '/images/globe/earth-topology.png',
            atmosphereColor: '#38BDF8',
            atmosphereAltitude: 0.15,
            labelColor: 'rgba(15, 23, 42, 0.95)',
        };
    }
    return {
        globeImage: '/images/globe/earth-night.jpg',
        bumpImage: '/images/globe/earth-topology.png',
        atmosphereColor: '#2563EB',
        atmosphereAltitude: 0.18,
        labelColor: 'rgba(255, 255, 255, 0.85)',
    };
}

/**
 * Update 3D Globe texture when theme switches
 */
export function updateMaritimeGlobeTheme(theme) {
    if (!globeInstance) return;
    const cfg = getGlobeThemeConfig(theme);
    globeInstance
        .globeImageUrl(cfg.globeImage)
        .atmosphereColor(cfg.atmosphereColor)
        .atmosphereAltitude(cfg.atmosphereAltitude)
        .labelColor(() => cfg.labelColor);
}

/**
 * Render dataset onto the 3D Globe
 */
function renderGlobeData(dataset) {
    if (!globeInstance) return;

    const shipments = dataset.shipments || [];
    const locations = dataset.locations || [];

    const arcsData = [];
    const vesselMarkers = [];

    shipments.forEach(s => {
        runLayerSafely(`3D route ${s?.tracking_number || s?.id || 'unknown'}`, () => {
            const orig = s.origin_location || s.originLocation || s.origin;
            const dest = s.destination_location || s.destinationLocation || s.destination;

            const startLat = orig?.latitude ?? orig?.lat;
            const startLng = orig?.longitude ?? orig?.lng;
            const endLat = dest?.latitude ?? dest?.lat;
            const endLng = dest?.longitude ?? dest?.lng;

            if (!isFiniteCoord(startLat) || !isFiniteCoord(startLng) || !isFiniteCoord(endLat) || !isFiniteCoord(endLng)) {
                return;
            }

            const sLat = Number(startLat);
            const sLng = Number(startLng);
            const eLat = Number(endLat);
            const eLng = Number(endLng);

            const isDelayed = Boolean(s.is_delayed);
            const hasException = Boolean(s.has_exception || (s.exceptions && s.exceptions.length > 0));
            const modeInfo = getModeDisplay(s.mode || s.transport_mode || s.carrier_mode);
            const severityColor = getSeverityColor(s.delay_severity, hasException);

            const arcAltitude = modeInfo.code === 'AIR' ? 0.45 : (modeInfo.code === 'ROAD' ? 0.05 : 0.22);
            const arcDashLength = modeInfo.code === 'AIR' ? 0.3 : 1.0;
            const arcDashGap = modeInfo.code === 'AIR' ? 0.15 : 0;

            arcsData.push({
                shipmentId: s.id,
                trackingNumber: s.tracking_number,
                transportMode: modeInfo.code,
                carrier: s.carrier || 'Carrier',
                startLat: sLat,
                startLng: sLng,
                endLat: eLat,
                endLng: eLng,
                color: [severityColor, severityColor],
                altitude: arcAltitude,
                dashLength: arcDashLength,
                dashGap: arcDashGap,
                status: s.status_label || s.status,
                isDelayed,
                hasException,
            });

            const trackingPoint = s.latest_tracking || s.current;
            const markerLat = isFiniteCoord(trackingPoint?.lat) ? Number(trackingPoint.lat) : (sLat + eLat) / 2;
            const markerLng = isFiniteCoord(trackingPoint?.lng) ? Number(trackingPoint.lng) : (sLng + eLng) / 2;

            vesselMarkers.push({
                shipmentId: s.id,
                trackingNumber: s.tracking_number,
                transportMode: modeInfo.code,
                lat: markerLat,
                lng: markerLng,
                carrier: s.carrier || 'Freight',
                status: s.status_label || s.status,
                isDelayed,
                color: severityColor,
                name: `[${modeInfo.label}] ${s.tracking_number} (${getLocCode(orig) || 'ORI'} -> ${getLocCode(dest) || 'DST'})`,
            });
        });
    });

    const pointsData = [];
    locations.forEach(loc => {
        runLayerSafely(`3D location ${loc?.code || loc?.name || 'unknown'}`, () => {
            if (!isFiniteCoord(loc.latitude) || !isFiniteCoord(loc.longitude)) return;
            pointsData.push({
                id: loc.id,
                name: loc.name,
                code: getLocCode(loc),
                codeType: getLocCodeType(loc),
                unlocode: getLocCode(loc),
                country: loc.country,
                lat: Number(loc.latitude),
                lng: Number(loc.longitude),
                size: 0.8,
                color: '#2DD4BF',
            });
        });
    });

    const labelsData = pointsData.map(p => ({
        lat: p.lat,
        lng: p.lng,
        text: p.code,
        size: 1.1,
    }));

    runLayerSafely('3D globe layer commit', () => {
        globeInstance
            .arcsData(arcsData)
            .pointsData(pointsData)
            .labelsData(labelsData)
            .ringsData(vesselMarkers);
    });

    updateEmptyState(shipments.length === 0);
}

/**
 * Initialize 3D Earth Globe with Multimodal Routes & Locations
 */
export function initMaritimeGlobe(containerId, options = {}) {
    const el = document.getElementById(containerId);
    if (!el) return null;

    globeContainerEl = el;

    const shellEl = document.getElementById('dashboard-map-shell');
    mapDataUrl = options.dataUrl
        || shellEl?.getAttribute('data-map-data-url')
        || mapDataUrl;

    initialDataset = normalizeDataset(options);
    currentDataset = initialDataset;

    el.innerHTML = '';

    const themeCfg = getGlobeThemeConfig(getCurrentTheme());

    globeInstance = Globe()(el)
        .globeImageUrl(themeCfg.globeImage)
        .bumpImageUrl(themeCfg.bumpImage)
        .backgroundColor('rgba(0, 0, 0, 0)')
        .atmosphereColor(themeCfg.atmosphereColor)
        .atmosphereAltitude(themeCfg.atmosphereAltitude)
        .showAtmosphere(true)
        .arcColor('color')
        .arcAltitude('altitude')
        .arcStroke(1.8)
        .arcDashLength('dashLength')
        .arcDashGap('dashGap')
        .arcDashInitialGap(() => Math.random())
        .arcDashAnimateTime(prefersReducedMotion() ? 0 : 2200)
        .pointLat('lat')
        .pointLng('lng')
        .pointColor('color')
        .pointAltitude(0.015)
        .pointRadius(0.65)
        .pointLabel(d => `
            <div style="font-family: 'Inter', sans-serif; background: var(--bg-surface, #0B1728); border: 1px solid var(--border-color, rgba(255,255,255,0.1)); border-radius: 8px; padding: 6px 10px; color: var(--text-primary, #E6EEF8); font-size: 11px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                <div style="font-weight: 700; color: #2DD4BF;">${escapeHtml(d.name)}</div>
                <div style="color: var(--text-secondary, #8FA3BC); margin-top: 2px;">${escapeHtml(d.codeType)}: <b>${escapeHtml(d.code)}</b> &bull; ${escapeHtml(d.country)}</div>
            </div>
        `)
        .labelLat('lat')
        .labelLng('lng')
        .labelText('text')
        .labelSize('size')
        .labelDotRadius(0.3)
        .labelColor(() => themeCfg.labelColor)
        .labelResolution(2)
        .ringLat('lat')
        .ringLng('lng')
        .ringColor('color')
        .ringMaxRadius(2.2)
        .ringPropagationSpeed(prefersReducedMotion() ? 0 : 1.2)
        .ringRepeatPeriod(1800);

    renderGlobeData(currentDataset);

    globeInstance.pointOfView({ lat: 18.0, lng: 110.0, altitude: 2.2 }, 1000);

    const controls = globeInstance.controls();
    if (controls) {
        controls.autoRotate = !prefersReducedMotion();
        controls.autoRotateSpeed = 0.45;
        controls.enableZoom = true;

        controls.addEventListener('change', () => {
            if (currentMode === '3D') {
                const pov = globeInstance.pointOfView();
                updateMapCoordinates(pov.lat, pov.lng);
            }
        });
    }

    globeInstance.onPointClick(point => {
        if (controls) controls.autoRotate = false;
        globeInstance.pointOfView({ lat: point.lat, lng: point.lng, altitude: 1.5 }, 1200);
        updateMapCoordinates(point.lat, point.lng);
        highlightActivityRow(null, point.unlocode);
    });

    attachSharedListeners();

    setTimeout(() => {
        if (globeInstance && el.clientWidth) {
            globeInstance.width(el.clientWidth);
            globeInstance.height(el.clientHeight || 520);
        }
    }, 200);

    return globeInstance;
}

/**
 * Render dataset onto the 2D Leaflet map: unwrapped great-circle/road routes + origin, current, destination markers.
 */
function renderLeaflet2DData(dataset) {
    if (!leafletMapInstance || !leafletRouteLayer) return;

    leafletRouteLayer.clearLayers();
    routeRegistry.clear();

    const shipments = dataset.shipments || [];
    const isDark = getCurrentTheme() === 'dark';
    const markerRingColor = isDark ? '#0B1728' : '#FFFFFF';

    shipments.forEach(s => {
        runLayerSafely(`2D route ${s?.tracking_number || s?.id || 'unknown'}`, () => {
            const orig = s.origin_location || s.originLocation || s.origin;
            const dest = s.destination_location || s.destinationLocation || s.destination;

            const origLatRaw = orig?.latitude ?? orig?.lat;
            const origLngRaw = orig?.longitude ?? orig?.lng;
            const destLatRaw = dest?.latitude ?? dest?.lat;
            const destLngRaw = dest?.longitude ?? dest?.lng;

            if (!isFiniteCoord(origLatRaw) || !isFiniteCoord(origLngRaw) || !isFiniteCoord(destLatRaw) || !isFiniteCoord(destLngRaw)) {
                return;
            }

            const origLat = Number(origLatRaw);
            const origLng = Number(origLngRaw);
            const destLat = Number(destLatRaw);
            const destLng = unwrapLongitudeNear(Number(destLngRaw), origLng);

            const modeInfo = getModeDisplay(s.mode || s.transport_mode || s.carrier_mode);
            const hasException = Boolean(s.has_exception || (s.exceptions && s.exceptions.length > 0));
            const strokeColor = getSeverityColor(s.delay_severity, hasException);

            const points = getGreatCircleArc([origLat, origLng], [destLat, destLng], {
                mode: modeInfo.code,
                numPoints: 64,
            });
            const segments = splitAntimeridianSegments(points);

            // Route line styling per mode:
            // SEA: solid curved arc
            // AIR: dashed higher arc
            // ROAD: straight line
            const dashArray = modeInfo.code === 'AIR' ? '8, 6' : null;
            const weight = modeInfo.code === 'ROAD' ? 3.0 : (modeInfo.code === 'AIR' ? 2.4 : 2.6);

            const polyline = runLayerSafely(`polyline ${s.tracking_number}`, () => {
                const line = L.polyline(segments, {
                    color: strokeColor,
                    weight,
                    dashArray,
                    opacity: 0.9,
                }).addTo(leafletRouteLayer);
                const routeLabel = `${orig?.short_name || orig?.name || 'Origin'} -> ${dest?.short_name || dest?.name || 'Destination'}`;
                line.bindPopup(buildShipmentPopupHtml(s, 'Route', routeLabel));
                return line;
            });

            const originMarker = runLayerSafely(`origin marker ${s.tracking_number}`, () => {
                const marker = L.circleMarker([origLat, normalizeLongitude(origLng)], {
                    radius: 5,
                    fillColor: '#2563EB',
                    color: markerRingColor,
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.95,
                }).addTo(leafletRouteLayer);
                marker.bindPopup(buildShipmentPopupHtml(s, 'Origin', orig?.name || 'Origin', getLocCode(orig)));
                return marker;
            });

            const destinationMarker = runLayerSafely(`destination marker ${s.tracking_number}`, () => {
                const marker = L.circleMarker([destLat, normalizeLongitude(Number(destLngRaw))], {
                    radius: 5,
                    fillColor: '#0891B2',
                    color: markerRingColor,
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.95,
                }).addTo(leafletRouteLayer);
                marker.bindPopup(buildShipmentPopupHtml(s, 'Destination', dest?.name || 'Destination', getLocCode(dest)));
                return marker;
            });

            let currentMarker = null;
            const trackingPoint = s.latest_tracking || s.current;
            if (isFiniteCoord(trackingPoint?.lat) && isFiniteCoord(trackingPoint?.lng)) {
                currentMarker = runLayerSafely(`current marker ${s.tracking_number}`, () => {
                    const currLat = Number(trackingPoint.lat);
                    const currLng = normalizeLongitude(Number(trackingPoint.lng));

                    const marker = L.circleMarker([currLat, currLng], {
                        radius: 7,
                        fillColor: strokeColor,
                        color: markerRingColor,
                        weight: 2.5,
                        opacity: 1,
                        fillOpacity: 1,
                    }).addTo(leafletRouteLayer);

                    const currName = trackingPoint.location_name || trackingPoint.name || 'Latest checkpoint';
                    const currCode = trackingPoint.code || trackingPoint.unlocode || '';
                    marker.bindPopup(buildShipmentPopupHtml(s, 'Current location', currName, currCode));
                    return marker;
                });
            }

            const bounds = (polyline && polyline.getBounds && polyline.getBounds().isValid())
                ? polyline.getBounds()
                : L.latLngBounds([
                    [origLat, normalizeLongitude(origLng)],
                    [destLat, normalizeLongitude(Number(destLngRaw))],
                ]);
            if (currentMarker) {
                bounds.extend(currentMarker.getLatLng());
            }

            routeRegistry.set(s.tracking_number, {
                shipment: s,
                polyline,
                originMarker,
                destinationMarker,
                currentMarker,
                bounds,
            });
        });
    });

    updateEmptyState(shipments.length === 0);
}

/**
 * Fit Leaflet bounds to all visible routes when container is visible.
 */
function fitLeafletBounds() {
    if (!leafletMapInstance || !leafletContainerEl) return;
    if (leafletContainerEl.offsetWidth === 0 || leafletContainerEl.offsetHeight === 0) return;

    const entries = Array.from(routeRegistry.values());
    if (entries.length === 0) {
        leafletMapInstance.setView([15, 95], 2, { animate: false });
        return;
    }

    const combinedBounds = entries.reduce((acc, entry) => {
        return acc ? acc.extend(entry.bounds) : L.latLngBounds(entry.bounds.getSouthWest(), entry.bounds.getNorthEast());
    }, null);

    if (combinedBounds && combinedBounds.isValid()) {
        leafletMapInstance.fitBounds(combinedBounds, {
            padding: [48, 48],
            maxZoom: 6,
            animate: false,
        });
    }
}

/**
 * Initialize 2D Leaflet Routes Map inside the same container bounds
 */
export function initRoutes2DMap(containerId, options = {}) {
    const el = document.getElementById(containerId);
    if (!el) return null;

    leafletContainerEl = el;

    const shellEl = document.getElementById('dashboard-map-shell');
    mapDataUrl = options.dataUrl
        || el.getAttribute('data-map-data-url')
        || shellEl?.getAttribute('data-map-data-url')
        || mapDataUrl;

    const dataset = options.initialData || options.shipments ? normalizeDataset(options) : currentDataset;
    if (initialDataset.shipments.length === 0 && dataset.shipments.length > 0) {
        initialDataset = dataset;
    }
    currentDataset = dataset;

    const map = createMap(containerId, {
        center: [15, 95],
        zoom: 2,
        minZoom: 2,
        maxZoom: 18,
        maxBounds: [[-75, -180], [80, 180]],
        maxBoundsViscosity: 0.7,
        zoomControl: false,
        worldCopyJump: false,
        tileOverrides: {
            tileUrl: dataset.meta?.tile_url,
            attribution: dataset.meta?.tile_attribution,
        },
    });

    if (!map) return null;

    leafletMapInstance = map;
    window.CargoTraceLeafletMap = map;
    leafletRouteLayer = L.layerGroup().addTo(map);

    map.on('moveend', () => {
        if (currentMode === '2D') {
            const center = map.getCenter();
            updateMapCoordinates(center.lat, center.lng);
        }
    });

    map._onThemeChange = () => {
        renderLeaflet2DData(currentDataset);
        if (currentMode === '2D') {
            map.invalidateSize({ animate: false });
        }
    };

    renderLeaflet2DData(currentDataset);
    attachSharedListeners();

    return map;
}

function handleViewportResize() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (globeInstance && globeContainerEl && globeContainerEl.clientWidth) {
            globeInstance.width(globeContainerEl.clientWidth);
            globeInstance.height(globeContainerEl.clientHeight || 520);
        }
        if (currentMode === '2D' && leafletMapInstance && leafletContainerEl && leafletContainerEl.offsetWidth > 0) {
            leafletMapInstance.invalidateSize({ animate: false });
            fitLeafletBounds();
        }
    }, 120);
}

function attachSharedListeners() {
    if (windowListenersAttached || typeof window === 'undefined') return;
    windowListenersAttached = true;

    window.addEventListener('resize', handleViewportResize);
    window.addEventListener('cargotrace:sidebar-collapse', () => {
        setTimeout(handleViewportResize, 320);
    });
}

/**
 * Update the dynamic map center coordinates displayed on the left panel
 */
export function updateMapCoordinates(lat, lng) {
    const latEl = document.getElementById('map-coord-lat');
    const lngEl = document.getElementById('map-coord-lng');
    if (latEl && isFiniteCoord(lat)) latEl.textContent = Number(lat).toFixed(6);
    if (lngEl && isFiniteCoord(lng)) lngEl.textContent = Number(lng).toFixed(6);
}

function flyAndOpenLeafletEntry(entry) {
    if (!leafletMapInstance || !entry) return;

    leafletMapInstance.invalidateSize({ animate: false });
    const targetLayer = entry.currentMarker || entry.originMarker || entry.polyline;
    const animate = !prefersReducedMotion();

    let opened = false;
    const openTargetPopup = () => {
        if (opened) return;
        opened = true;
        if (targetLayer && typeof targetLayer.openPopup === 'function') {
            targetLayer.openPopup();
        }
    };

    leafletMapInstance.once('moveend', openTargetPopup);
    leafletMapInstance.flyToBounds(entry.bounds, {
        padding: [64, 64],
        maxZoom: 6,
        duration: animate ? 0.75 : 0,
        animate,
    });
    setTimeout(openTargetPopup, animate ? 850 : 50);
}

/**
 * Fly camera to a specific shipment route on 3D Globe and 2D Leaflet Map and open its popup
 */
export async function focusMaritimeEntity(lat, lng, trackingNumber = null) {
    highlightActivityRow(trackingNumber);

    if (isFiniteCoord(lat) && isFiniteCoord(lng)) {
        updateMapCoordinates(lat, lng);
    }

    if (globeInstance && isFiniteCoord(lat) && isFiniteCoord(lng)) {
        const controls = globeInstance.controls();
        if (controls) controls.autoRotate = false;
        globeInstance.pointOfView({ lat: Number(lat), lng: Number(lng), altitude: 1.6 }, prefersReducedMotion() ? 0 : 1000);
    }

    if (!leafletMapInstance) return;

    if (trackingNumber && !routeRegistry.has(trackingNumber)) {
        const existsInInitial = initialDataset.shipments.some(s => s.tracking_number === trackingNumber);
        if (existsInInitial && activeFilter !== 'ALL') {
            await setMapFilter('ALL');
        }
    }

    if (trackingNumber && routeRegistry.has(trackingNumber)) {
        const entry = routeRegistry.get(trackingNumber);
        if (currentMode === '2D') {
            flyAndOpenLeafletEntry(entry);
        } else {
            pendingFocusTracking = trackingNumber;
        }
        return;
    }

    if (currentMode === '2D' && isFiniteCoord(lat) && isFiniteCoord(lng)) {
        leafletMapInstance.invalidateSize({ animate: false });
        leafletMapInstance.flyTo([Number(lat), Number(lng)], 5, { animate: !prefersReducedMotion() });
    }
}

/**
 * Highlight corresponding row in Freight Activities table
 */
function highlightActivityRow(trackingNumber = null) {
    const rows = document.querySelectorAll('.activity-row');
    rows.forEach(r => {
        r.classList.remove('bg-blue-600/15', 'border-blue-500/40');
        if (trackingNumber && r.getAttribute('data-tracking') === trackingNumber) {
            r.classList.add('bg-blue-600/15', 'border-blue-500/40');
            r.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'nearest' });
        }
    });
}

function updateFilterTabButtons(activeKey) {
    document.querySelectorAll('.map-filter-tab').forEach(btn => {
        btn.className = 'px-2.5 py-1 rounded-xl font-semibold transition map-filter-tab theme-text-muted hover:theme-heading cursor-pointer inline-flex items-center gap-1';
        btn.setAttribute('aria-pressed', 'false');
    });

    const activeBtn = document.getElementById(`tab-filter-${activeKey.toLowerCase()}`);
    if (activeBtn) {
        activeBtn.className = 'px-2.5 py-1 rounded-xl font-semibold transition map-filter-tab bg-blue-600 text-white shadow-sm cursor-pointer inline-flex items-center gap-1';
        activeBtn.setAttribute('aria-pressed', 'true');
    }
}

function filterDatasetClientSide(dataset, key) {
    const shipments = (dataset.shipments || []).filter(s => {
        const mode = String(s.mode || s.transport_mode || 'SEA').toUpperCase();
        const hasException = Boolean(s.has_exception || (s.exceptions && s.exceptions.length > 0));
        if (key === 'SEA' || key === 'AIR' || key === 'ROAD') return mode === key;
        if (key === 'DELAYED') return Boolean(s.is_delayed);
        if (key === 'EXCEPTIONS') return hasException;
        return true;
    });

    return {
        shipments,
        locations: dataset.locations || [],
        meta: dataset.meta || {},
    };
}

/**
 * Filter both 3D Globe and 2D Leaflet Map via the role-scoped map-data endpoint.
 */
export async function setMapFilter(filterKey) {
    const key = String(filterKey || 'ALL').toUpperCase();
    activeFilter = key;
    updateFilterTabButtons(key);
    setErrorState(false);

    if (!mapDataUrl) {
        currentDataset = filterDatasetClientSide(initialDataset, key);
        renderGlobeData(currentDataset);
        renderLeaflet2DData(currentDataset);
        if (currentMode === '2D' && leafletMapInstance) {
            leafletMapInstance.invalidateSize({ animate: false });
            fitLeafletBounds();
        }
        return currentDataset;
    }

    if (activeFetchController) {
        activeFetchController.abort();
    }
    activeFetchController = new AbortController();

    const params = new URLSearchParams();
    if (key === 'SEA' || key === 'AIR' || key === 'ROAD') {
        params.set('mode', key);
    } else if (key === 'DELAYED' || key === 'EXCEPTIONS') {
        params.set('filter', key);
    }

    const queryString = params.toString();
    const url = queryString ? `${mapDataUrl}?${queryString}` : mapDataUrl;

    setLoadingState(true);

    try {
        const res = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: activeFetchController.signal,
        });

        if (!res.ok) {
            throw new Error(`Map data request failed (${res.status})`);
        }

        const json = await res.json();
        const payload = json && json.data ? json.data : json;
        currentDataset = normalizeDataset({ initialData: payload });

        renderGlobeData(currentDataset);
        renderLeaflet2DData(currentDataset);

        if (currentMode === '2D' && leafletMapInstance) {
            leafletMapInstance.invalidateSize({ animate: false });
            fitLeafletBounds();
        }
    } catch (err) {
        if (err && err.name === 'AbortError') {
            return currentDataset;
        }
        setErrorState(true);
        currentDataset = filterDatasetClientSide(initialDataset, key);
        renderGlobeData(currentDataset);
        renderLeaflet2DData(currentDataset);
        if (currentMode === '2D' && leafletMapInstance) {
            leafletMapInstance.invalidateSize({ animate: false });
            fitLeafletBounds();
        }
    } finally {
        setLoadingState(false);
    }

    return currentDataset;
}

/**
 * Switch between 3D Globe and 2D Leaflet Map
 */
export function setMapMode(mode) {
    currentMode = mode === '2D' ? '2D' : '3D';
    const globe3dEl = document.getElementById('globe-3d-canvas');
    const map2dEl = document.getElementById('globe-2d-canvas');
    const btn3d = document.getElementById('btn-mode-3d');
    const btn2d = document.getElementById('btn-mode-2d');

    if (currentMode === '3D') {
        if (globe3dEl) globe3dEl.classList.remove('hidden');
        if (map2dEl) map2dEl.classList.add('hidden');

        if (btn3d) {
            btn3d.className = 'px-3.5 py-1 rounded-full text-xs font-semibold bg-blue-600 text-white shadow-md transition-all flex items-center gap-1.5 cursor-pointer';
            btn3d.setAttribute('aria-pressed', 'true');
        }
        if (btn2d) {
            btn2d.className = 'px-3.5 py-1 rounded-full text-xs font-semibold theme-text-muted hover:text-cyan-400 transition-all flex items-center gap-1.5 cursor-pointer';
            btn2d.setAttribute('aria-pressed', 'false');
        }

        if (globeInstance) {
            const controls = globeInstance.controls();
            if (controls) controls.autoRotate = !prefersReducedMotion();
            const pov = globeInstance.pointOfView();
            updateMapCoordinates(pov.lat, pov.lng);
        }
    } else {
        if (globe3dEl) globe3dEl.classList.add('hidden');
        if (map2dEl) map2dEl.classList.remove('hidden');

        if (btn3d) {
            btn3d.className = 'px-3.5 py-1 rounded-full text-xs font-semibold theme-text-muted hover:text-cyan-400 transition-all flex items-center gap-1.5 cursor-pointer';
            btn3d.setAttribute('aria-pressed', 'false');
        }
        if (btn2d) {
            btn2d.className = 'px-3.5 py-1 rounded-full text-xs font-semibold bg-blue-600 text-white shadow-md transition-all flex items-center gap-1.5 cursor-pointer';
            btn2d.setAttribute('aria-pressed', 'true');
        }

        if (globeInstance) {
            const controls = globeInstance.controls();
            if (controls) controls.autoRotate = false;
        }

        if (leafletMapInstance) {
            const refreshVisibleMap = () => {
                leafletMapInstance.invalidateSize({ animate: false });
                if (pendingFocusTracking && routeRegistry.has(pendingFocusTracking)) {
                    const entry = routeRegistry.get(pendingFocusTracking);
                    pendingFocusTracking = null;
                    flyAndOpenLeafletEntry(entry);
                } else {
                    fitLeafletBounds();
                }
                const center = leafletMapInstance.getCenter();
                updateMapCoordinates(center.lat, center.lng);
            };

            requestAnimationFrame(refreshVisibleMap);
            setTimeout(() => {
                if (currentMode === '2D' && leafletMapInstance) {
                    leafletMapInstance.invalidateSize({ animate: false });
                }
            }, 120);
        }
    }
}

/**
 * Zoom In / Zoom Out controls
 */
export function zoomMap(direction) {
    if (currentMode === '3D' && globeInstance) {
        const pov = globeInstance.pointOfView();
        const delta = direction === 'in' ? -0.4 : 0.4;
        const newAltitude = Math.max(0.6, Math.min(3.5, pov.altitude + delta));
        globeInstance.pointOfView({ altitude: newAltitude }, prefersReducedMotion() ? 0 : 400);
    } else if (leafletMapInstance) {
        if (direction === 'in') {
            leafletMapInstance.zoomIn();
        } else {
            leafletMapInstance.zoomOut();
        }
    }
}
