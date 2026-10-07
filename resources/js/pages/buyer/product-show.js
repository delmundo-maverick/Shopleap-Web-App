document.addEventListener('DOMContentLoaded', () => {
    const productData = JSON.parse(document.getElementById('productData').textContent);
    const { productId, basePrice, baseStock, variants, hasOptions } = productData;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const checkoutRoute = document.getElementById('productPageData')?.dataset.checkoutRoute;

    let modalIntent = 'cart';

    // ---------------------------------------------------
    // THUMBNAIL SWITCHING
    // ---------------------------------------------------
    document.querySelectorAll('.thumb-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('mainImage').src = this.dataset.thumb;
            document.querySelectorAll('.thumb-btn').forEach(b => {
                b.classList.remove('border-primary');
                b.classList.add('border-transparent');
            });
            this.classList.remove('border-transparent');
            this.classList.add('border-primary');
        });
    });

    // ---------------------------------------------------
    // VARIANT SELECTION MODAL (Shopee-style)
    // ---------------------------------------------------
    const selectedOptions = {};
    let selectedVariant = null;

    const variantModalOverlay = document.getElementById('variantModalOverlay');
    const modalPrice = document.getElementById('modalPrice');
    const modalStock = document.getElementById('modalStock');
    const modalWarning = document.getElementById('modalVariantWarning');
    const modalQtyInput = document.getElementById('modalQtyInput');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');

    function resetVariantSelection() {
        Object.keys(selectedOptions).forEach(k => delete selectedOptions[k]);
        selectedVariant = null;

        document.querySelectorAll('.variant-value-btn').forEach(b => {
            b.classList.remove('border-primary', 'bg-primary/5', 'text-primary');
            b.classList.add('border-light-gray', 'text-charcoal');
        });

        if (modalQtyInput) {
            modalQtyInput.value = 1;
            modalQtyInput.max = baseStock;
        }
        if (modalWarning) modalWarning.classList.add('hidden');
        if (modalConfirmBtn) modalConfirmBtn.disabled = true;

        updateModalPriceStock();
    }

    function openVariantModal(intent = 'cart') {
        modalIntent = intent;
        resetVariantSelection();
        if (modalConfirmBtn) {
            modalConfirmBtn.textContent = intent === 'buyNow' ? 'Buy Now' : 'Add to Cart';
        }
        variantModalOverlay.classList.remove('hidden');
        variantModalOverlay.classList.add('flex');
    }

    function closeVariantModal() {
        variantModalOverlay.classList.add('hidden');
        variantModalOverlay.classList.remove('flex');
    }

    document.querySelectorAll('.variant-value-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const groupName = this.dataset.optionName;
            const value = this.dataset.optionValue;

            document.querySelectorAll(`.variant-value-btn[data-option-name="${groupName}"]`)
                .forEach(sibling => {
                    sibling.classList.remove('border-primary', 'bg-primary/5', 'text-primary');
                    sibling.classList.add('border-light-gray', 'text-charcoal');
                });

            this.classList.remove('border-light-gray', 'text-charcoal');
            this.classList.add('border-primary', 'bg-primary/5', 'text-primary');

            selectedOptions[groupName] = value;
            resolveSelectedVariant();
        });
    });

    function resolveSelectedVariant() {
        const optionGroups = document.querySelectorAll('.variant-option-group');
        const allSelected = optionGroups.length > 0 &&
            Array.from(optionGroups).every(group => selectedOptions[group.dataset.optionName]);

        if (!allSelected) {
            selectedVariant = null;
            if (modalConfirmBtn) modalConfirmBtn.disabled = true;
            if (modalWarning) modalWarning.classList.add('hidden');
            return;
        }

        selectedVariant = variants.find(v =>
            Object.keys(selectedOptions).every(key => v.options[key] === selectedOptions[key])
        ) || null;

        if (modalWarning) modalWarning.classList.toggle('hidden', !!selectedVariant);
        if (modalConfirmBtn) modalConfirmBtn.disabled = !selectedVariant || selectedVariant.stock_quantity < 1;

        updateModalPriceStock();
    }

    function updateModalPriceStock() {
        const price = selectedVariant ? selectedVariant.price : basePrice;
        const stock = selectedVariant ? selectedVariant.stock_quantity : baseStock;

        if (modalPrice) modalPrice.textContent = `₱${price.toFixed(2)}`;
        if (modalStock) modalStock.textContent = `${stock} in stock`;
        if (modalQtyInput) {
            modalQtyInput.max = stock;
            if (parseInt(modalQtyInput.value) > stock) modalQtyInput.value = stock > 0 ? 1 : 0;
        }
    }

    document.getElementById('modalQtyMinus')?.addEventListener('click', () => {
        modalQtyInput.value = Math.max(1, parseInt(modalQtyInput.value || 1) - 1);
    });
    document.getElementById('modalQtyPlus')?.addEventListener('click', () => {
        const max = parseInt(modalQtyInput.max || baseStock);
        modalQtyInput.value = Math.min(max, parseInt(modalQtyInput.value || 1) + 1);
    });

    document.getElementById('modalCloseBtn')?.addEventListener('click', closeVariantModal);
    variantModalOverlay?.addEventListener('click', (e) => {
        if (e.target === variantModalOverlay) closeVariantModal();
    });

    // ---------------------------------------------------
    // SIMPLE QUANTITY STEPPER (only rendered when product has no options)
    // ---------------------------------------------------
    const qtyInput = document.getElementById('qtyInput');
    if (qtyInput) {
        document.getElementById('qtyMinus')?.addEventListener('click', () => {
            qtyInput.value = Math.max(1, parseInt(qtyInput.value || 1) - 1);
        });
        document.getElementById('qtyPlus')?.addEventListener('click', () => {
            const max = parseInt(qtyInput.max);
            qtyInput.value = Math.min(max, parseInt(qtyInput.value || 1) + 1);
        });
    }

    // ---------------------------------------------------
    // ADD TO CART
    // ---------------------------------------------------
    function showToast(message, isError = false) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.style.background = isError ? '#DC2626' : '#16A34A';
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    }

    const addBtn = document.getElementById('addToCartBtn');
    const buyBtn = document.getElementById('buyNowBtn');

    function setBusy(busy) {
        if (addBtn) addBtn.disabled = busy;
        if (buyBtn) buyBtn.disabled = busy;
        if (modalConfirmBtn) {
            modalConfirmBtn.disabled = busy || !selectedVariant || selectedVariant.stock_quantity < 1;
        }
        [addBtn, buyBtn].forEach(b => b?.classList.toggle('opacity-60', busy));
    }

    async function addToCart(quantity, variantId, options, buyNow = false) {
        let redirecting = false;
        setBusy(true);
        try {
            const res = await fetch(`/buyer/cart/${productId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    quantity,
                    variant_id: variantId,
                    selected_options: options,
                }),
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                showToast(data.message || 'Could not add to cart.', true);
                return;
            }

            const badge = document.getElementById('buyerCartBadge');
            if (badge) badge.textContent = data.cart_count;

            if (buyNow) {
                if (!checkoutRoute || !data.cart_item_id) {
                    showToast('Added to cart, but could not proceed to checkout automatically.', true);
                    closeVariantModal();
                    return;
                }
                redirecting = true;
                window.location.href = `${checkoutRoute}?items=${data.cart_item_id}`;
                return;
            }

            document.getElementById('cartModalQty').textContent = `Qty: ${quantity}`;
            closeVariantModal();
            openCartModal();
        } catch (err) {
            showToast('Network error — please try again.', true);
        } finally {
            if (!redirecting) setBusy(false);
        }
    }

    document.getElementById('addToCartBtn')?.addEventListener('click', function () {
        if (hasOptions) {
            openVariantModal('cart');
            return;
        }
        const quantity = Math.max(1, Math.min(parseInt(qtyInput.max), parseInt(qtyInput.value || 1)));
        addToCart(quantity, null, null, false);
    });

    document.getElementById('buyNowBtn')?.addEventListener('click', function () {
        if (hasOptions) {
            openVariantModal('buyNow');
            return;
        }
        const quantity = Math.max(1, Math.min(parseInt(qtyInput.max), parseInt(qtyInput.value || 1)));
        addToCart(quantity, null, null, true);
    });

    modalConfirmBtn?.addEventListener('click', function () {
        if (!selectedVariant) return;
        const quantity = parseInt(modalQtyInput.value || 1);
        addToCart(quantity, selectedVariant.id, selectedOptions, modalIntent === 'buyNow');
    });

    // ---------------------------------------------------
    // ADDED-TO-CART CONFIRMATION MODAL
    // ---------------------------------------------------
    const cartModalOverlay = document.getElementById('cartModalOverlay');

    function openCartModal() {
        cartModalOverlay.classList.remove('hidden');
        cartModalOverlay.classList.add('flex');
    }

    function closeCartModal() {
        cartModalOverlay.classList.add('hidden');
        cartModalOverlay.classList.remove('flex');
    }

    document.getElementById('cartModalContinueBtn')?.addEventListener('click', closeCartModal);
    cartModalOverlay?.addEventListener('click', (e) => {
        if (e.target === cartModalOverlay) closeCartModal();
    });
});
