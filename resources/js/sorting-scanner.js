import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

export default function sortingScanner() {
    // Kept outside Alpine's reactive state: proxies break html5-qrcode.
    let scanner = null;
    let timer = null;
    const lastSeen = {};

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const post = async (url, body = {}) => {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });
        let data = {};
        try { data = await response.json(); } catch (e) { /* empty or non-JSON body */ }
        return { ok: response.ok, status: response.status, data };
    };

    const disposeScanner = async () => {
        const s = scanner;
        scanner = null;
        if (!s) return;
        try { await s.stop(); } catch (e) { /* not running */ }
        try { s.clear(); } catch (e) { /* ignore */ }
    };

    return {
        // Session state
        session: null,
        serverTimeOffset: 0,
        elapsedSeconds: 0,

        // Scanner state
        isScanning: false,
        cameraBusy: false,
        cameraError: '',
        manualCode: '',
        scanDebounceMs: 3000,
        needsRefresh: false,

        progress: { total_scans: 0, sorted: 0, needs_review: 0, rejected: 0, duplicates: 0 },
        lastResult: null,

        startingSession: false,
        endingSession: false,
        scanning: false,

        init() {
            const el = document.getElementById('sessionData');
            if (!el) return;

            const data = JSON.parse(el.textContent);
            this.session = data.session;
            this.serverTimeOffset = new Date(data.serverTime).getTime() - Date.now();

            if (this.session) this.startTimer();

            window.addEventListener('pagehide', () => disposeScanner());
        },

        // ---------- session ----------
        async startSession() {
            if (this.startingSession || this.session) return;
            this.startingSession = true;

            try {
                const { ok, data } = await post('/logistics/sorting/sessions/start');
                if (!ok) {
                    this.showError(data.message || 'Failed to start session');
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
                this.showError('Network error. Please try again.');
            } finally {
                this.startingSession = false;
            }
        },

        async endSession() {
            if (!this.session || this.endingSession) return;
            if (!confirm('End this sorting session? You can view the summary after.')) return;

            this.endingSession = true;

            try {
                await this.stopScanner(false); // never blocks ending the session

                const { ok, status, data } = await post(`/logistics/sorting/sessions/${this.session.id}/end`);

                if (!ok && status !== 422) {
                    this.showError(data.message || 'Failed to end session');
                    return;
                }

                // 422 = already ended / auto-closed: just resync with the server.
                this.stopTimer();
                this.session = null;

                if (ok && data.summary) this.showSessionSummary(data.summary);

                window.location.reload();
            } catch (error) {
                console.error(error);
                this.showError('Network error. Please try again.');
            } finally {
                this.endingSession = false;
            }
        },

        // ---------- camera ----------
        describeCameraError(err) {
            const name = err?.name || '';
            const msg = String(err?.message || err || '');
            if (name === 'NotAllowedError' || /permission/i.test(msg)) {
                return 'Camera permission denied. Allow camera access in the browser address bar, then try again.';
            }
            if (name === 'NotFoundError' || /no camera|device not found/i.test(msg)) {
                return 'No camera was found on this device.';
            }
            if (name === 'NotReadableError') {
                return 'The camera is being used by another app or tab.';
            }
            return `Could not start the camera: ${msg}`;
        },

        async startScanner() {
            if (this.isScanning || this.cameraBusy || !this.session) return;
            this.cameraError = '';

            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                this.cameraError =
                    'The camera only works on HTTPS or http://localhost. Open the site through localhost or HTTPS.';
                return;
            }

            this.cameraBusy = true;
            this.isScanning = true;       // show the container first
            await this.$nextTick();

            const config = {
                fps: 10,
                qrbox: (w, h) => ({
                    width: Math.floor(Math.min(w * 0.9, 420)),
                    height: Math.floor(Math.min(h * 0.6, 260)),
                }),
            };
            const onDecoded = (text) => this.handleScan(text);
            const onFrameError = () => { };

            try {
                scanner = new Html5Qrcode('scanner-container', {
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats.QR_CODE,
                        Html5QrcodeSupportedFormats.CODE_128,
                    ],
                    verbose: false,
                });

                try {
                    await scanner.start({ facingMode: 'environment' }, config, onDecoded, onFrameError);
                } catch (firstError) {
                    // Laptops/desktops have no rear camera: fall back to any camera.
                    if (firstError?.name === 'NotAllowedError') throw firstError;
                    const cameras = await Html5Qrcode.getCameras();
                    if (!cameras.length) throw firstError;
                    await scanner.start(cameras[0].id, config, onDecoded, onFrameError);
                }
            } catch (err) {
                console.error('Camera start failed', err);
                this.cameraError = this.describeCameraError(err);
                this.isScanning = false;
                await disposeScanner();
            } finally {
                this.cameraBusy = false;
            }
        },

        async stopScanner(refreshIfNeeded = true) {
            this.cameraBusy = true;
            try {
                await disposeScanner();
            } finally {
                this.isScanning = false;
                this.cameraBusy = false;
            }

            // Refresh the To Sort / To Assign lists only once the camera is off.
            if (refreshIfNeeded && this.needsRefresh) {
                window.location.reload();
            }
        },

        // ---------- scanning ----------
        async handleScan(raw) {
            const code = String(raw || '').trim();
            if (!code) return;

            // The camera decodes the same label many times per second.
            const now = Date.now();
            if (lastSeen[code] && now - lastSeen[code] < this.scanDebounceMs) return;
            lastSeen[code] = now;

            await this.processScan(code);
        },

        async handleManualInput() {
            const code = this.manualCode.trim();
            if (!code) return;

            await this.processScan(code);
            this.manualCode = '';
        },

        async processScan(code) {
            if (!this.session || this.scanning) return;

            this.scanning = true;
            this.lastResult = null;

            try {
                const { ok, status, data } = await post(
                    `/logistics/sorting/sessions/${this.session.id}/scan`,
                    { code }
                );

                if (!ok) {
                    // Session was closed elsewhere (e.g. auto-closed): resync.
                    if (status === 422 || status === 404) {
                        this.showError(data.message || 'This session is no longer active.');
                        window.location.reload();
                        return;
                    }
                    this.showError(data.message || 'Scan failed');
                    return;
                }

                this.progress = data.progress;
                this.showScanResult(data);

                if (data.result === 'sorted') {
                    this.needsRefresh = true;
                    // Don't kill the camera mid-session; refresh when it is stopped.
                    if (!this.isScanning) setTimeout(() => window.location.reload(), 1500);
                }
            } catch (error) {
                console.error(error);
                this.showError('Network error. Please try again.');
            } finally {
                this.scanning = false;
            }
        },

        showScanResult(data) {
            const colors = {
                sorted: 'text-leaf-green',
                duplicate: 'text-orange-600',
                rejected: 'text-sale-red',
                needs_review: 'text-orange-600',
            };
            this.lastResult = { ...data, color: colors[data.result] || 'text-charcoal' };
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
            this.elapsedSeconds = Math.max(0, Math.floor((Date.now() + this.serverTimeOffset - startedAt) / 1000));
        },

        formatDuration(seconds) {
            const s = Math.max(0, Math.floor(seconds || 0));
            const pad = (n) => String(n).padStart(2, '0');
            return `${pad(Math.floor(s / 3600))}:${pad(Math.floor((s % 3600) / 60))}:${pad(s % 60)}`;
        },

        get formattedElapsed() {
            return this.formatDuration(this.elapsedSeconds);
        },

        showError(message) {
            alert(message);
        },
    };
}
