const money = value => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0));
const validImage = file => ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 10 * 1024 * 1024;

export function productForm(config) {
    return {
        editing: config.editing, name: config.name, description: config.description,
        category: config.category, subcategory: config.subcategory, categories: config.categories,
        price: config.price, stock: config.stock,
        existingImages: config.existingImages, removedImages: config.removedImages.map(String),
        selectedImages: [], imageError: '', variantError: '', nextKey: 0,
        options: Object.values(config.options || {}).map((row, index) => ({ ...row, key: index })),
        variants: Object.values(config.variants || {}).map((row, index) => ({ ...row, is_active: Number(row.is_active ?? 1), key: row.key || row.id || 'restored-' + index })),
        get variantMode() { return this.variants.length > 0; },
        get subcategories() { return this.categories.find(category => category.name === this.category)?.subcategories || []; },
        get cover() {
            return this.existingImages.find(image => !this.removedImages.includes(String(image.id)))?.url
                || this.selectedImages[0]?.url
                || this.variants.find(row => row.preview || (row.image_url && !row.remove_image))?.preview
                || this.variants.find(row => row.image_url && !row.remove_image)?.image_url || '';
        },
        get previewPrice() {
            const active = this.variants.filter(row => Number(row.is_active));
            return (this.variantMode ? 'From ' : '') + money(this.variantMode ? Math.min(...(active.length ? active : this.variants).map(row => Number(row.price || 0))) : this.price);
        },
        get pricingComplete() {
            return this.variantMode
                ? this.variants.some(row => Number(row.is_active)) && this.variants.every(row => row.price !== '' && row.price != null && row.stock_quantity !== '' && row.stock_quantity != null)
                : this.price !== '' && this.price != null && this.stock !== '' && this.stock != null;
        },
        syncImages() {
            const transfer = new DataTransfer();
            this.selectedImages.forEach(image => transfer.items.add(image.file));
            this.$refs.images.files = transfer.files;
        },
        addImages(event) {
            this.imageError = '';
            const incoming = Array.from(event.target.files || []);
            const remaining = 5 - this.existingImages.filter(image => !this.removedImages.includes(String(image.id))).length - this.selectedImages.length;
            if (incoming.length > remaining) this.imageError = 'Keep at most five product images.';
            for (const file of incoming.slice(0, Math.max(0, remaining))) {
                if (!validImage(file)) { this.imageError = 'Use JPG, PNG or WebP images no larger than 10 MB.'; continue; }
                this.selectedImages.push({ key: ++this.nextKey, file, url: URL.createObjectURL(file) });
            }
            this.syncImages();
        },
        removeImage(index) {
            URL.revokeObjectURL(this.selectedImages[index].url);
            this.selectedImages.splice(index, 1);
            this.syncImages();
        },
        previewVariant(event, row) {
            const file = event.target.files?.[0];
            if (row.preview) URL.revokeObjectURL(row.preview);
            row.preview = '';
            if (!file) return;
            if (!validImage(file)) {
                this.imageError = 'Use JPG, PNG or WebP images no larger than 10 MB.';
                event.target.value = '';
                return;
            }
            this.imageError = '';
            row.preview = URL.createObjectURL(file);
        },
        addOption() {
            if (this.options.length < 3) this.options.push({ key: ++this.nextKey + Date.now(), name: '', values: '' });
        },
        removeOption(index) { this.options.splice(index, 1); if (!this.options.length) this.variants = []; },
        variantLabel(row) { return Object.entries(row.options || {}).map(([key, value]) => key + ': ' + value).join(' / '); },
        generateVariants() {
            this.variantError = '';
            const names = this.options.map(row => row.name.trim());
            if (names.some(name => !name || /[\[\]]/.test(name)) || new Set(names.map(name => name.toLowerCase())).size !== names.length) {
                this.variantError = 'Give every option a unique name without square brackets.'; return;
            }
            const groups = this.options.map(row => ({ name: row.name.trim(), values: [...new Set(row.values.split(',').map(value => value.trim()).filter(Boolean))] }));
            if (groups.some(group => !group.values.length) || groups.reduce((count, group) => count * group.values.length, 1) > 100) {
                this.variantError = 'Enter option values and keep the total at 100 combinations or fewer.'; return;
            }
            const combinations = groups.reduce((rows, group) => rows.flatMap(row => group.values.map(value => ({ ...row, [group.name]: value }))), [{}]);
            const existing = new Map(this.variants.map(row => [JSON.stringify(row.options), row]));
            this.variants = combinations.map(options => existing.get(JSON.stringify(options)) || {
                key: JSON.stringify(options), options, price: this.price, stock_quantity: this.stock, sku: '', weight_kg: '', is_active: 1,
            });
        },
        destroy() {
            this.selectedImages.forEach(image => URL.revokeObjectURL(image.url));
            this.variants.forEach(row => { if (row.preview) URL.revokeObjectURL(row.preview); });
        },
    };
}
