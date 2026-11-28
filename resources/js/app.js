import '../css/app.css';
import { enqueue, all, remove, uuid } from './queue.js';
import { syncNow, counts } from './sync.js';

const i18n = (() => { try { return JSON.parse(document.body.dataset.i18n || '{}'); } catch { return {}; } })();
const t = (k, vars = {}) => (i18n[k] || k).replace(/:(\w+)/g, (_, n) => vars[n] ?? '');
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

function toast(msg) {
    const el = $('#toast');
    if (!el) return;
    el.textContent = msg;
    el.classList.add('on');
    clearTimeout(toast.h);
    toast.h = setTimeout(() => el.classList.remove('on'), 4500);
}

// ---- Theme ----------------------------------------------------------------------------
$('#theme-btn')?.addEventListener('click', () => {
    const cur = document.documentElement.getAttribute('data-theme')
        || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('fr-theme', next); } catch { /* storage unavailable */ }
});

// ---- Quick-pick chips and steppers ----------------------------------------------------------
$$('[data-set]').forEach((b) => b.addEventListener('click', () => {
    const input = $(b.dataset.set);
    if (!input) return;
    input.value = b.dataset.value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    $$(`[data-set="${b.dataset.set}"]`).forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
}));
$$('[data-bump]').forEach((b) => b.addEventListener('click', () => {
    const input = $(b.dataset.bump);
    if (!input) return;
    const step = Number(b.dataset.by);
    const v = Number(input.value || 0) + step;
    input.value = Math.max(Number(input.min || 0), Math.min(Number(input.max || 1e9), v));
}));
$$('[data-choice]').forEach((b) => b.addEventListener('click', () => {
    const input = $(b.dataset.choice);
    input.value = b.dataset.value;
    $$(`[data-choice="${b.dataset.choice}"]`).forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
}));

// ---- Sell wizard (5 steps) ---------------------------------------------------------------------
const wiz = $('[data-wizard]');
if (wiz) {
    const steps = $$('.wiz-step', wiz);
    const bar = $$('.progress i', wiz);
    let at = 0;
    const show = (n) => {
        at = n;
        steps.forEach((s, i) => s.classList.toggle('on', i === n));
        bar.forEach((b, i) => b.classList.toggle('on', i <= n));
        const count = $('[data-step-count]', wiz);
        if (count) count.textContent = t('step_of', { n: n + 1, total: steps.length });
        $('[data-back]', wiz).classList.toggle('hide', n === 0);
        $('[data-next]', wiz).classList.toggle('hide', n === steps.length - 1);
        $('[data-submit]', wiz).classList.toggle('hide', n !== steps.length - 1);
        if (n === steps.length - 1) fillReview();
        steps[n].querySelector('input:not([type=hidden]),select,textarea,button')?.focus({ preventScroll: true });
        window.scrollTo({ top: 0 });
    };
    const fillReview = () => {
        const g = (n) => wiz.elements[n]?.value || '-';
        $('#rv-bags').textContent = g('quantity_bags');
        $('#rv-date').textContent = g('harvest_date');
        $('#rv-grade').textContent = g('quality_grade');
        $('#rv-price').textContent = g('price_kes');
        $('#rv-place').textContent = `${g('place')}, ${g('county')}`;
    };
    const validStep = () => {
        const fields = $$('input,select,textarea', steps[at]).filter((f) => f.type !== 'hidden' && f.type !== 'file');
        for (const f of fields) {
            if (!f.checkValidity()) { f.reportValidity(); return false; }
        }
        // choice buttons write to a hidden required input: make sure one was picked
        for (const h of $$('input[type=hidden][required]', steps[at])) {
            if (!h.value) {
                let e = $('.err[data-choice-err]', steps[at]);
                if (!e) { e = document.createElement('div'); e.className = 'err'; e.setAttribute('role', 'alert'); e.dataset.choiceErr = '1'; e.textContent = t('pick_one'); steps[at].append(e); }
                steps[at].querySelector('[data-choice]')?.focus();
                return false;
            }
        }
        return true;
    };
    $('[data-next]', wiz).addEventListener('click', () => { if (validStep()) show(at + 1); });
    $('[data-back]', wiz).addEventListener('click', () => show(at - 1));
    // Enter inside a step advances instead of submitting early
    wiz.addEventListener('keydown', (e) => { if (e.key === 'Enter' && e.target.tagName === 'INPUT' && at < steps.length - 1) { e.preventDefault(); if (validStep()) show(at + 1); } });
    const firstErr = $('.err', wiz);
    show(firstErr ? steps.findIndex((s) => s.contains(firstErr)) : 0);
}

// ---- Compare (max 3) ------------------------------------------------------------------------------
const cmpBoxes = $$('input.cmp');
if (cmpBoxes.length) {
    const link = $('#cmp-link');
    const update = () => {
        const ids = cmpBoxes.filter((c) => c.checked).map((c) => c.value);
        cmpBoxes.forEach((c) => { c.disabled = !c.checked && ids.length >= 3; });
        link.classList.toggle('hide', ids.length < 2);
        link.href = '/market/compare?' + ids.map((i) => `ids[]=${i}`).join('&');
    };
    cmpBoxes.forEach((c) => c.addEventListener('change', update));
    update();
}

// ---- Offline capture --------------------------------------------------------------------------------
const pill = $('#net-pill');
const banner = $('#queue-banner');

async function renderState() {
    const { pending: n, rejected } = await counts().catch(() => ({ pending: 0, rejected: 0 }));
    const offline = !navigator.onLine;
    if (pill) {
        pill.classList.toggle('hide', !offline && n === 0);
        pill.classList.toggle('off', offline);
        pill.classList.toggle('sync', !offline && n > 0);
        pill.textContent = offline ? t('offline') : t('waiting', { n });
    }
    if (banner) {
        banner.innerHTML = '';
        const show = n > 0 || rejected > 0;
        banner.classList.toggle('hide', !show);
        if (show) {
            const span = document.createElement('span');
            span.textContent = (n ? t('waiting', { n }) + '. ' : '') + (rejected ? t('rejected', { n: rejected }) : '') + (offline ? ' ' + t('will_send') : '');
            banner.append(span);
            if (!offline && n > 0) {
                const b = document.createElement('button');
                b.type = 'button'; b.className = 'btn sm'; b.id = 'send-now'; b.textContent = t('send_now');
                b.addEventListener('click', () => runSync(true));
                banner.append(b);
            }
        }
    }
    renderQueuedLists();
}

async function renderQueuedLists() {
    const boxes = $$('[data-queue-list]');
    if (!boxes.length) return;
    const ops = await all().catch(() => []);
    boxes.forEach((box) => {
        const mine = ops.filter((o) => o.type === box.dataset.queueList);
        box.innerHTML = '';
        mine.forEach((o) => {
            const card = document.createElement('div');
            card.className = 'card tight row between';
            card.dataset.queued = o.uuid;
            const txt = document.createElement('div');
            txt.textContent = o.summary || o.type;
            const badge = document.createElement('span');
            badge.className = 'badge ' + (o.state === 'rejected' ? 'b-danger' : 'b-warn');
            badge.textContent = o.state === 'rejected' ? t('rejected_one') : t('queued');
            card.append(txt, badge);
            if (o.state === 'rejected') {
                const d = document.createElement('button');
                d.type = 'button'; d.className = 'btn sm ghost'; d.textContent = t('discard');
                d.addEventListener('click', async () => { await remove(o.uuid); renderState(); });
                card.append(d);
            }
            box.append(card);
        });
    });
}

let retryDelay = 0;
async function runSync(manual = false) {
    const r = await syncNow();
    // a failed attempt (network not ready right after reconnecting, server blip) retries soon with backoff, instead of waiting for the 30 s poll
    if (r.error) { retryDelay = Math.min((retryDelay || 1500) * 2, 20000); clearTimeout(runSync.h); runSync.h = setTimeout(() => runSync(), retryDelay); } else if (!r.skipped) { retryDelay = 0; }
    if (r.auth === false) { if (manual) toast(t('session_expired')); return; }
    if (r.error && manual) toast(t('sync_failed'));
    if (r.created || r.duplicate) {
        toast(t('synced', { n: r.created + r.duplicate }));
        if ($('[data-reload-on-sync]')) setTimeout(() => location.reload(), 900);
    }
    renderState();
}

function summarise(type, p) {
    const f = {
        listing: () => `${p.quantity_bags} bags, grade ${p.quality_grade}, KES ${p.price_kes}, ${p.place}`,
        loss: () => `${p.bags} bags, ${p.stage}: ${p.reason}`,
        enquiry: () => `${p.quantity_bags} bags (enquiry)`,
        need: () => `${p.quantity_bags} bags needed by ${p.needed_by}`,
        price: () => `KES ${p.price_kes} at ${p.market}`,
    };
    return (f[type] || (() => type))();
}

$$('form[data-offline]').forEach((form) => {
    // each form render gets its own idempotency key; online double-submits are deduplicated server-side
    const key = form.querySelector('[name=client_uuid]');
    if (key && !key.value) key.value = uuid();
    form.addEventListener('submit', async (e) => {
        if (navigator.onLine) return;
        e.preventDefault();
        const fd = new FormData(form);
        const payload = {};
        let hadPhoto = false;
        for (const [k, v] of fd.entries()) {
            if (k === '_token' || k === '_method') continue;
            if (v instanceof File) { hadPhoto = hadPhoto || v.size > 0; continue; }
            if (k.endsWith('[]')) { (payload[k.slice(0, -2)] ||= []).push(v); } else { payload[k] = v; }
        }
        const type = form.dataset.offline;
        await enqueue({ uuid: payload.client_uuid || uuid(), type, captured_at: new Date().toISOString(), payload, summary: summarise(type, payload) });
        toast(t('saved_offline') + (hadPhoto ? ' ' + t('photo_skipped') : ''));
        form.reset();
        if (key) key.value = uuid();
        renderState();
        if (wiz) { document.location.hash = ''; }
    });
});

window.addEventListener('online', () => { renderState(); runSync(); });
window.addEventListener('offline', renderState);
setInterval(() => { if (navigator.onLine) runSync(); }, 30000);
renderState();
if (navigator.onLine) runSync();

// ---- Service worker + logout cache hygiene -----------------------------------------------------------
if ('serviceWorker' in navigator && location.protocol.startsWith('http')) {
    navigator.serviceWorker.register('/sw.js').then(() => navigator.serviceWorker.ready).then((reg) => {
        let last = 0;
        try { last = Number(localStorage.getItem('fr-warm') || 0); } catch { /* storage unavailable */ }
        // warm the offline page cache at most once every 30 minutes per device, never on every page view
        if (document.body.dataset.authed === '1' && navigator.onLine && Date.now() - last > 30 * 60 * 1000) {
            try { localStorage.setItem('fr-warm', String(Date.now())); } catch { /* storage unavailable */ }
            reg.active?.postMessage({ type: 'warm', urls: ['/home', '/sell', '/my/listings', '/losses', '/market', '/calendar', '/prices', '/alerts', '/enquiries', '/more'] });
        }
    }).catch(() => {});
}
$$('form[action$="/logout"]').forEach((f) => f.addEventListener('submit', () => {
    navigator.serviceWorker?.controller?.postMessage({ type: 'clear' });
    try { localStorage.removeItem('fr-warm'); } catch { /* storage unavailable */ }
}));

// ---- Photo upload: shrink big phone photos before sending (slow connections, 4 MB server limit) ----------------------
$$('input[type=file][data-shrink]').forEach((input) => input.addEventListener('change', async () => {
    const f = input.files?.[0];
    if (!f || f.size < 900 * 1024 || !/^image\/(jpeg|png|webp)$/.test(f.type) || !window.DataTransfer) return;
    try {
        const bmp = await createImageBitmap(f);
        const k = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
        const c = document.createElement('canvas');
        c.width = Math.round(bmp.width * k); c.height = Math.round(bmp.height * k);
        c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
        const blob = await new Promise((r) => c.toBlob(r, 'image/jpeg', 0.8));
        if (!blob || blob.size >= f.size) return;
        const dt = new DataTransfer();
        dt.items.add(new File([blob], f.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
        input.files = dt.files;
        let h = input.parentElement.querySelector('[data-shrunk]');
        if (!h) { h = document.createElement('div'); h.className = 'hint'; h.dataset.shrunk = '1'; h.setAttribute('role', 'status'); input.parentElement.append(h); }
        h.textContent = t('photo_ready', { kb: Math.round(blob.size / 1024) });
    } catch { /* keep the original file */ }
}));

// Forms marked data-confirm ask before submitting (no inline handlers)
$$('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); }));

// Market filters are collapsed on phones only (CSS keeps them visible without JS and on wide screens)
$$('[data-toggle]').forEach((b) => {
    const target = $(b.dataset.toggle);
    if (!target) return;
    const sync = () => b.setAttribute('aria-expanded', target.classList.contains('on') ? 'true' : 'false');
    b.addEventListener('click', () => { target.classList.toggle('on'); sync(); });
    sync();
});
