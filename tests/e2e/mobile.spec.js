import { test, expect } from '@playwright/test';
import { login, loginCoop } from './helpers.js';

const accounts = {
    farmer: ['wanjiru@freshroute.test', ['/home', '/sell', '/my/listings', '/calendar', '/losses', '/losses/report', '/enquiries', '/prices', '/prices?tab=user', '/storage', '/transport', '/alerts', '/my-data', '/more', '/farms']],
    buyer: ['njeri@freshroute.test', ['/market', '/saved', '/needs', '/needs/new', '/enquiries', '/prices', '/alerts', '/more']],
    storage: ['njoro-cold@freshroute.test', ['/storage/mine', '/storage-requests', '/storage/new', '/more']],
    transporter: ['kariuki@freshroute.test', ['/transport/profile', '/deliveries', '/more']],
};

test.use({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });

for (const [role, [email, paths]] of Object.entries(accounts)) {
    test(`mobile 390px: ${role} pages have no horizontal overflow, 44px targets, labelled inputs`, async ({ page }) => {
        await login(page, email);
        for (const p of paths) {
            await page.goto(p);
            const r = await page.evaluate(() => {
                const vis = (el) => { const r = el.getBoundingClientRect(); const cs = getComputedStyle(el); return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none'; };
                const small = [...document.querySelectorAll('a[href],button,select,input:not([type=hidden]):not([type=checkbox]):not([type=radio]),textarea')].filter(vis).filter((el) => {
                    const r = el.getBoundingClientRect();
                    return !el.classList.contains('skip') && (r.height < 43.5 || r.width < 43.5) && !el.closest('.sr') && !(el.tagName === 'A' && el.closest('p,li,.hint,.small,td,dd,figcaption,.muted'));
                }).map((el) => `${el.tagName}.${el.className}:${Math.round(el.getBoundingClientRect().width)}x${Math.round(el.getBoundingClientRect().height)} ${el.textContent.trim().slice(0, 20)}`);
                const unlabeled = [...document.querySelectorAll('input:not([type=hidden]),select,textarea')].filter(vis).filter((el) => !(el.labels && el.labels.length) && !el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby')).map((el) => el.name);
                return { overflow: document.documentElement.scrollWidth - window.innerWidth, small, unlabeled, tabbar: getComputedStyle(document.querySelector('.tabbar')).display, side: getComputedStyle(document.querySelector('.side')).display, lang: document.documentElement.lang, h1: document.querySelectorAll('h1').length };
            });
            expect.soft(r.overflow, `${p} horizontal overflow`).toBeLessThanOrEqual(0);
            expect.soft(r.small, `${p} small targets`).toEqual([]);
            expect.soft(r.unlabeled, `${p} unlabeled inputs`).toEqual([]);
            expect.soft(r.tabbar, `${p} tab bar`).not.toBe('none');
            expect.soft(r.side, `${p} side nav hidden`).toBe('none');
            expect.soft(r.h1, `${p} h1 count`).toBe(1);
        }
    });
}

test('mobile: bottom tab bar navigates, raised Sell action is present for farmers', async ({ page }) => {
    await login(page, 'wanjiru@freshroute.test');
    await expect(page.locator('.tabbar .tab')).toHaveCount(5);
    await expect(page.locator('.tabbar .tab.cta')).toContainText('Sell');
    await page.locator('.tabbar').getByText('Losses').click();
    await expect(page).toHaveURL(/\/losses$/);
    await expect(page.locator('.tabbar .tab[aria-current=page]')).toContainText('Losses');
});

test('mobile: language toggle switches to Swahili and persists', async ({ page }) => {
    await login(page, 'wanjiru@freshroute.test');
    await page.locator('a.langbtn').click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'sw');
    await expect(page.locator('.tabbar')).toContainText('Mavuno');
    await page.goto('/sell');
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Uza viazi');
    await page.locator('a.langbtn').click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
});

test('mobile: dark theme toggles and persists across reload', async ({ page }) => {
    await login(page, 'wanjiru@freshroute.test');
    await page.click('#theme-btn');
    const dark = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
    await page.reload();
    expect(await page.evaluate(() => document.documentElement.getAttribute('data-theme'))).toBe(dark);
    const bg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
    expect(['rgb(26, 19, 13)', 'rgb(251, 246, 234)']).toContain(bg);
});

test('desktop: side navigation replaces the tab bar at >= 900px', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await context.newPage();
    await login(page, 'wanjiru@freshroute.test');
    expect(await page.evaluate(() => getComputedStyle(document.querySelector('.tabbar')).display)).toBe('none');
    await expect(page.locator('.side .navlink')).toHaveCount(13);
    expect(await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(0);
    await context.close();
});

test('coop admin pages fit a phone too (after TOTP)', async ({ page }) => {
    await loginCoop(page);
    for (const p of ['/coop', '/coop/members', '/coop/reports']) {
        await page.goto(p);
        expect.soft(await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth), p).toBeLessThanOrEqual(0);
    }
});
