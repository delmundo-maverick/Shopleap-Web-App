document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('buyerMessageWidgetToggle');
    const panel = document.getElementById('buyerMessageWidgetPanel');
    const badge = document.getElementById('buyerMessageWidgetBadge');
    const closeBtn = document.getElementById('buyerMessageWidgetClose');

    const listView = document.getElementById('widgetListView');
    const listContent = document.getElementById('widgetListContent');

    const threadView = document.getElementById('widgetThreadView');
    const threadBackBtn = document.getElementById('widgetThreadBackBtn');
    const threadCloseBtn = document.getElementById('widgetThreadCloseBtn');
    const threadAvatar = document.getElementById('widgetThreadAvatar');
    const threadName = document.getElementById('widgetThreadName');
    const threadMessages = document.getElementById('widgetThreadMessages');
    const threadForm = document.getElementById('widgetThreadForm');
    const threadInput = document.getElementById('widgetThreadInput');
    const productCard = document.getElementById('widgetThreadProductCard');

    if (!toggleBtn || !panel) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let activeConversationId = null;
    let lastMessageId = 0;
    let pollTimer = null;

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

    // ---------------------------------------------------
    // OPEN / CLOSE PANEL
    // ---------------------------------------------------
    function openPanel() {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        showListView();
        loadList();
    }

    function closePanel() {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        stopPolling();
    }

    toggleBtn.addEventListener('click', () => {
        panel.classList.contains('hidden') ? openPanel() : closePanel();
    });
    closeBtn?.addEventListener('click', closePanel);
    threadCloseBtn?.addEventListener('click', closePanel);

    document.addEventListener('click', (e) => {
        if (!panel.contains(e.target) && !toggleBtn.contains(e.target)) {
            closePanel();
        }
    });

    // ---------------------------------------------------
    // LIST VIEW
    // ---------------------------------------------------
    function showListView() {
        listView.classList.remove('hidden');
        listView.classList.add('flex');
        threadView.classList.add('hidden');
        threadView.classList.remove('flex');
        stopPolling();
    }

    async function loadList() {
        listContent.innerHTML = '<p class="p-4 text-center text-xs text-charcoal/50">Loading…</p>';

        try {
            const res = await fetch('/messages/widget-data', { headers: { Accept: 'application/json' } });
            const data = await res.json();

            listContent.innerHTML = '';
            let totalUnread = 0;

            if (data.conversations.length === 0 && data.quick_starts.length === 0) {
                listContent.innerHTML = '<p class="p-4 text-center text-xs text-charcoal/50">No messages yet. Buy something to message its seller, or contact support.</p>';
                return;
            }

            data.conversations.forEach(conv => {
                totalUnread += conv.unread_count;
                listContent.appendChild(buildConversationRow(conv));
            });

            if (data.quick_starts.length > 0) {
                const heading = document.createElement('p');
                heading.className = 'mt-2 mb-1 px-2 text-[11px] font-bold uppercase tracking-wide text-charcoal/40';
                heading.textContent = 'Start a Conversation';
                listContent.appendChild(heading);

                data.quick_starts.forEach(item => {
                    listContent.appendChild(buildQuickStartRow(item));
                });
            }

            updateBadge(totalUnread);
        } catch (err) {
            listContent.innerHTML = '<p class="p-4 text-center text-xs text-sale-red">Could not load messages.</p>';
        }
    }

    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.remove('hidden');
            badge.classList.add('flex');
        } else {
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        }
    }

    function buildConversationRow(conv) {
        const wrapper = document.createElement('div');
        wrapper.className = 'group relative';

        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'flex w-full items-start gap-3 rounded-xl p-2.5 pr-9 text-left transition hover:bg-ice-blue';
        row.innerHTML = `
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                ${conv.name.charAt(0).toUpperCase()}
            </span>
            <span class="min-w-0 flex-1">
                <span class="flex items-center justify-between gap-2">
                    <span class="truncate text-sm font-semibold text-charcoal">${escapeHtml(conv.name)}</span>
                    ${conv.unread_count > 0 ? `<span class="shrink-0 rounded-full bg-sale-red px-1.5 py-0.5 text-[10px] font-bold text-white">${conv.unread_count}</span>` : ''}
                </span>
                ${conv.product ? `<span class="block truncate text-[11px] text-primary/70">Re: ${escapeHtml(conv.product.name)}</span>` : ''}
                <span class="block truncate text-xs text-charcoal/50">${conv.last_message ? escapeHtml(conv.last_message) : 'No messages yet'}</span>
            </span>
        `;
        row.addEventListener('click', () => openThread(conv.conversation_id, conv.name));

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.title = 'Delete chat';
        deleteBtn.className = 'absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-lg p-1.5 text-charcoal/30 transition hover:bg-sale-red/10 hover:text-sale-red group-hover:block';
        deleteBtn.innerHTML = `
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
            </svg>
        `;
        deleteBtn.addEventListener('click', async () => {
            if (!confirm('Delete this chat?')) return;
            await deleteConversation(conv.conversation_id);
            wrapper.remove();
        });

        wrapper.appendChild(row);
        wrapper.appendChild(deleteBtn);
        return wrapper;
    }

    async function deleteConversation(id) {
        try {
            await fetch(`/messages/${id}/delete`, { method: 'POST', headers: jsonHeaders() });
        } catch (err) {
            console.error(err);
        }
    }

    function buildQuickStartRow(item) {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = `flex w-full items-center gap-3 rounded-xl p-2.5 text-left transition hover:bg-ice-blue ${item.is_support ? 'border border-primary/20 bg-primary/5' : ''}`;
        row.innerHTML = `
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${item.is_support ? 'bg-primary text-white' : 'bg-primary/10 text-primary'} text-sm font-bold">
                ${item.is_support
                ? '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                : item.name.charAt(0).toUpperCase()}
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-charcoal">${escapeHtml(item.name)}</span>
                <span class="block truncate text-xs text-charcoal/50">${escapeHtml(item.subtitle)}</span>
            </span>
        `;
        row.addEventListener('click', () => startConversation(item.recipient_id, item.product_id, item.name));
        return row;
    }

    async function startConversation(recipientId, productId, name) {
        listContent.innerHTML = '<p class="p-4 text-center text-xs text-charcoal/50">Starting conversation…</p>';
        try {
            const res = await fetch('/messages/open', {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ recipient_id: recipientId, product_id: productId }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error();
            openThread(data.conversation_id, name);
        } catch (err) {
            listContent.innerHTML = '<p class="p-4 text-center text-xs text-sale-red">Could not start conversation.</p>';
        }
    }

    // ---------------------------------------------------
    // THREAD VIEW
    // ---------------------------------------------------
    function openThread(conversationId, name) {
        activeConversationId = conversationId;
        lastMessageId = 0;
        threadMessages.innerHTML = '';
        productCard.classList.add('hidden');
        threadName.textContent = name;
        threadAvatar.textContent = name.charAt(0).toUpperCase();

        listView.classList.add('hidden');
        listView.classList.remove('flex');
        threadView.classList.remove('hidden');
        threadView.classList.add('flex');

        fetchMessages();
        pollTimer = setInterval(fetchMessages, 4000);
    }

    function stopPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = null;
    }

    threadBackBtn?.addEventListener('click', () => {
        stopPolling();
        showListView();
        loadList();

        document.getElementById('widgetThreadDeleteBtn')?.addEventListener('click', async () => {
            if (!activeConversationId || !confirm('Delete this chat?')) return;
            await deleteConversation(activeConversationId);
            stopPolling();
            showListView();
            loadList();
        });
    });

    async function fetchMessages() {
        if (!activeConversationId) return;
        try {
            const res = await fetch(`/messages/${activeConversationId}/messages?after=${lastMessageId}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();

            if (data.product) {
                productCard.classList.remove('hidden');
                productCard.classList.add('flex');
                document.getElementById('widgetThreadProductImage').src = data.product.image || '';
                document.getElementById('widgetThreadProductName').textContent = data.product.name;
                document.getElementById('widgetThreadProductPrice').textContent = `₱${Number(data.product.price).toFixed(2)}`;
            }

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

    function renderMessage(msg) {
        const isMine = msg.sender_id === parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);
        const wrapper = document.createElement('div');
        wrapper.className = `flex ${isMine ? 'justify-end' : 'justify-start'}`;

        const photos = (msg.attachments || []).map(a => `
        <a href="${escapeHtml(a.url)}" target="_blank" rel="noopener" class="mb-1 block overflow-hidden rounded-lg">
            <img src="${escapeHtml(a.url)}" alt="Photo" loading="lazy" class="max-h-40 w-full object-cover">
        </a>`).join('');

        wrapper.innerHTML = `
        <div class="max-w-[75%] rounded-2xl px-3.5 py-2 text-xs ${isMine ? 'bg-primary text-white' : 'bg-ice-blue text-charcoal'}">
            ${photos}
            ${msg.body ? `<p class="whitespace-pre-line">${escapeHtml(msg.body)}</p>` : ''}
            <p class="mt-1 text-[9px] opacity-60">${msg.created_at}</p>
        </div>
    `;
        return wrapper;
    }

    threadForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = threadInput.value.trim();
        if (!body || !activeConversationId) return;

        threadInput.value = '';

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
});
