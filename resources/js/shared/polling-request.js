export function pollingRequest(interval = 10000) {
    const requests = new Map();

    return async (url, options = {}) => {
        if (document.hidden || navigator.onLine === false) { return null; }
        const key = new URL(url, window.location.origin).pathname;
        const state = requests.get(key) || { pending: false, next: 0, failures: 0 };
        requests.set(key, state);
        if (state.pending || Date.now() < state.next) { return null; }
        state.pending = true;
        try {
            const response = await fetch(url, { ...options, signal: options.signal || AbortSignal.timeout(15000) });
            if ([401, 403, 404, 419].includes(response.status) || response.redirected) {
                state.next = Infinity;
            } else if (response.status === 429 || response.status >= 500) {
                const retry = response.headers.get('Retry-After');
                const retryDelay = retry === null ? 0 : (/^\d+$/.test(retry) ? Number(retry) * 1000 : Date.parse(retry) - Date.now());
                state.failures++;
                state.next = Date.now() + Math.max(Number.isFinite(retryDelay) ? retryDelay : 0, Math.min(300000, interval * 2 ** Math.min(state.failures, 8)));
            } else {
                state.failures = 0;
                state.next = Date.now() + interval;
            }
            return response;
        } catch (error) {
            state.failures++;
            state.next = Date.now() + Math.min(300000, interval * 2 ** Math.min(state.failures, 8));
            throw error;
        } finally {
            state.pending = false;
        }
    };
}
