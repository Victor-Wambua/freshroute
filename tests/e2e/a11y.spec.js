import { test, expect } from '@playwright/test';
import fs from 'fs';
import { createRequire } from 'module';
import { roles, signIn, resolvePaths } from './pages.js';

// Automated accessibility pass (axe-core, WCAG 2.x A/AA + best-practice tags) over every reachable screen for every role,
// at 375 and 1280 px wide, light and dark. Fails on any serious or critical violation. Dev-only dependency; runs against localhost.
const axeSource = fs.readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
const views = [
    ['375-light', { width: 375, height: 812 }, 'light'],
    ['375-dark', { width: 375, height: 812 }, 'dark'],
    ['1280-light', { width: 1280, height: 800 }, 'light'],
    ['1280-dark', { width: 1280, height: 800 }, 'dark'],
];
const tags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];
const summary = [];
const incomplete = [];

for (const role of Object.keys(roles)) {
    test(`axe: ${role}`, async ({ browser }) => {
        test.setTimeout(240_000);
        const probe = await browser.newContext();
        const pp = await probe.newPage();
        await signIn(pp, role);
        const paths = await resolvePaths(pp, role);
        const cookies = await probe.cookies();
        await probe.close();

        const bad = [];
        for (const [name, viewport, scheme] of views) {
            const context = await browser.newContext({ viewport, colorScheme: scheme, bypassCSP: true, isMobile: viewport.width < 600, hasTouch: viewport.width < 600 });
            await context.addCookies(cookies);
            const page = await context.newPage();
            for (const path of paths) {
                await page.goto(path);
                await page.evaluate((t) => document.documentElement.setAttribute('data-theme', t), scheme);
                await page.addScriptTag({ content: axeSource });
                const res = await page.evaluate((runTags) => window.axe.run(document, { runOnly: { type: 'tag', values: runTags } }), tags);
                for (const v of res.incomplete.filter((i) => i.id === 'color-contrast')) incomplete.push({ role, view: name, path, nodes: v.nodes.map((n) => n.target.join(' ') + ' :: ' + (n.any[0]?.message || '')).slice(0, 5) });
                for (const v of res.violations) {
                    const nodes = v.nodes.map((n) => n.target.join(' ')).slice(0, 3);
                    summary.push({ role, view: name, path, id: v.id, impact: v.impact, nodes: v.nodes.length });
                    if (v.impact === 'serious' || v.impact === 'critical') bad.push(`${name} ${path} ${v.id} (${v.impact}) x${v.nodes.length}: ${nodes.join(' | ')}`);
                }
            }
            await context.close();
        }
        fs.mkdirSync('test-results', { recursive: true });
        fs.writeFileSync(`test-results/axe-${role}.json`, JSON.stringify({ paths, incomplete: incomplete.filter((s) => s.role === role), summary: summary.filter((s) => s.role === role) }, null, 1));
        expect(bad, bad.join('\n')).toEqual([]);
    });
}
