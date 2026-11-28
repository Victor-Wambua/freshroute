import { test, expect } from '@playwright/test';
import zlib from 'zlib';
import { login } from './helpers.js';

// Generates a real, valid PNG of random noise (incompressible, so the file really is > 4 MB) without any fixture file or dependency.
function noisePng(w, h) {
    const crcTable = Array.from({ length: 256 }, (_, n) => { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1; return c >>> 0; });
    const crc = (buf) => { let c = 0xffffffff; for (const b of buf) c = crcTable[(c ^ b) & 0xff] ^ (c >>> 8); return (c ^ 0xffffffff) >>> 0; };
    const chunk = (type, data) => { const len = Buffer.alloc(4); len.writeUInt32BE(data.length); const td = Buffer.concat([Buffer.from(type), data]); const c = Buffer.alloc(4); c.writeUInt32BE(crc(td)); return Buffer.concat([len, td, c]); };
    const ihdr = Buffer.alloc(13); ihdr.writeUInt32BE(w, 0); ihdr.writeUInt32BE(h, 4); ihdr[8] = 8; ihdr[9] = 2; // 8-bit RGB
    const raw = Buffer.alloc((w * 3 + 1) * h);
    let seed = 12345;
    for (let y = 0; y < h; y++) { const o = y * (w * 3 + 1); raw[o] = 0; for (let i = 1; i <= w * 3; i++) { seed = (seed * 1664525 + 1013904223) >>> 0; raw[o + i] = seed >>> 24; } }
    return Buffer.concat([Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]), chunk('IHDR', ihdr), chunk('IDAT', zlib.deflateSync(raw, { level: 1 })), chunk('IEND', Buffer.alloc(0))]);
}

test('a real >4 MB phone-sized photo is shrunk in the browser and then accepted by the server', async ({ browser }) => {
    test.setTimeout(120_000);
    const original = noisePng(2000, 1500);
    expect(original.length, 'fixture must really exceed the 4 MB server limit').toBeGreaterThan(4 * 1024 * 1024);
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const page = await ctx.newPage();
    await login(page, 'agnes@freshroute.test');
    await page.goto('/sell');
    await page.fill('#f-quantity_bags', '40');
    await page.click('[data-next]');
    await page.click('[data-next]');
    await page.click('[data-choice="#f-quality_grade"][data-value="A"]');
    await page.selectOption('#f-county', 'Nyandarua');
    await page.fill('#f-place', 'Ol Kalou');
    await page.click('[data-next]');
    await page.fill('#f-price_kes', '2300');
    await page.setInputFiles('#f-photo', { name: 'big-photo.png', mimeType: 'image/png', buffer: original });
    await expect(page.locator('[data-shrunk]')).toContainText(/Photo ready/);
    const sent = await page.evaluate(() => { const f = document.querySelector('#f-photo').files[0]; return { size: f.size, type: f.type, name: f.name }; });
    expect(sent.type).toBe('image/jpeg');
    expect(sent.size).toBeLessThan(4 * 1024 * 1024);
    expect(sent.size).toBeLessThan(original.length / 2);
    await page.click('[data-next]');
    await page.click('[data-submit]');
    await page.waitForURL(/\/listings\/\d+$/);
    await expect(page.locator('main img[src*="/photos/"]').first()).toBeVisible();
    await ctx.close();
});
