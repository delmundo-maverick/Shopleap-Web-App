const emptyForm = () => ({ label: '', recipient_name: '', phone: '', province: '', city_municipality: '', barangay: '', street_address: '', postal_code: '', notes: '', is_default: false });

export function addressBook(config) {
    return {
        addresses: config.addresses, checkout: config.checkout, selected: Number(config.selected) || null,
        showAll: false, modalOpen: false, editingId: null, form: emptyForm(), errors: {}, error: '', busy: false,
        loadingLocations: false, locationError: '', options: { province: [], city_municipality: [], barangay: [] },
        init() {
            if (this.checkout && !this.selected) { this.selected = this.addresses.find(a => a.is_default)?.id || null; }
            if (this.checkout && !this.addresses.length) { this.$nextTick(() => this.openModal()); }
        },
        fullAddress(a) { return [a.street_address, a.barangay, a.city_municipality, a.province, a.postal_code].filter(Boolean).join(', '); },
        choose(id) {
            this.selected = id;
            this.showAll = false;
            this.$dispatch('buyer-address-selected', { id });
        },
        async openModal(address = null) {
            this.editingId = address?.id || null;
            this.form = { ...emptyForm(), recipient_name: config.name, phone: config.phone || '', ...(address || {}) };
            this.errors = {}; this.error = ''; this.modalOpen = true;
            await this.loadLocations();
            this.$nextTick(() => document.getElementById('address-recipient_name')?.focus());
        },
        closeModal() { if (!this.busy) { this.modalOpen = false; } },
        async places(level, code = '') {
            const url = new URL(config.locationsUrl, location.origin);
            url.searchParams.set('level', level);
            if (code) { url.searchParams.set('code', code); }
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) { throw new Error('Could not load locations. Please retry.'); }
            return response.json();
        },
        async loadLocations() {
            this.loadingLocations = true; this.locationError = '';
            try {
                this.options.province = await this.places('province');
                await this.loadChildren();
            } catch (error) { this.locationError = error.message; }
            finally { this.loadingLocations = false; }
        },
        async loadChildren() {
            const province = this.options.province.find(p => p.name === this.form.province);
            this.options.city_municipality = province ? await this.places('municipality', province.code) : [];
            const city = this.options.city_municipality.find(p => p.name === this.form.city_municipality);
            this.options.barangay = city ? await this.places('barangay', city.code) : [];
        },
        async locationChanged(field) {
            if (field === 'barangay') { return; }
            if (field === 'province') { this.form.city_municipality = ''; }
            this.form.barangay = '';
            this.loadingLocations = true; this.locationError = '';
            try { await this.loadChildren(); }
            catch (error) { this.locationError = error.message; }
            finally { this.loadingLocations = false; }
        },
        async mutate(url, method, payload = {}) {
            this.busy = true; this.error = ''; this.errors = {};
            try {
                const response = await fetch(url, { method, headers: {
                    'Content-Type': 'application/json', Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }, body: JSON.stringify(payload) });
                const data = await response.json();
                if (!response.ok) {
                    this.errors = data.errors || {};
                    throw new Error(Object.values(this.errors).flat()[0] || data.message || 'Could not save the address.');
                }
                this.addresses = data.addresses;
                const defaultAddress = this.addresses.find(a => a.is_default);
                window.dispatchEvent(new CustomEvent('buyer-default-address-updated', { detail: defaultAddress || null }));
                if (this.checkout) {
                    const id = data.saved_id || (this.addresses.some(a => a.id === this.selected) ? this.selected : defaultAddress?.id);
                    this.choose(id || null);
                }
                return true;
            } catch (error) { this.error = error.message || 'Network error. Please try again.'; return false; }
            finally { this.busy = false; }
        },
        async save() {
            if (this.busy) { return; }
            const url = config.indexUrl + (this.editingId ? `/${this.editingId}` : '');
            if (await this.mutate(url, this.editingId ? 'PUT' : 'POST', this.form)) { this.modalOpen = false; }
        },
        async makeDefault(address) { if (this.busy) { return; } await this.mutate(`${config.indexUrl}/${address.id}/default`, 'PATCH'); },
        async remove(address) {
            if (this.busy) { return; }
            if (window.confirm('Delete this address? Existing orders will keep their delivery address.')) {
                await this.mutate(`${config.indexUrl}/${address.id}`, 'DELETE');
            }
        },
    };
}
