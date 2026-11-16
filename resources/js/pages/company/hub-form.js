import { pinPicker } from './pin-picker.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('companyPinPicker', pinPicker);
    window.Alpine.data('companyHubForm', () => ({
        province: '', municipality: '', barangay: '', provinces: [], municipalities: [], locationBarangays: [], barangays: [], selected: [],
        loading: false, error: '', requestVersion: 0, form: null,
        async json(url) {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not load locations. Please retry.');
            return data;
        },
        async init() {
            this.form ??= this.$el;
            this.loading = true;
            this.error = '';
            try {
                this.province = this.form.dataset.province || '';
                this.municipality = this.form.dataset.municipality || '';
                this.barangay = this.form.dataset.barangay || '';
                this.selected = JSON.parse(this.form.dataset.selected || '[]');
                this.provinces = (await this.json(`${this.form.dataset.locationsUrl}?level=province`)).sort((a, b) => a.name.localeCompare(b.name));
                if (this.province) {
                    await this.loadMunicipalities();
                    if (this.municipality) await this.loadBarangays();
                }
            } catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        async loadMunicipalities() {
            const place = this.provinces.find(item => item.name === this.province);
            this.municipalities = place ? (await this.json(`${this.form.dataset.locationsUrl}?level=municipality&code=${encodeURIComponent(place.code)}`)).sort((a, b) => a.name.localeCompare(b.name)) : [];
            await this.$nextTick();
        },
        async loadBarangays() {
            const version = ++this.requestVersion;
            const params = new URLSearchParams({ province: this.province, municipality: this.municipality });
            if (this.form.dataset.hub) params.set('hub', this.form.dataset.hub);
            const municipality = this.municipalities.find(place => place.name === this.municipality);
            if (!municipality) throw new Error('Choose a municipality from the list.');
            const [rows, locations] = await Promise.all([
                this.json(`${this.form.dataset.barangaysUrl}?${params}`),
                this.json(`${this.form.dataset.locationsUrl}?level=barangay&code=${encodeURIComponent(municipality.code)}`),
            ]);
            if (version === this.requestVersion) {
                this.barangays = rows.sort((a, b) => a.barangay.localeCompare(b.barangay));
                this.locationBarangays = locations.sort((a, b) => a.name.localeCompare(b.name));
                await this.$nextTick();
                this.selected = this.selected.filter(name => rows.some(row => row.barangay === name));
            }
        },
        async changeProvince() {
            this.municipality = ''; this.barangay = ''; this.municipalities = []; this.locationBarangays = []; this.barangays = []; this.selected = [];
            this.locateBarangay();
            this.loading = true; this.error = '';
            try { await this.loadMunicipalities(); }
            catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        async changeMunicipality() {
            this.barangay = ''; this.locationBarangays = []; this.barangays = []; this.selected = []; this.error = ''; this.loading = true;
            this.locateBarangay();
            try { if (this.municipality) await this.loadBarangays(); }
            catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        locateBarangay() {
            this.$dispatch('hub-address-changed', {
                province: this.province, municipality: this.municipality, barangay: this.barangay,
            });
        },
    }));
});
