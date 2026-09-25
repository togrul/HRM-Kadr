/**
 * Retry a Livewire update once when the network drops it before it reaches the server.
 *
 * Safari reuses a keep-alive connection the server has just closed (nginx idles them out)
 * and fails the request with "The network connection was lost" instead of resending it —
 * it only resends idempotent requests, and every Livewire update is a POST. The result was
 * a random failed update: a lazy island stuck on its skeleton, or a click that did nothing.
 *
 * ponytail: only failures within FAST_FAILURE_MS are retried — a dead keep-alive fails at
 * once, before the server sees the request. A slower failure may have reached the server,
 * and resending it could run an action twice, so it surfaces as before.
 */
const FAST_FAILURE_MS = 1000;
const RETRY_DELAY_MS = 150;

const nativeFetch = window.fetch.bind(window);

function isLivewireUpdate(input, init) {
    const url = typeof input === 'string' ? input : (input?.url ?? '');
    const headers = init?.headers ?? {};

    return headers['X-Livewire'] !== undefined || /\/livewire[^/]*\/update/.test(url);
}

window.fetch = async (input, init) => {
    const startedAt = performance.now();

    try {
        return await nativeFetch(input, init);
    } catch (error) {
        const aborted = error?.name === 'AbortError' || init?.signal?.aborted;

        if (aborted || ! isLivewireUpdate(input, init) || performance.now() - startedAt > FAST_FAILURE_MS) {
            throw error;
        }

        await new Promise((resolve) => setTimeout(resolve, RETRY_DELAY_MS));

        return nativeFetch(input, init);
    }
};
