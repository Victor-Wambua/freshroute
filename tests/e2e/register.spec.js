import { test, expect } from '@playwright/test';
import { logout } from './helpers.js';

const base = { password: 'Fresh-route-77', county: 'Nakuru' };

async function fill(page, vals) {
    for (const [k, v] of Object.entries(vals)) {
        const el = page.locator(`[name="${k}"]`);
        if ((await el.evaluate((n) => n.tagName)) === 'SELECT') await el.selectOption(v);
        else await el.fill(v);
    }
}

test('a new farmer registers with a farm and crop profile, and lands on Home', async ({ page }) => {
    await page.goto('/register');
    await page.getByRole('link', { name: /Farmer/ }).click();
    await fill(page, { name: 'Mwangi Test', email: 'mwangi.e2e@example.test', phone: '0711222333', password: base.password, password_confirmation: base.password, county: 'Nyandarua', place: 'Ol Kalou', farm_name: 'Mwangi farm', capacity_bags: '300', variety: 'Shangi' });
    await page.check('#consent');
    await page.click('main button[type=submit]');
    await expect(page).toHaveURL(/\/home$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Mwangi Test');
    await page.goto('/farms');
    await expect(page.locator('main')).toContainText('Mwangi farm');
    await expect(page.locator('main')).toContainText('Capacity: 300');
});

test('registration rejects weak passwords and missing consent with accessible errors', async ({ page }) => {
    await page.goto('/register/buyer');
    await fill(page, { name: 'B', business_name: 'Biz', email: 'weak.e2e@example.test', phone: '0711222444', password: 'short', password_confirmation: 'short', place: 'Nakuru town' });
    await page.click('main button[type=submit]');
    await expect(page.locator('#error-summary')).toBeVisible();
    await expect(page.locator('#e-password')).toBeVisible();
    await expect(page.locator('#f-password')).toHaveAttribute('aria-invalid', 'true');
});

test('storage provider and transporter onboarding flows end in a listed profile', async ({ page, browser }) => {
    await page.goto('/register/storage_provider');
    await fill(page, { name: 'Store Owner', business_name: 'Bahati Dry Store', email: 'store.e2e@example.test', phone: '0722333444', password: base.password, password_confirmation: base.password, place: 'Bahati' });
    await page.check('#consent');
    await page.click('main button[type=submit]');
    await expect(page).toHaveURL(/\/storage\/new$/);
    await fill(page, { name: 'Bahati Dry Store', capacity_bags: '900', free_bags: '400', price_kes: '15', place: 'Bahati' });
    await page.click('main button[type=submit]');
    await expect(page.locator('main')).toContainText('Bahati Dry Store');
    await page.goto('/storage');
    await expect(page.locator('main')).toContainText('Bahati Dry Store');
    await expect(page.locator('main')).toContainText('400');

    const c2 = await browser.newContext();
    const p2 = await c2.newPage();
    await p2.goto('/register/transporter');
    await fill(p2, { name: 'Driver One', business_name: 'Quick Pickup', email: 'tr.e2e@example.test', phone: '0733444555', password: base.password, password_confirmation: base.password, place: 'Nakuru town' });
    await p2.check('#consent');
    await p2.click('main button[type=submit]');
    await expect(p2).toHaveURL(/\/transport\/profile$/);
    await fill(p2, { vehicle_type: 'pickup', capacity_bags: '25', routes: 'Njoro - Nakuru', price_kes: '4500' });
    await p2.click('main button[type=submit]');
    await p2.goto('/transport');
    await expect(p2.locator('main')).toContainText('Quick Pickup');
    await c2.close();
});

test('a new coop admin is forced into TOTP enrolment and gets recovery codes', async ({ page }) => {
    await page.goto('/register/coop_admin');
    await fill(page, { name: 'Coop Boss', email: 'coop.e2e@example.test', phone: '0744555666', password: base.password, password_confirmation: base.password, place: 'Molo', coop_name: 'Molo E2E Growers' });
    await page.check('#consent');
    await page.click('main button[type=submit]');
    await page.goto('/coop');
    await expect(page).toHaveURL(/\/mfa\/setup$/);
    const secret = (await page.locator('#mfa-secret').textContent()).trim();
    const { totp } = await import('./helpers.js');
    await page.fill('#f-code', totp(secret));
    await page.click('main button[type=submit]');
    await expect(page.locator('main')).toContainText('Recovery codes');
    expect(await page.locator('ul.mono li').count()).toBe(8);
    await page.goto('/coop');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Molo E2E Growers');
    await expect(page.locator('main')).toContainText('Not yet verified');
});
