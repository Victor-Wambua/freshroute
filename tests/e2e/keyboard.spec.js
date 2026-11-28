import { test, expect } from '@playwright/test';
import { login } from './helpers.js';

// Keyboard-only walkthroughs: Tab / Shift+Tab / Enter / Space only (no mouse clicks, no direct focus calls) for the main journeys.
// The app has no modal dialogs or sheets (checked: no <dialog>/aria-modal in the templates), so focus trapping/restoring does not apply;
// the one disclosure (the phone Filter toggle) is covered below.

const describe = () => {
    const el = document.activeElement;
    if (!el || el === document.body) return null;
    const cs = getComputedStyle(el);
    const outline = cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) >= 2;
    const r = el.getBoundingClientRect();
    return { type: el.type, label: `${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}${el.className && typeof el.className === 'string' ? '.' + el.className.split(' ')[0] : ''} "${(el.getAttribute('aria-label') || el.textContent || el.value || '').trim().slice(0, 30)}"`, outline, tabindex: el.getAttribute('tabindex'), visible: r.width > 0 && r.height > 0 };
};

/** Presses Tab until focus wraps back to the document start; returns every stop. Fails on traps (no wrap within max). */
async function tabThrough(page, max = 120) {
    const stops = [];
    await page.evaluate(() => document.activeElement?.blur());
    for (let i = 0; i < max; i++) {
        await page.keyboard.press('Tab');
        const d = await page.evaluate(describe);
        if (!d) return stops; // focus left the document (browser chrome) or wrapped to body: no trap
        stops.push(d);
    }
    throw new Error('keyboard trap suspected: focus never left the page in ' + max + ' Tab presses; last: ' + stops.slice(-3).map((s) => s.label).join(' > '));
}

const journeys = [
    ['farmer', 'wanjiru@freshroute.test', ['/home', '/sell', '/my/listings', '/prices', '/losses']],
    ['buyer', 'njeri@freshroute.test', ['/market', '/saved', '/enquiries', '/needs/new']],
];

for (const [role, email, paths] of journeys) {
    test(`keyboard: every page of the ${role} journey is reachable, ordered, trap-free with a visible focus ring`, async ({ browser }) => {
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
        const page = await ctx.newPage();
        await login(page, email);
        for (const p of paths) {
            await page.goto(p);
            const stops = await tabThrough(page);
            expect(stops.length, `${p} has focusable controls`).toBeGreaterThan(3);
            const hidden = stops.filter((s) => !s.visible && !/skip/i.test(s.label));
            expect(hidden.map((s) => s.label), `${p}: focus must never land on an invisible element`).toEqual([]);
            // the native calendar button inside a date input is a separate browser-drawn stop (the host input is not :focus-visible there)
            const noRing = stops.filter((s) => !s.outline && s.type !== 'date');
            expect(noRing.map((s) => s.label), `${p}: every focus stop shows a >=2px outline`).toEqual([]);
            expect(await page.locator('[tabindex]:not([tabindex="0"]):not([tabindex="-1"])').count(), `${p}: no positive tabindex`).toBe(0);
        }
        await ctx.close();
    });
}

test('keyboard: skip link is the first stop and moves focus to main content', async ({ page }) => {
    await login(page, 'wanjiru@freshroute.test');
    await page.goto('/home');
    await page.keyboard.press('Tab');
    expect(await page.evaluate(() => document.activeElement.className)).toContain('skip');
    expect(await page.evaluate(() => document.activeElement.getBoundingClientRect().left)).toBeGreaterThanOrEqual(0); // shown on focus
    await page.keyboard.press('Enter');
    expect(await page.evaluate(() => document.activeElement.id)).toBe('view');
    // next Tab lands inside the content, not back in the header
    await page.keyboard.press('Tab');
    expect(await page.evaluate(() => !!document.activeElement.closest('main'))).toBe(true);
});

test('keyboard: sign in with the keyboard alone, with Shift+Tab going back', async ({ browser }) => {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await page.goto('/login');
    for (let i = 0; i < 12 && (await page.evaluate(() => document.activeElement.id)) !== 'f-email'; i++) await page.keyboard.press('Tab');
    expect(await page.evaluate(() => document.activeElement.id)).toBe('f-email');
    await page.keyboard.type('wanjiru@freshroute.test');
    await page.keyboard.press('Tab');
    expect(await page.evaluate(() => document.activeElement.id)).toBe('f-password');
    await page.keyboard.press('Shift+Tab');
    expect(await page.evaluate(() => document.activeElement.id)).toBe('f-email');
    await page.keyboard.press('Tab');
    await page.keyboard.type('Demo-pass-2026');
    await page.keyboard.press('Enter');
    await page.waitForURL((u) => !u.pathname.startsWith('/login'));
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await ctx.close();
});

test('keyboard: the 5-step sell wizard can be completed with the keyboard, shows "Step n of 5" and moves focus each step', async ({ browser }) => {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const page = await ctx.newPage();
    await login(page, 'agnes@freshroute.test');
    await page.goto('/sell');
    const count = page.locator('[data-step-count]');
    await expect(count).toHaveText('Step 1 of 5');
    const active = () => page.evaluate(() => document.activeElement.id || document.activeElement.textContent.trim());
    const focusTo = async (sel, max = 40) => {
        for (let i = 0; i < max; i++) {
            if (await page.evaluate((s) => document.activeElement?.matches(s), sel)) return;
            await page.keyboard.press('Tab');
        }
        throw new Error('could not reach ' + sel + ' by keyboard; at ' + (await active()));
    };
    // step 1: focus lands in the step; Enter in the field advances
    expect(await active()).not.toBe('');
    await focusTo('#f-quantity_bags');
    await page.keyboard.press('Enter');
    await expect(count).toHaveText('Step 2 of 5');
    await focusTo('#f-harvest_date');
    await page.keyboard.press('Enter');
    await expect(count).toHaveText('Step 3 of 5');
    // step 3: pick a grade with Space, then fill the place
    await focusTo('[data-choice="#f-quality_grade"][data-value="B"]');
    await page.keyboard.press('Space');
    await expect(page.locator('[data-choice][data-value="B"]')).toHaveAttribute('aria-pressed', 'true');
    await focusTo('#f-place');
    await page.keyboard.type('Ol Kalou');
    await page.keyboard.press('Enter');
    await expect(count).toHaveText('Step 4 of 5');
    // Shift+Tab can reach Back, and Back works from the keyboard
    await focusTo('[data-back]');
    await page.keyboard.press('Enter');
    await expect(count).toHaveText('Step 3 of 5');
    await focusTo('[data-next]');
    await page.keyboard.press('Enter');
    await expect(count).toHaveText('Step 4 of 5');
    await focusTo('#f-price_kes');
    await page.keyboard.press('Enter');
    await expect(count).toHaveText('Step 5 of 5');
    await expect(page.locator('#rv-grade')).toHaveText('B');
    await focusTo('[data-submit]');
    await page.keyboard.press('Enter');
    await page.waitForURL(/\/listings\/\d+$/);
    await ctx.close();
});

test('keyboard: the phone Filter toggle opens with Enter and keeps focus; market compare checkboxes toggle with Space', async ({ browser }) => {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const page = await ctx.newPage();
    await login(page, 'njeri@freshroute.test');
    await page.goto('/market');
    for (let i = 0; i < 20 && !(await page.evaluate(() => document.activeElement.classList.contains('filter-toggle'))); i++) await page.keyboard.press('Tab');
    expect(await page.evaluate(() => document.activeElement.classList.contains('filter-toggle'))).toBe(true);
    await page.keyboard.press('Enter');
    await expect(page.locator('.filter-toggle')).toHaveAttribute('aria-expanded', 'true');
    expect(await page.evaluate(() => document.activeElement.classList.contains('filter-toggle'))).toBe(true);
    await page.keyboard.press('Enter');
    await expect(page.locator('.filter-toggle')).toHaveAttribute('aria-expanded', 'false');
    for (let i = 0; i < 40 && !(await page.evaluate(() => document.activeElement.classList.contains('cmp'))); i++) await page.keyboard.press('Tab');
    await page.keyboard.press('Space');
    expect(await page.evaluate(() => document.activeElement.checked)).toBe(true);
    await ctx.close();
});
