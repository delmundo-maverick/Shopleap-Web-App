document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const checkoutRoute = document.getElementById('cartPageData').dataset.checkoutRoute;
    const cartData = JSON.parse(document.getElementById('cartData').textContent);
    const selectedIds = new Set();

    function showToast(message, isError = false) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.style.background = isError ? '#DC2626' : '#16A34A';
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    }

    function updateHeaderBadge(count) {
        const badge = document.getElementById('buyerCartBadge');
        if (badge) badge.textContent = count;
    }

    function peso(amount) {
        return `₱${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
    }

    // -----------------------------------------------------
    // SELECTION / STICKY FOOTER LOGIC
    // -----------------------------------------------------
    function getAllRows() {
        return document.querySelectorAll('[data-cart-item-id]');
    }

    function updateFooter() {
        const rows = getAllRows();
        let subtotal = 0;

        rows.forEach(row => {
            const id = row.dataset.cartItemId;
            if (selectedIds.has(id)) {
                subtotal += parseFloat(row.dataset.lineTotal || 0);
            }
        });

        const count = selectedIds.size;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('selectedCountPlural').textContent = count === 1 ? '' : 's';
        document.getElementById('selectedSubtotal').textContent = peso(subtotal);
        document.getElementById('totalItemCount').textContent = rows.length;

        const checkoutBtn = document.getElementById('checkoutBtn');
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        checkoutBtn.disabled = count === 0;
        deleteBtn.disabled = count === 0;

        const allChecked = rows.length > 0 && count === rows.length;
        ['selectAllTop', 'selectAllBottom'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.checked = allChecked;
            el.indeterminate = count > 0 && !allChecked;
        });
    }

    function toggleSelectAll(checked) {
        getAllRows().forEach(row => {
            const checkbox = row.querySelector('.item-checkbox');
            checkbox.checked = checked;
            const id = row.dataset.cartItemId;
            checked ? selectedIds.add(id) : selectedIds.delete(id);
        });
        updateFooter();
    }

    document.getElementById('selectAllTop')?.addEventListener('change', e => toggleSelectAll(e.target.checked));
    document.getElementById('selectAllBottom')?.addEventListener('change', e => toggleSelectAll(e.target.checked));

    function wireCheckbox(checkbox) {
        const row = checkbox.closest('[data-cart-item-id]');
        checkbox.addEventListener('change', () => {
            const id = row.dataset.cartItemId;
            checkbox.checked ? selectedIds.add(id) : selectedIds.delete(id);
            updateFooter();
        });
    }
    document.querySelectorAll('.item-checkbox').forEach(wireCheckbox);

    // -----------------------------------------------------
    // DELETE SELECTED (bulk)
    // -----------------------------------------------------
    document.getElementById('deleteSelectedBtn')?.addEventListener('click', async () => {
        if (selectedIds.size === 0) return;
        if (!confirm(`Remove ${selectedIds.size} item(s) from your cart?`)) return;

        const idsToRemove = Array.from(selectedIds);
        let lastData = null;

        for (const id of idsToRemove) {
            try {
                const res = await fetch(`/buyer/cart/item/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    document.querySelector(`[data-cart-item-id="${id}"]`)?.remove();
                    selectedIds.delete(id);
                    lastData = data;
                }
            } catch (err) {
                showToast('Network error while removing an item.', true);
            }
        }

        if (lastData) updateHeaderBadge(lastData.cart_count);
        updateFooter();

        if (getAllRows().length === 0) window.location.reload();
    });

    // -----------------------------------------------------
    // CHECKOUT (only the selected items)
    // -----------------------------------------------------
    document.getElementById('checkoutBtn')?.addEventListener('click', () => {
        if (selectedIds.size === 0) return;
        window.location.href = `${checkoutRoute}?items=${Array.from(selectedIds).join(',')}`;
    });

    // -----------------------------------------------------
    // QUANTITY / REMOVE (per item)
    // -----------------------------------------------------
    async function patchQuantity(row, quantity, variantId = null) {
        const id = row.dataset.cartItemId;
        try {
            const res = await fetch(`/buyer/cart/item/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ quantity, variant_id: variantId }),
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                showToast(data.message || 'Could not update quantity.', true);
                return null;
            }

            updateHeaderBadge(data.cart_count);
            return data;
        } catch (err) {
            showToast('Network error — please try again.', true);
            return null;
        }
    }

    function wireRow(row) {
        const input = row.querySelector('.qty-input');

        row.querySelector('.qty-minus')?.addEventListener('click', async () => {
            const newVal = Math.max(1, parseInt(input.value || 1) - 1);
            input.value = newVal;
            const data = await patchQuantity(row, newVal);
            if (data) applyRowUpdate(row, data);
        });

        row.querySelector('.qty-plus')?.addEventListener('click', async () => {
            const max = parseInt(input.max);
            const newVal = Math.min(max, parseInt(input.value || 1) + 1);
            input.value = newVal;
            const data = await patchQuantity(row, newVal);
            if (data) applyRowUpdate(row, data);
        });

        input?.addEventListener('change', async () => {
            let val = parseInt(input.value || 1);
            val = Math.max(1, Math.min(parseInt(input.max), val));
            input.value = val;
            const data = await patchQuantity(row, val);
            if (data) applyRowUpdate(row, data);
        });

        row.querySelector('.remove-btn')?.addEventListener('click', async () => {
            const id = row.dataset.cartItemId;
            try {
                const res = await fetch(`/buyer/cart/item/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    showToast('Could not remove item.', true);
                    return;
                }

                selectedIds.delete(id);
                row.remove();
                updateHeaderBadge(data.cart_count);
                updateFooter();

                if (data.cart_count === 0) window.location.reload();
            } catch (err) {
                showToast('Network error — please try again.', true);
            }
        });
    }

    function applyRowUpdate(row, data) {
        if (data.merged) {
            // The row we edited got folded into another existing line — remove it,
            // update the target row's quantity/total, and refresh the footer.
            const targetRow = document.querySelector(`[data-cart-item-id="${data.target_id}"]`);
            row.remove();
            if (targetRow) {
                targetRow.dataset.lineTotal = data.line_total;
                targetRow.querySelector('.line-total').textContent = peso(data.line_total);
                const targetInput = targetRow.querySelector('.qty-input');
                if (targetInput) {
                    targetInput.value = data.quantity;
                    targetInput.max = data.stock;
                }
            }
            selectedIds.delete(row.dataset.cartItemId);
            updateFooter();
            showToast('Merged with an existing matching item in your cart.');
            return;
        }

        row.dataset.lineTotal = data.line_total;
        row.querySelector('.line-total').textContent = peso(data.line_total);

        const priceCells = row.querySelectorAll('.unit-price-cell');
        priceCells.forEach(cell => cell.textContent = peso(data.unit_price));

        const qtyInput = row.querySelector('.qty-input');
        if (qtyInput) qtyInput.max = data.stock;

        const optionsLabel = row.querySelector('.selected-options-label');
        if (optionsLabel) {
            if (data.selected_options) {
                optionsLabel.textContent = Object.entries(data.selected_options)
                    .map(([k, v]) => `${k}: ${v}`).join(', ');
                optionsLabel.classList.remove('hidden');
            } else {
                optionsLabel.classList.add('hidden');
            }
        }

        updateFooter();
    }

    document.querySelectorAll('[data-cart-item-id]').forEach(wireRow);

    // -----------------------------------------------------
    // CHANGE VARIANT MODAL
    // -----------------------------------------------------
    const variantModalOverlay = document.getElementById('cartVariantModalOverlay');
    const variantOptionsContainer = document.getElementById('cartVariantOptionsContainer');
    const variantModalPrice = document.getElementById('cartVariantModalPrice');
    const variantModalStock = document.getElementById('cartVariantModalStock');
    const variantModalWarning = document.getElementById('cartVariantModalWarning');
    const variantModalConfirmBtn = document.getElementById('cartVariantModalConfirmBtn');

    let activeItem = null;
    let activeSelections = {};
    let activeVariant = null;

    function findItemData(cartItemId) {
        return cartData.find(item => String(item.id) === String(cartItemId));
    }

    function openVariantModal(cartItemId) {
        activeItem = findItemData(cartItemId);
        if (!activeItem) return;

        activeSelections = { ...(activeItem.selectedOptions || {}) };
        activeVariant = null;

        variantOptionsContainer.innerHTML = '';

        activeItem.options.forEach(option => {
            const groupWrap = document.createElement('div');
            groupWrap.className = 'mb-4';

            const label = document.createElement('p');
            label.className = 'mb-2 text-sm font-semibold text-charcoal';
            label.textContent = option.name;
            groupWrap.appendChild(label);

            const btnWrap = document.createElement('div');
            btnWrap.className = 'flex flex-wrap gap-2 cart-variant-option-group';
            btnWrap.dataset.optionName = option.name;

            option.values.forEach(value => {
                const btn = document.createElement('button');
                btn.type = 'button';
                const isActive = activeSelections[option.name] === value;
                btn.className = `cart-variant-value-btn rounded-lg border px-4 py-2 text-xs font-semibold transition ${isActive
                        ? 'border-primary bg-primary/5 text-primary'
                        : 'border-light-gray text-charcoal hover:border-primary'
                    }`;
                btn.dataset.optionName = option.name;
                btn.dataset.optionValue = value;
                btn.textContent = value;
                btn.addEventListener('click', () => selectVariantValue(option.name, value));
                btnWrap.appendChild(btn);
            });

            groupWrap.appendChild(btnWrap);
            variantOptionsContainer.appendChild(groupWrap);
        });

        resolveActiveVariant();

        variantModalOverlay.classList.remove('hidden');
        variantModalOverlay.classList.add('flex');
    }

    function selectVariantValue(groupName, value) {
        document.querySelectorAll(`.cart-variant-value-btn[data-option-name="${groupName}"]`)
            .forEach(btn => {
                const isMatch = btn.dataset.optionValue === value;
                btn.classList.toggle('border-primary', isMatch);
                btn.classList.toggle('bg-primary/5', isMatch);
                btn.classList.toggle('text-primary', isMatch);
                btn.classList.toggle('border-light-gray', !isMatch);
                btn.classList.toggle('text-charcoal', !isMatch);
            });

        activeSelections[groupName] = value;
        resolveActiveVariant();
    }

    function resolveActiveVariant() {
        const groups = document.querySelectorAll('.cart-variant-option-group');
        const allSelected = groups.length > 0 &&
            Array.from(groups).every(g => activeSelections[g.dataset.optionName]);

        if (!allSelected) {
            activeVariant = null;
            variantModalWarning.classList.add('hidden');
            variantModalConfirmBtn.disabled = true;
            return;
        }

        activeVariant = activeItem.variants.find(v =>
            Object.keys(activeSelections).every(key => v.options[key] === activeSelections[key])
        ) || null;

        variantModalWarning.classList.toggle('hidden', !!activeVariant);
        variantModalConfirmBtn.disabled = !activeVariant || activeVariant.stock_quantity < 1;

        const price = activeVariant ? activeVariant.price : activeItem.unitPrice;
        const stock = activeVariant ? activeVariant.stock_quantity : activeItem.maxStock;
        variantModalPrice.textContent = peso(price);
        variantModalStock.textContent = `${stock} in stock`;
    }

    function closeVariantModal() {
        variantModalOverlay.classList.add('hidden');
        variantModalOverlay.classList.remove('flex');
        activeItem = null;
        activeVariant = null;
        activeSelections = {};
    }

    document.querySelectorAll('.change-variant-btn').forEach(btn => {
        btn.addEventListener('click', () => openVariantModal(btn.dataset.cartItemId));
    });

    document.getElementById('cartVariantModalCloseBtn')?.addEventListener('click', closeVariantModal);
    variantModalOverlay?.addEventListener('click', (e) => {
        if (e.target === variantModalOverlay) closeVariantModal();
    });

    variantModalConfirmBtn?.addEventListener('click', async () => {
        if (!activeItem || !activeVariant) return;

        const row = document.querySelector(`[data-cart-item-id="${activeItem.id}"]`);
        const qtyInput = row?.querySelector('.qty-input');
        const currentQty = qtyInput ? parseInt(qtyInput.value || 1) : 1;

        const data = await patchQuantity(row, currentQty, activeVariant.id);

        if (data) {
            applyRowUpdate(row, data);

            // Keep the in-memory snapshot in sync so reopening the modal
            // shows the correct pre-selected values without a page reload.
            activeItem.unitPrice = data.merged ? data.unit_price : data.unit_price;
            activeItem.selectedOptions = data.selected_options;
            activeItem.maxStock = data.stock;

            showToast(data.merged ? 'Updated — merged with a matching item.' : 'Option updated.');
            closeVariantModal();
        }
    });

    updateFooter();
});
