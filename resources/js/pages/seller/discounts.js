export function productDiscounts(config) {
    return {
        selected: config.selected, type: config.type, value: config.value, editing: config.editing,
        money(value) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0)); },
        isSelected(id) { return this.selected.some(product => Number(product.id) === Number(id)); },
        remove(id) { this.selected = this.selected.filter(product => Number(product.id) !== Number(id)); },
        toggle(product, event) {
            if (!event.target.checked) { this.remove(product.id); return; }
            if (this.selected.length >= 100) { event.target.checked = false; this.notify('Select up to 100 products.', true); return; }
            if (!this.isSelected(product.id)) this.selected.push(product);
        },
        finalPrice(product) {
            const original = Number(product.price);
            return Math.round((this.type === 'percent' ? original * (1 - Number(this.value) / 100) : original - Number(this.value)) * 100) / 100;
        },
        validPrice(product) {
            return Number(this.value) > 0 && (this.type !== 'percent' || Number(this.value) <= 90)
                && this.finalPrice(product) >= 0.01 && this.finalPrice(product) < Number(product.price);
        },
        get canSave() { return this.selected.length > 0 && this.selected.every(product => this.validPrice(product)); },
        preserveSelection(event) {
            const link = event.target.closest('a[href]');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;
            event.preventDefault();
            const url = new URL(link.href);
            url.searchParams.delete('product_ids[]');
            [...url.searchParams.keys()].filter(key => key.startsWith('product_ids[')).forEach(key => url.searchParams.delete(key));
            this.selected.forEach(product => url.searchParams.append('product_ids[]', product.id));
            window.location.assign(url);
        },
    };
}
