import Globe from 'globe.gl';
import L from 'leaflet';
import { getBezierCurve, createMap } from './map';

/**
 * Global State for Maritime 3D Globe & 2D Leaflet Map
 */
let globeInstance = null;
let leafletMapInstance = null;
let currentMode = '3D'; // '3D' or '2D'
let currentShipments = [];
let currentLocations = [];

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
 * Initialize 3D Earth Globe with Ocean Container Routes & Seaports
 */
export function initMaritimeGlobe(containerId, options = {}) {
    const el = document.getElementById(containerId);
    if (!el) return null;

    currentShipments = options.shipments || [];
    currentLocations = options.locations || [];

    // Format arcs from shipments
    const arcsData = [];
    const vesselMarkers = [];

    currentShipments.forEach(s => {
        const orig = s.origin_location || s.originLocation || s.origin;
        const dest = s.destination_location || s.destinationLocation || s.destination;

        const startLat = parseFloat(orig?.latitude || orig?.lat || 0);
        const startLng = parseFloat(orig?.longitude || orig?.lng || 0);
        const endLat = parseFloat(dest?.latitude || dest?.lat || 0);
        const endLng = parseFloat(dest?.longitude || dest?.lng || 0);

        if (startLat && endLat) {
            const isDelayed = Boolean(s.is_delayed);
            const hasException = Boolean(s.exceptions && s.exceptions.length > 0);
            const mode = (s.transport_mode || s.carrier_mode || 'SEA').toUpperCase();

            // Mode icon & label for tooltips / popups
            const modeIcon = mode === 'AIR' ? '✈️' : (mode === 'ROAD' ? '🚚' : '🚢');
            const modeLabel = mode === 'AIR' ? 'Air' : (mode === 'ROAD' ? 'Road' : 'Sea');

            // Arc colors
            let arcColor;
            if (hasException) {
                arcColor = ['#F43F5E', '#FB7185'];
            } else if (isDelayed) {
                arcColor = ['#F59E0B', '#FBBF24'];
            } else {
                arcColor = mode === 'AIR'
                    ? ['#0284C7', '#38BDF8']
                    : (mode === 'ROAD' ? ['#059669', '#34D399'] : ['#2563EB', '#60A5FA']);
            }

            // Styling per mode:
            // Sea: solid arc (arcAltitude 0.22, dashLength 1.0)
            // Air: dashed higher arc (arcAltitude 0.45, dashLength 0.3)
            // Road: straight low line (arcAltitude 0.05, dashLength 1.0)
            const arcAltitude = mode === 'AIR' ? 0.45 : (mode === 'ROAD' ? 0.05 : 0.22);
            const arcDashLength = mode === 'AIR' ? 0.3 : 1.0;
            const arcDashGap = mode === 'AIR' ? 0.15 : 0;

            arcsData.push({
                shipmentId: s.id,
                trackingNumber: s.tracking_number,
                transportMode: mode,
                carrier: s.carrier || 'Carrier',
                startLat,
                startLng,
                endLat,
                endLng,
                color: arcColor,
                altitude: arcAltitude,
                dashLength: arcDashLength,
                dashGap: arcDashGap,
                status: s.status,
                isDelayed,
                hasException,
            });

            // Midpoint or current vessel position for vessel ring marker
            const midLat = (startLat + endLat) / 2;
            const midLng = (startLng + endLng) / 2;
            vesselMarkers.push({
                shipmentId: s.id,
                trackingNumber: s.tracking_number,
                transportMode: mode,
                lat: s.current ? parseFloat(s.current.lat) : midLat,
                lng: s.current ? parseFloat(s.current.lng) : midLng,
                carrier: s.carrier || 'Freight',
                status: s.status,
                isDelayed,
                color: hasException ? '#F43F5E' : (isDelayed ? '#F59E0B' : (mode === 'AIR' ? '#38BDF8' : (mode === 'ROAD' ? '#34D399' : '#60A5FA'))),
                name: `${modeIcon} ${s.tracking_number} [${modeLabel}] (${orig?.unlocode || 'ORI'} → ${dest?.unlocode || 'DST'})`,
            });
        }
    });

    // Format Seaport points
    const pointsData = currentLocations.map(loc => ({
        id: loc.id,
        name: loc.name,
        unlocode: loc.unlocode,
        country: loc.country,
        lat: parseFloat(loc.latitude),
        lng: parseFloat(loc.longitude),
        size: 0.8,
        color: '#2DD4BF',
    }));

    // Format Seaport labels
    const labelsData = currentLocations.map(loc => ({
        lat: parseFloat(loc.latitude),
        lng: parseFloat(loc.longitude),
        text: loc.unlocode,
        size: 1.1,
    }));

    // Clear previous canvas if any
    el.innerHTML = '';

    const currentTheme = document.documentElement.getAttribute('data-theme')
        || (document.documentElement.classList.contains('light') ? 'light' : 'dark');
    const themeCfg = getGlobeThemeConfig(currentTheme);

    // Create Globe instance
    globeInstance = Globe()(el)
        .globeImageUrl(themeCfg.globeImage)
        .bumpImageUrl(themeCfg.bumpImage)
        .backgroundColor('rgba(0, 0, 0, 0)')
        .atmosphereColor(themeCfg.atmosphereColor)
        .atmosphereAltitude(themeCfg.atmosphereAltitude)
        .showAtmosphere(true)
        // Arcs (Trade lanes per mode)
        .arcsData(arcsData)
        .arcColor('color')
        .arcAltitude('altitude')
        .arcStroke(1.8)
        .arcDashLength('dashLength')
        .arcDashGap('dashGap')
        .arcDashInitialGap(() => Math.random())
        .arcDashAnimateTime(2200)
        // Seaports (Points)
        .pointsData(pointsData)
        .pointLat('lat')
        .pointLng('lng')
        .pointColor('color')
        .pointAltitude(0.015)
        .pointRadius(0.65)
        .pointLabel(d => `
            <div style="font-family: 'Inter', sans-serif; background: var(--bg-surface, #0B101E); border: 1px solid var(--border-color, #1E293B); border-radius: 8px; padding: 6px 10px; color: var(--text-primary, #fff); font-size: 11px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                <div style="font-weight: 700; color: #2DD4BF;">${d.name}</div>
                <div style="color: var(--text-secondary, #94A3B8); margin-top: 2px;">UN/LOCODE: <b>${d.unlocode}</b> &bull; ${d.country}</div>
            </div>
        `)
        // Labels
        .labelsData(labelsData)
        .labelLat('lat')
        .labelLng('lng')
        .labelText('text')
        .labelSize('size')
        .labelDotRadius(0.3)
        .labelColor(() => themeCfg.labelColor)
        .labelResolution(2)
        // Rings / Vessel markers
        .ringsData(vesselMarkers)
        .ringLat('lat')
        .ringLng('lng')
        .ringColor('color')
        .ringMaxRadius(2.2)
        .ringPropagationSpeed(1.2)
        .ringRepeatPeriod(1800);

    // Initial camera view (Centered near Singapore / South China Sea gateway)
    globeInstance.pointOfView({ lat: 18.0, lng: 110.0, altitude: 2.2 }, 1000);

    // Auto-rotation
    const controls = globeInstance.controls();
    if (controls) {
        controls.autoRotate = true;
        controls.autoRotateSpeed = 0.45;
        controls.enableZoom = true;

        // When user interacts, update map center coordinates display
        controls.addEventListener('change', () => {
            const pov = globeInstance.pointOfView();
            updateMapCoordinates(pov.lat, pov.lng);
        });
    }

    // On point / seaport click: flyTo seaport
    globeInstance.onPointClick(point => {
        if (controls) controls.autoRotate = false;
        globeInstance.pointOfView({ lat: point.lat, lng: point.lng, altitude: 1.5 }, 1200);
        updateMapCoordinates(point.lat, point.lng);
        highlightActivityRow(null, point.unlocode);
    });

    // Resize handler
    window.addEventListener('resize', () => {
        if (globeInstance && el.clientWidth) {
            globeInstance.width(el.clientWidth);
            globeInstance.height(el.clientHeight || 460);
        }
    });

    // Initial size trigger
    setTimeout(() => {
        if (globeInstance && el.clientWidth) {
            globeInstance.width(el.clientWidth);
            globeInstance.height(el.clientHeight || 460);
        }
    }, 200);

    return globeInstance;
}

/**
 * Update the dynamic map center coordinates displayed on the left panel
 */
export function updateMapCoordinates(lat, lng) {
    const latEl = document.getElementById('map-coord-lat');
    const lngEl = document.getElementById('map-coord-lng');
    if (latEl) latEl.textContent = Number(lat).toFixed(6);
    if (lngEl) lngEl.textContent = Number(lng).toFixed(6);
}

/**
 * Fly camera to a specific shipment route or seaport on 3D Globe and 2D Map
 */
export function focusMaritimeEntity(lat, lng, trackingNumber = null) {
    updateMapCoordinates(lat, lng);

    // 1. Focus 3D Globe
    if (globeInstance) {
        const controls = globeInstance.controls();
        if (controls) controls.autoRotate = false;
        globeInstance.pointOfView({ lat: Number(lat), lng: Number(lng), altitude: 1.6 }, 1000);
    }

    // 2. Focus 2D Leaflet Map
    if (leafletMapInstance) {
        leafletMapInstance.setView([Number(lat), Number(lng)], 4, { animate: true });
    }

    // 3. Highlight row in activities table
    highlightActivityRow(trackingNumber);
}

/**
 * Highlight corresponding row in Maritime Activities table
 */
function highlightActivityRow(trackingNumber = null, portUnlocode = null) {
    const rows = document.querySelectorAll('.activity-row');
    rows.forEach(r => {
        r.classList.remove('bg-blue-600/15', 'border-blue-500/40');
        if (trackingNumber && r.getAttribute('data-tracking') === trackingNumber) {
            r.classList.add('bg-blue-600/15', 'border-blue-500/40');
            r.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
}

/**
 * Initialize 2D Leaflet Map inside the same container bounds
 */
export function initMaritime2DMap(containerId, options = {}) {
    const map = createMap(containerId, {
        center: [20, 10],
        zoom: 2,
        minZoom: 2,
        maxZoom: 18,
        zoomControl: false, // Custom zoom buttons in UI
    });

    if (!map) return null;
    leafletMapInstance = map;
    window.CargoTraceLeafletMap = map;

    const layerAll = L.layerGroup().addTo(map);
    const layerSea = L.layerGroup();
    const layerAir = L.layerGroup();
    const layerRoad = L.layerGroup();
    const layerDelayed = L.layerGroup();
    const layerExceptions = L.layerGroup();

    map._modeLayers = {
        ALL: layerAll,
        SEA: layerSea,
        AIR: layerAir,
        ROAD: layerRoad,
        DELAYED: layerDelayed,
        EXCEPTIONS: layerExceptions,
    };

    // Plot Seaports
    locations.forEach(loc => {
        if (!loc.latitude || !loc.longitude) return;
        const marker = L.circleMarker([parseFloat(loc.latitude), parseFloat(loc.longitude)], {
            radius: 5,
            fillColor: '#2DD4BF',
            color: '#0D9488',
            weight: 2,
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

    // Plot Ocean / Multimodal Arcs
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
            // Road: straight line (direct polyline without curve)
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

            const points = mode === 'ROAD' ? [start, end] : getBezierCurve(start, end);
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
                    <div style="color: #64748B; margin-top: 4px;">${orig?.name || 'Origin'} &rarr; ${dest?.name || 'Destination'}</div>
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
 * Switch between 3D Globe and 2D Leaflet Map
 */
export function setMapMode(mode) {
    currentMode = mode;
    const globe3dEl = document.getElementById('globe-3d-canvas');
    const map2dEl = document.getElementById('globe-2d-canvas');
    const btn3d = document.getElementById('btn-mode-3d');
    const btn2d = document.getElementById('btn-mode-2d');

    if (mode === '3D') {
        if (globe3dEl) globe3dEl.classList.remove('hidden');
        if (map2dEl) map2dEl.classList.add('hidden');

        if (btn3d) {
            btn3d.className = 'px-3.5 py-1 rounded-full text-xs font-semibold bg-blue-600 text-white shadow-md transition-all flex items-center gap-1.5';
        }
        if (btn2d) {
            btn2d.className = 'px-3.5 py-1 rounded-full text-xs font-semibold text-slate-400 hover:text-white transition-all flex items-center gap-1.5';
        }

        if (globeInstance) {
            const controls = globeInstance.controls();
            if (controls) controls.autoRotate = true;
        }
    } else {
        if (globe3dEl) globe3dEl.classList.add('hidden');
        if (map2dEl) {
            map2dEl.classList.remove('hidden');
            if (leafletMapInstance) {
                setTimeout(() => leafletMapInstance.invalidateSize(), 100);
            }
        }

        if (btn3d) {
            btn3d.className = 'px-3.5 py-1 rounded-full text-xs font-semibold text-slate-400 hover:text-white transition-all flex items-center gap-1.5';
        }
        if (btn2d) {
            btn2d.className = 'px-3.5 py-1 rounded-full text-xs font-semibold bg-blue-600 text-white shadow-md transition-all flex items-center gap-1.5';
        }

        if (globeInstance) {
            const controls = globeInstance.controls();
            if (controls) controls.autoRotate = false;
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
        globeInstance.pointOfView({ altitude: newAltitude }, 400);
    } else if (leafletMapInstance) {
        if (direction === 'in') {
            leafletMapInstance.zoomIn();
        } else {
            leafletMapInstance.zoomOut();
        }
    }
}
