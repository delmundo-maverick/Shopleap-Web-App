import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/**
 * POST JSON with the CSRF token and normalised response shape.
 *
 * @param {string} url
 * @param {Record<string, unknown>} body
 * @returns {Promise<{ok: boolean, status: number, data: Record<string, any>}>}
 */
export async function postJson(url, body = {}) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: JSON.stringify(body),
    });

    let data = {};
    try {
        data = await response.json();
    } catch {
        // Some validation responses have no JSON body.
    }

    return { ok: response.ok, status: response.status, data };
}

/**
 * Shared camera + manual scanner used by Sort & Assign and Inbound.
 *
 * Page-specific factories spread this object into their Alpine component and
 * provide the endpoint and result mapping. Camera resources stay outside the
 * reactive object because html5-qrcode mutates its own DOM nodes.
 *
 * @param {{
 *   containerId: string,
 *   send: (code: string, state: any) => Promise<{ok: boolean, status: number, data: Record<string, any>}>,
 *   onResult: (response: {ok: boolean, status: number, data: Record<string, any>}, state: any) => Record<string, any>,
 *   debounceMs?: number,
 * }} options
 */
export function createScanner({ containerId, send, onResult, debounceMs = 3000 }) {
    let scanner = null;
    const lastSeen = new Map();

    const dispose = async () => {
        const active = scanner;
        scanner = null;

        if (!active) return;

        try {
            await active.stop();
        } catch {
            // The scanner may already be stopped.
        }

        try {
            active.clear();
        } catch {
            // Ignore DOM cleanup errors.
        }
    };

    return {
        isScanning: false,
        cameraBusy: false,
        cameraError: '',
        manualCode: '',
        scanning: false,
        scanDebounceMs: debounceMs,
        lastResult: null,
        scanned: [],
        needsRefresh: false,

        initScanner() {
            window.addEventListener('pagehide', () => {
                void dispose();
            });
        },

        describeCameraError(error) {
            const name = error?.name || '';
            const message = String(error?.message || error || '');

            if (name === 'NotAllowedError' || /permission/i.test(message)) {
                return 'Camera permission denied. Allow camera access in the browser address bar, then try again.';
            }

            if (name === 'NotFoundError' || /no camera|device not found/i.test(message)) {
                return 'No camera was found on this device.';
            }

            if (name === 'NotReadableError') {
                return 'The camera is being used by another app or tab.';
            }

            return `Could not start the camera: ${message}`;
        },

        async startScanner() {
            if (this.isScanning || this.cameraBusy) return;

            this.cameraError = '';

            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                this.cameraError =
                    'The camera only works on HTTPS or http://localhost. Open the site through localhost or HTTPS.';
                return;
            }

            this.cameraBusy = true;
            this.isScanning = true; // Render the container before html5-qrcode measures it.
            await this.$nextTick();

            const config = {
                fps: 10,
                qrbox: (width, height) => ({
                    width: Math.floor(Math.min(width * 0.9, 420)),
                    height: Math.floor(Math.min(height * 0.6, 260)),
                }),
            };

            const onDecoded = (text) => {
                void this.handleScan(text);
            };
            const onFrameError = () => {};

            try {
                scanner = new Html5Qrcode(containerId, {
                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE, Html5QrcodeSupportedFormats.CODE_128],
                    verbose: false,
                });

                try {
                    await scanner.start({ facingMode: 'environment' }, config, onDecoded, onFrameError);
                } catch (firstError) {
                    if (firstError?.name === 'NotAllowedError') throw firstError;

                    // Laptops/desktops usually have no rear camera: fall back to the first camera.
                    const cameras = await Html5Qrcode.getCameras();
                    if (!cameras.length) throw firstError;

                    await scanner.start(cameras[0].id, config, onDecoded, onFrameError);
                }
            } catch (error) {
                console.error('Camera start failed', error);
                this.cameraError = this.describeCameraError(error);
                this.isScanning = false;
                await dispose();
            } finally {
                this.cameraBusy = false;
            }
        },

        async stopScanner(reloadIfNeeded = true) {
            this.cameraBusy = true;

            try {
                await dispose();
            } finally {
                this.isScanning = false;
                this.cameraBusy = false;
            }

            if (reloadIfNeeded && this.needsRefresh) {
                window.location.reload();
            }
        },

        isDuplicate(code) {
            const now = Date.now();
            const seenAt = lastSeen.get(code);

            if (seenAt && now - seenAt < this.scanDebounceMs) {
                return true;
            }

            lastSeen.set(code, now);
            return false;
        },

        async handleScan(raw) {
            const code = String(raw || '').trim();
            if (!code || this.isDuplicate(code)) return;

            await this.processScan(code);
        },

        async handleManualInput() {
            const code = this.manualCode.trim();
            if (!code) return;

            if (this.isDuplicate(code)) {
                this.lastResult = {
                    ok: false,
                    message: 'That code was just scanned. Wait a moment before submitting it again.',
                    color: 'text-orange-600',
                };
                return;
            }

            await this.processScan(code);
            this.manualCode = '';
        },

        async processScan(code) {
            if (this.scanning) return;

            this.scanning = true;
            this.lastResult = null;

            try {
                const response = await send(code, this);
                const result = onResult(response, this);

                if (result) {
                    this.lastResult = result;
                    this.playTone(result.ok ? 880 : 220, result.ok ? 'sine' : 'square');
                }
            } catch (error) {
                console.error(error);
                this.lastResult = {
                    ok: false,
                    message: 'Network error. Please try again.',
                    color: 'text-sale-red',
                };
                this.playTone(220, 'square');
            } finally {
                this.scanning = false;
            }
        },

        playTone(frequency = 800, type = 'square') {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                const context = new AudioContext();
                const oscillator = context.createOscillator();
                const gain = context.createGain();

                oscillator.type = type;
                oscillator.frequency.setValueAtTime(frequency, context.currentTime);
                gain.gain.setValueAtTime(0.1, context.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.2);

                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start();
                oscillator.stop(context.currentTime + 0.2);

                oscillator.addEventListener('ended', () => {
                    void context.close();
                });
            } catch {
                // Audio feedback is optional.
            }
        },

        clearResults() {
            this.scanned = [];
            this.lastResult = null;
        },

        refreshLists() {
            window.location.reload();
        },
    };
}
