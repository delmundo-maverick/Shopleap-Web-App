import '../../../css/pages/company/maps.css';

export const PHILIPPINES_CENTER = [12.88, 121.77];
let leafletPromise;
let clusterPromise;
let boundaryPromise;

function stylesheet(url) {
    if (document.querySelector(`link[href="${url}"]`)) return;
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = url;
    link.crossOrigin = 'anonymous';
    document.head.append(link);
}

function script(url) {
    return new Promise((resolve, reject) => {
        const element = document.createElement('script');
        element.src = url;
        element.crossOrigin = 'anonymous';
        element.onload = resolve;
        element.onerror = () => { element.remove(); reject(new Error('The map could not load. Please check your connection and retry.')); };
        document.head.append(element);
    });
}

export async function loadLeaflet(clusters = false) {
    if (!leafletPromise) {
        stylesheet('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css');
        leafletPromise = script('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js')
            .catch(error => { leafletPromise = null; throw error; });
    }
    await leafletPromise;
    if (clusters && !clusterPromise) {
        stylesheet('https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css');
        clusterPromise = script('https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js')
            .catch(error => { clusterPromise = null; throw error; });
    }
    if (clusters) await clusterPromise;
    return window.L;
}

export function addTiles(L, map) {
    return L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
    }).addTo(map);
}

export function hubIcon(L, status = 'active') {
    const icon = document.createElement('span');
    icon.className = `company-map-pin company-map-pin-${status === 'active' ? 'active' : 'suspended'}`;
    return L.divIcon({ html: icon, className: 'company-map-marker', iconSize: [22, 22], iconAnchor: [11, 11], popupAnchor: [0, -12] });
}

export function loadBoundary() {
    boundaryPromise ??= import('./philippines.json').then(module => module.default)
        .catch(error => { boundaryPromise = null; throw error; });
    return boundaryPromise;
}

function inRing(latitude, longitude, ring) {
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const [x, y] = ring[i];
        const [px, py] = ring[j];
        const cross = (longitude - x) * (py - y) - (latitude - y) * (px - x);
        if (Math.abs(cross) < 1e-10 && longitude >= Math.min(x, px) && longitude <= Math.max(x, px)
            && latitude >= Math.min(y, py) && latitude <= Math.max(y, py)) return true;
        if ((y > latitude) !== (py > latitude) && longitude < (px - x) * (latitude - y) / (py - y) + x) inside = !inside;
    }
    return inside;
}

export function insidePhilippines(boundary, latitude, longitude) {
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return false;
    return boundary.features.some(feature => {
        const geometry = feature.geometry;
        const polygons = geometry.type === 'Polygon' ? [geometry.coordinates] : geometry.coordinates;
        return polygons.some(polygon => inRing(latitude, longitude, polygon[0])
            && !polygon.slice(1).some(hole => inRing(latitude, longitude, hole)));
    });
}
