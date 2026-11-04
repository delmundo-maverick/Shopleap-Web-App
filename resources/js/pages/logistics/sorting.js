import { createScanner, postJson } from './scanner.js';
import { initLogisticsUi } from './ui.js';

function sortingScanner() {
    let timer = null;

    const scanner = createScanner({
        containerId: 'scanner-container',
        debounceMs: 3000,
        send: (code, state) => {
            if (!state.session) {
                return Promise.resolve({
                    ok: false,
                    status: 422,
                    data: { message: 'Start a sorting session first.' },
                });
            }

            return postJson(`/logistics/sorting/sessions/${state.session.id}/scan`, { code });
        },
        onResult: ({ ok, status, data }, state) => {
            const colors = {
                sorted: 'text-leaf-green',
                duplicate: 'text-orange-600',
                rejected: 'text-sale-red',
                needs_review: 'text-orange-600',
            };

            if (!ok) {
                const result = {
                    ok: false,
                    result: data.result || 'rejected',
                    message: data.message || 'Scan failed.',
                    color: colors[data.result] || 'text-sale-red',
                };

                // Session closed elsewhere (auto-closed after 12 hours, or another tab).
                if (status === 422 || status === 404) {
                    window.setTimeout(() => window.location.reload(), 1500);
                }

                return result;
            }

            state.progress = data.progress;

            if (data.result === 'sorted') {
                state.needsRefresh = true;

                // Do not stop the camera mid-session; refresh once it is off.
                if (!state.isScanning) {
                    window.setTimeout(() => window.location.reload(), 1500);
                }
            }

            return {
                ok: true,
                ...data,
                color: colors[data.result] || 'text-charcoal',
            };
        },
    });

    return {
        ...scanner,

        // Session state
        session: null,
        serverTimeOffset: 0,
        elapsedSeconds: 0,
        startingSession: false,
        endingSession: false,
        sortingAll: false,
        sessionError: '',

        progress: { total_scans: 0, sorted: 0, needs_review: 0, rejected: 0, duplicates: 0 },

        init() {
            this.initScanner();

            const el = document.getElementById('sessionData');
            if (!el) return;

            const data = JSON.parse(el.textContent);
            this.session = data.session;
            this.serverTimeOffset = new Date(data.serverTime).getTime() - Date.now();

            if (this.session) this.startTimer();
        },

        // ---------- session ----------
        async startSession() {
            if (this.startingSession || this.session) return;

            this.startingSession = true;
            this.sessionError = '';

            try {
                const { ok, data } = await postJson('/logistics/sorting/sessions/start');

                if (!ok) {
                    this.sessionError = data.message || 'Failed to start session.';
                    return;
                }

                // Safety net: never adopt a session that is already ended.
                if (data.session?.ended_at) {
                    window.location.reload();
                    return;
                }

                this.session = data.session;
                this.serverTimeOffset = new Date(data.server_time).getTime() - Date.now();
                this.startTimer();
            } catch (error) {
                console.error(error);
                this.sessionError = 'Network error. Please try again.';
            } finally {
                this.startingSession = false;
            }
        },

        async endSession() {
            if (!this.session || this.endingSession) return;
            if (!confirm('End this sorting session? You can view the summary after.')) return;

            this.endingSession = true;
            this.sessionError = '';

            try {
                await this.stopScanner(false); // never blocks ending the session

                const { ok, status, data } = await postJson(`/logistics/sorting/sessions/${this.session.id}/end`);

                if (!ok && status !== 422) {
                    this.sessionError = data.message || 'Failed to end session.';
                    return;
                }

                // 422 = already ended / auto-closed: just resync with the server.
                this.stopTimer();
                this.session = null;

                if (ok && data.summary) this.showSessionSummary(data.summary);

                window.location.reload();
            } catch (error) {
                console.error(error);
                this.sessionError = 'Network error. Please try again.';
            } finally {
                this.endingSession = false;
            }
        },

        sortAll(event) {
            if (!this.session || this.sortingAll) {
                event.preventDefault();
                return;
            }

            this.sortingAll = true;

            // Let Alpine paint the loading state before the browser navigates.
            event.preventDefault();
            const form = event.currentTarget;
            window.setTimeout(() => form.submit(), 60);
        },

        showSessionSummary(summary) {
            const lines = [
                'Session Summary',
                '',
                `Duration: ${this.formatDuration(summary.duration_seconds)}`,
                `Total scans: ${summary.total_scans}`,
                `Sorted: ${summary.sorted}`,
                `Awaiting transfer: ${summary.awaiting_transfer ?? 0}`,
                `Needs review: ${summary.needs_review}`,
                `Rejected: ${summary.rejected}`,
                `Duplicates: ${summary.duplicates}`,
                `Assigned during session: ${summary.assigned_during_session}`,
            ];

            const groups = Array.isArray(summary.groups) ? summary.groups : [];

            if (groups.length) {
                lines.push('', 'Sorted by destination hub:');

                groups.forEach((group) => {
                    const company = group.company ? ` (${group.company})` : '';
                    lines.push(`• ${group.hub_name}${company} — ${group.total} parcel(s)`);

                    (group.barangays || []).forEach((barangay) => {
                        lines.push(`   - Brgy. ${barangay.barangay}: ${barangay.count}`);
                    });
                });
            }

            alert(lines.join('\n'));
        },

        // ---------- timer ----------
        startTimer() {
            if (timer) return;
            this.updateElapsed();
            timer = setInterval(() => this.updateElapsed(), 1000);
        },

        stopTimer() {
            if (timer) clearInterval(timer);
            timer = null;
        },

        updateElapsed() {
            if (!this.session) return;

            const startedAt = new Date(this.session.started_at).getTime();
            this.elapsedSeconds = Math.max(
                0,
                Math.floor((Date.now() + this.serverTimeOffset - startedAt) / 1000)
            );
        },

        formatDuration(seconds) {
            const safeSeconds = Math.max(0, Math.floor(seconds || 0));
            const pad = (value) => String(value).padStart(2, '0');

            return `${pad(Math.floor(safeSeconds / 3600))}:${pad(Math.floor((safeSeconds % 3600) / 60))}:${pad(safeSeconds % 60)}`;
        },

        get formattedElapsed() {
            return this.formatDuration(this.elapsedSeconds);
        },
    };
}

window.sortingScanner = sortingScanner;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initLogisticsUi(), { once: true });
} else {
    initLogisticsUi();
}
