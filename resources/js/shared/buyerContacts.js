export async function loadBuyerContacts(listContainer, onSelect) {
    listContainer.innerHTML = '<p class="p-3 text-center text-xs text-charcoal/50">Loading…</p>';

    try {
        const res = await fetch('/messages/recent-purchases', { headers: { Accept: 'application/json' } });
        const purchases = res.ok ? await res.json() : [];

        listContainer.innerHTML = '';

        if (purchases.length > 0) {
            purchases.forEach(item => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'flex w-full items-center gap-3 rounded-xl p-2.5 text-left transition hover:bg-ice-blue';
                btn.innerHTML = `
                    <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                        ${item.product_image ? `<img src="${item.product_image}" class="h-full w-full object-cover">` : ''}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-charcoal">${item.product_name}</span>
                        <span class="block text-xs text-charcoal/50">Seller: ${item.seller_name}</span>
                    </span>
                `;
                btn.addEventListener('click', () => onSelect({ id: item.seller_id, product_id: item.product_id }));
                listContainer.appendChild(btn);
            });
        } else {
            listContainer.innerHTML = '<p class="p-3 text-center text-xs text-charcoal/50">No recent purchases yet. Buy something to message its seller.</p>';
        }
    } catch (err) {
        listContainer.innerHTML = '<p class="p-3 text-center text-xs text-sale-red">Could not load contacts.</p>';
    }
}

export async function openConversation(recipientId, productId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const res = await fetch('/messages/open', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ recipient_id: recipientId, product_id: productId }),
    });

    if (!res.ok) {
        throw new Error('Could not open conversation.');
    }

    return res.json();
}
