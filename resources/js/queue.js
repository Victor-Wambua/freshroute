// Offline capture queue backed by IndexedDB. Each op = { uuid, type, captured_at, payload, summary, state, errors }.
// The uuid is the idempotency key the server uses; the same op can be sent any number of times safely.
const DB = 'freshroute';
const STORE = 'queue';

function open() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB, 1);
        req.onupgradeneeded = () => req.result.createObjectStore(STORE, { keyPath: 'uuid' });
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function tx(mode, fn) {
    const db = await open();
    return new Promise((resolve, reject) => {
        const t = db.transaction(STORE, mode);
        const res = fn(t.objectStore(STORE));
        t.oncomplete = () => { db.close(); resolve(res && 'result' in res ? res.result : undefined); };
        t.onerror = () => { db.close(); reject(t.error); };
    });
}

export const uuid = () => (crypto.randomUUID ? crypto.randomUUID() : ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, (c) => (c ^ (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))).toString(16)));

export const enqueue = (op) => tx('readwrite', (s) => s.put({ state: 'pending', errors: null, ...op }));
export const all = () => tx('readonly', (s) => s.getAll());
export const remove = (id) => tx('readwrite', (s) => s.delete(id));
export const update = (op) => tx('readwrite', (s) => s.put(op));
export const pending = async () => (await all()).filter((o) => o.state === 'pending');
