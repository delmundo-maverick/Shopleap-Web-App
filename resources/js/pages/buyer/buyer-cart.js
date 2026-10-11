/**
 * Buyer cart (items grouped by shop).
 * Markup contract lives in resources/views/Buyer/cart/index.blade.php:
 *   .shop-group > .shop-checkbox, .shop-subtotal
 *   .cart-row[data-cart-item-id|shop-id|name|unit-price|line-total|qty|stock|available|reason]
 */
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('cartItemsList');
    if (!list) return; // empty-cart state: nothing to wire up

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const page = document.getElementById('cartPageData').dataset;
    const cartData = JSON.parse(document.getElementById('cartData')?.textContent || '[]');

    const selected = new Set(); // cart item ids (strings) ticked for checkout

    // -----------------------------------------------------
    // Small helpers
    // -----------------------------------------------------
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    const peso = (amount) =>
        '₱' + Number(amount || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

    const urlFor = (template, id) => template.replace('__ID__', id);
    const rows = () => $$('.cart-row', list);
    const rowById = (id) => $(`.cart-row[data-cart-item-id="${id}"]`, list);
    const isAvailable = (row) => row.dataset.available === '1';
    const clampQty = (value, stock) => Math.min(Math.max(1, Number(value) || 1), Math.max(1, Number(stock)));

    /** fetch wrapper: JSON + CSRF, never throws, always resolves { ok, status, data }. */
    async function api(url, { method = 'GET', body } = {}) {
        try {
            const res = await fetch(url, {
                method,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    ...(body && { 'Content-Type': 'application/json' }),
                },
                body: body ? JSON.stringify(body) : undefined,
            });
            const data = await res.json().catch(() => ({}));
            return { ok: res.ok && data.success !== false, status: res.status, data };
        } catch {
            return { ok: false, status: 0, data: { message: 'Network error. Please try again.' } };
        }
    }

    const toastEl = document.getElementById('toast');
    let toastTimer = null;

    function showToast(message, isError = false) {
        clearTimeout(toastTimer);
        toastEl.textContent = message;
        toastEl.classList.toggle('bg-sale-red', isError);
        toastEl.classList.toggle('bg-leaf-green', !isError);
        toastEl.classList.remove('hidden');
        toastTimer = setTimeout(() => toastEl.classList.add('hidden'), 3200);
    }

    function updateHeaderBadge(count) {
        const badge = document.getElementById('buyerCartBadge');
        if (badge && count !== undefined) badge.textContent = count;
    }

    // -----------------------------------------------------
    // Row state (availability, stock warnings)
    // -----------------------------------------------------
    function refreshRowState(row) {
        const id = row.dataset.cartItemId;
        const available = isAvailable(row);
        const stock = Number(row.dataset.stock);
        const qty = Number(row.dataset.qty);

        let message = available ? '' : row.dataset.reason || 'This item is unavailable.';
        if (available && qty > stock) message = `Only ${stock} left. Please reduce the quantity.`;

        const notice = $('.row-notice', row);
        notice.textContent = message;
        notice.classList.toggle('hidden', !message);

        const checkbox = $('.item-checkbox', row);
        checkbox.disabled = !available;
        if (!available) {
            checkbox.checked = false;
            selected.delete(id);
        }

        $('.row-thumb', row)?.classList.toggle('opacity-50', !available);
        $$('.qty-minus, .qty-plus, .qty-input', row).forEach((el) => (el.disabled = !available));

        const input = $('.qty-input', row);
        input.max = Math.max(1, stock);
        input.value = qty;
    }

    /** Commit a confirmed quantity/price to the row and re-render it. */
    function setRowQuantity(row, qty, unitPrice = Number(row.dataset.unitPrice)) {
        row.dataset.qty = qty;
        row.dataset.unitPrice = unitPrice;
        row.dataset.lineTotal = qty * unitPrice;

        $('.line-total', row).textContent = peso(row.dataset.lineTotal);
        $$('.unit-price-cell', row).forEach((cell) => (cell.textContent = peso(unitPrice)));
        refreshRowState(row);
    }

    /** Optimistic display while the (debounced) request is on its way. */
    function previewQuantity(row, qty) {
        row.dataset.lineTotal = qty * Number(row.dataset.unitPrice);
        $('.line-total', row).textContent = peso(row.dataset.lineTotal);
        $('.qty-input', row).value = qty;
        updateFooter();
    }

    function setOptionsLabel(row, options) {
        const label = $('.selected-options-label', row);
        const text = options
            ? Object.entries(options).map(([name, value]) => `${name}: ${value}`).join(', ')
            : '';
        label.textContent = text;
        label.classList.toggle('hidden', !text);
    }

    // -----------------------------------------------------
    // Selection + sticky footer
    // -----------------------------------------------------
    function setSelected(row, checked) {
        const id = row.dataset.cartItemId;
        $('.item-checkbox', row).checked = checked;
        checked ? selected.add(id) : selected.delete(id);
    }

    function updateFooter() {
        const all = rows();
        const selectable = all.filter(isAvailable);
        const picked = all.filter((row) => selected.has(row.dataset.cartItemId));

        const subtotal = picked.reduce((sum, row) => sum + Number(row.dataset.lineTotal), 0);
        const shopCount = new Set(picked.map((row) => row.dataset.shopId)).size;

        // Per-shop checkbox + "Selected: ₱x" line
        $$('.shop-group', list).forEach((group) => {
            const groupSelectable = $$('.cart-row', group).filter(isAvailable);
            const groupPicked = groupSelectable.filter((row) => selected.has(row.dataset.cartItemId));
            const box = $('.shop-checkbox', group);

            box.disabled = groupSelectable.length === 0;
            box.checked = groupSelectable.length > 0 && groupPicked.length === groupSelectable.length;
            box.indeterminate = groupPicked.length > 0 && groupPicked.length < groupSelectable.length;

            const shopTotal = groupPicked.reduce((sum, row) => sum + Number(row.dataset.lineTotal), 0);
            $('.shop-subtotal', group).textContent = groupPicked.length ? `Selected: ${peso(shopTotal)}` : '';
        });

        // Select-all checkboxes
        const allChecked = selectable.length > 0 && selectable.every((row) => selected.has(row.dataset.cartItemId));
        ['selectAllTop', 'selectAllBottom'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.disabled = selectable.length === 0;
            el.checked = allChecked;
            el.indeterminate = picked.length > 0 && !allChecked;
        });

        document.getElementById('totalItemCount').textContent = selectable.length;
        document.getElementById('selectedCount').textContent = picked.length;
        document.getElementById('selectedCountPlural').textContent = picked.length === 1 ? '' : 's';
        document.getElementById('selectedSubtotal').textContent = peso(subtotal);

        const shopsEl = document.getElementById('selectedShops');
        shopsEl.textContent = `${shopCount} shops · ships as ${shopCount} orders`;
        shopsEl.classList.toggle('hidden', shopCount < 2);

        document.getElementById('checkoutBtn').disabled = picked.length === 0;
        document.getElementById('deleteSelectedBtn').disabled = picked.length === 0;

        // Banner for lines that can't be bought
        const unavailable = all.filter((row) => !isAvailable(row));
        const banner = document.getElementById('unavailableBanner');
        document.getElementById('unavailableCount').textContent = unavailable.length;
        banner.classList.toggle('hidden', unavailable.length === 0);
        banner.classList.toggle('flex', unavailable.length > 0);
    }

    list.addEventListener('change', (event) => {
        const target = event.target;

        if (target.matches('.item-checkbox')) {
            setSelected(target.closest('.cart-row'), target.checked);
        } else if (target.matches('.shop-checkbox')) {
            $$('.cart-row', target.closest('.shop-group'))
                .filter(isAvailable)
                .forEach((row) => setSelected(row, target.checked));
        } else if (target.matches('.qty-input')) {
            const row = target.closest('.cart-row');
            scheduleQuantity(row, clampQty(target.value, row.dataset.stock));
            return;
        }

        updateFooter();
    });

    ['selectAllTop', 'selectAllBottom'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', (event) => {
            rows().filter(isAvailable).forEach((row) => setSelected(row, event.target.checked));
            updateFooter();
        });
    });

    // -----------------------------------------------------
    // Quantity changes (debounced, newest request wins)
    // -----------------------------------------------------
    const timers = new Map(); // row id -> { timer, row, qty }
    const tickets = new Map(); // row id -> latest request number
    const inFlight = new Set();

    function track(promise) {
        inFlight.add(promise);
        promise.finally(() => inFlight.delete(promise));
        return promise;
    }

    function scheduleQuantity(row, qty) {
        const id = row.dataset.cartItemId;

        previewQuantity(row, qty);
        clearTimeout(timers.get(id)?.timer);

        const timer = setTimeout(() => {
            timers.delete(id);
            track(commitQuantity(row, qty));
        }, 350);
        timers.set(id, { timer, row, qty });
    }

    /** Send anything still waiting on the debounce, then wait for running requests. */
    async function flushPending() {
        for (const [id, entry] of [...timers]) {
            clearTimeout(entry.timer);
            timers.delete(id);
            track(commitQuantity(entry.row, entry.qty));
        }
        await Promise.all([...inFlight]);
    }

    async function commitQuantity(row, qty, variantId = null) {
        const id = row.dataset.cartItemId;
        const ticket = (tickets.get(id) || 0) + 1;
        tickets.set(id, ticket);

        const result = await api(urlFor(page.updateUrl, id), {
            method: 'POST',
            body: { quantity: qty, ...(variantId && { variant_id: variantId }) },
        });

        if (!row.isConnected) return null;

        // A newer change superseded this one; it will render the final state.
        if (tickets.get(id) !== ticket) {
            if (result.ok) row.dataset.qty = qty;
            return null;
        }

        if (!result.ok) {
            setRowQuantity(row, Number(row.dataset.qty)); // roll back to the last confirmed quantity
            updateFooter();
            showToast(result.data.message || 'Could not update your cart.', true);
            return null;
        }

        applyUpdate(row, result.data, qty);
        return result.data;
    }

    function applyUpdate(row, data, qty) {
        updateHeaderBadge(data.cart_count);

        if (data.merged) {
            // This line was folded into an existing identical line.
            const target = rowById(data.target_id);
            if (target) {
                target.dataset.stock = data.stock;
                setRowQuantity(target, Number(data.quantity), Number(data.unit_price));
                target.dataset.lineTotal = data.line_total;
                $('.line-total', target).textContent = peso(data.line_total);
            }
            removeRowFromDom(row);
            updateFooter();
            showToast('Merged with a matching item already in your cart.');
            return;
        }

        row.dataset.stock = data.stock;
        row.dataset.available = '1'; // the server accepted this option and quantity
        row.dataset.reason = '';
        setRowQuantity(row, qty, Number(data.unit_price));
        row.dataset.lineTotal = data.line_total; // trust the server's total
        $('.line-total', row).textContent = peso(data.line_total);
        setOptionsLabel(row, data.selected_options);
        updateFooter();
    }

    // -----------------------------------------------------
    // Row actions (event delegation)
    // -----------------------------------------------------
    list.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) return;

        const row = button.closest('.cart-row');
        if (!row) return;

        if (button.matches('.qty-minus')) stepQuantity(row, -1);
        else if (button.matches('.qty-plus')) stepQuantity(row, +1);
        else if (button.matches('.remove-btn')) removeRow(row);
        else if (button.matches('.change-variant-btn')) openVariantModal(row, button);
    });

    function stepQuantity(row, delta) {
        const input = $('.qty-input', row);
        const stock = Number(row.dataset.stock);
        const current = Number(input.value) || Number(row.dataset.qty);
        const next = clampQty(current + delta, stock);

        if (next === current) {
            if (delta > 0) showToast(`Only ${stock} in stock.`, true);
            return;
        }
        scheduleQuantity(row, next);
    }

    function removeRowFromDom(row) {
        const group = row.closest('.shop-group');
        selected.delete(row.dataset.cartItemId);
        row.remove();
        if (group && !$('.cart-row', group)) group.remove();
    }

    function finishRemoval() {
        if (rows().length === 0) {
            window.location.reload(); // re-render the empty-cart state
            return;
        }
        updateFooter();
    }

    async function removeRow(row) {
        row.classList.add('opacity-50');
        const result = await api(urlFor(page.destroyUrl, row.dataset.cartItemId), { method: 'DELETE' });

        if (!result.ok) {
            row.classList.remove('opacity-50');
            showToast('Could not remove the item.', true);
            return;
        }

        updateHeaderBadge(result.data.cart_count);
        removeRowFromDom(row);
        finishRemoval();
    }

    async function removeMany(ids, confirmText = null) {
        if (ids.length === 0) return;
        if (confirmText && !window.confirm(confirmText)) return;

        const result = await api(page.bulkDestroyUrl, { method: 'DELETE', body: { ids } });

        if (!result.ok) {
            showToast(result.data.message || 'Could not remove the items.', true);
            return;
        }

        (result.data.removed_ids || ids).forEach((id) => {
            const row = rowById(id);
            if (row) removeRowFromDom(row);
        });
        updateHeaderBadge(result.data.cart_count);
        finishRemoval();
    }

    document.getElementById('deleteSelectedBtn')?.addEventListener('click', () => {
        const ids = [...selected];
        removeMany(ids, `Remove ${ids.length} item${ids.length === 1 ? '' : 's'} from your cart?`);
    });

    document.getElementById('removeUnavailableBtn')?.addEventListener('click', () => {
        removeMany(rows().filter((row) => !isAvailable(row)).map((row) => row.dataset.cartItemId));
    });

    // -----------------------------------------------------
    // Checkout (only the ticked items)
    // -----------------------------------------------------
    document.getElementById('checkoutBtn')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (selected.size === 0) return;

        button.disabled = true;
        await flushPending(); // make sure the server has the quantities the buyer is looking at

        const picked = rows().filter((row) => selected.has(row.dataset.cartItemId));
        const tooMany = picked.find((row) => Number(row.dataset.qty) > Number(row.dataset.stock));

        if (picked.length === 0) {
            updateFooter();
            return;
        }

        if (tooMany) {
            showToast(`Only ${tooMany.dataset.stock} left of "${tooMany.dataset.name}". Please reduce the quantity.`, true);
            updateFooter();
            return;
        }

        window.location.href = `${page.checkoutRoute}?items=${picked.map((row) => row.dataset.cartItemId).join(',')}`;
    });

    // -----------------------------------------------------
    // CHANGE VARIANT MODAL
    // -----------------------------------------------------
    const overlay = document.getElementById('cartVariantModalOverlay');
    const optionsContainer = document.getElementById('cartVariantOptionsContainer');
    const modalTitle = document.getElementById('cartVariantModalTitle');
    const modalPrice = document.getElementById('cartVariantModalPrice');
    const modalStock = document.getElementById('cartVariantModalStock');
    const modalWarning = document.getElementById('cartVariantModalWarning');
    const confirmBtn = document.getElementById('cartVariantModalConfirmBtn');

    const BTN_BASE = 'cart-variant-value-btn rounded-lg border px-4 py-2 text-xs font-semibold transition';

    let activeItem = null;
    let activeRow = null;
    let activeVariant = null;
    let activeSelections = {};
    let lastTrigger = null;

    function openVariantModal(row, trigger) {
        const item = cartData.find((entry) => String(entry.id) === row.dataset.cartItemId);
        if (!item) return;

        activeItem = item;
        activeRow = row;
        lastTrigger = trigger;
        activeSelections = { ...(item.selectedOptions || {}) };

        modalTitle.textContent = row.dataset.name;
        optionsContainer.innerHTML = '';

        item.options.forEach((option) => {
            const group = document.createElement('div');
            group.className = 'mb-4';

            const label = document.createElement('p');
            label.className = 'mb-2 text-sm font-semibold text-charcoal';
            label.textContent = option.name;
            group.appendChild(label);

            const buttons = document.createElement('div');
            buttons.className = 'flex flex-wrap gap-2';

            option.values.forEach((value) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.optionName = option.name;
                btn.dataset.optionValue = value;
                btn.textContent = value;
                btn.addEventListener('click', () => {
                    activeSelections[option.name] = value;
                    resolveActiveVariant();
                });
                buttons.appendChild(btn);
            });

            group.appendChild(buttons);
            optionsContainer.appendChild(group);
        });

        resolveActiveVariant();

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        ($('.cart-variant-value-btn:not(:disabled)', optionsContainer) || confirmBtn).focus();
    }

    function priceRangeText() {
        const prices = activeItem.variants.map((variant) => variant.price);
        if (prices.length === 0) return peso(activeItem.unitPrice);

        const min = Math.min(...prices);
        const max = Math.max(...prices);
        return min === max ? peso(min) : `${peso(min)} – ${peso(max)}`;
    }

    /** Styles each option button and disables values that are sold out for the current picks. */
    function refreshOptionButtons() {
        $$('.cart-variant-value-btn', optionsContainer).forEach((btn) => {
            const group = btn.dataset.optionName;
            const value = btn.dataset.optionValue;
            const isSelected = activeSelections[group] === value;
            const others = Object.entries(activeSelections).filter(([name]) => name !== group);

            const possible = activeItem.variants.filter(
                (variant) =>
                    variant.options[group] === value && others.every(([name, val]) => variant.options[name] === val),
            );
            const soldOut = possible.length === 0 || possible.every((variant) => variant.stock_quantity < 1);

            btn.disabled = soldOut && !isSelected;
            btn.setAttribute('aria-pressed', String(isSelected));
            btn.className =
                `${BTN_BASE} ` +
                (isSelected
                    ? 'border-primary bg-primary/5 text-primary'
                    : btn.disabled
                        ? 'cursor-not-allowed border-light-gray text-charcoal/30 line-through'
                        : 'border-light-gray text-charcoal hover:border-primary');
        });
    }

    function resolveActiveVariant() {
        const names = activeItem.options.map((option) => option.name);
        const allSelected = names.every((name) => activeSelections[name]);

        activeVariant = allSelected
            ? activeItem.variants.find((variant) => names.every((name) => variant.options[name] === activeSelections[name])) || null
            : null;

        refreshOptionButtons();

        modalWarning.classList.toggle('hidden', !(allSelected && !activeVariant));
        confirmBtn.disabled = !activeVariant || activeVariant.stock_quantity < 1;

        if (activeVariant) {
            modalPrice.textContent = peso(activeVariant.price);
            modalStock.textContent = activeVariant.stock_quantity > 0 ? `${activeVariant.stock_quantity} in stock` : 'Out of stock';
        } else {
            modalPrice.textContent = priceRangeText();
            modalStock.textContent = 'Select all options';
        }
    }

    function closeVariantModal() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');

        if (lastTrigger?.isConnected) lastTrigger.focus();

        activeItem = null;
        activeRow = null;
        activeVariant = null;
        activeSelections = {};
        lastTrigger = null;
    }

    document.getElementById('cartVariantModalCloseBtn')?.addEventListener('click', closeVariantModal);
    overlay?.addEventListener('click', (event) => {
        if (event.target === overlay) closeVariantModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !overlay.classList.contains('hidden')) closeVariantModal();
    });

    confirmBtn?.addEventListener('click', async () => {
        if (!activeItem || !activeVariant || !activeRow) return;

        const row = activeRow;
        const item = activeItem;
        const variant = activeVariant;

        confirmBtn.disabled = true;
        await flushPending();
        if (!row.isConnected) {
            closeVariantModal();
            return;
        }

        // Keep the buyer's quantity, but never above the new variant's stock.
        const wanted = Number(row.dataset.qty);
        const qty = Math.min(wanted, variant.stock_quantity);

        const data = await commitQuantity(row, qty, variant.id);

        if (!data) {
            resolveActiveVariant(); // failed: keep the modal open and re-enable Confirm
            return;
        }

        // Keep the in-memory snapshot in sync so reopening the modal shows the right pre-selection.
        item.selectedOptions = data.selected_options;
        item.unitPrice = data.unit_price;
        item.maxStock = data.stock;
        if (data.merged) {
            const index = cartData.indexOf(item);
            if (index !== -1) cartData.splice(index, 1);
        }

        if (!data.merged) {
            showToast(qty < wanted ? `Option updated. Quantity adjusted to ${qty} (stock limit).` : 'Option updated.');
        }
        closeVariantModal();
    });

    // -----------------------------------------------------
    // Init
    // -----------------------------------------------------
    rows().forEach(refreshRowState);
    updateFooter();
});
