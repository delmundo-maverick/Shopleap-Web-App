/**
 * Alpine component for seller/products/index.blade.php
 * Config comes from Blade: productsPage({ categories, routes, statusVariants, lowStock })
 */

const peso = (value) =>
    '₱' + Number(value).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const emptyForm = () => ({
    name: '',
    description: '',
    category: '',
    subcategory: '',
    price: '',
    discount_price: '',
    stock_quantity: '',
});

const csrfToken = () => document.querySelector('meta[name="csrf-token"]').content;

/** fetch wrapper: always JSON, always CSRF, never throws on HTTP errors. */
async function api(url, { method = 'GET', body } = {}) {
    const res = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            ...(body && { 'Content-Type': 'application/json' }),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    const data = await res.json().catch(() => ({}));

    return {
        ok: res.ok && data.success !== false,
        status: res.status,
        data,
    };
}

export default function productsPage(config) {
    let toastTimer = null;

    return {
        // --- state ---
        categories: config.categories,
        modalOpen: false,
        loading: true,
        saving: false,
        productId: null,
        subcategories: [],
        images: [],
        form: emptyForm(),
        hasVariants: false,
        variants: [],
        errors: {},
        toast: { show: false, message: '', error: false },

        // --- derived (variant products) ---
        get activeVariants() {
            return this.variants.filter((v) => v.is_active);
        },

        get totalStock() {
            return this.activeVariants.reduce((sum, v) => sum + (Number(v.stock_quantity) || 0), 0);
        },

        get startingPrice() {
            const prices = this.activeVariants.map((v) => Number(v.price)).filter((n) => !Number.isNaN(n));
            return prices.length ? Math.min(...prices) : 0;
        },

        // --- helpers ---
        url(name, id) {
            return config.routes[name].replace('__ID__', id);
        },

        row(id) {
            return document.querySelector(`tr[data-row-id="${id}"]`);
        },

        money(value) {
            return peso(value);
        },

        isLow(qty) {
            return Number(qty) <= config.lowStock;
        },

        variantLabel(v) {
            return Object.entries(v.options || {})
                .map(([name, value]) => `${name}: ${value}`)
                .join(', ');
        },

        /** Laravel returns nested errors as "variants.0.price". */
        variantError(index, field) {
            return this.errors[`variants.${index}.${field}`]?.[0];
        },

        showToast(message, isError = false) {
            clearTimeout(toastTimer);
            this.toast = { show: true, message, error: isError };
            toastTimer = setTimeout(() => (this.toast.show = false), 3500);
        },

        /** Handles 422 validation errors; returns true if it handled the failure. */
        handleFailure({ status, data }) {
            if (status === 422 && data.errors) {
                this.errors = data.errors;
            }
            this.showToast(data.message || 'Something went wrong.', true);
        },

        // --- categories ---
        populateSubcategories(categoryName, selected = null) {
            const match = this.categories.find((c) => c.name === categoryName);
            this.subcategories = match ? match.subcategories : [];
            this.form.subcategory =
                selected && this.subcategories.includes(selected) ? selected : '';
        },

        onCategoryChange() {
            this.populateSubcategories(this.form.category);
        },

        // --- modal ---
        async openProduct(id) {
            this.productId = id;
            this.errors = {};
            this.modalOpen = true;
            this.loading = true;

            try {
                const { ok, data } = await api(this.url('details', id));

                // Ignore stale responses if the modal was closed or switched meanwhile
                if (this.productId !== id || !this.modalOpen) return;
                if (!ok) throw new Error('load failed');

                this.form = {
                    name: data.name,
                    description: data.description || '',
                    category: data.category,
                    subcategory: '',
                    price: data.price,
                    discount_price: data.discount_price ?? '',
                    stock_quantity: data.stock_quantity,
                };
                this.populateSubcategories(data.category, data.subcategory);
                this.images = data.images;
                this.hasVariants = !!data.has_variants;
                this.variants = (data.variants || []).map((v) => ({ ...v, sku: v.sku ?? '' }));
                this.loading = false;
                this.updateRow(id, data);
            } catch {
                this.showToast('Failed to load product details.', true);
                this.closeModal();
            }
        },

        closeModal() {
            this.modalOpen = false;
            this.loading = true;
            this.productId = null;
            this.images = [];
            this.hasVariants = false;
            this.variants = [];
            this.errors = {};
        },

        // --- actions ---
        buildSavePayload() {
            const base = {
                name: this.form.name,
                description: this.form.description,
                category: this.form.category,
                subcategory: this.form.subcategory || null,
            };

            // Variant products: price/stock live on the variants; the server recalculates the product.
            if (this.hasVariants) {
                return {
                    ...base,
                    variants: this.variants.map((v) => ({
                        id: v.id,
                        price: v.price,
                        stock_quantity: v.stock_quantity,
                        sku: v.sku || null,
                        is_active: !!v.is_active,
                    })),
                };
            }

            return {
                ...base,
                price: this.form.price,
                discount_price: this.form.discount_price || null,
                stock_quantity: this.form.stock_quantity,
            };
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            this.errors = {};

            try {
                const result = await api(this.url('update', this.productId), {
                    method: 'POST',
                    body: this.buildSavePayload(),
                });

                if (!result.ok) return this.handleFailure(result);

                this.showToast(result.data.message);

                // Take the server's canonical values back (trimmed SKUs, recalculated totals).
                if (this.hasVariants && result.data.variants) {
                    this.variants = result.data.variants.map((v) => ({ ...v, sku: v.sku ?? '' }));
                }

                this.updateRow(this.productId, result.data.product);
            } catch {
                this.showToast('Network error. Please try again.', true);
            } finally {
                this.saving = false;
            }
        },

        async setStatus(status) {
            if (this.saving) return;
            this.saving = true;

            try {
                const result = await api(this.url('status', this.productId), {
                    method: 'POST',
                    body: { status },
                });

                if (!result.ok) return this.handleFailure(result);

                this.showToast(result.data.message);
                this.updateRowStatus(this.productId, result.data.status);
            } catch {
                this.showToast('Network error. Please try again.', true);
            } finally {
                this.saving = false;
            }
        },

        // --- table row updates (no full reload) ---
        updateRow(id, product) {
            const row = this.row(id);
            if (!row) return;

            // Fall back to the submitted form if the server omits a field
            const p = { ...this.form, ...product };
            const hasDiscount = p.discount_price !== null && p.discount_price !== '' && p.discount_price !== undefined;
            const lowVariants = Number(p.low_stock_variants || 0);

            row.querySelector('[data-field="name"]').textContent = p.name;
            row.querySelector('[data-field="category"]').textContent = p.category;
            row.querySelector('[data-field="price"]').textContent = peso(hasDiscount ? p.discount_price : p.price);

            const original = row.querySelector('[data-field="original-price"]');
            original.textContent = peso(p.price);
            original.classList.toggle('hidden', !hasDiscount);

            const stock = row.querySelector('[data-field="stock"]');
            stock.textContent = p.stock_quantity;
            stock.classList.toggle('is-low', Number(p.stock_quantity) <= config.lowStock || lowVariants > 0);

            const note = row.querySelector('[data-field="stock-note"]');
            if (note) {
                note.textContent = `${lowVariants} ${lowVariants === 1 ? 'variant' : 'variants'} low`;
                note.classList.toggle('hidden', lowVariants === 0);
            }
        },

        updateRowStatus(id, status) {
            const badge = this.row(id)?.querySelector('[data-badge="status"]');
            if (!badge) return;

            const variant = config.statusVariants[status] ?? 'neutral';
            badge.textContent = status;
            badge.className = `status-badge badge--${variant}`;
        },
    };
}

// Register with Alpine. This file must load BEFORE app.js calls Alpine.start()
// (see the @vite order in the Blade view).
document.addEventListener('alpine:init', () => {
    window.Alpine.data('productsPage', productsPage);
});
