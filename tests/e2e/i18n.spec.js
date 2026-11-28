import { test, expect } from '@playwright/test';
import fs from 'fs';
import { roles, signIn, resolvePaths } from './pages.js';

// Swahili pass: every screen of every role is rendered in English and in Swahili. Checks (1) no raw translation keys, (2) no horizontal
// overflow at 375 px, (3) visible text fragments that are identical in both languages are listed (likely untranslated; some are names/data).
// This does NOT verify the Swahili is correct: the strings are an unreviewed draft and need a native speaker.
const texts = () => [...document.querySelectorAll('main *, header *, nav *')].filter((e) => e.children.length === 0 && e.offsetParent !== null).map((e) => (e.textContent || '').trim()).filter((t) => /[A-Za-z]{4,}/.test(t));
const found = {};

for (const role of Object.keys(roles)) {
    test(`swahili: ${role}`, async ({ browser }) => {
        test.setTimeout(180_000);
        const probe = await browser.newContext();
        const pp = await probe.newPage();
        await signIn(pp, role);
        const paths = (await resolvePaths(pp, role)).filter((p) => !p.startsWith('/no-such'));
        const cookies = await probe.cookies();
        await probe.close();
        const grab = async (loc) => {
            const ctx = await browser.newContext({ viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true });
            await ctx.addCookies(cookies);
            const page = await ctx.newPage();
            await page.goto(`/locale/${loc}`);
            const out = {};
            for (const p of paths) {
                await page.goto(p);
                out[p] = { t: await page.evaluate(texts), overflow: await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth), lang: await page.evaluate(() => document.documentElement.lang) };
            }
            await page.goto('/locale/en'); // the locale lives in the (shared, cached) session: always leave it as English for the other specs
            await ctx.close();
            return out;
        };
        const en = await grab('en');
        const sw = await grab('sw');
        const problems = [];
        for (const p of paths) {
            expect(sw[p].lang, p).toBe('sw');
            if (sw[p].overflow) problems.push(`overflow at 375px: ${p}`);
            for (const t of sw[p].t) if (/\b(ui|notice|pwa)\.[a-z_]{3,}\b/.test(t)) problems.push(`raw key on ${p}: ${t}`);
            const same = sw[p].t.filter((t) => en[p].t.includes(t));
            (found[role] ||= {})[p] = [...new Set(same)];
        }
        fs.mkdirSync('test-results', { recursive: true });
        fs.writeFileSync(`test-results/i18n-${role}.json`, JSON.stringify(found[role], null, 1));
        expect(problems).toEqual([]);
    });
}
