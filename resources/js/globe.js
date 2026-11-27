import Globe from 'globe.gl';
import L from 'leaflet';
import {
    computeGlobeArcAltitude,
    computeRouteGeometry,
    hexToRgba,
    isValidCoordinatePair,
    normalizeMapDataset,
} from './geo';
import {
    createMap,
    getModeSvg,
    getSeverityColor,
    getSeverityLabel,
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
let initialDataset = { routes: [], shipments: [], locations: [], meta: {} };
let currentDataset = { routes: [], shipments: [], locations: [], meta: {} };
let routeRegistry = new Map();
let pendingFocusTracking = null;
let activeFetchController = null;
let windowListenersAttached = false;
let resizeTimer = null;
let containerResizeObserver = null;
let resizeRafId = null;

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

function getModeDisplay(mode) {
    const normalized = String(mode || 'SEA').toUpperCase();
    if (normalized === 'AIR') return { code: 'AIR', svg: getModeSvg('AIR', 11), label: 'Air' };
    if (normalized === 'ROAD') return { code: 'ROAD', svg: getModeSvg('ROAD', 11), label: 'Road' };
    return { code: 'SEA', svg: getModeSvg('SEA', 11), label: 'Sea' };
}

function getLocCode(loc) {
    return loc?.display_code || loc?.code || loc?.unlocode || '';
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

function buildShipmentPopupHtml(route, pointRole, locationName, locationCode = '') {
    const raw = route.raw || route;
    const modeInfo = getModeDisplay(route.mode);
    const severityColor = getSeverityColor(route.delay_severity, route.has_exception);
    const severityText = getSeverityLabel(route.delay_severity, route.has_exception);
    const statusLabel = route.status_label || route.status || 'IN_TRANSIT';
    const lastEvent = raw.latest_tracking;
    const lastEventTime = lastEvent?.occurred_at_formatted || lastEvent?.occurred_at || 'No events recorded';
    const lastEventLocation = lastEvent?.location_name || route.currentMeta?.name || '';
    const codeSuffix = locationCode ? ` (${escapeHtml(locationCode)})` : '';
    const detailUrl = route.url ? escapeHtml(route.url) : '#';

    return `
        <div style="font-family: 'Inter', sans-serif; font-size: 11px; min-width: 210px; max-width: 260px; line-height: 1.45;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;">
                <a href="${detailUrl}" style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #38BDF8; text-decoration: none;">
                    ${escapeHtml(route.tracking_number)}
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
            atmosphereAltitude: 0.14,
            labelColor: 'rgba(15, 23, 42, 0.95)',
        };
    }
    return {
        globeImage: '/images/globe/earth-night.jpg',
        bumpImage: '/images/globe/earth-topology.png',
        atmosphereColor: '#2563EB',
        atmosphereAltitude: 0.16,
        labelColor: 'rgba(255, 255, 255, 0.85)',
    };
}

/**
 * Update 3D Globe texture and arc opacity when theme switches
 */
export function updateMaritimeGlobeTheme(theme) {
    if (!globeInstance) return;
    const cfg = getGlobeThemeConfig(theme);
    globeInstance
        .globeImageUrl(cfg.globeImage)
        .atmosphereColor(cfg.atmosphereColor)
        .atmosphereAltitude(cfg.atmosphereAltitude)
        .labelColor(() => cfg.labelColor);

    renderGlobeData(currentDataset);
}

/**
 * Render normalized dataset onto the 3D Globe
 */
function renderGlobeData(dataset) {
    if (!globeInstance) return;

    const routes = dataset.routes || [];
    const locations = dataset.locations || [];
    const isLight = getCurrentTheme() === 'light';

    const arcsData = [];
    const vesselMarkers = [];

    routes.forEach(route => {
        runLayerSafely(`3D route ${route.tracking_number || route.id || 'unknown'}`, () => {
            const [sLat, sLng] = route.origin;
            const [eLat, eLng] = route.destination;

            const modeInfo = getModeDisplay(route.mode);
            const severityColor = getSeverityColor(route.delay_severity, route.has_exception);
            const severityText = getSeverityLabel(route.delay_severity, route.has_exception);
            const isAlert = route.is_delayed || route.has_exception;

            const arcAltitude = computeGlobeArcAltitude(route.mode, route.origin, route.destination);
            const arcStroke = route.mode === 'ROAD' ? 0.26 : (route.mode === 'AIR' ? 0.30 : 0.36);
            const arcDashLength = route.mode === 'AIR' ? 0.25 : 1.0;
            const arcDashGap = route.mode === 'AIR' ? 0.12 : 0;

            const startAlpha = isAlert ? (isLight ? 0.84 : 0.78) : (isLight ? 0.65 : 0.55);
            const endAlpha = isAlert ? (isLight ? 0.98 : 0.95) : (isLight ? 0.85 : 0.78);

            const origCode = getLocCode(route.originMeta) || 'ORI';
            const destCode = getLocCode(route.destinationMeta) || 'DST';

            arcsData.push({
                shipmentId: route.id,
                trackingNumber: route.tracking_number,
                transportMode: modeInfo.code,
                modeLabel: modeInfo.label,
                carrier: route.carrier || 'Carrier',
                origLabel: `${route.originMeta?.short_name || route.originMeta?.name || 'Origin'} (${origCode})`,
                destLabel: `${route.destinationMeta?.short_name || route.destinationMeta?.name || 'Destination'} (${destCode})`,
                startLat: sLat,
                startLng: sLng,
                endLat: eLat,
                endLng: eLng,
                color: [hexToRgba(severityColor, startAlpha), hexToRgba(severityColor, endAlpha)],
                altitude: arcAltitude,
                stroke: arcStroke,
                dashLength: arcDashLength,
                dashGap: arcDashGap,
                status: route.status_label,
                severityText,
                severityColor,
                isDelayed: route.is_delayed,
                hasException: route.has_exception,
            });

            if (route.current && isValidCoordinatePair(route.current[0], route.current[1])) {
                vesselMarkers.push({
                    shipmentId: route.id,
                    trackingNumber: route.tracking_number,
                    transportMode: modeInfo.code,
                    lat: route.current[0],
                    lng: route.current[1],
                    carrier: route.carrier || 'Freight',
                    status: route.status_label,
                    isDelayed: route.is_delayed,
                    color: severityColor,
                    name: `[${modeInfo.label}] ${route.tracking_number} (${origCode} -> ${destCode})`,
                });
            }
        });
    });

    const pointsData = locations.map(loc => ({
        id: loc.id,
        name: loc.name,
        code: loc.code,
        codeType: loc.codeType,
        unlocode: loc.unlocode,
        country: loc.country,
        lat: loc.lat,
        lng: loc.lng,
        size: 0.42,
        color: '#2DD4BF',
    }));

    const labelsData = pointsData.map(p => ({
        lat: p.lat,
        lng: p.lng,
        text: p.code,
        size: 0.95,
    }));

    runLayerSafely('3D globe layer commit', () => {
        globeInstance
            .arcsData(arcsData)
            .pointsData(pointsData)
            .labelsData(labelsData)
            .ringsData(vesselMarkers);
    });

    updateEmptyState(routes.length === 0);
}

/**
 * Resize and recenter 3D Globe and 2D Leaflet cleanly when the sidebar or window changes size.
 */
function syncMapContainerSize() {
    const shellEl = document.getElementById('dashboard-map-shell');
    const measureEl = shellEl || globeContainerEl || leafletContainerEl;
    if (!measureEl) return;

    const width = measureEl.clientWidth;
    const height = measureEl.clientHeight || 520;
    if (!width || width <= 0) return;

    if (globeInstance && globeContainerEl) {
        const targetWidth = globeContainerEl.clientWidth || width;
        const targetHeight = globeContainerEl.clientHeight || height;
        if (targetWidth > 0) {
            globeInstance.width(targetWidth);
            globeInstance.height(targetHeight);
            const controls = globeInstance.controls();
            if (controls && typeof controls.update === 'function') {
                controls.update();
            }
        }
    }

    if (currentMode === '2D' && leafletMapInstance && leafletContainerEl && leafletContainerEl.offsetWidth > 0) {
        leafletMapInstance.invalidateSize({ animate: false });
    }
}

function attachContainerResizeObserver() {
    if (typeof ResizeObserver === 'undefined') return;
    const targetEl = document.getElementById('dashboard-map-shell') || globeContainerEl || leafletContainerEl;
    if (!targetEl) return;

    if (containerResizeObserver) {
        containerResizeObserver.disconnect();
    }

    containerResizeObserver = new ResizeObserver(() => {
        if (resizeRafId) {
            cancelAnimationFrame(resizeRafId);
        }
        resizeRafId = requestAnimationFrame(() => {
            syncMapContainerSize();
        });
    });

    containerResizeObserver.observe(targetEl);
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

    initialDataset = normalizeMapDataset(options);
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
        .arcAltitudeAutoScale(0.22)
        .arcStroke('stroke')
        .arcCurveResolution(64)
        .arcDashLength('dashLength')
        .arcDashGap('dashGap')
        .arcDashInitialGap(() => Math.random())
        .arcDashAnimateTime(prefersReducedMotion() ? 0 : 3600)
        .arcLabel(d => `
            <div style="font-family: 'Inter', sans-serif; background: var(--bg-surface, #0B1728); border: 1px solid var(--border-color, rgba(255,255,255,0.1)); border-radius: 8px; padding: 6px 10px; color: var(--text-primary, #E6EEF8); font-size: 11px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                <div style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #38BDF8;">${escapeHtml(d.trackingNumber)} &bull; ${escapeHtml(d.modeLabel)}</div>
                <div style="margin-top: 2px;">${escapeHtml(d.origLabel)} &rarr; ${escapeHtml(d.destLabel)}</div>
                <div style="margin-top: 2px; font-weight: 600; color: ${d.severityColor};">${escapeHtml(d.status)} (${escapeHtml(d.severityText)})</div>
            </div>
        `)
        .pointLat('lat')
        .pointLng('lng')
        .pointColor('color')
        .pointAltitude(0.004)
        .pointRadius(0.42)
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
        .labelDotRadius(0.25)
        .labelColor(() => themeCfg.labelColor)
        .labelResolution(2)
        .ringLat('lat')
        .ringLng('lng')
        .ringColor('color')
        .ringAltitude(0.003)
        .ringMaxRadius(1.6)
        .ringPropagationSpeed(prefersReducedMotion() ? 0 : 1.0)
        .ringRepeatPeriod(2000);

    renderGlobeData(currentDataset);

    globeInstance.pointOfView({ lat: 18.0, lng: 110.0, altitude: 2.2 }, prefersReducedMotion() ? 0 : 1000);

    const controls = globeInstance.controls();
    if (controls) {
        controls.autoRotate = !prefersReducedMotion();
        controls.autoRotateSpeed = 0.4;
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
        globeInstance.pointOfView({ lat: point.lat, lng: point.lng, altitude: 1.5 }, prefersReducedMotion() ? 0 : 1200);
        updateMapCoordinates(point.lat, point.lng);
        highlightActivityRow(null, point.unlocode);
    });

    attachSharedListeners();
    attachContainerResizeObserver();

    requestAnimationFrame(syncMapContainerSize);
    setTimeout(syncMapContainerSize, 200);

    return globeInstance;
}

/**
 * Render normalized dataset onto the 2D Leaflet map with continuous paths and aligned markers.
 */
function renderLeaflet2DData(dataset) {
    if (!leafletMapInstance || !leafletRouteLayer) return;

    leafletRouteLayer.clearLayers();
    routeRegistry.clear();

    const routes = dataset.routes || [];
    const isDark = getCurrentTheme() === 'dark';
    const markerRingColor = isDark ? '#0B1728' : '#FFFFFF';

    routes.forEach(route => {
        runLayerSafely(`2D route ${route.tracking_number || route.id || 'unknown'}`, () => {
            const geometry = computeRouteGeometry(route, { numPoints: 64 });
            const strokeColor = getSeverityColor(route.delay_severity, route.has_exception);
            const isAlert = route.is_delayed || route.has_exception;

            const dashArray = route.mode === 'AIR' ? '7, 5' : null;
            const weight = route.mode === 'ROAD' ? 2.6 : (route.mode === 'AIR' ? 2.0 : 2.2);
            const opacity = isAlert ? 0.92 : 0.8;

            const origName = route.originMeta?.short_name || route.originMeta?.name || 'Origin';
            const destName = route.destinationMeta?.short_name || route.destinationMeta?.name || 'Destination';
            const routeLabel = `${origName} -> ${destName}`;

            const renderCopy = (coords) => {
                const polyline = runLayerSafely(`polyline ${route.tracking_number}`, () => {
                    const line = L.polyline(coords.points, {
                        color: strokeColor,
                        weight,
                        dashArray,
                        opacity,
                    }).addTo(leafletRouteLayer);
                    line.bindPopup(buildShipmentPopupHtml(route, 'Route', routeLabel));
                    return line;
                });

                const originMarker = runLayerSafely(`origin marker ${route.tracking_number}`, () => {
                    const marker = L.circleMarker(coords.originLatLng, {
                        radius: 4.8,
                        fillColor: '#2563EB',
                        color: markerRingColor,
                        weight: 1.8,
                        opacity: 1,
                        fillOpacity: 0.95,
                    }).addTo(leafletRouteLayer);
                    marker.bindPopup(buildShipmentPopupHtml(
                        route,
                        'Origin',
                        route.originMeta?.name || 'Origin',
                        getLocCode(route.originMeta)
                    ));
                    return marker;
                });

                const destinationMarker = runLayerSafely(`destination marker ${route.tracking_number}`, () => {
                    const marker = L.circleMarker(coords.destinationLatLng, {
                        radius: 4.8,
                        fillColor: '#0891B2',
                        color: markerRingColor,
                        weight: 1.8,
                        opacity: 1,
                        fillOpacity: 0.95,
                    }).addTo(leafletRouteLayer);
                    marker.bindPopup(buildShipmentPopupHtml(
                        route,
                        'Destination',
                        route.destinationMeta?.name || 'Destination',
                        getLocCode(route.destinationMeta)
                    ));
                    return marker;
                });

                let currentMarker = null;
                if (coords.currentLatLng && route.currentMeta) {
                    currentMarker = runLayerSafely(`current marker ${route.tracking_number}`, () => {
                        const marker = L.circleMarker(coords.currentLatLng, {
                            radius: 6.5,
                            fillColor: strokeColor,
                            color: markerRingColor,
                            weight: 2.2,
                            opacity: 1,
                            fillOpacity: 1,
                        }).addTo(leafletRouteLayer);

                        const currName = route.currentMeta.location_name || route.currentMeta.name || 'Latest checkpoint';
                        const currCode = getLocCode(route.currentMeta);
                        marker.bindPopup(buildShipmentPopupHtml(route, 'Current location', currName, currCode));
                        return marker;
                    });
                }

                return { polyline, originMarker, destinationMarker, currentMarker };
            };

            const primaryLayers = renderCopy(geometry);
            if (geometry.mirrored) {
                renderCopy(geometry.mirrored);
            }

            const bounds = L.latLngBounds(geometry.points);
            if (geometry.currentLatLng) {
                bounds.extend(geometry.currentLatLng);
            }

            routeRegistry.set(route.tracking_number, {
                shipment: route.raw || route,
                route,
                polyline: primaryLayers.polyline,
                originMarker: primaryLayers.originMarker,
                destinationMarker: primaryLayers.destinationMarker,
                currentMarker: primaryLayers.currentMarker,
                bounds,
            });
        });
    });

    updateEmptyState(routes.length === 0);
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

    const hasExplicitOptions = Boolean(options.initialData || options.shipments);
    const dataset = hasExplicitOptions ? normalizeMapDataset(options) : currentDataset;
    if (initialDataset.routes.length === 0 && dataset.routes.length > 0) {
        initialDataset = dataset;
    }
    currentDataset = dataset;

    const map = createMap(containerId, {
        center: [15, 95],
        zoom: 2,
        minZoom: 2,
        maxZoom: 18,
        maxBounds: [[-82, -540], [82, 540]],
        maxBoundsViscosity: 0.7,
        zoomControl: false,
        worldCopyJump: true,
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
    attachContainerResizeObserver();

    return map;
}

function handleViewportResize() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        syncMapContainerSize();
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
        requestAnimationFrame(syncMapContainerSize);
        setTimeout(syncMapContainerSize, 160);
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

    const hasValidTarget = isValidCoordinatePair(lat, lng);

    if (hasValidTarget) {
        updateMapCoordinates(lat, lng);
    }

    if (globeInstance && hasValidTarget) {
        const controls = globeInstance.controls();
        if (controls) controls.autoRotate = false;
        globeInstance.pointOfView({ lat: Number(lat), lng: Number(lng), altitude: 1.6 }, prefersReducedMotion() ? 0 : 1000);
    }

    if (!leafletMapInstance) return;

    if (trackingNumber && !routeRegistry.has(trackingNumber)) {
        const existsInInitial = initialDataset.routes.some(r => r.tracking_number === trackingNumber);
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

    if (currentMode === '2D' && hasValidTarget) {
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
    const routes = (dataset.routes || []).filter(route => {
        if (key === 'SEA' || key === 'AIR' || key === 'ROAD') return route.mode === key;
        if (key === 'DELAYED') return route.is_delayed;
        if (key === 'EXCEPTIONS') return route.has_exception;
        return true;
    });

    return {
        routes,
        shipments: routes.map(r => r.raw),
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
        currentDataset = normalizeMapDataset({ initialData: payload });

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
            requestAnimationFrame(syncMapContainerSize);
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
