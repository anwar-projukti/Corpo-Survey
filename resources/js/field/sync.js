import offlineQueue from './offline-queue';

const API_BASE = '/api/v1/my/surveys';

/**
 * Flushes the offline queue (spec §7) against the real API whenever the
 * device is online. Each item's own client_submission_id (set to the
 * queue item's id at enqueue time) is what makes a retry after a partial
 * failure safe — the server-side unique index on
 * (collected_by_user_id, client_submission_id) means POSTing the same
 * item twice is a no-op, not a duplicate response.
 */
class FieldSyncService {
    constructor() {
        this.syncing = false;
        this.listeners = new Set();
    }

    onChange(callback) {
        this.listeners.add(callback);
        return () => this.listeners.delete(callback);
    }

    notify() {
        this.listeners.forEach((callback) => callback());
    }

    /**
     * Attempts to send every pending/failed item in the queue. Items that
     * fail with a network error stay queued for the next attempt; items
     * the server rejects outright (e.g. validation — the survey changed
     * since the item was queued) are marked 'failed' with the server's
     * message rather than retried forever.
     */
    async syncAll() {
        if (this.syncing || !navigator.onLine) return;

        this.syncing = true;
        this.notify();

        try {
            const items = await offlineQueue.all();

            for (const item of items) {
                await this.syncItem(item);
            }
        } finally {
            this.syncing = false;
            this.notify();
        }
    }

    async syncItem(item) {
        await offlineQueue.markStatus(item.id, 'syncing');
        this.notify();

        try {
            await axios.post(`${API_BASE}/${item.surveyId}/responses`, item.payload);
            await offlineQueue.remove(item.id);
        } catch (error) {
            if (this.isNetworkError(error)) {
                await offlineQueue.markStatus(item.id, 'pending');
            } else {
                const message = error?.response?.data?.message || 'Submission rejected by the server.';
                await offlineQueue.markStatus(item.id, 'failed', message);
            }
        }

        this.notify();
    }

    isNetworkError(error) {
        return !error?.response;
    }

    startAutoSync() {
        window.addEventListener('online', () => this.syncAll());
        // A connection can be technically "online" per the browser but
        // still failing requests (captive portal, flaky signal) — a
        // periodic retry catches that case without needing the user to
        // do anything.
        setInterval(() => this.syncAll(), 30_000);
        this.syncAll();
    }
}

export default new FieldSyncService();
