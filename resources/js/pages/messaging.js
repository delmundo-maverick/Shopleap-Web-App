import { loadBuyerContacts, openConversation } from '../shared/buyerContacts.js';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('messagingApp');
    if (!app) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const currentUserId = parseInt(app.dataset.currentUserId, 10);
    const currentUserRole = app.dataset.currentUserRole;
    const isAdmin = currentUserRole === 'super_admin';
    const isBuyerMode = app.dataset.buyerMode === '1';
    let activeConversationId = app.dataset.initialConversationId ? parseInt(app.dataset.initialConversationId, 10) : null;
    let lastMessageId = 0;
    let pollTimer = null;
    let selectedContact = null;
    let productContext = null;
    let pendingFiles = [];
    let keepBottomUntil = 0;

    const MAX_FILES = 4;
    const MAX_BYTES = 5 * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    const conversationList = document.getElementById('conversationList');
    const threadHeader = document.getElementById('threadHeader');
    const threadHeaderAvatar = document.getElementById('threadHeaderAvatar');
    const threadHeaderName = document.getElementById('threadHeaderName');
    const threadHeaderMeta = document.getElementById('threadHeaderMeta');
    const threadStatusBadge = document.getElementById('threadStatusBadge');
    const threadEmptyState = document.getElementById('threadEmptyState');
    const threadMessages = document.getElementById('threadMessages');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const attachBtn = document.getElementById('attachBtn');
    const attachInput = document.getElementById('attachInput');
    const attachmentPreview = document.getElementById('attachmentPreview');
    const threadBackBtn = document.getElementById('threadBackBtn');
    const threadClaimBtn = document.getElementById('threadClaimBtn');
    const threadResolveBtn = document.getElementById('threadResolveBtn');
    const threadHeaderAssigned = document.getElementById('threadHeaderAssigned');
    const kindTabs = document.querySelectorAll('[data-kind-tab]');
    const kindEmpty = document.getElementById('kindEmpty');
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImg = document.getElementById('lightboxImg');

    function authHeaders() {
        return { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' };
    }

    function jsonHeaders() {
        return { 'Content-Type': 'application/json', ...authHeaders() };
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function firstLetter(name) {
        const trimmed = (name || '').trim();
        return trimmed ? Array.from(trimmed)[0].toUpperCase() : '?';
    }

    function flash(text) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-6 left-1/2 z-[100] -translate-x-1/2 rounded-xl bg-sale-red px-4 py-2.5 text-sm font-semibold text-white shadow-xl';
        toast.textContent = text;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    // ---------------------------------------------------
    // Layout helpers
    // ---------------------------------------------------
    function setView(view) {
        app.dataset.view = view;
    }

    function showFlex(el) {
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

    function hideFlex(el) {
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    function isNearBottom() {
        return threadMessages.scrollHeight - threadMessages.scrollTop - threadMessages.clientHeight < 80;
    }

    function scrollToBottom() {
        threadMessages.scrollTop = threadMessages.scrollHeight;
    }

    // Photos load after being appended; keep the view pinned to the bottom briefly.
    function pinBottom() {
        keepBottomUntil = Date.now() + 3000;
        requestAnimationFrame(scrollToBottom);
    }

    function autoGrow() {
        messageInput.style.height = 'auto';
        const borders = messageInput.offsetHeight - messageInput.clientHeight;
        messageInput.style.height = `${Math.min(messageInput.scrollHeight + borders, 128)}px`;
    }

    // ---------------------------------------------------
    // Photo viewer
    // ---------------------------------------------------
    function openLightbox(url) {
        lightboxImg.src = url;
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
    }

    function closeLightbox() {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        lightboxImg.src = '';
    }

    lightbox?.addEventListener('click', (e) => {
        if (e.target !== lightboxImg) closeLightbox();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !lightbox.classList.contains('hidden')) closeLightbox();
    });

    // ---------------------------------------------------
    // Messages
    // ---------------------------------------------------
    function renderAttachments(list) {
        const grid = document.createElement('div');
        grid.className = `mb-1 grid gap-1 ${list.length > 1 ? 'grid-cols-2' : 'grid-cols-1'}`;

        list.forEach((att) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'block overflow-hidden rounded-lg bg-charcoal/5';
            const img = document.createElement('img');
            img.src = att.url;
            img.alt = att.name || 'Photo';
            img.loading = 'lazy';
            img.className = 'max-h-52 w-full object-cover';
            img.addEventListener('load', () => {
                if (Date.now() < keepBottomUntil) scrollToBottom();
            });
            btn.appendChild(img);
            btn.addEventListener('click', () => openLightbox(att.url));
            grid.appendChild(btn);
        });

        return grid;
    }

    function renderMessage(msg) {
        const isMine = msg.sender_id === currentUserId;
        const wrapper = document.createElement('div');
        wrapper.className = `group flex min-w-0 items-center gap-1.5 ${isMine ? 'justify-end' : 'justify-start'}`;
        wrapper.dataset.messageId = msg.id;

        const bubble = document.createElement('div');
        bubble.className = `min-w-0 max-w-[85%] break-words rounded-2xl px-3.5 py-2 text-sm [overflow-wrap:anywhere] sm:max-w-[70%] ${isMine ? 'bg-primary text-white' : 'bg-ice-blue text-charcoal'}`;

        if (msg.is_forwarded) {
            const tag = document.createElement('p');
            tag.className = 'mb-1 text-[10px] font-bold uppercase tracking-wide opacity-70';
            tag.textContent = 'Forwarded';
            bubble.appendChild(tag);
        }

        if (msg.attachments && msg.attachments.length) {
            bubble.appendChild(renderAttachments(msg.attachments));
        }

        if (msg.body) {
            const text = document.createElement('p');
            text.className = 'whitespace-pre-line break-words';
            text.textContent = msg.body;
            bubble.appendChild(text);
        }

        const time = document.createElement('p');
        time.className = 'mt-1 text-[10px] opacity-60';
        time.textContent = msg.created_at;
        bubble.appendChild(time);

        if (currentUserRole === 'super_admin') {
            const forwardBtn = document.createElement('button');
            forwardBtn.type = 'button';
            forwardBtn.title = 'Forward message';
            forwardBtn.className = 'hidden shrink-0 rounded-lg p-1.5 text-charcoal/30 transition hover:bg-primary/10 hover:text-primary group-hover:block';
            forwardBtn.innerHTML = `
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>`;
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

    function appendMessage(msg) {
        if (threadMessages.querySelector(`[data-message-id="${msg.id}"]`)) return false;
        threadMessages.appendChild(renderMessage(msg));
        lastMessageId = Math.max(lastMessageId, msg.id);
        return true;
    }

    function showThreadUI() {
        showFlex(threadHeader);
        threadEmptyState.classList.add('hidden');
        threadMessages.classList.remove('hidden');
        showFlex(messageForm);
        setView('thread');
        app.dataset.details = 'on';
    }

    function highlightActiveConversation() {
        document.querySelectorAll('.conversation-item').forEach(item => {
            const isActive = parseInt(item.dataset.conversationId, 10) === activeConversationId;
            item.classList.toggle('bg-primary/10', isActive);
        });
    }

    function setHeaderMeta(text) {
        if (threadHeaderMeta) threadHeaderMeta.textContent = text || '';
    }

    // Ticket info + status badge + composer placeholder
    function applyTicket(data) {
        const t = data.ticket;

        if (t) {
            const orderPart = t.order_id ? ` · Order #${t.order_id}` : '';
            setHeaderMeta(`${t.number} · ${t.topic}${orderPart}`);
            if (!isAdmin && t.subject) threadHeaderName.textContent = t.subject;
            threadStatusBadge.textContent = t.state.label;
            threadStatusBadge.className = `shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold ${t.state.class}`;
        } else {
            threadStatusBadge.classList.add('hidden');
        }

        messageInput.placeholder = t && !isAdmin && data.status === 'resolved'
            ? 'Reply to reopen this ticket…'
            : 'Type a message…';
    }

    async function loadThread(conversationId, headerName = null) {
        activeConversationId = conversationId;
        lastMessageId = 0;
        threadMessages.innerHTML = '';
        clearPending();
        renderProductCard(null);
        setHeaderMeta('');
        threadStatusBadge.classList.add('hidden');
        showThreadUI();
        highlightActiveConversation();

        const item = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
        const name = (headerName || item?.dataset.title || 'Conversation').trim();
        threadHeaderName.textContent = name;
        const isTicketRow = item?.closest('[data-kind]')?.dataset.kind === 'ticket';
        threadHeaderAvatar.textContent = isTicketRow && !isAdmin ? 'S' : firstLetter(name);
        setHeaderMeta(item?.dataset.meta || '');

        history.replaceState(null, '', `?conversation=${conversationId}`);

        if (isAdmin) {
            paintDetails('<p class="p-4 text-center text-xs text-charcoal/50">Loading details…</p>');
            loadDetails();
        }

        await fetchNewMessages(true);
        restartPolling();
    }

    function renderProductCard(product) {
        const card = document.getElementById('threadProductCard');
        const link = document.getElementById('threadProductLink');
        if (!card) return;

        if (!product) {
            hideFlex(card);
            return;
        }

        showFlex(card);
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

    // forceScroll = true on first load of a thread; polling only scrolls
    // when the reader is already near the bottom.
    async function fetchNewMessages(forceScroll = false) {
        if (!activeConversationId) return;
        const requestedId = activeConversationId;
        try {
            const res = await fetch(`/messages/${requestedId}/messages?after=${lastMessageId}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();

            // Ignore a stale response if the user switched threads meanwhile.
            if (requestedId !== activeConversationId) return;

            const stickToBottom = forceScroll === true || isNearBottom();

            renderProductCard(data.product);
            applyTicket(data);
            updateAdminThreadControls(data);

            let added = 0;
            data.messages.forEach(msg => { if (appendMessage(msg)) added++; });

            if (added > 0 && stickToBottom) pinBottom();
            if (added > 0 && isAdmin && forceScroll !== true) loadDetails();
        } catch (err) {
            console.error(err);
        }
    }

    function restartPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(() => fetchNewMessages(), 4000);
    }

    // ---------------------------------------------------
    // List: tabs, selecting, deleting
    // ---------------------------------------------------
    function applyKindFilter(kind) {
        kindTabs.forEach(tab => {
            const on = tab.dataset.kindTab === kind;
            tab.classList.toggle('bg-primary/10', on);
            tab.classList.toggle('text-primary', on);
            tab.classList.toggle('text-charcoal/55', !on);
        });

        const rows = conversationList.querySelectorAll('[data-kind]');
        let visible = 0;
        rows.forEach(row => {
            const show = kind === 'all' || row.dataset.kind === kind;
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });
        kindEmpty?.classList.toggle('hidden', rows.length === 0 || visible !== 0);
    }

    kindTabs.forEach(tab => tab.addEventListener('click', () => applyKindFilter(tab.dataset.kindTab)));

    conversationList?.addEventListener('click', (e) => {
        const item = e.target.closest('.conversation-item');
        if (!item) return;

        const id = parseInt(item.dataset.conversationId, 10);
        if (!id) return;

        // Same thread already open (e.g. Back was pressed on mobile): just show it again.
        if (id === activeConversationId) {
            setView('thread');
            return;
        }

        loadThread(id);
    });

    threadBackBtn?.addEventListener('click', () => setView('list'));

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
        hideFlex(threadHeader);
        renderProductCard(null);
        threadEmptyState.classList.remove('hidden');
        threadMessages.classList.add('hidden');
        threadMessages.innerHTML = '';
        clearPending();
        hideFlex(messageForm);
        setView('list');
        app.dataset.details = 'off';
        history.replaceState(null, '', window.location.pathname);
        applyKindFilter(document.querySelector('[data-kind-tab].text-primary')?.dataset.kindTab || 'all');
    }

    document.querySelectorAll('.delete-conversation-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this chat?')) return;
            const id = parseInt(btn.dataset.conversationId, 10);
            await deleteConversation(id);
            btn.closest('.group')?.remove();
            if (activeConversationId === id) resetToEmptyState();
            else applyKindFilter(document.querySelector('[data-kind-tab].text-primary')?.dataset.kindTab || 'all');
        });
    });

    document.getElementById('threadDeleteBtn')?.addEventListener('click', async () => {
        if (!activeConversationId || !confirm('Delete this chat?')) return;
        const id = activeConversationId;
        await deleteConversation(id);
        document.querySelector(`.conversation-item[data-conversation-id="${id}"]`)?.closest('.group')?.remove();
        resetToEmptyState();
    });

    // ---------------------------------------------------
    // Composer: photos, Enter to send, auto-grow
    // ---------------------------------------------------
    function clearPending() {
        pendingFiles = [];
        renderPending();
    }

    function addFiles(fileList) {
        for (const file of Array.from(fileList)) {
            if (!ALLOWED_TYPES.includes(file.type)) {
                flash('Only JPG, PNG, WEBP or GIF photos are allowed.');
                continue;
            }
            if (file.size > MAX_BYTES) {
                flash(`"${file.name}" is larger than 5 MB.`);
                continue;
            }
            if (pendingFiles.length >= MAX_FILES) {
                flash(`You can attach up to ${MAX_FILES} photos.`);
                break;
            }
            pendingFiles.push(file);
        }
        renderPending();
    }

    function renderPending() {
        attachmentPreview.innerHTML = '';

        if (pendingFiles.length === 0) {
            hideFlex(attachmentPreview);
            return;
        }

        showFlex(attachmentPreview);
        pendingFiles.forEach((file, index) => {
            const url = URL.createObjectURL(file);
            const chip = document.createElement('div');
            chip.className = 'relative h-16 w-16 overflow-hidden rounded-lg border border-charcoal/10 bg-white';
            chip.innerHTML = `
                <img src="${url}" alt="" class="h-full w-full object-cover">
                <button type="button" aria-label="Remove photo"
                    class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-charcoal/70 text-xs font-bold text-white hover:bg-sale-red">×</button>`;
            chip.querySelector('img').addEventListener('load', () => URL.revokeObjectURL(url));
            chip.querySelector('button').addEventListener('click', () => {
                pendingFiles.splice(index, 1);
                renderPending();
            });
            attachmentPreview.appendChild(chip);
        });
    }

    attachBtn?.addEventListener('click', () => attachInput.click());
    attachInput?.addEventListener('change', () => {
        addFiles(attachInput.files);
        attachInput.value = '';
    });

    messageInput?.addEventListener('paste', (e) => {
        const files = Array.from(e.clipboardData?.files || []).filter(f => f.type.startsWith('image/'));
        if (files.length) {
            e.preventDefault();
            addFiles(files);
        }
    });

    // Enter sends, Shift+Enter adds a new line.
    messageInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            messageForm.requestSubmit();
        }
    });
    messageInput?.addEventListener('input', autoGrow);

    messageForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = messageInput.value.trim();
        if ((!body && pendingFiles.length === 0) || !activeConversationId) return;

        const submitBtn = messageForm.querySelector('button[type="submit"]');
        const form = new FormData();
        if (body) form.append('body', body);
        pendingFiles.forEach(file => form.append('images[]', file));

        submitBtn.disabled = true;

        try {
            const res = await fetch(`/messages/${activeConversationId}/messages`, {
                method: 'POST',
                headers: authHeaders(),
                body: form,
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                messageInput.value = '';
                messageInput.style.height = '';
                clearPending();
                appendMessage(data);
                pinBottom();
            } else {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                flash(firstError || data.message || 'Could not send your message.');
            }
        } catch (err) {
            console.error(err);
            flash('Network problem. Please try again.');
        } finally {
            submitBtn.disabled = false;
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

    function setModalTitle(text) {
        const title = document.getElementById('newMessageModalTitle');
        if (title) title.textContent = text;
    }

    function openModal() {
        forwardingMessageId = null;
        setModalTitle('New Message');
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
        setModalTitle('Forward Message');
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
        toast.innerHTML = `<span>${escapeHtml(text)}</span><a href="?conversation=${conversationId}" class="font-bold text-sky-blue hover:underline">View</a>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // Buyer picker: recent purchases -> that product's seller.
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
                        ${escapeHtml(firstLetter(contact.name))}
                    </span>
                    <span>
                        <span class="block font-medium text-charcoal">${escapeHtml(contact.name)}</span>
                        <span class="block text-[11px] capitalize text-charcoal/45">${escapeHtml(contact.role.replace('_', ' '))}</span>
                    </span>
                `;
                btn.addEventListener('click', () => selectContact(contact));
                resultsContainer.appendChild(btn);
            });
        } catch (err) {
            console.error(err);
        }
    }

    // ---------------------------------------------------
    // Admin controls
    // ---------------------------------------------------
    // ---------------------------------------------------
    // Ticket / conversation details (admin only)
    // ---------------------------------------------------
    const detailsDockedBody = document.getElementById('detailsDockedBody');
    const detailsModal = document.getElementById('detailsModal');
    const detailsModalBody = document.getElementById('detailsModalBody');
    const threadDetailsBtn = document.getElementById('threadDetailsBtn');
    let detailsRequest = 0;

    const money = (n) => `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    function dRow(label, value) {
        if (value === null || value === undefined || value === '') return '';
        return `<div class="flex justify-between gap-3 py-1 text-xs">
            <dt class="shrink-0 text-charcoal/50">${escapeHtml(label)}</dt>
            <dd class="min-w-0 break-words text-right font-medium text-charcoal">${escapeHtml(String(value))}</dd>
        </div>`;
    }

    function dSection(title, inner) {
        if (!inner) return '';
        return `<section class="border-b border-charcoal/10 px-4 py-3">
            <h3 class="mb-1 text-[11px] font-bold uppercase tracking-wide text-charcoal/45">${escapeHtml(title)}</h3>
            ${inner}
        </section>`;
    }

    function renderPerson(p) {
        return `<div class="mb-2 last:mb-0">
            <p class="flex items-center gap-2 text-sm font-semibold text-charcoal">
                <span class="min-w-0 truncate">${escapeHtml(p.name || 'Unknown')}</span>
                <span class="shrink-0 rounded bg-primary/10 px-1.5 py-0.5 text-[9px] font-bold uppercase text-primary">${escapeHtml(p.role || '')}</span>
            </p>
            <dl>${dRow('Email', p.email)}${dRow('Contact', p.contact)}${dRow('Location', p.location)}${dRow('Member since', p.member_since)}</dl>
        </div>`;
    }

    function renderDetails(d) {
        const t = d.ticket;
        const f = d.facts;
        let html = '';

        html += `<div class="border-b border-charcoal/10 bg-ice-blue/60 px-4 py-3">
            ${t ? `
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-bold text-charcoal">${escapeHtml(t.number)}</p>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ${t.state.class}">${escapeHtml(t.state.label)}</span>
                </div>
                <p class="text-xs text-charcoal/60">${escapeHtml(t.topic)}</p>
                <p class="mt-1.5 break-words text-sm font-semibold text-charcoal">${escapeHtml(t.subject || '')}</p>`
                : '<p class="text-sm font-bold text-charcoal">Direct conversation</p><p class="text-xs text-charcoal/60">Not a support ticket</p>'}
        </div>`;

        html += dSection(t ? 'Ticket' : 'Conversation', `<dl>
            ${dRow('Status', f.status)}
            ${dRow('Opened', f.opened_at)}
            ${dRow('Age', f.opened_ago)}
            ${dRow('Last activity', f.last_activity)}
            ${dRow('Assigned to', f.assigned_to || 'Unassigned')}
            ${t ? dRow('First response', f.first_response || 'No reply yet') : ''}
            ${dRow('Messages', `${f.customer_messages} customer · ${f.staff_messages} staff`)}
            ${dRow('Photos', f.photo_count)}
        </dl>`);

        if (t && d.requester) {
            html += dSection('Requester', renderPerson(d.requester));
        } else if (d.people && d.people.length) {
            html += dSection(d.people.length > 1 ? 'People' : 'Person', d.people.map(renderPerson).join(''));
        }

        if (d.order) {
            const o = d.order;
            const items = o.items.map(i => `
                <li class="flex justify-between gap-3 py-1 text-xs">
                    <span class="min-w-0 break-words text-charcoal">${escapeHtml(i.name || 'Item')} <span class="text-charcoal/50">× ${i.quantity}</span>
                        ${i.options ? `<span class="block text-[11px] text-charcoal/50">${escapeHtml(i.options)}</span>` : ''}</span>
                    <span class="shrink-0 font-medium text-charcoal">${money(i.subtotal)}</span>
                </li>`).join('');

            html += dSection(`Order #${o.id}`, `
                <dl>
                    <div class="flex items-center justify-between gap-3 py-1 text-xs">
                        <dt class="text-charcoal/50">Status</dt>
                        <dd><span class="rounded-full bg-charcoal/10 px-2 py-0.5 text-[10px] font-bold text-charcoal/70">${escapeHtml(o.status)}</span></dd>
                    </div>
                    ${dRow('Placed', o.placed_at)}
                    ${dRow('Seller', o.seller)}
                    ${dRow('Total', money(o.total))}
                    ${dRow('Commission', money(o.commission))}
                    ${dRow('Delivery area', o.delivery_area)}
                    ${dRow('Delivery attempts', o.delivery_attempts)}
                    ${dRow('Pickup status', o.pickup_status)}
                    ${dRow('Pickup date', o.pickup_date)}
                    ${dRow('Pickup window', o.pickup_slot)}
                    ${dRow('Pickup declined', o.pickup_decline_reason)}
                </dl>
                ${items ? `<ul class="mt-2 divide-y divide-charcoal/10 border-t border-charcoal/10">${items}</ul>` : ''}`);

            const s = o.ship_to || {};
            html += dSection('Ship to', `<dl>
                ${dRow('Name', s.name || o.buyer_name)}
                ${dRow('Contact', s.contact_no)}
                ${dRow('Address', s.address)}
            </dl>`);

            if (o.events && o.events.length) {
                html += dSection('Order timeline', `<ol class="space-y-2">${o.events.map(e => `
                    <li class="text-xs">
                        <p class="font-semibold text-charcoal">${escapeHtml(e.title || '')}</p>
                        ${e.note ? `<p class="break-words text-charcoal/60">${escapeHtml(e.note)}</p>` : ''}
                        <p class="text-[10px] text-charcoal/40">${escapeHtml(e.at || '')}</p>
                    </li>`).join('')}</ol>`);
            }
        }

        if (d.product) {
            const p = d.product;
            html += dSection('Product', `<div class="flex items-center gap-3">
                ${p.image ? `<img src="${escapeHtml(p.image)}" alt="" class="h-12 w-12 shrink-0 rounded-lg bg-ice-blue object-cover">` : ''}
                <div class="min-w-0">
                    <p class="break-words text-xs font-semibold text-charcoal">${escapeHtml(p.name)}</p>
                    <p class="text-xs font-bold text-primary">${money(p.price)}</p>
                    ${p.seller ? `<p class="text-[11px] text-charcoal/50">${escapeHtml(p.seller)}</p>` : ''}
                </div>
            </div>`);
        }

        if (d.original_request) {
            html += dSection(t ? 'Original request' : 'First message',
                `<div class="whitespace-pre-line break-words rounded-lg bg-ice-blue p-3 text-xs text-charcoal">${escapeHtml(d.original_request)}</div>`);
        }

        if (d.photos && d.photos.length) {
            html += dSection(`Photos (${d.photos.length})`, `<div class="grid grid-cols-3 gap-1.5">${d.photos.map(ph => `
                <button type="button" data-lightbox="${escapeHtml(ph.url)}" class="block aspect-square overflow-hidden rounded-lg bg-charcoal/5">
                    <img src="${escapeHtml(ph.url)}" alt="${escapeHtml(ph.name || 'Photo')}" loading="lazy" class="h-full w-full object-cover">
                </button>`).join('')}</div>`);
        }

        return html;
    }

    function paintDetails(html) {
        if (detailsDockedBody) detailsDockedBody.innerHTML = html;
        if (detailsModalBody) detailsModalBody.innerHTML = html;
    }

    async function loadDetails() {
        if (!isAdmin || !activeConversationId) return;
        const id = activeConversationId;
        const request = ++detailsRequest;

        try {
            const res = await fetch(`/messages/${id}/details`, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('details failed');
            const data = await res.json();
            if (request !== detailsRequest || id !== activeConversationId) return;
            paintDetails(renderDetails(data));
        } catch (err) {
            console.error(err);
            if (request === detailsRequest) {
                paintDetails('<p class="p-4 text-center text-xs text-sale-red">Could not load details.</p>');
            }
        }
    }

    function openDetailsModal() {
        detailsModal.classList.remove('hidden');
        detailsModal.classList.add('flex');
    }

    function closeDetailsModal() {
        detailsModal.classList.add('hidden');
        detailsModal.classList.remove('flex');
    }

    threadDetailsBtn?.addEventListener('click', openDetailsModal);
    document.getElementById('detailsModalClose')?.addEventListener('click', closeDetailsModal);
    detailsModal?.addEventListener('click', (e) => {
        if (e.target === detailsModal) closeDetailsModal();
    });
    [detailsDockedBody, detailsModalBody].forEach(el => el?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-lightbox]');
        if (btn) openLightbox(btn.dataset.lightbox);
    }));

    function updateAdminThreadControls(data) {
        if (!threadClaimBtn || !threadResolveBtn) return;

        if (data.assigned_to) {
            threadClaimBtn.textContent = data.assigned_to === currentUserId ? 'Unassign' : `Assigned: ${data.assigned_name}`;
            threadClaimBtn.disabled = data.assigned_to !== currentUserId;
            threadClaimBtn.classList.toggle('opacity-50', data.assigned_to !== currentUserId);
            if (threadHeaderAssigned) threadHeaderAssigned.textContent = ` · Assigned to ${data.assigned_name}`;
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
            loadDetails();
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
            loadDetails();
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
    // DEEP-LINK: ?contact=ID&product=ID (the "Message Seller" button on the product page)
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
