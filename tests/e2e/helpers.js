import crypto from 'crypto';

export const PASSWORD = 'Demo-pass-2026';

const cache = new Map();

/**
 * Signs in through the real login form. The first sign-in per account is cached (cookies) so the suite does not trip the
 * login rate limiter (5 per minute per account); pass {fresh: true} to force a full UI sign-in.
 */
export async function login(page, email, { fresh = false, password = PASSWORD } = {}) {
    if (!fresh && cache.has(email)) {
        await page.context().addCookies(cache.get(email));
        await page.goto('/home');
        return;
    }
    await page.goto('/login');
    await page.fill('#f-email', email);
    await page.fill('#f-password', password);
    await Promise.all([page.waitForURL((u) => !u.pathname.startsWith('/login')), page.click('main button[type=submit]')]);
    if (!fresh) cache.set(email, await page.context().cookies());
}

/** Coop admin: password, then TOTP (the seeded demo secret). The post-MFA session is cached. */
export async function loginCoop(page, email = 'hannah@freshroute.test') {
    if (cache.has(email)) {
        await page.context().addCookies(cache.get(email));
        await page.goto('/coop');
        return;
    }
    await login(page, email, { fresh: true });
    await submitTotp(page, 'JBSWY3DPEHPK3PXP');
    cache.set(email, await page.context().cookies());
}

const spent = new Map(); // code -> time until which the server refuses a replay (it remembers codes for 120 s)

/**
 * Enters a TOTP code on the challenge page. A code works once per account, and several specs sign the same demo account in,
 * so this picks a step (next, current, previous: all inside the server's +-1 window) whose code this process has not already
 * used, and if every step is spent it waits for the window to move on. Deterministic, no retries on luck.
 */
export async function submitTotp(page, secret) {
    for (let round = 0; round < 3; round++) {
        for (const offset of [30000, 0, -30000]) {
            const code = totp(secret, Date.now() + offset);
            if ((spent.get(code) || 0) > Date.now()) continue;
            spent.set(code, Date.now() + 125000);
            await page.fill('#f-code', code);
            await page.click('main button[type=submit]');
            await page.waitForLoadState();
            if (!new URL(page.url()).pathname.startsWith('/mfa')) return;
        }
        await page.waitForTimeout(31000);
    }
    throw new Error('no TOTP code was accepted');
}

export async function logout(page) {
    await page.goto('/more');
    await Promise.all([page.waitForURL('**/'), page.click('form[action$="/logout"] button')]);
}

// RFC 6238 TOTP (SHA-1, 6 digits, 30 s): same algorithm as the app, implemented independently for the test.
export function totp(secretB32, time = Date.now()) {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    let bits = '';
    for (const c of secretB32.replace(/=+$/, '')) bits += alphabet.indexOf(c).toString(2).padStart(5, '0');
    const bytes = Buffer.from(bits.match(/.{8}/g).map((b) => parseInt(b, 2)));
    const counter = Buffer.alloc(8);
    counter.writeBigUInt64BE(BigInt(Math.floor(time / 1000 / 30)));
    const h = crypto.createHmac('sha1', bytes).update(counter).digest();
    const o = h[h.length - 1] & 0xf;
    const code = ((h[o] & 0x7f) << 24) | (h[o + 1] << 16) | (h[o + 2] << 8) | h[o + 3];
    return String(code % 1_000_000).padStart(6, '0');
}

export const ymd = (days) => new Date(Date.now() + days * 86400000).toISOString().slice(0, 10);

/** Drives the 5-step sell wizard. */
export async function sellWizard(page, { bags, price, grade = 'A', place = 'Ol Kalou', county = 'Nyandarua', days = 3 }) {
    await page.goto('/sell');
    await page.fill('#f-quantity_bags', String(bags));
    await page.click('[data-next]');
    await page.fill('#f-harvest_date', ymd(days));
    await page.click('[data-next]');
    await page.click(`[data-choice="#f-quality_grade"][data-value="${grade}"]`);
    await page.selectOption('#f-county', county);
    await page.fill('#f-place', place);
    await page.click('[data-next]');
    await page.fill('#f-price_kes', String(price));
    await page.click('[data-next]');
    await page.click('[data-submit]');
}
