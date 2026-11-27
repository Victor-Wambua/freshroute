import { test, expect } from '@playwright/test';
import fs from 'fs';
import { login, loginCoop, sellWizard, ymd, totp } from './helpers.js';

// One continuous journey with real browsers against the real app (synthetic seed data):
// farmer lists (online) -> buyer finds and enquires -> farmer responds -> delivery request -> loss recorded -> report exported.

test.describe.configure({ mode: 'serial' });

let ctx = {};

test('farmer creates a listing online with the 5-step wizard', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await login(page, 'agnes@freshroute.test');
    await sellWizard(page, { bags: 150, price: 2475, grade: 'A', place: 'Engineer', county: 'Nyandarua', days: 4 });
    await expect(page).toHaveURL(/\/listings\/\d+$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('150 bags');
    await expect(page.locator('main')).toContainText('KES 2,475');
    ctx.listingUrl = page.url();
    ctx.listingId = ctx.listingUrl.split('/').pop();
    await context.close();
});

test('wizard validation: quality must be chosen, bad values are refused', async ({ page }) => {
    await login(page, 'agnes@freshroute.test');
    await page.goto('/sell');
    await page.fill('#f-quantity_bags', '0');
    await page.click('[data-next]');
    // native validation blocks step 1 with min=1
    await expect(page.locator('.wiz-step.on')).toContainText('How many bags');
    await page.fill('#f-quantity_bags', '60');
    await page.click('[data-next]');
    await page.click('[data-next]');
    await page.click('[data-next]'); // step 3 without a grade
    await expect(page.locator('[data-choice-err]')).toBeVisible();
    // server-side: bypass the client by posting an invalid price directly
    const res = await page.request.post('/listings', { form: { quantity_bags: '10', harvest_date: ymd(2), quality_grade: 'A', price_kes: '1', county: 'Nakuru', place: 'Molo' }, headers: { 'X-CSRF-TOKEN': await page.locator('meta[name=csrf-token]').getAttribute('content') } });
    expect(res.status()).toBeLessThan(400); // redirected back with validation errors rendered, nothing stored
    await page.goto('/my/listings');
    await expect(page.locator('main')).not.toContainText('10 bags');
});

test('buyer finds the listing, compares, and sends an enquiry', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await login(page, 'njeri@freshroute.test');
    await page.goto('/market');
    await page.fill('#q', 'Engineer');
    await page.selectOption('#county', 'Nyandarua');
    await page.fill('#min_bags', '100');
    await page.click('form[role=search] button[type=submit]');
    await expect(page.locator('#result-count')).toContainText('1');
    await expect(page.locator('article')).toHaveCount(1);
    await expect(page.locator('article')).toContainText('150 bags');
    await expect(page.locator('article')).toContainText('vs official price');
    await page.getByRole('link', { name: 'View' }).click();
    await expect(page.getByRole('heading', { level: 1 })).toContainText('150 bags');
    await expect(page.locator('main')).not.toContainText('0712'); // farmer phone hidden before reply
    await page.fill('#f-quantity_bags', '151');
    await page.click('button:has-text("Send enquiry")');
    await expect(page.locator('.err')).toContainText('Only 150 bags');
    await page.fill('#f-quantity_bags', '70');
    await page.fill('#f-message', 'Can collect on Friday morning.');
    await page.click('button:has-text("Send enquiry")');
    await expect(page).toHaveURL(/\/enquiries\/\d+$/);
    ctx.enquiryUrl = page.url();
    await expect(page.locator('main')).toContainText('Sent');
    await context.close();
});

test('farmer sees the alert, replies, and agrees; buyer sees progress and the phone', async ({ browser }) => {
    const fctx = await browser.newContext();
    const f = await fctx.newPage();
    await login(f, 'agnes@freshroute.test');
    await f.goto('/alerts');
    await expect(f.locator('main')).toContainText('New enquiry from Mama Njeri Greens Ltd for 70 bags');
    await f.goto(ctx.enquiryUrl);
    await f.fill('#f-reply', 'Yes, ready Friday from 7am.');
    await f.click('button:has-text("Send reply")');
    await expect(f.locator('main')).toContainText('Replied');
    await f.click('button:has-text("Mark agreed")');
    await expect(f.locator('main')).toContainText('Agreed');

    const bctx = await browser.newContext();
    const b = await bctx.newPage();
    await login(b, 'njeri@freshroute.test');
    await b.goto(ctx.enquiryUrl);
    await expect(b.locator('.steps li.done')).toHaveCount(3);
    await expect(b.locator('main')).toContainText('Yes, ready Friday from 7am.');
    await b.goto('/alerts');
    await expect(b.locator('main')).toContainText('Enquiry update');
    await fctx.close();
    await bctx.close();
});

test('farmer requests a delivery and the transporter moves it to delivered', async ({ browser }) => {
    const fctx = await browser.newContext();
    const f = await fctx.newPage();
    await login(f, 'agnes@freshroute.test');
    await f.goto('/transport');
    const card = f.locator('article', { hasText: 'Kariuki Haulage' });
    await card.locator('input[name=bags]').fill('60');
    await card.locator('input[name=pickup_place]').fill('Engineer');
    await card.locator('select[name=listing_id]').selectOption({ index: 1 });
    await card.getByRole('button', { name: 'Request delivery' }).click();
    await expect(f).toHaveURL(/\/deliveries\/\d+$/);
    ctx.deliveryUrl = f.url();
    await expect(f.locator('.steps li.done')).toHaveCount(1);

    const tctx = await browser.newContext();
    const t = await tctx.newPage();
    await login(t, 'kariuki@freshroute.test');
    await t.goto('/alerts');
    await expect(t.locator('main')).toContainText('New request for 60 bags');
    await t.goto(ctx.deliveryUrl);
    for (const [btn, done] of [['Accept', 2], ['Mark picked up', 3], ['Mark delivered', 4]]) {
        await t.getByRole('button', { name: btn }).click();
        await expect(t.locator('.steps li.done')).toHaveCount(done);
    }
    await f.reload();
    await expect(f.locator('.steps li.done')).toHaveCount(4);
    await f.goto('/alerts');
    await expect(f.locator('main')).toContainText('Delivery update: Delivered');
    await fctx.close();
    await tctx.close();
});

test('farmer records losses with a lot that reconciles, sees the report and exports CSV', async ({ browser }) => {
    const context = await browser.newContext({ acceptDownloads: true });
    const page = await context.newPage();
    await login(page, 'agnes@freshroute.test');
    await page.goto('/losses');
    // harvest lot: 100 harvested, 70 sold
    const lot = page.locator('form[action$="/losses/lots"]');
    await lot.locator('input[name=harvested_on]').fill(ymd(-6));
    await lot.locator('input[name=harvested_bags]').fill('100');
    await lot.locator('input[name=sold_bags]').fill('70');
    await lot.getByRole('button', { name: 'Save' }).click();
    await expect(page.locator('main')).toContainText('Harvested 100 = Sold 70');
    // a loss that fits (20 spoiled, in storage) then one that exceeds the lot (11 more) is refused
    const rec = async (stage, bags, cat, reason) => {
        await page.goto('/losses');
        await page.click(`[data-choice="#f-stage"][data-value="${stage}"]`);
        await page.fill('#f-bags', String(bags));
        await page.selectOption('#f-category', cat);
        await page.selectOption('#f-reason', reason);
        await page.selectOption('#f-lot_id', { index: 1 });
        await page.locator('form[data-offline=loss] button[type=submit]').click();
    };
    await rec('storage', 20, 'spoiled', 'rot');
    await expect(page.locator('main')).toContainText('Loss recorded');
    await rec('transport', 11, 'damaged', 'delay');
    await expect(page.locator('.errsum')).toContainText('must not exceed the harvest');
    await rec('market', 10, 'rejected', 'greening');
    await expect(page.locator('main')).toContainText('Loss recorded');
    await expect(page.locator('main')).toContainText('Unaccounted 0');

    await page.goto('/losses/report');
    await expect(page.locator('main')).toContainText('farmer-reported');
    await expect(page.locator('.bars')).toContainText('Storage');
    const [dl] = await Promise.all([page.waitForEvent('download'), page.getByRole('link', { name: 'Export CSV' }).click()]);
    const path = await dl.path();
    const csv = fs.readFileSync(path, 'utf8');
    expect(csv).toContain('commodity,stage,category,reason,bags,events,data_label');
    expect(csv).toContain('"Irish potato",storage,spoiled,rot,20,1,farmer-reported');
    expect(csv).toContain('"Irish potato",market,rejected,greening,10,1,farmer-reported');
    await context.close();
});

test('user export (JSON and CSV) and account deletion request work for a signed-in user', async ({ browser }) => {
    const context = await browser.newContext({ acceptDownloads: true });
    const page = await context.newPage();
    await login(page, 'agnes@freshroute.test');
    await page.goto('/my-data');
    const [d1] = await Promise.all([page.waitForEvent('download'), page.getByRole('link', { name: /Download everything/ }).click()]);
    const j = JSON.parse(fs.readFileSync(await d1.path(), 'utf8'));
    expect(j.profile[0].email).toBe('agnes@freshroute.test');
    expect(j.listings.length).toBeGreaterThanOrEqual(2);
    expect(j.losses.length).toBe(2);
    await page.fill('#f-confirm', 'DELETE');
    await page.fill('#f-password', 'Demo-pass-2026');
    await page.getByRole('button', { name: 'Request deletion' }).click();
    await expect(page.locator('main')).toContainText('Deletion is scheduled');
    await page.getByRole('button', { name: 'Cancel deletion' }).click();
    await expect(page.locator('main')).toContainText('Deletion cancelled');
    await context.close();
});

test('cooperative admin must pass TOTP, then sees only their members', async ({ page }) => {
    await login(page, 'hannah@freshroute.test', { fresh: true });
    await expect(page).toHaveURL(/\/mfa\/challenge/);
    await page.goto('/coop');
    await expect(page).toHaveURL(/\/mfa\/challenge/); // cannot skip the challenge
    await page.fill('#f-code', '000000');
    await page.click('main button[type=submit]');
    await expect(page.locator('.errsum')).toBeVisible();
    await page.fill('#f-code', totp('JBSWY3DPEHPK3PXP'));
    await page.click('main button[type=submit]');
    await page.goto('/coop/members');
    await expect(page.locator('table')).toContainText('Wanjiru Kamau');
    await expect(page.locator('table')).not.toContainText('Samuel Kiprono'); // not in this coop
    await page.goto('/admin');
    await expect(page.getByText('Not allowed')).toBeVisible();
});

test('state-changing requests without a CSRF token are refused (419)', async ({ request }) => {
    const res = await request.post('/login', { form: { email: 'agnes@freshroute.test', password: 'Demo-pass-2026' } });
    expect(res.status()).toBe(419);
});
