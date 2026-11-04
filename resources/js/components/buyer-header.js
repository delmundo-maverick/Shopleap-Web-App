import { initBuyerSearch } from '../shared/buyerSearch.js';

document.addEventListener('DOMContentLoaded', () => {
    initBuyerSearch();
});

window.addEventListener('buyer-default-address-updated', event => {
    const address = event.detail;
    document.querySelectorAll('[data-buyer-header-location]').forEach(element => {
        element.textContent = address ? `${address.label || 'Deliver to'}, ${address.city_municipality}` : 'Add delivery address';
    });
});
