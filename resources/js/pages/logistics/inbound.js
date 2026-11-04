import { createScanner, postJson } from './scanner.js';

let scanSequence = 0;

function inboundScanner() {
    return {
        ...createScanner({
            containerId: 'inbound-scanner-container',
            debounceMs: 3000,
            send: (code) => postJson('/logistics/inbound/scan', { code }),
            onResult: ({ ok, data }, state) => {
                if (!ok) {
                    return {
                        ok: false,
                        message: data.message || 'Scan failed.',
                        reference: data.reference ?? null,
                        type: data.type ?? 'parcel',
                        color: 'text-sale-red',
                    };
                }

                state.needsRefresh = true;

                const reference = data.reference || (data.order_id ? `#${data.order_id}` : 'Bag');
                const entry = {
                    key: `scan-${Date.now()}-${++scanSequence}`,
                    reference,
                    message: data.message || `Received ${reference}.`,
                    type: data.type === 'bag' ? 'Bag' : 'Parcel',
                    time: new Date().toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                    }),
                };

                state.scanned.unshift(entry);
                state.scanned = state.scanned.slice(0, 50);

                return {
                    ok: true,
                    message: entry.message,
                    reference: entry.reference,
                    type: entry.type,
                    color: 'text-leaf-green',
                };
            },
        }),

        init() {
            this.initScanner();
        },
    };
}

window.inboundScanner = inboundScanner;
