document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('helpWizard');
    if (!root) return;

    const topics = JSON.parse(document.getElementById('helpTopics').textContent);
    const orders = JSON.parse(document.getElementById('helpOrders').textContent);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const body = document.getElementById('wizardBody');
    const dots = document.querySelectorAll('[data-step-dot]');

    const MAX_FILES = 4;
    const MAX_BYTES = 5 * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    const state = {
        step: 1,
        category: null,
        issueIndex: null,
        followUp: null,
        orderId: '',
        details: '',
        files: [],
        error: '',
        submitting: false,
        result: null,
    };

    const esc = (s) => {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    };

    const btnPrimary = 'rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue disabled:opacity-50';
    const btnGhost = 'rounded-xl border border-charcoal/15 bg-white px-4 py-2.5 text-sm font-semibold text-charcoal/70 transition hover:bg-primary/5 hover:text-primary';
    const optionCard = 'flex cursor-pointer items-center gap-3 rounded-xl border border-charcoal/10 bg-white px-3 py-2.5 text-sm transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5';
    const fieldClass = 'w-full rounded-xl border border-charcoal/15 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10';

    function setStep(n) {
        state.step = n;
        state.error = '';
        dots.forEach((dot) => {
            const i = parseInt(dot.dataset.stepDot, 10);
            const done = n === 4 || i < n;
            const active = i === n;
            dot.className = 'flex h-6 w-6 items-center justify-center rounded-full ' +
                (done ? 'bg-leaf-green text-white' : active ? 'bg-primary text-white' : 'bg-light-gray text-charcoal/50');
            dot.textContent = done ? '✓' : i;
        });
        render();
        root.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function render() {
        ({ 1: renderTopics, 2: renderDetails, 3: renderReview, 4: renderDone })[state.step]();
    }

    // ---------------- Step 1: topic ----------------
    function renderTopics() {
        body.innerHTML = `
            <h2 class="text-base font-bold text-charcoal">What do you need help with?</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                ${Object.entries(topics).map(([key, t]) => `
                    <button type="button" data-topic="${esc(key)}"
                        class="flex items-start gap-3 rounded-xl border border-charcoal/10 bg-white p-3 text-left transition hover:border-primary hover:bg-primary/5">
                        <span class="text-xl leading-none">${t.icon}</span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-charcoal">${esc(t.label)}</span>
                            <span class="block text-xs text-charcoal/55">${esc(t.description)}</span>
                        </span>
                    </button>`).join('')}
            </div>`;

        body.querySelectorAll('[data-topic]').forEach((btn) => {
            btn.addEventListener('click', () => {
                state.category = btn.dataset.topic;
                state.issueIndex = null;
                state.followUp = null;
                state.orderId = '';
                setStep(2);
            });
        });
    }

    // ---------------- Step 2: guided questions ----------------
    function renderDetails() {
        const t = topics[state.category];
        const needsOrder = t.order !== 'none';
        const orderRequired = t.order === 'required';

        body.innerHTML = `
            <button type="button" id="wizBack" class="text-xs font-semibold text-primary hover:underline">← Change topic</button>
            <h2 class="mt-2 text-base font-bold text-charcoal">${t.icon} ${esc(t.label)}</h2>

            <fieldset class="mt-4">
                <legend class="mb-2 text-sm font-semibold text-charcoal">What's going on?</legend>
                <div class="space-y-2">
                    ${t.issues.map((issue, i) => `
                        <label class="${optionCard}">
                            <input type="radio" name="issue" value="${i}" class="accent-primary" ${state.issueIndex === i ? 'checked' : ''}>
                            <span>${esc(issue.label)}</span>
                        </label>`).join('')}
                </div>
            </fieldset>

            <fieldset id="followUpBox" class="mt-4 hidden">
                <legend id="followUpQuestion" class="mb-2 text-sm font-semibold text-charcoal"></legend>
                <div class="flex gap-2">
                    ${['yes', 'no'].map((v) => `
                        <label class="${optionCard} w-24 justify-center capitalize">
                            <input type="radio" name="followUp" value="${v}" class="accent-primary" ${state.followUp === v ? 'checked' : ''}>
                            <span>${v}</span>
                        </label>`).join('')}
                </div>
            </fieldset>

            ${needsOrder ? `
                <div class="mt-4">
                    <label for="wizOrder" class="mb-2 block text-sm font-semibold text-charcoal">
                        Which order is this about? ${orderRequired ? '<span class="text-sale-red">*</span>' : '<span class="font-normal text-charcoal/45">(optional)</span>'}
                    </label>
                    <select id="wizOrder" class="${fieldClass}">
                        <option value="">${orderRequired ? 'Select an order' : 'Not about a specific order'}</option>
                        ${orders.map((o) => `<option value="${o.id}" ${String(o.id) === String(state.orderId) ? 'selected' : ''}>${esc(o.label)}</option>`).join('')}
                    </select>
                    ${orders.length === 0 && orderRequired ? '<p class="mt-1 text-xs text-sale-red">You have no orders yet, so this topic is not available.</p>' : ''}
                </div>` : ''}

            <div class="mt-4">
                <label for="wizDetails" class="mb-2 block text-sm font-semibold text-charcoal">Tell us more</label>
                <textarea id="wizDetails" rows="4" maxlength="1500" placeholder="Describe what happened so we can help faster."
                    class="${fieldClass} resize-none">${esc(state.details)}</textarea>
                <p class="mt-1 text-right text-[11px] text-charcoal/45"><span id="wizCount">${state.details.length}</span>/1500</p>
            </div>

            <div class="mt-2">
                <p class="mb-2 text-sm font-semibold text-charcoal">Photos <span class="font-normal text-charcoal/45">(optional, up to ${MAX_FILES})</span></p>
                <div id="wizPhotos" class="flex flex-wrap items-center gap-2"></div>
                <input type="file" id="wizFile" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden">
            </div>

            <p id="wizError" class="mt-3 hidden text-xs font-semibold text-sale-red"></p>

            <div class="mt-4 flex justify-end">
                <button type="button" id="wizNext" class="${btnPrimary}">Review</button>
            </div>`;

        const followUpBox = body.querySelector('#followUpBox');
        const followUpQuestion = body.querySelector('#followUpQuestion');
        const errorEl = body.querySelector('#wizError');

        function showError(text) {
            errorEl.textContent = text;
            errorEl.classList.remove('hidden');
        }

        function syncFollowUp() {
            const issue = t.issues[state.issueIndex];
            if (issue && issue.ask) {
                followUpQuestion.textContent = issue.ask;
                followUpBox.classList.remove('hidden');
            } else {
                followUpBox.classList.add('hidden');
                state.followUp = null;
            }
        }
        syncFollowUp();

        // Photos
        const photosEl = body.querySelector('#wizPhotos');
        const fileInput = body.querySelector('#wizFile');

        function renderPhotos() {
            photosEl.innerHTML = '';
            state.files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                const chip = document.createElement('div');
                chip.className = 'relative h-16 w-16 overflow-hidden rounded-lg border border-charcoal/10 bg-white';
                chip.innerHTML = `
                    <img src="${url}" alt="" class="h-full w-full object-cover">
                    <button type="button" aria-label="Remove photo"
                        class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-charcoal/70 text-xs font-bold text-white hover:bg-sale-red">×</button>`;
                chip.querySelector('img').addEventListener('load', () => URL.revokeObjectURL(url));
                chip.querySelector('button').addEventListener('click', () => {
                    state.files.splice(index, 1);
                    renderPhotos();
                });
                photosEl.appendChild(chip);
            });

            if (state.files.length < MAX_FILES) {
                const add = document.createElement('button');
                add.type = 'button';
                add.className = 'flex h-16 w-16 flex-col items-center justify-center gap-0.5 rounded-lg border border-dashed border-charcoal/25 text-[11px] font-semibold text-charcoal/55 transition hover:border-primary hover:text-primary';
                add.innerHTML = '<span class="text-lg leading-none">+</span><span>Add</span>';
                add.addEventListener('click', () => fileInput.click());
                photosEl.appendChild(add);
            }
        }
        renderPhotos();

        fileInput.addEventListener('change', () => {
            for (const file of Array.from(fileInput.files)) {
                if (!ALLOWED_TYPES.includes(file.type)) { showError('Only JPG, PNG, WEBP or GIF photos are allowed.'); continue; }
                if (file.size > MAX_BYTES) { showError(`"${file.name}" is larger than 5 MB.`); continue; }
                if (state.files.length >= MAX_FILES) { showError(`You can attach up to ${MAX_FILES} photos.`); break; }
                state.files.push(file);
            }
            fileInput.value = '';
            renderPhotos();
        });

        body.querySelector('#wizBack').addEventListener('click', () => setStep(1));
        body.querySelectorAll('input[name="issue"]').forEach((r) => r.addEventListener('change', () => {
            state.issueIndex = parseInt(r.value, 10);
            state.followUp = null;
            body.querySelectorAll('input[name="followUp"]').forEach((f) => (f.checked = false));
            syncFollowUp();
        }));
        body.querySelectorAll('input[name="followUp"]').forEach((r) => r.addEventListener('change', () => (state.followUp = r.value)));
        body.querySelector('#wizOrder')?.addEventListener('change', (e) => (state.orderId = e.target.value));
        body.querySelector('#wizDetails').addEventListener('input', (e) => {
            state.details = e.target.value;
            body.querySelector('#wizCount').textContent = state.details.length;
        });

        body.querySelector('#wizNext').addEventListener('click', () => {
            const issue = t.issues[state.issueIndex];
            let error = '';

            if (!issue) error = 'Please choose what is going on.';
            else if (issue.ask && !state.followUp) error = 'Please answer the follow-up question.';
            else if (needsOrder && orderRequired && !state.orderId) error = 'Please select the order this is about.';
            else if (state.details.trim().length < 10) error = 'Please add a few more details (at least 10 characters).';

            if (error) return showError(error);
            setStep(3);
        });
    }

    // ---------------- Step 3: review ----------------
    function renderReview() {
        const t = topics[state.category];
        const issue = t.issues[state.issueIndex];
        const order = orders.find((o) => String(o.id) === String(state.orderId));

        const rows = [
            ['Topic', t.label],
            ['Issue', issue.label],
            issue.ask && state.followUp ? [issue.ask, state.followUp === 'yes' ? 'Yes' : 'No'] : null,
            order ? ['Order', order.label] : null,
            ['Details', state.details.trim()],
            state.files.length ? ['Photos', `${state.files.length} attached`] : null,
        ].filter(Boolean);

        body.innerHTML = `
            <h2 class="text-base font-bold text-charcoal">Review your ticket</h2>
            <dl class="mt-3 divide-y divide-charcoal/10 rounded-xl border border-charcoal/10 bg-white">
                ${rows.map(([k, v]) => `
                    <div class="px-3 py-2.5">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">${esc(k)}</dt>
                        <dd class="mt-0.5 whitespace-pre-line break-words text-sm text-charcoal">${esc(v)}</dd>
                    </div>`).join('')}
            </dl>
            <p id="wizError" class="mt-3 ${state.error ? '' : 'hidden'} text-xs font-semibold text-sale-red">${esc(state.error)}</p>
            <div class="mt-4 flex items-center justify-between gap-2">
                <button type="button" id="wizEdit" class="${btnGhost}">Edit</button>
                <button type="button" id="wizSubmit" class="${btnPrimary}" ${state.submitting ? 'disabled' : ''}>
                    ${state.submitting ? 'Sending…' : 'Submit ticket'}
                </button>
            </div>`;

        body.querySelector('#wizEdit').addEventListener('click', () => setStep(2));
        body.querySelector('#wizSubmit').addEventListener('click', submit);
    }

    async function submit() {
        if (state.submitting) return;
        const t = topics[state.category];
        const issue = t.issues[state.issueIndex];

        const form = new FormData();
        form.append('category', state.category);
        form.append('issue', issue.label);
        if (state.followUp) form.append('follow_up', state.followUp);
        if (state.orderId) form.append('order_id', state.orderId);
        form.append('details', state.details.trim());
        state.files.forEach((file) => form.append('images[]', file));

        state.submitting = true;
        state.error = '';
        renderReview();

        try {
            const res = await fetch(root.dataset.submitUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: form,
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                state.error = firstError || data.message || 'Could not submit your ticket. Please try again.';
                state.submitting = false;
                renderReview();
                return;
            }

            state.result = data;
            state.submitting = false;
            setStep(4);
        } catch (err) {
            console.error(err);
            state.error = 'Network problem. Please try again.';
            state.submitting = false;
            renderReview();
        }
    }

    // ---------------- Step 4: done ----------------
    function renderDone() {
        const { ticket_number: number, conversation_id: id } = state.result;
        body.innerHTML = `
            <div class="py-4 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-leaf-green/10 text-xl text-leaf-green">✓</div>
                <h2 class="mt-3 text-base font-bold text-charcoal">Your ticket has been sent</h2>
                <p class="mt-1 text-sm text-charcoal/60">Ticket number <span class="font-bold text-charcoal">${esc(number)}</span></p>
                <p class="mt-1 text-xs text-charcoal/50">Our support team will reply inside your ticket.</p>
                <div class="mt-5 flex flex-col justify-center gap-2 sm:flex-row">
                    <a href="${root.dataset.ticketsUrl}?conversation=${id}" class="${btnPrimary}">View ticket</a>
                    <a href="${window.location.pathname}" class="${btnGhost}">Start another</a>
                </div>
            </div>`;
    }

    // ?topic=<key> preselects a topic (deep links from other pages).
    const preset = new URLSearchParams(window.location.search).get('topic');
    if (preset && topics[preset]) {
        state.category = preset;
        setStep(2);
    } else {
        setStep(1);
    }
});
