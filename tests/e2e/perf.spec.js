import { test, expect } from '@playwright/test';
import fs from 'fs';
import { roles, signIn, resolvePaths } from './pages.js';

// Page weight, request count and layout shift measured in Chromium at 375 px (cold cache, service worker blocked so every byte is counted).
// Budgets are deliberately generous regression guards, not targets. Numbers are written to test-results/perf.json.
const budget = { kb: 400, requests: 25, cls: 0.1 };
const roleList = ['farmer', 'buyer', 'coop', 'admin'];
const rows = [];

for (const role of roleList) {
    test(`perf: ${role}`, async ({ browser }) => {
        test.setTimeout(120_000);
        const probe = await browser.newContext();
        const pp = await probe.newPage();
        await signIn(pp, role);
        const paths = (await resolvePaths(pp, role)).filter((p) => !p.startsWith('/mfa'));
        const cookies = await probe.cookies();
        await probe.close();
        const ctx = await browser.newContext({ viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true, serviceWorkers: 'block' });
        await ctx.addCookies(cookies);
        for (const p of paths) {
            const page = await ctx.newPage();
            let bytes = 0, requests = 0;
            page.on('response', async (r) => { requests++; const len = Number((await r.allHeaders())['content-length'] || 0); bytes += len || (await r.body().catch(() => Buffer.alloc(0))).length; });
            await page.addInitScript(() => { window.__cls = 0; new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; }).observe({ type: 'layout-shift', buffered: true }); });
            await page.goto(p, { waitUntil: 'networkidle' });
            await page.waitForTimeout(300);
            const cls = await page.evaluate(() => window.__cls);
            rows.push({ role, path: p, kb: Math.round(bytes / 1024), requests, cls: Number(cls.toFixed(4)) });
            await page.close();
        }
        await ctx.close();
        fs.mkdirSync('test-results', { recursive: true });
        fs.writeFileSync(`test-results/perf-${role}.json`, JSON.stringify(rows.filter((r) => r.role === role), null, 1));
        const over = rows.filter((r) => r.role === role && (r.kb > budget.kb || r.requests > budget.requests || r.cls > budget.cls));
        expect(over, 'pages over budget ' + JSON.stringify(over)).toEqual([]);
    });
}
