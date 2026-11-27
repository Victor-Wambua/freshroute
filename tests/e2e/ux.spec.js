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
