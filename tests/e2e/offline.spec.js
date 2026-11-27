import { test, expect } from '@playwright/test';
import { login, ymd } from './helpers.js';

// Real service worker + IndexedDB queue in Chromium. Offline is simulated at the network layer (context.setOffline).

async function queueOps(page) {
    return page.evaluate(() => new Promise((resolve, reject) => {
        const req = indexedDB.open('freshroute', 1);
        req.onsuccess = () => {
            const tx = req.result.transaction('queue', 'readonly');
            const all = tx.objectStore('queue').getAll();
            all.onsuccess = () => resolve(all.result);
            all.onerror = () => reject(all.error);
        };
        req.onerror = () => reject(req.error);
    }));
}

test('service worker registers, precaches the shell and serves cached pages offline', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await login(page, 'samuel@freshroute.test');
    await page.goto('/home');
    await page.waitForFunction(async () => (await navigator.serviceWorker.getRegistration())?.active?.state === 'activated');
    await page.waitForFunction(async () => { const names = await caches.keys(); for (const n of names) { if (await (await caches.open(n)).match('/sell')) return true; } return false; }, null, { timeout: 15000 });
    await context.setOffline(true);
    await page.goto('/sell');
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sell potatoes');
    await expect(page.locator('#net-pill')).toContainText('Offline');
    // a page that was never cached falls back to the offline page
    await page.goto('/farms-never-visited-zzz');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('You are offline');
    await context.close();
});

test('listing and loss captured offline are queued, then sync once, idempotently, when back online', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await login(page, 'david@freshroute.test');
    await page.goto('/home');
    await page.waitForFunction(async () => (await navigator.serviceWorker.getRegistration())?.active?.state === 'activated');
    await page.waitForFunction(async () => { for (const n of await caches.keys()) { const c = await caches.open(n); if ((await c.match('/sell')) && (await c.match('/losses'))) return true; } return false; }, null, { timeout: 15000 });

    await context.setOffline(true);

    // --- listing offline through the real wizard
    await page.goto('/sell');
    await page.fill('#f-quantity_bags', '77');
    await page.click('[data-next]');
    await page.fill('#f-harvest_date', ymd(2));
    await page.click('[data-next]');
    await page.click('[data-choice="#f-quality_grade"][data-value="B"]');
    await page.fill('#f-place', 'Rongai');
    await page.click('[data-next]');
    await page.fill('#f-price_kes', '2310');
    await page.click('[data-next]');
    await page.click('[data-submit]');
    await expect(page.locator('#toast')).toContainText('Saved on this phone');
    await expect(page.locator('#queue-banner')).toContainText('1 waiting');
    await expect(page.locator('[data-queue-list="listing"] [data-queued]')).toContainText('77 bags');

    // --- loss offline
    await page.goto('/losses');
    await page.click('[data-choice="#f-stage"][data-value="storage"]');
    await page.fill('#f-bags', '13');
    await page.selectOption('#f-category', 'spoiled');
    await page.selectOption('#f-reason', 'rot');
    await page.locator('form[data-offline=loss] button[type=submit]').click();
    await expect(page.locator('#toast')).toContainText('Saved on this phone');
    await expect(page.locator('#queue-banner')).toContainText('2 waiting');

    const ops = await queueOps(page);
    expect(ops.map((o) => o.type).sort()).toEqual(['listing', 'loss']);
    expect(ops.every((o) => /^[0-9a-f-]{36}$/.test(o.uuid))).toBe(true);

    // --- back online: auto-sync
    await context.setOffline(false);
    await expect(page.locator('#queue-banner')).toBeHidden({ timeout: 20000 });
    expect(await queueOps(page)).toHaveLength(0);

    await page.goto('/my/listings');
    await expect(page.locator('main')).toContainText('77 bags');
    await expect(page.locator('main')).toContainText('Rongai');
    await page.goto('/losses');
    await expect(page.locator('main')).toContainText('13 bags');

    // --- replay the very same operations: server answers "duplicate", nothing is added
    const csrf = await page.evaluate(async () => (await (await fetch('/api/v1/session')).json()).csrf);
    const replay = await page.evaluate(async ({ ops, csrf }) => {
        const r = await fetch('/api/v1/sync', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ ops: ops.map(({ uuid, type, captured_at, payload }) => ({ uuid, type, captured_at, payload })) }) });
        return (await r.json()).results.map((x) => x.status);
    }, { ops, csrf });
    expect(replay).toEqual(['duplicate', 'duplicate']);
    await page.goto('/my/listings');
    await expect(page.locator('main a.card')).toHaveCount(2); // seeded one + the one synced, not three
    await context.close();
});

test('an invalid offline record is not lost silently: it is flagged for the user after sync', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await login(page, 'joyce@freshroute.test');
    await page.goto('/home');
    await page.waitForFunction(async () => (await navigator.serviceWorker.getRegistration())?.active?.state === 'activated');
    await page.waitForFunction(async () => { for (const n of await caches.keys()) { const c = await caches.open(n); if (await c.match('/losses')) return true; } return false; }, null, { timeout: 15000 });
    await context.setOffline(true);
    await page.goto('/losses');
    await page.click('[data-choice="#f-stage"][data-value="market"]');
    await page.fill('#f-bags', '999999'); // beyond the server limit (client has no max)
    await page.selectOption('#f-category', 'rejected');
    await page.selectOption('#f-reason', 'greening');
    await page.locator('form[data-offline=loss] button[type=submit]').click();
    await expect(page.locator('#queue-banner')).toContainText('1 waiting');
    await context.setOffline(false);
    await expect(page.locator('#queue-banner')).toContainText('could not be saved', { timeout: 20000 });
    await expect(page.locator('[data-queue-list="loss"]')).toContainText('Needs checking');
    await context.close();
});
