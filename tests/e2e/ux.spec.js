import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

test.use({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });

test('mobile market: secondary filters are collapsed behind a Filter button and can be opened', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const page = await context.newPage();
    await login(page, 'njeri@freshroute.test');
    await page.goto('/market');
    await expect(page.locator('#county')).toBeHidden();
    const toggle = page.locator('.filter-toggle');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#county')).toBeVisible();
    await context.close();
});

test('mobile prices: the source column is dropped on narrow screens but the licence status stays', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const page = await context.newPage();
    await login(page, 'wanjiru@freshroute.test');
    await page.goto('/prices');
    await expect(page.locator('table th.hide-sm')).toBeHidden();
    await expect(page.locator('table th', { hasText: 'Licence' })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
    await context.close();
});

test('admin: suspending asks for confirmation; wide tables show a scroll hint on phones; compare rows have 44px hit areas', async ({ browser }) => {
    const { loginCoop } = await import('./helpers.js');
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const page = await ctx.newPage();
    await loginCoop(page, 'admin@freshroute.test');
    await page.goto('/admin/users');
    expect(await page.locator('.tablewrap').evaluate((el) => getComputedStyle(el, '::before').content)).toContain('Swipe sideways');
    let asked = '';
    page.once('dialog', (d) => { asked = d.message(); d.dismiss(); });
    await page.locator('form[data-confirm] button').first().click();
    expect(asked).toContain('Suspend this account');
    await expect(page.locator('main')).not.toContainText('Suspended');
    await ctx.close();

    const c2 = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const p2 = await c2.newPage();
    await login(p2, 'njeri@freshroute.test');
    await p2.goto('/market');
    const box = await p2.locator('label.cmp-row').first().boundingBox();
    expect(box.height).toBeGreaterThanOrEqual(44);
    expect(box.width).toBeGreaterThanOrEqual(44);
    await p2.locator('label.cmp-row').first().click({ position: { x: box.width - 4, y: 4 } });
    await expect(p2.locator('input.cmp').first()).toBeChecked();
    await c2.close();
});
