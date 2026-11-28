import { test, expect } from '@playwright/test';
import { signIn } from './pages.js';

// Report and export pages under print media: app chrome and controls are hidden, the synthetic-data notice stays on paper,
// nothing overflows an A4-width page, and dark mode still prints dark-on-white.
const pages = [
    ['farmer', ['/losses/report', '/listings/1', '/prices']],
    ['coop', ['/coop', '/coop/members', '/coop/reports']],
    ['admin', ['/admin/audit', '/admin/users']],
    ['buyer', ['/market/compare?ids[]=1&ids[]=2']],
];

for (const [role, paths] of pages) {
    test(`print styles: ${role}`, async ({ browser }) => {
        const ctx = await browser.newContext({ viewport: { width: 794, height: 1123 }, colorScheme: 'dark' });
        const page = await ctx.newPage();
        await signIn(page, role);
        for (const p of paths) {
            await page.goto(p);
            await page.emulateMedia({ media: 'print' });
            await expect(page.locator('.top'), p).toBeHidden();
            await expect(page.locator('.tabbar'), p).toBeHidden();
            for (const f of await page.locator('form[method=get]').all()) await expect(f, p).toBeHidden();
            await expect(page.locator('main > .banner.syn').first(), `${p} keeps the synthetic notice`).toBeVisible();
            await expect(page.locator('.print-foot'), p).toBeVisible();
            expect(await page.evaluate(() => getComputedStyle(document.body).backgroundColor), `${p} white background`).toBe('rgb(255, 255, 255)');
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth), `${p} fits the page width`).toBe(true);
        }
        await ctx.close();
    });
}
