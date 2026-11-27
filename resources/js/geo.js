/**
 * Pure geographic validation, route normalization, 3D altitude scaling,
 * and continuous antimeridian-safe 2D arc geometry shared by 3D Globe and 2D Leaflet.
 */

/**
 * Validate that a latitude/longitude pair is finite, within bounds, and not Null Island (0, 0).
 * Valid equatorial points such as [0, 109.33] return true.
 */
export function isValidCoordinatePair(lat, lng) {
    if (lat === null || lat === undefined || lat === '' || lng === null || lng === undefined || lng === '') {
        return false;
    }

    const nLat = Number(lat);
    const nLng = Number(lng);
    if (!Number.isFinite(nLat) || !Number.isFinite(nLng)) {
        return false;
    }

    if (nLat < -90 || nLat > 90 || nLng < -180 || nLng > 180) {
        return false;
    }

    if (Math.abs(nLat) < 1e-4 && Math.abs(nLng) < 1e-4) {
        return false;
    }

    return true;
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
 * Normalize a location object and discard invalid or (0, 0) coordinates.
 */
export function normalizeLocation(loc) {
    if (!loc || typeof loc !== 'object') return null;

    const rawLat = loc.latitude ?? loc.lat;
    const rawLng = loc.longitude ?? loc.lng;
    if (!isValidCoordinatePair(rawLat, rawLng)) {
        return null;
    }

    const code = loc.display_code || loc.code || loc.unlocode || '';
    const type = String(loc.type || '').toUpperCase();
    const codeType = loc.code_type_label
        || loc.code_type
        || (type === 'AIRPORT' ? 'IATA' : ((type === 'PORT' || type === 'SEAPORT') ? 'UN/LOCODE' : 'Internal code'));

    return {
        id: loc.id ?? null,
        name: loc.name || loc.short_name || 'Location',
        short_name: loc.short_name || loc.name || 'Location',
        code,
        codeType,
        unlocode: code,
        country: loc.country || '',
        lat: Number(rawLat),
        lng: normalizeLongitude(rawLng),
    };
}

/**
 * Normalize a shipment record into a canonical route structure consumed by both 3D Globe and 2D Leaflet.
 * Returns null if origin or destination coordinates are missing, invalid, or (0, 0).
 */
export function normalizeShipmentRoute(raw) {
    if (!raw || typeof raw !== 'object') return null;

    const orig = raw.origin || raw.origin_location || raw.originLocation;
    const dest = raw.destination || raw.destination_location || raw.destinationLocation;
    const curr = raw.latest_tracking || raw.current || raw.current_location || raw.currentLocation;

    const origLat = orig?.latitude ?? orig?.lat;
    const origLng = orig?.longitude ?? orig?.lng;
    const destLat = dest?.latitude ?? dest?.lat;
    const destLng = dest?.longitude ?? dest?.lng;

    if (!isValidCoordinatePair(origLat, origLng) || !isValidCoordinatePair(destLat, destLng)) {
        return null;
    }

    const currLat = curr?.latitude ?? curr?.lat;
    const currLng = curr?.longitude ?? curr?.lng;
    const hasValidCurrent = isValidCoordinatePair(currLat, currLng);

    const rawMode = String(raw.mode || raw.transport_mode || raw.carrier_mode || 'SEA').toUpperCase();
    const mode = rawMode === 'AIR' || rawMode === 'ROAD' ? rawMode : 'SEA';

    const hasException = Boolean(raw.has_exception || (Array.isArray(raw.exceptions) && raw.exceptions.length > 0));

    return {
        id: raw.id ?? null,
        tracking_number: raw.tracking_number || '',
        origin: [Number(origLat), normalizeLongitude(origLng)],
        destination: [Number(destLat), normalizeLongitude(destLng)],
        current: hasValidCurrent ? [Number(currLat), normalizeLongitude(currLng)] : null,
        mode,
        status: raw.status || 'IN_TRANSIT',
        status_label: raw.status_label || raw.status || 'IN_TRANSIT',
        delay_severity: String(raw.delay_severity || 'NONE').toUpperCase(),
        is_delayed: Boolean(raw.is_delayed),
        has_exception: hasException,
        carrier: raw.carrier || 'Carrier',
        url: raw.url || '#',
        originMeta: orig || {},
        destinationMeta: dest || {},
        currentMeta: hasValidCurrent ? (curr || {}) : null,
        raw,
    };
}

/**
 * Normalize an incoming map dataset payload once so 3D and 2D views consume identical routes and locations.
 */
export function normalizeMapDataset(options = {}) {
    const source = (options.initialData && Array.isArray(options.initialData.shipments))
        ? options.initialData
        : options;

    const rawShipments = Array.isArray(source.shipments) ? source.shipments : [];
    const rawLocations = Array.isArray(source.locations) ? source.locations : [];

    const routes = rawShipments
        .map(normalizeShipmentRoute)
        .filter(Boolean);

    const locations = rawLocations
        .map(normalizeLocation)
        .filter(Boolean);

    return {
        routes,
        shipments: routes.map(r => r.raw),
        locations,
        meta: source.meta || {},
    };
}

/**
 * Compute Haversine great-circle angular distance in radians [0, Math.PI].
 */
export function getGreatCircleDistanceRadians(origin, destination) {
    const toRad = (deg) => (Number(deg) * Math.PI) / 180;
    const lat1 = toRad(origin[0]);
    const lng1 = toRad(origin[1]);
    const lat2 = toRad(destination[0]);
    const lng2 = toRad(destination[1]);

    const dLat = lat2 - lat1;
    const dLng = lng2 - lng1;
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;

    return 2 * Math.atan2(Math.sqrt(Math.max(0, a)), Math.sqrt(Math.max(0, 1 - a)));
}

/**
 * Clamp 3D globe arc altitude tightly per transport mode and gently scale by great-circle distance
 * so every arc starts and lands cleanly on the globe surface without jutting wildly outside the sphere.
 */
export function computeGlobeArcAltitude(mode, origin, destination) {
    const normalizedMode = String(mode || 'SEA').toUpperCase();
    const distRatio = getGreatCircleDistanceRadians(origin, destination) / Math.PI;

    if (normalizedMode === 'ROAD') {
        return Math.min(0.02, Math.max(0.005, 0.005 + distRatio * 0.12));
    }
    if (normalizedMode === 'AIR') {
        return Math.min(0.24, Math.max(0.06, 0.06 + distRatio * 0.22));
    }
    return Math.min(0.15, Math.max(0.035, 0.035 + distRatio * 0.15));
}

/**
 * Convert a 3- or 6-digit hex color to an rgba() string.
 */
export function hexToRgba(hex, alpha = 1) {
    const clean = String(hex || '#10B981').replace('#', '').trim();
    const full = clean.length === 3
        ? clean.split('').map(c => c + c).join('')
        : clean;

    const num = parseInt(full, 16);
    if (Number.isNaN(num)) {
        return `rgba(16, 185, 129, ${alpha})`;
    }

    const r = (num >> 16) & 255;
    const g = (num >> 8) & 255;
    const b = num & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/**
 * Resolve continuous target longitude for 2D Leaflet rendering.
 * - Routes within 180 deg stay inside [-180, 180].
 * - Near-antipodal Indonesia -> US East Coast corridors (|delta| in (180, 198]) route
 *   consistently Westward across the Indian Ocean / Eurasia / Atlantic in a single unbroken
 *   path inside [-180, 180], unless a live checkpoint explicitly indicates trans-Pacific transit.
 * - True trans-Pacific routes (|delta| > 198, e.g. Indonesia -> US West Coast) unwrap
 *   Eastward continuously past +180 so the curve never breaks in mid-ocean.
 */
export function resolveContinuousTargetLng(origLng, destLng, currentLng = null) {
    const oLng = normalizeLongitude(origLng);
    const dLng = normalizeLongitude(destLng);
    const rawDelta = dLng - oLng;

    if (Math.abs(rawDelta) <= 180) {
        return dLng;
    }

    if (Math.abs(rawDelta) <= 198) {
        const hasCurrent = currentLng !== null
            && currentLng !== undefined
            && currentLng !== ''
            && Number.isFinite(Number(currentLng));

        if (hasCurrent) {
            const cLng = normalizeLongitude(currentLng);
            const isExplicitTransPacific = Math.abs(oLng - 105) < 40 && (cLng > 135 || cLng < -110);
            if (isExplicitTransPacific) {
                return unwrapLongitudeNear(dLng, oLng);
            }
        }

        return dLng;
    }

    return unwrapLongitudeNear(dLng, oLng);
}

/**
 * Compute a continuous Mercator-bounded curved arc (SEA, AIR) or direct segment (ROAD)
 * with exact start and end coordinates matching the origin and destination markers.
 */
export function getGreatCircleArc(start, end, options = {}) {
    const lat1 = Number(start[0]);
    const lng1 = normalizeLongitude(start[1]);
    const lat2 = Number(end[0]);
    const rawLng2 = Number(end[1]);
    const mode = String(options.mode || 'SEA').toUpperCase();
    const numPoints = options.numPoints || 64;

    const endLng = options.targetLng !== undefined
        ? Number(options.targetLng)
        : resolveContinuousTargetLng(lng1, rawLng2, options.currentLng ?? null);

    if (mode === 'ROAD') {
        return [
            [lat1, lng1],
            [lat2, endLng],
        ];
    }

    const dLng = endLng - lng1;
    const midLat = (lat1 + lat2) / 2;
    const chordDeg = Math.hypot(lat2 - lat1, dLng);
    const loftDeg = mode === 'AIR'
        ? Math.min(16, Math.max(2.5, chordDeg * 0.095))
        : Math.min(10, Math.max(1.5, chordDeg * 0.06));

    const direction = midLat < -12 ? -1 : 1;
    const controlLat = Math.max(-58, Math.min(68, midLat + direction * loftDeg));

    const points = [];
    for (let i = 0; i <= numPoints; i++) {
        if (i === 0) {
            points.push([lat1, lng1]);
            continue;
        }
        if (i === numPoints) {
            points.push([lat2, endLng]);
            continue;
        }

        const t = i / numPoints;
        const oneMinusT = 1 - t;
        const rawLat = oneMinusT * oneMinusT * lat1 + 2 * oneMinusT * t * controlLat + t * t * lat2;
        const boundedLat = Math.max(-75, Math.min(75, rawLat));
        const lng = lng1 + dLng * t;
        points.push([boundedLat, lng]);
    }

    return points;
}

/**
 * Compute continuous 2D polyline points, aligned endpoint markers, aligned current checkpoint,
 * and an optional +-360 mirrored copy when a route spans across the +-180 antimeridian.
 */
export function computeRouteGeometry(route, options = {}) {
    const origLat = Number(route.origin[0]);
    const origLng = normalizeLongitude(route.origin[1]);
    const destLat = Number(route.destination[0]);
    const destLngNorm = normalizeLongitude(route.destination[1]);
    const currentCoord = route.current || null;

    const targetLng = resolveContinuousTargetLng(
        origLng,
        destLngNorm,
        currentCoord ? currentCoord[1] : null
    );

    const points = getGreatCircleArc([origLat, origLng], [destLat, targetLng], {
        mode: route.mode,
        numPoints: options.numPoints || 64,
        targetLng,
    });

    const originLatLng = points[0];
    const destinationLatLng = points[points.length - 1];

    let currentLatLng = null;
    if (currentCoord && isValidCoordinatePair(currentCoord[0], currentCoord[1])) {
        const routeMidLng = (origLng + targetLng) / 2;
        const currLat = Number(currentCoord[0]);
        const baseLng = normalizeLongitude(currentCoord[1]);
        const candidates = [baseLng - 360, baseLng, baseLng + 360];
        let bestLng = baseLng;
        let bestDist = Infinity;
        for (const cand of candidates) {
            const d = Math.abs(cand - routeMidLng);
            if (d < bestDist) {
                bestDist = d;
                bestLng = cand;
            }
        }
        currentLatLng = [currLat, bestLng];
    }

    const crossesAntimeridian = targetLng > 180 || targetLng < -180;
    let mirrored = null;

    if (crossesAntimeridian) {
        const shift = targetLng > 180 ? -360 : 360;
        mirrored = {
            points: points.map(([lat, lng]) => [lat, lng + shift]),
            originLatLng: [originLatLng[0], originLatLng[1] + shift],
            destinationLatLng: [destinationLatLng[0], destinationLatLng[1] + shift],
            currentLatLng: currentLatLng ? [currentLatLng[0], currentLatLng[1] + shift] : null,
        };
    }

    return {
        points,
        originLatLng,
        destinationLatLng,
        currentLatLng,
        crossesAntimeridian,
        mirrored,
    };
}
