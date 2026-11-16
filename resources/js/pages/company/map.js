import { loadLeaflet, addTiles, hubIcon, PHILIPPINES_CENTER } from './leaflet.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('companyHubMap', () => {
        let map, clusters, L, root, observer;
        const markers = new Map();
        return {
            hubs: [], province: '', status: '', search: '', selected: null, error: '', loading: true,
            get provinces() { return [...new Set(this.hubs.map(hub => hub.province))].sort(); },
            get filtered() {
                const search = this.search.trim().toLocaleLowerCase();
                return this.hubs.filter(hub => (!this.province || hub.province === this.province)
                    && (!this.status || hub.status === this.status)
                    && (!search || hub.name.toLocaleLowerCase().includes(search)));
            },
            get mapped() { return this.filtered.filter(hub => hub.latitude !== null && hub.longitude !== null); },
            get unmapped() { return this.filtered.filter(hub => hub.latitude === null || hub.longitude === null); },
            async init() {
                root = this.$el;
                this.hubs = JSON.parse(root.dataset.hubs || '[]');
                await this.loadMap();
            },
            async loadMap() {
                this.loading = true; this.error = '';
                try {
                    L = await loadLeaflet(true);
                    if (map) { map.eachLayer(layer => layer.redraw?.()); return; }
                    map = L.map(this.$refs.mapCanvas).setView(PHILIPPINES_CENTER, 6);
                    addTiles(L, map).on('tileerror', () => { this.error = 'Map tiles are temporarily unavailable. You can still use the hub list and filters.'; });
                    clusters = L.markerClusterGroup({
                        showCoverageOnHover: false,
                        iconCreateFunction: cluster => {
                            const label = document.createElement('span');
                            label.textContent = cluster.getChildCount();
                            return L.divIcon({ html: label, className: 'company-map-cluster', iconSize: [38, 38] });
                        },
                    }).addTo(map);
                    observer = new ResizeObserver(() => map?.invalidateSize());
                    observer.observe(this.$refs.mapCanvas);
                    this.refresh();
                } catch (error) { this.error = error.message; }
                finally { this.loading = false; }
            },
            popup(hub) {
                const node = document.createElement('section');
                const title = document.createElement('h2');
                title.className = 'text-base font-semibold'; title.textContent = hub.name; node.append(title);
                for (const text of [hub.status === 'active' ? 'Active hub' : 'Suspended hub', `Manager: ${hub.manager}`,
                    `${hub.municipality}, ${hub.province}`, `Riders: ${hub.riders}`, `Parcels handled today: ${hub.handledToday}`]) {
                    const line = document.createElement('p'); line.textContent = text; node.append(line);
                }
                const link = document.createElement('a'); link.href = hub.editUrl; link.textContent = 'Edit hub';
                link.className = 'btn btn-primary'; node.append(link);
                return node;
            },
            refresh() {
                if (!map) return;
                clusters.clearLayers(); markers.clear();
                if (!this.filtered.some(hub => hub.code === this.selected)) this.selected = null;
                for (const hub of this.mapped) {
                    const marker = L.marker([hub.latitude, hub.longitude], { icon: hubIcon(L, hub.status), title: hub.name, alt: hub.name });
                    marker.bindPopup(this.popup(hub));
                    marker.on('popupopen', () => {
                        this.selected = hub.code;
                        if (window.matchMedia('(min-width: 1024px)').matches) {
                            this.$nextTick(() => root.querySelector(`[data-hub-code="${hub.code}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
                        }
                    });
                    markers.set(hub.code, marker); clusters.addLayer(marker);
                }
                const bounds = clusters.getBounds();
                if (bounds.isValid()) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
                else map.setView(PHILIPPINES_CENTER, 6);
            },
            focusHub(code) {
                this.selected = code;
                const marker = markers.get(code);
                if (!marker || !map) return;
                clusters.zoomToShowLayer(marker, () => {
                    map.setView(marker.getLatLng(), Math.max(map.getZoom(), 14));
                    marker.openPopup();
                });
                this.$refs.mapCanvas.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            },
            destroy() { observer?.disconnect(); map?.remove(); map = null; },
        };
    });
});
