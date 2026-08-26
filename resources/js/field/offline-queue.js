/**
 * A minimal IndexedDB-backed queue for door-to-door submissions made while
 * offline (spec §7 — connectivity isn't guaranteed during a field visit).
 * No external library: the operations this needs (add/list/remove/update
 * one object store, keyed by a generated id) don't justify a dependency,
 * and IndexedDB's callback-style API is small enough to wrap directly.
 *
 * A queued item's shape:
 *   {
 *     id: string,               // client-generated, doubles as client_submission_id
 *     surveyId: number,
 *     payload: object,          // the exact body POSTed to /api/v1/my/surveys/{id}/responses
 *     createdAt: string,        // ISO timestamp, for display ("queued 2 minutes ago")
 *     status: 'pending' | 'syncing' | 'failed',
 *     lastError: string | null,
 * }
 */

const DB_NAME = 'field_collection_queue';
const DB_VERSION = 1;
const STORE_NAME = 'submissions';

let dbPromise = null;

function openDb() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    return dbPromise;
}

async function withStore(mode, fn) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, mode);
        const store = tx.objectStore(STORE_NAME);
        const result = fn(store);

        tx.oncomplete = () => resolve(result);
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error);
    });
}

function requestToPromise(request) {
    return new Promise((resolve, reject) => {
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function generateId() {
    return typeof crypto !== 'undefined' && crypto.randomUUID
        ? crypto.randomUUID()
        : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

export default {
    /** Adds a new pending submission to the queue and returns its id. */
    async enqueue(surveyId, payload) {
        const item = {
            id: generateId(),
            surveyId,
            payload,
            createdAt: new Date().toISOString(),
            status: 'pending',
            lastError: null,
        };

        await withStore('readwrite', (store) => store.put(item));
        return item.id;
    },

    async all() {
        return withStore('readonly', (store) => requestToPromise(store.getAll()));
    },

    async forSurvey(surveyId) {
        const items = await this.all();
        return items.filter((item) => item.surveyId === surveyId);
    },

    async count() {
        const items = await this.all();
        return items.length;
    },

    async remove(id) {
        return withStore('readwrite', (store) => store.delete(id));
    },

    async markStatus(id, status, lastError = null) {
        return withStore('readwrite', (store) => {
            const getRequest = store.get(id);
            getRequest.onsuccess = () => {
                const item = getRequest.result;
                if (!item) return;
                item.status = status;
                item.lastError = lastError;
                store.put(item);
            };
        });
    },
};
