import { login, loginCoop } from './helpers.js';

// Every reachable screen, per role. `find` entries discover a detail URL by following the first matching link on an index page.
export const roles = {
    public: { email: null, paths: ['/', '/login', '/register', '/register/farmer', '/register/buyer', '/forgot-password', '/prices', '/offline', '/no-such-page'] },
    farmer: {
        email: 'wanjiru@freshroute.test',
        paths: ['/home', '/sell', '/my/listings', '/farms', '/calendar', '/losses', '/losses/report', '/enquiries', '/deliveries', '/storage', '/transport', '/prices', '/alerts', '/my-data', '/more'],
        find: [['/my/listings', /^\/listings\/\d+$/], ['/my/listings', /^\/listings\/\d+\/edit$/], ['/enquiries', /^\/enquiries\/\d+$/]],
    },
    buyer: {
        email: 'njeri@freshroute.test',
        paths: ['/market', '/market?county=Nyandarua&sort=price', '/saved', '/needs', '/needs/new', '/enquiries', '/deliveries', '/storage', '/transport', '/alerts', '/my-data', '/more'],
        find: [['/market', /^\/market\/\d+$/], ['/needs', /^\/needs\/\d+$/], ['/enquiries', /^\/enquiries\/\d+$/]],
        compare: true,
    },
    storage: {
        email: 'njoro-cold@freshroute.test',
        paths: ['/storage/mine', '/storage/new', '/storage-requests', '/storage', '/alerts', '/more'],
        find: [['/storage/mine', /^\/storage\/\d+\/edit$/]],
    },
    transporter: {
        email: 'kariuki@freshroute.test',
        paths: ['/transport/profile', '/deliveries', '/transport', '/alerts', '/more'],
        find: [['/deliveries', /^\/deliveries\/\d+$/]],
    },
    coop: { email: 'hannah@freshroute.test', mfa: true, paths: ['/coop', '/coop/members', '/coop/reports', '/mfa/setup', '/alerts', '/my-data', '/more'] },
    admin: { email: 'admin@freshroute.test', mfa: true, paths: ['/admin', '/admin/users', '/admin/verify', '/admin/audit', '/admin/deletions', '/admin/prices', '/more'] },
};

export async function signIn(page, role) {
    const r = roles[role];
    if (!r.email) return;
    if (r.mfa) await loginCoop(page, r.email);
    else await login(page, r.email);
}

/** Resolves the full list of URLs for a role (static paths plus discovered detail pages). Page must be signed in. */
export async function resolvePaths(page, role) {
    const r = roles[role];
    const out = [...r.paths];
    for (const [index, re] of r.find || []) {
        await page.goto(index);
        const hrefs = await page.$$eval('a[href]', (as) => as.map((a) => new URL(a.href, location.origin).pathname));
        const hit = hrefs.find((h) => re.test(h));
        if (hit) out.push(hit);
    }
    if (r.compare) {
        await page.goto('/market');
        const ids = await page.$$eval('input.cmp', (xs) => xs.slice(0, 3).map((x) => x.value));
        if (ids.length >= 2) out.push('/market/compare?' + ids.map((i) => `ids[]=${i}`).join('&'));
    }
    return out;
}
