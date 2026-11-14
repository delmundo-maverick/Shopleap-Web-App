import { pollingRequest } from '../../shared/polling-request.js';

document.addEventListener('DOMContentLoaded', () => {
    const fetchStock = pollingRequest(30000);
    const productData = JSON.parse(document.getElementById('productData').textContent);
    const { productId, basePrice, variants, hasOptions, primaryImage, selectedVariantId } = productData;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const checkoutRoute = document.getElementById('productPageData')?.dataset.checkoutRoute;

    // Out-of-stock products render their purchase buttons disabled; setBusy()
    // must never re-enable them.
    let baseStock = productData.baseStock;
    let unavailable = baseStock < 1;
    let purchaseBusy = false;

    let modalIntent = 'cart';

    // ---------------------------------------------------
    // THUMBNAIL / MAIN IMAGE SWITCHING
    // ---------------------------------------------------
    const mainImage = document.getElementById('mainImage');
    const modalImage = document.getElementById('modalVariantImage');

    function setMainImage(url) {
        if (!url || !mainImage) return;

        mainImage.src = url;

        if (modalImage) {
            modalImage.src = url;
        }

        // Keep the active thumbnail in step. When a variant image is not part
        // of the general gallery, no general thumbnail is marked active.
        document.querySelectorAll('.thumb-btn').forEach(btn => {
            const active = btn.dataset.thumb === url;

            btn.classList.toggle('border-primary', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('hover:border-primary/50', !active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function applyVariantImage(variant) {
        // Variant image first, then the primary general image.
        setMainImage(variant?.image_url || primaryImage);
    }

    document.querySelectorAll('.thumb-btn').forEach(btn => {
        btn.addEventListener('click', () => setMainImage(btn.dataset.thumb));
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

    function openVariantModal(intent = 'cart') {
        modalIntent = intent;

        // The inline variation chips share this selection state, so keep it
        // instead of clearing what the buyer already picked.
        if (modalQtyInput) {
            modalQtyInput.value = 1;
        }

        if (modalConfirmBtn) {
            modalConfirmBtn.textContent = intent === 'buyNow' ? 'Buy Now' : 'Add to Cart';
        }

        resolveSelectedVariant();

        variantModalOverlay.classList.remove('hidden');
        variantModalOverlay.classList.add('flex');
    }

    function closeVariantModal() {
        variantModalOverlay.classList.add('hidden');
        variantModalOverlay.classList.remove('flex');
    }

    // The selected look of an option button is styled in app.css from
    // aria-pressed, so the script only has to set that attribute.
    function markVariantButton(btn, active) {
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    }

    document.querySelectorAll('.variant-value-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const groupName = this.dataset.optionName;
            const value = this.dataset.optionValue;

            document.querySelectorAll(`.variant-value-btn[data-option-name="${groupName}"]`)
                .forEach(sibling => markVariantButton(sibling, sibling.dataset.optionValue === value));

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
            setMainImage(primaryImage);
            updateModalPriceStock();
            return;
        }

        selectedVariant = variants.find(v =>
            Object.keys(selectedOptions).every(key => v.options[key] === selectedOptions[key])
        ) || null;

        if (modalWarning) modalWarning.classList.toggle('hidden', !!selectedVariant);
        if (modalConfirmBtn) modalConfirmBtn.disabled = !selectedVariant || selectedVariant.stock_quantity < 1;

        if (selectedVariant) {
            applyVariantImage(selectedVariant);
        } else {
            setMainImage(primaryImage);
        }

        updateModalPriceStock();
    }

    function updateModalPriceStock() {
        const price = selectedVariant ? selectedVariant.price : basePrice;
        const stock = selectedVariant ? selectedVariant.stock_quantity : baseStock;

        const stockLabel = document.getElementById('productStock');
        if (stockLabel) stockLabel.textContent = `${stock} in stock`;
        const inlineQuantity = document.getElementById('qtyInput');
        [inlineQuantity, modalQtyInput].filter(Boolean).forEach(input => {
            input.max = stock;
            input.min = stock > 0 ? 1 : 0;
            input.value = stock > 0 ? Math.min(stock, Math.max(1, Number(input.value) || 1)) : 0;
            input.disabled = stock < 1;
        });
        document.querySelectorAll('#qtyMinus, #qtyPlus, #modalQtyMinus, #modalQtyPlus').forEach(button => {
            button.disabled = stock < 1;
        });
        unavailable = stock < 1;
        document.querySelectorAll('[data-action="add-to-cart"], [data-action="buy-now"]').forEach(button => {
            button.disabled = purchaseBusy || unavailable;
        });
        if (modalConfirmBtn) modalConfirmBtn.disabled = purchaseBusy || !selectedVariant || stock < 1;
        if (modalPrice) modalPrice.textContent = `₱${price.toFixed(2)}`;
        if (modalStock) modalStock.textContent = `${stock} in stock`;
        if (modalQtyInput) {
            modalQtyInput.max = stock;
            if (parseInt(modalQtyInput.value) > stock) modalQtyInput.value = stock > 0 ? 1 : 0;
        }
    }

    /**
     * If the page was opened with a preselected variant (e.g. ?variant=123),
     * highlight its options and show its image straight away.
     */
    function preselectInitialVariant() {
        if (!selectedVariantId) return;

        const variant = variants.find(v => Number(v.id) === Number(selectedVariantId));
        if (!variant) return;

        selectedVariant = variant;

        document.querySelectorAll('.variant-value-btn').forEach(btn => {
            const active = variant.options?.[btn.dataset.optionName] === btn.dataset.optionValue;

            markVariantButton(btn, active);

            if (active) {
                selectedOptions[btn.dataset.optionName] = btn.dataset.optionValue;
            }
        });

        if (modalConfirmBtn) modalConfirmBtn.disabled = variant.stock_quantity < 1;
        updateModalPriceStock();
        applyVariantImage(variant);
    }

    preselectInitialVariant();
    updateModalPriceStock();

    let refreshingStock = false;
    async function refreshStock() {
        if (document.hidden || refreshingStock || !productData.stockUrl) { return; }
        refreshingStock = true;
        try {
            const response = await fetchStock(productData.stockUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response) { return; }
            if (response.status === 404) {
                baseStock = 0;
                variants.forEach(variant => { variant.stock_quantity = 0; });
                updateModalPriceStock();
                return;
            }
            if (!response.ok) { return; }
            const snapshot = await response.json();
            if (!Number.isInteger(snapshot.stock_quantity) || !Array.isArray(snapshot.variants)) { return; }
            baseStock = Math.max(0, snapshot.stock_quantity);
            const stocks = new Map(snapshot.variants.map(variant => [Number(variant.id), variant.stock_quantity]));
            variants.forEach(variant => { variant.stock_quantity = Math.max(0, Number(stocks.get(Number(variant.id))) || 0); });
            updateModalPriceStock();
        } catch {
            // Keep the last known stock when the connection is temporarily unavailable.
        } finally {
            refreshingStock = false;
        }
    }
    let stockTimer = setInterval(refreshStock, 30000);
    document.addEventListener('visibilitychange', refreshStock);
    window.addEventListener('focus', refreshStock);
    window.addEventListener('pagehide', () => clearInterval(stockTimer));
    window.addEventListener('pageshow', event => {
        if (event.persisted) { clearInterval(stockTimer); stockTimer = setInterval(refreshStock, 30000); refreshStock(); }
    });
    refreshStock();

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
        toast.classList.toggle('bg-sale-red', isError);
        toast.classList.toggle('bg-charcoal', !isError);
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    }

    // Every visible purchase button (desktop row and mobile sticky bar).
    const addButtons = document.querySelectorAll('[data-action="add-to-cart"]');
    const buyButtons = document.querySelectorAll('[data-action="buy-now"]');

    function setBusy(busy) {
        purchaseBusy = busy;
        [...addButtons, ...buyButtons].forEach(btn => {
            btn.disabled = busy || unavailable;
        });

        if (modalConfirmBtn) {
            modalConfirmBtn.disabled = busy || !selectedVariant || selectedVariant.stock_quantity < 1;
        }
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

    function readQuantity() {
        return Math.max(1, Math.min(parseInt(qtyInput.max), parseInt(qtyInput.value || 1)));
    }

    function handleAddToCart() {
        if (hasOptions) {
            // Chips in the product page select the variant directly. Only fall
            // back to the modal when a complete combination is still missing.
            if (!selectedVariant || selectedVariant.stock_quantity < 1) {
                openVariantModal('cart');
                return;
            }

            addToCart(1, selectedVariant.id, selectedOptions, false);
            return;
        }

        addToCart(readQuantity(), null, null, false);
    }

    function handleBuyNow() {
        if (hasOptions) {
            if (!selectedVariant) {
                openVariantModal('buyNow');
                return;
            }

            // Buy Now goes straight to checkout with the selected variant.
            addToCart(1, selectedVariant.id, selectedOptions, true);
            return;
        }

        addToCart(readQuantity(), null, null, true);
    }

    addButtons.forEach(btn => btn.addEventListener('click', handleAddToCart));
    buyButtons.forEach(btn => btn.addEventListener('click', handleBuyNow));

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
        const image = document.getElementById('cartModalImage');

        if (image) {
            image.src = selectedVariant?.image_url || primaryImage || image.src;
        }

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
