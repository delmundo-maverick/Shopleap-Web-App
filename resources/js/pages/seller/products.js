export function productList(config) {
    return {
        selected: [],
        toggleAll(event) { this.selected = event.target.checked ? config.ids.map(String) : []; },
        discountSelected() {
            const url = new URL(config.discounts, window.location.origin);
            this.selected.forEach(id => url.searchParams.append('product_ids[]', id));
            window.location.assign(url);
        },
    };
}

export function productNavigation(active) {
    return { expanded: Boolean(active), toggleGroup() { this.expanded = !this.expanded; } };
}
