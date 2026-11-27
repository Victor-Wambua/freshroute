import { all, pending, remove, update } from './queue.js';

let running = false;

export async function syncNow() {
    if (running || !navigator.onLine) return { skipped: true };
    const ops = await pending();
    if (!ops.length) return { created: 0, duplicate: 0, rejected: 0 };
    running = true;
    try {
        const sess = await fetch('/api/v1/session', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const s = await sess.json();
        if (!s.authenticated) return { auth: false };
        const res = await fetch('/api/v1/sync', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': s.csrf },
            body: JSON.stringify({ ops: ops.slice(0, 50).map(({ uuid, type, captured_at, payload }) => ({ uuid, type, captured_at, payload })) }),
        });
        if (!res.ok) return { error: res.status };
        const { results } = await res.json();
        const out = { created: 0, duplicate: 0, rejected: 0 };
        for (const r of results) {
            const op = ops.find((o) => o.uuid === r.uuid);
            if (!op) continue;
            if (r.status === 'created' || r.status === 'duplicate') {
                out[r.status]++;
                await remove(op.uuid);
            } else {
                out.rejected++;
                await update({ ...op, state: 'rejected', errors: r.errors });
            }
        }
        return out;
    } catch (e) {
        return { error: 'network' };
    } finally {
        running = false;
    }
}

export async function counts() {
    const ops = await all();
    return { pending: ops.filter((o) => o.state === 'pending').length, rejected: ops.filter((o) => o.state === 'rejected').length };
}
