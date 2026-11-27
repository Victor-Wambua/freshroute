import { test } from '@playwright/test';
import fs from 'fs';
import { login, loginCoop } from './helpers.js';

// Captures the real app at the same reference sizes as the approved design screenshots (390x844 @2x, 1440x900).
// Output: docs/screenshots/{viewport}-{theme}-{screen}.png (compared in docs/DESIGN-FIDELITY.md).
const out = 'docs/screenshots';
fs.mkdirSync(out, { recursive: true });

const shots = [
    ['farmer', 'wanjiru@freshroute.test', [['farmer-home', '/home'], ['farmer-sell', '/sell'], ['farmer-calendar', '/calendar'], ['farmer-losses', '/losses'], ['farmer-enquiries', '/enquiries'], ['farmer-prices', '/prices']]],
    ['buyer', 'njeri@freshroute.test', [['buyer-market', '/market'], ['buyer-saved', '/saved'], ['buyer-need', '/needs/new']]],
    ['provider', 'njoro-cold@freshroute.test', [['provider-storage', '/storage/mine']]],
    ['provider', 'kariuki@freshroute.test', [['provider-requests', '/deliveries']]],
];

for (const [vp, size] of [['mobile', { width: 390, height: 844 }], ['desktop', { width: 1440, height: 900 }]]) {
    for (const theme of ['light', 'dark']) {
        test(`screenshots ${vp} ${theme}`, async ({ browser }) => {
            test.setTimeout(120000);
            for (const [, email, pages] of shots) {
                const context = await browser.newContext({ viewport: size, deviceScaleFactor: vp === 'mobile' ? 2 : 1, colorScheme: theme, isMobile: vp === 'mobile', hasTouch: vp === 'mobile' });
                const page = await context.newPage();
                await login(page, email);
                for (const [name, url] of pages) {
                    await page.goto(url);
                    await page.evaluate((t) => document.documentElement.setAttribute('data-theme', t), theme);
                    await page.waitForTimeout(150);
                    await page.screenshot({ path: `${out}/${vp}-${theme}-${name}.png`, fullPage: false });
                }
                await context.close();
            }
            // coop and login
            const context = await browser.newContext({ viewport: size, deviceScaleFactor: vp === 'mobile' ? 2 : 1, colorScheme: theme });
            const page = await context.newPage();
            await page.goto('/login');
            await page.evaluate((t) => document.documentElement.setAttribute('data-theme', t), theme);
            await page.screenshot({ path: `${out}/${vp}-${theme}-login.png` });
            await login(page, 'hannah@freshroute.test');
            await page.fill('#f-code', totp('JBSWY3DPEHPK3PXP'));
            await page.click('main button[type=submit]');
            for (const [name, url] of [['coop-dashboard', '/coop'], ['coop-members', '/coop/members'], ['coop-reports', '/coop/reports']]) {
                await page.goto(url);
                await page.evaluate((t) => document.documentElement.setAttribute('data-theme', t), theme);
                await page.screenshot({ path: `${out}/${vp}-${theme}-${name}.png` });
            }
            await context.close();
        });
    }
}
