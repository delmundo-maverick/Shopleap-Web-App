import { loadBuyerContacts, openConversation } from '../shared/buyerContacts.js';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('messagingApp');
    if (!app) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const currentUserId = parseInt(app.dataset.currentUserId, 10);
    const currentUserRole = app.dataset.currentUserRole;
    const isBuyerMode = app.dataset.buyerMode === '1';
    let activeConversationId = app.dataset.initialConversationId ? parseInt(app.dataset.initialConversationId, 10) : null;
    let lastMessageId = 0;
    let pollTimer = null;
    let selectedContact = null;
    let productContext = null;

    const conversationList = document.getElementById('conversationList');
    const threadHeader = document.getElementById('threadHeader');
    const threadHeaderAvatar = document.getElementById('threadHeaderAvatar');
    const threadHeaderName = document.getElementById('threadHeaderName');
    const threadEmptyState = document.getElementById('threadEmptyState');
    const threadMessages = document.getElementById('threadMessages');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const threadClaimBtn = document.getElementById('threadClaimBtn');
    const threadResolveBtn = document.getElementById('threadResolveBtn');
    const threadHeaderAssigned = document.getElementById('threadHeaderAssigned');

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        };
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderMessage(msg) {
        const isMine = msg.sender_id === currentUserId;
        const wrapper = document.createElement('div');
        wrapper.className = `group flex items-center gap-1.5 ${isMine ? 'justify-end' : 'justify-start'}`;
        wrapper.dataset.messageId = msg.id;

        const bubble = document.createElement('div');
        bubble.className = `max-w-[70%] rounded-2xl px-4 py-2.5 text-sm ${isMine ? 'bg-primary text-white' : 'bg-ice-blue text-charcoal'}`;
        bubble.innerHTML = `
                ${msg.is_forwarded ? '<p class="mb-1 text-[10px] font-bold uppercase tracking-wide opacity-70">Forwarded</p>' : ''}
                <p class="whitespace-pre-line">${escapeHtml(msg.body)}</p>
                <p class="mt-1 text-[10px] opacity-60">${msg.created_at}</p>
            `;

        if (currentUserRole === 'super_admin') {
            const forwardBtn = document.createElement('button');
            forwardBtn.type = 'button';
            forwardBtn.title = 'Forward message';
            forwardBtn.className = 'hidden shrink-0 rounded-lg p-1.5 text-charcoal/30 transition hover:bg-primary/10 hover:text-primary group-hover:block';
            forwardBtn.innerHTML = `
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                `;
            forwardBtn.addEventListener('click', () => openForwardModal(msg.id));

            if (isMine) {
                wrapper.appendChild(forwardBtn);
                wrapper.appendChild(bubble);
            } else {
                wrapper.appendChild(bubble);
                wrapper.appendChild(forwardBtn);
            }
        } else {
            wrapper.appendChild(bubble);
        }

        return wrapper;
    }

    function showThreadUI() {
        threadHeader.classList.remove('hidden');
        threadEmptyState.classList.add('hidden');
        threadMessages.classList.remove('hidden');
        messageForm.classList.remove('hidden');
    }

    function highlightActiveConversation() {
        document.querySelectorAll('.conversation-item').forEach(item => {
            const isActive = parseInt(item.dataset.conversationId, 10) === activeConversationId;
            item.classList.toggle('bg-primary/10', isActive);
        });
    }

    async function loadThread(conversationId, headerName = null) {
        activeConversationId = conversationId;
        lastMessageId = 0;
        threadMessages.innerHTML = '';
        renderProductCard(null);
        showThreadUI();
        highlightActiveConversation();

        const item = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
        const name = headerName || item?.querySelector('p.font-semibold')?.textContent || 'Conversation';
        threadHeaderName.textContent = name;
        threadHeaderAvatar.textContent = name.charAt(0).toUpperCase();

        history.replaceState(null, '', `?conversation=${conversationId}`);

        await fetchNewMessages();
        restartPolling();
    }

    function renderProductCard(product) {
        const card = document.getElementById('threadProductCard');
        const link = document.getElementById('threadProductLink');
        if (!card) return;

        if (!product) {
            card.classList.add('hidden');
            card.classList.remove('flex');
            return;
        }

        card.classList.remove('hidden');
        card.classList.add('flex');
        document.getElementById('threadProductImage').src = product.image || '';
        document.getElementById('threadProductName').textContent = product.name;
        document.getElementById('threadProductPrice').textContent = `₱${Number(product.price).toFixed(2)}`;

        if (currentUserRole === 'buyer') {
            link.href = `/buyer/products/${product.id}`;
            link.classList.remove('hidden');
        } else {
            link.classList.add('hidden');
        }
    }

    async function fetchNewMessages() {
        if (!activeConversationId) return;
        try {
            const res = await fetch(`/messages/${activeConversationId}/messages?after=${lastMessageId}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();
            renderProductCard(data.product);
            updateAdminThreadControls(data);
            data.messages.forEach(msg => {
                threadMessages.appendChild(renderMessage(msg));
                lastMessageId = Math.max(lastMessageId, msg.id);
            });
            if (data.messages.length > 0) {
                threadMessages.scrollTop = threadMessages.scrollHeight;
            }
        } catch (err) {
            console.error(err);
        }
    }

    function restartPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(fetchNewMessages, 4000);
    }

    conversationList?.addEventListener('click', (e) => {
        const item = e.target.closest('.conversation-item');
        if (!item) return;

        const id = parseInt(item.dataset.conversationId, 10);
        if (!id || id === activeConversationId) return;

        loadThread(id);
    });

    async function deleteConversation(id) {
        try {
            await fetch(`/messages/${id}/delete`, { method: 'POST', headers: jsonHeaders() });
        } catch (err) {
            console.error(err);
        }
    }

    async function deleteConversation(id) {
        try {
            await fetch(`/messages/${id}/delete`, { method: 'POST', headers: jsonHeaders() });
        } catch (err) {
            console.error(err);
        }
    }

    function resetToEmptyState() {
        activeConversationId = null;
        if (pollTimer) clearInterval(pollTimer);
        threadHeader.classList.add('hidden');
        threadEmptyState.classList.remove('hidden');
        threadMessages.classList.add('hidden');
        threadMessages.innerHTML = '';
        messageForm.classList.add('hidden');
        history.replaceState(null, '', window.location.pathname);
    }

    document.querySelectorAll('.delete-conversation-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this chat?')) return;
            const id = parseInt(btn.dataset.conversationId, 10);
            await deleteConversation(id);
            btn.closest('.group')?.remove();
            if (activeConversationId === id) resetToEmptyState();
        });
    });

    document.getElementById('threadDeleteBtn')?.addEventListener('click', async () => {
        if (!activeConversationId || !confirm('Delete this chat?')) return;
        const id = activeConversationId;
        await deleteConversation(id);
        document.querySelector(`.conversation-item[data-conversation-id="${id}"]`)?.closest('.group')?.remove();
        resetToEmptyState();
    });

    messageForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = messageInput.value.trim();
        if (!body || !activeConversationId) return;

        messageInput.value = '';

        try {
            const res = await fetch(`/messages/${activeConversationId}/messages`, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ body }),
            });
            const data = await res.json();
            if (res.ok) {
                threadMessages.appendChild(renderMessage(data));
                lastMessageId = Math.max(lastMessageId, data.id);
                threadMessages.scrollTop = threadMessages.scrollHeight;
            }
        } catch (err) {
            console.error(err);
        }
    });

    // ---------------------------------------------------
    // NEW MESSAGE MODAL
    // ---------------------------------------------------
    const newMessageBtn = document.getElementById('newMessageBtn');
    const modalOverlay = document.getElementById('newMessageModalOverlay');
    const closeBtn = document.getElementById('newMessageCloseBtn');
    const searchInput = document.getElementById('contactSearchInput');
    const resultsContainer = document.getElementById('contactResults');
    const composeArea = document.getElementById('newMessageComposeArea');
    const selectedContactName = document.getElementById('selectedContactName');
    const newMessageBody = document.getElementById('newMessageBody');
    const sendBtn = document.getElementById('newMessageSendBtn');

    let forwardingMessageId = null;

    function openModal() {
        forwardingMessageId = null;
        document.getElementById('newMessageModalTitle').textContent = 'New Message';
        modalOverlay.classList.remove('hidden');
        modalOverlay.classList.add('flex');
        searchInput.value = '';
        resultsContainer.innerHTML = '';
        composeArea.classList.add('hidden');
        selectedContact = null;
        newMessageBody.value = '';
        searchInput.focus();
        loadContacts('');
    }

    function closeModal() {
        modalOverlay.classList.add('hidden');
        modalOverlay.classList.remove('flex');
        forwardingMessageId = null;
    }

    function openForwardModal(messageId) {
        forwardingMessageId = messageId;
        document.getElementById('newMessageModalTitle').textContent = 'Forward Message';
        modalOverlay.classList.remove('hidden');
        modalOverlay.classList.add('flex');
        searchInput.value = '';
        resultsContainer.innerHTML = '';
        composeArea.classList.add('hidden');
        loadContacts('');
    }

    function showToastMessage(text, conversationId) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-6 right-6 z-[100] flex items-center gap-3 rounded-xl bg-charcoal px-4 py-3 text-sm text-white shadow-xl';
        toast.innerHTML = `<span>${text}</span><a href="?conversation=${conversationId}" class="font-bold text-sky-blue hover:underline">View</a>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    const buyerContactModalOverlay = document.getElementById('buyerContactModalOverlay');
    const buyerContactModalList = document.getElementById('buyerContactModalList');
    const buyerContactModalCloseBtn = document.getElementById('buyerContactModalCloseBtn');

    function openBuyerContactModal() {
        buyerContactModalOverlay.classList.remove('hidden');
        buyerContactModalOverlay.classList.add('flex');
        loadBuyerContacts(buyerContactModalList, async ({ id, product_id }) => {
            try {
                const data = await openConversation(id, product_id);
                closeBuyerContactModal();
                window.location.href = `?conversation=${data.conversation_id}`;
            } catch (err) {
                alert('Could not start conversation.');
            }
        });
    }

    function closeBuyerContactModal() {
        buyerContactModalOverlay?.classList.add('hidden');
        buyerContactModalOverlay?.classList.remove('flex');
    }

    buyerContactModalCloseBtn?.addEventListener('click', closeBuyerContactModal);
    buyerContactModalOverlay?.addEventListener('click', (e) => {
        if (e.target === buyerContactModalOverlay) closeBuyerContactModal();
    });

    newMessageBtn?.addEventListener('click', () => {
        if (isBuyerMode) {
            openBuyerContactModal();
        } else {
            openModal();
        }
    });

    closeBtn?.addEventListener('click', closeModal);
    modalOverlay?.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
    });

    async function loadContacts(search) {
        try {
            const res = await fetch(`/messages/contacts?search=${encodeURIComponent(search)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const contacts = await res.json();
            resultsContainer.innerHTML = '';

            if (contacts.length === 0) {
                resultsContainer.innerHTML = '<p class="p-3 text-center text-xs text-charcoal/50">No contacts found.</p>';
                return;
            }

            contacts.forEach(contact => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'flex w-full items-center gap-3 rounded-lg p-2.5 text-left text-sm hover:bg-ice-blue';
                btn.innerHTML = `
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                        ${contact.name.charAt(0).toUpperCase()}
                    </span>
                    <span>
                        <span class="block font-medium text-charcoal">${escapeHtml(contact.name)}</span>
                        <span class="block text-[11px] capitalize text-charcoal/45">${contact.role.replace('_', ' ')}</span>
                    </span>
                `;
                btn.addEventListener('click', () => selectContact(contact));
                resultsContainer.appendChild(btn);
            });
        } catch (err) {
            console.error(err);
        }
    }

    function updateAdminThreadControls(data) {
        if (!threadClaimBtn || !threadResolveBtn) return;

        if (data.assigned_to) {
            threadClaimBtn.textContent = data.assigned_to === currentUserId ? 'Unassign' : `Assigned: ${data.assigned_name}`;
            threadClaimBtn.disabled = data.assigned_to !== currentUserId;
            threadClaimBtn.classList.toggle('opacity-50', data.assigned_to !== currentUserId);
            if (threadHeaderAssigned) threadHeaderAssigned.textContent = `Assigned to ${data.assigned_name}`;
        } else {
            threadClaimBtn.textContent = 'Claim';
            threadClaimBtn.disabled = false;
            threadClaimBtn.classList.remove('opacity-50');
            if (threadHeaderAssigned) threadHeaderAssigned.textContent = '';
        }

        threadResolveBtn.textContent = data.status === 'resolved' ? 'Reopen' : 'Mark Resolved';
        threadResolveBtn.classList.toggle('bg-leaf-green', data.status !== 'resolved');
        threadResolveBtn.classList.toggle('bg-charcoal/60', data.status === 'resolved');
    }

    threadClaimBtn?.addEventListener('click', async () => {
        if (!activeConversationId) return;
        const isMine = threadClaimBtn.textContent === 'Unassign';
        const url = `/messages/${activeConversationId}/${isMine ? 'unclaim' : 'claim'}`;

        try {
            const res = await fetch(url, { method: 'POST', headers: jsonHeaders() });
            const data = await res.json();
            if (!res.ok) {
                alert(data.message || 'Could not update assignment.');
                return;
            }
            updateAdminThreadControls({
                assigned_to: isMine ? null : currentUserId,
                assigned_name: isMine ? null : 'You',
                status: threadResolveBtn.textContent === 'Reopen' ? 'resolved' : 'open',
            });
        } catch (err) {
            console.error(err);
        }
    });

    threadResolveBtn?.addEventListener('click', async () => {
        if (!activeConversationId) return;
        const isResolved = threadResolveBtn.textContent === 'Reopen';
        const url = `/messages/${activeConversationId}/${isResolved ? 'reopen' : 'resolve'}`;

        try {
            const res = await fetch(url, { method: 'POST', headers: jsonHeaders() });
            const data = await res.json();
            if (!res.ok) return;

            updateAdminThreadControls({
                assigned_to: threadClaimBtn?.textContent === 'Unassign' ? currentUserId : null,
                assigned_name: 'You',
                status: data.status,
            });
        } catch (err) {
            console.error(err);
        }
    });

    async function selectContact(contact) {
        selectedContact = contact;

        if (forwardingMessageId) {
            try {
                const res = await fetch(`/messages/forward/${forwardingMessageId}`, {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ recipient_id: contact.id }),
                });
                const data = await res.json();

                if (!res.ok) {
                    alert(data.message || 'Could not forward message.');
                    return;
                }

                closeModal();
                showToastMessage(`Forwarded to ${contact.name}.`, data.conversation_id);
            } catch (err) {
                console.error(err);
            }
            return;
        }

        selectedContactName.textContent = contact.name;
        composeArea.classList.remove('hidden');
        newMessageBody.focus();
    }

    let searchDebounce = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => loadContacts(searchInput.value), 250);
    });

    sendBtn?.addEventListener('click', async () => {
        const body = newMessageBody.value.trim();
        if (!selectedContact || !body) return;

        try {
            const res = await fetch('/messages', {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({
                    recipient_id: selectedContact.id,
                    body,
                    product_id: productContext,
                }),
            });
            const data = await res.json();

            if (!res.ok) {
                alert(data.message || 'Could not send message.');
                return;
            }

            closeModal();
            window.location.href = `?conversation=${data.conversation_id}`;
        } catch (err) {
            console.error(err);
        }
    });

    // ---------------------------------------------------
    // DEEP-LINK: ?contact=ID&product=ID opens the composer
    // pre-filled — used by the "Message Seller" button on
    // the buyer product page.
    // ---------------------------------------------------
    const params = new URLSearchParams(window.location.search);
    if (params.has('contact')) {
        const contactId = parseInt(params.get('contact'), 10);
        const productId = params.get('product') ? parseInt(params.get('product'), 10) : null;

        openConversation(contactId, productId)
            .then(data => {
                window.history.replaceState(null, '', '/messages');
                loadThread(data.conversation_id);
            })
            .catch(() => alert('Could not open a conversation with this seller.'));
    }

    // ---------------------------------------------------
    // INITIAL LOAD
    // ---------------------------------------------------
    if (activeConversationId) {
        loadThread(activeConversationId);
    }
});
