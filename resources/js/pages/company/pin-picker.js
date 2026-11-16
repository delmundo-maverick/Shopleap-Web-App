import { loadLeaflet, loadBoundary, insidePhilippines, addTiles, hubIcon, PHILIPPINES_CENTER } from './leaflet.js';

const addressCache = new Map();

export function pinPicker() {
    let map, marker, L, boundary, observer, root, form, addressListener, mapReady, geocodeTimer, controller;
    let geocodeVersion = 0;
    return {
        latitude: '', longitude: '', pinError: '', mapError: '', pinLoading: true, geocoding: false, locationMessage: '',
        async init() {
            root = this.$el;
            this.latitude = root.dataset.latitude || '';
            this.longitude = root.dataset.longitude || '';
            form = root.closest('form');
            addressListener = event => this.locateAddress(event.detail);
            form.addEventListener('hub-address-changed', addressListener);
            form.addEventListener('submit', event => {
                if (this.geocoding) {
                    event.preventDefault();
                    this.locationMessage = 'Please wait for the address lookup to finish, then save the hub.';
                    return;
                }
                if (!this.validateCoordinates()) {
                    event.preventDefault();
                    root.querySelector('[name="latitude"]').focus();
                }
            });
            mapReady = this.loadPinMap();
            await mapReady;
        },
        cancelGeocoding() {
            ++geocodeVersion;
            clearTimeout(geocodeTimer);
            controller?.abort();
            this.geocoding = false;
            this.locationMessage = '';
        },
        locateAddress(address) {
            this.clearPin();
            if (!address?.province || !address?.municipality || !address?.barangay) return;
            this.geocoding = true;
            const version = geocodeVersion;
            geocodeTimer = setTimeout(() => this.geocodeAddress(address, version), 700);
        },
        async geocodeAddress(address, version) {
            let timeout;
            try {
                await mapReady;
                if (version !== geocodeVersion) return;
                if (!boundary) throw new Error('Map boundary unavailable');
                const query = [address.barangay, address.municipality, address.province, 'Philippines'].join(', ');
                let features = addressCache.get(query);
                if (!features) {
                    controller = new AbortController();
                    const requestController = controller;
                    timeout = setTimeout(() => requestController.abort(), 10000);
                    const url = new URL(root.dataset.geocodingUrl);
                    url.search = new URLSearchParams({ q: query, countrycode: 'PH', bbox: '116,4,127,22', limit: '5', lang: 'en' });
                    const response = await fetch(url, { signal: requestController.signal, headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Location lookup unavailable');
                    features = (await response.json()).features || [];
                    addressCache.set(query, features);
                }
                if (version !== geocodeVersion) return;
                const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase().replace(/\b(barangay|brgy|city of|municipality of)\b/g, '')
                    .replace(/\b(i|ii|iii|iv|v|vi|vii|viii|ix|x)\b/g, numeral => ({ i: 1, ii: 2, iii: 3, iv: 4, v: 5, vi: 6, vii: 7, viii: 8, ix: 9, x: 10 })[numeral])
                    .replace(/[^a-z0-9]/g, '');
                const match = features.find(feature => {
                    const coordinates = feature.geometry?.coordinates;
                    const properties = feature.properties || {};
                    return feature.geometry?.type === 'Point' && Array.isArray(coordinates)
                        && normalize(properties.name) === normalize(address.barangay)
                        && normalize(Object.values(properties).join(' ')).includes(normalize(address.municipality))
                        && insidePhilippines(boundary, coordinates[1], coordinates[0]);
                });
                if (!match) {
                    this.locationMessage = 'No matching barangay location was found. Click the map or enter coordinates to set your branch location.';
                    return;
                }
                this.longitude = Number(match.geometry.coordinates[0]).toFixed(7);
                this.latitude = Number(match.geometry.coordinates[1]).toFixed(7);
                this.syncPin(true);
                this.locationMessage = 'Approximate barangay location found. Drag the pin to your branch’s exact location.';
            } catch (error) {
                if (version === geocodeVersion) this.locationMessage = 'Automatic location is unavailable. Click the map or enter coordinates, or retry locating the address.';
            } finally {
                clearTimeout(timeout);
                if (version === geocodeVersion) this.geocoding = false;
            }
        },
        async loadPinMap() {
            this.mapError = ''; this.pinLoading = true;
            try {
                [L, boundary] = await Promise.all([loadLeaflet(), loadBoundary()]);
                if (!map) {
                    map = L.map(this.$refs.pinMap).setView(PHILIPPINES_CENTER, 6);
                    addTiles(L, map).on('tileerror', () => { this.mapError = 'Map tiles are unavailable. Enter coordinates in the fields below.'; });
                    map.on('click', event => this.dropPin(event.latlng));
                    observer = new ResizeObserver(() => map?.invalidateSize());
                    observer.observe(this.$refs.pinMap);
                    map.attributionControl.addAttribution('Boundary: <a href="https://www.geoboundaries.org/" target="_blank" rel="noopener">geoBoundaries / OCHA / NAMRIA</a> (CC BY 3.0 IGO)');
                }
                this.syncPin(true);
            } catch (error) { this.mapError = 'The map could not load. You can still enter coordinates below.'; }
            finally { this.pinLoading = false; }
        },
        validateCoordinates() {
            this.pinError = '';
            const lat = String(this.latitude).trim();
            const lng = String(this.longitude).trim();
            if (!lat && !lng) return true;
            if (!lat || !lng) { this.pinError = 'Enter both latitude and longitude, or leave both blank.'; return false; }
            if (!Number.isFinite(Number(lat)) || !Number.isFinite(Number(lng))) { this.pinError = 'Enter valid numeric coordinates.'; return false; }
            if (boundary && !insidePhilippines(boundary, Number(lat), Number(lng))) { this.pinError = 'Choose a location inside the Philippines.'; return false; }
            return true;
        },
        dropPin(latlng) {
            this.cancelGeocoding();
            if (!insidePhilippines(boundary, latlng.lat, latlng.lng)) {
                this.pinError = 'Choose a location inside the Philippines.';
                if (marker && this.latitude !== '' && this.longitude !== '') marker.setLatLng([Number(this.latitude), Number(this.longitude)]);
                return;
            }
            this.latitude = latlng.lat.toFixed(7); this.longitude = latlng.lng.toFixed(7);
            this.syncPin();
        },
        syncPin(focus = false) {
            const valid = this.validateCoordinates();
            if (!map) return;
            if (!valid || this.latitude === '' || this.longitude === '') {
                if (marker) { map.removeLayer(marker); marker = null; }
                return;
            }
            const location = [Number(this.latitude), Number(this.longitude)];
            if (!marker) {
                marker = L.marker(location, { draggable: true, icon: hubIcon(L), title: 'Hub location', alt: 'Drag to set hub location' }).addTo(map);
                marker.on('dragend', () => this.dropPin(marker.getLatLng()));
            } else marker.setLatLng(location);
            if (focus) map.setView(location, 14);
        },
        clearPin() { this.cancelGeocoding(); this.latitude = ''; this.longitude = ''; this.pinError = ''; this.syncPin(); },
        destroy() { this.cancelGeocoding(); form?.removeEventListener('hub-address-changed', addressListener); observer?.disconnect(); map?.remove(); map = null; },
    };
}
