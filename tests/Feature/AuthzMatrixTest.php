<?php

use App\Enums\Role;

/** Role x endpoint-family matrix. Every cell is asserted: allowed roles get through, all others get 403. */
const ALL = ['farmer', 'coop_admin', 'buyer', 'storage_provider', 'transporter', 'platform_admin'];

dataset('get_families', [
    'farmer area: sell' => ['/sell', ['farmer']],
    'farmer area: my listings' => ['/my/listings', ['farmer']],
    'farmer area: losses' => ['/losses', ['farmer']],
    'farmer area: loss report' => ['/losses/report', ['farmer']],
    'farmer area: calendar' => ['/calendar', ['farmer']],
    'farmer area: farms' => ['/farms', ['farmer']],
    'buyer area: market' => ['/market', ['buyer']],
    'buyer area: saved' => ['/saved', ['buyer']],
    'buyer area: needs' => ['/needs', ['buyer']],
    'buyer area: post need' => ['/needs/new', ['buyer']],
    'enquiries' => ['/enquiries', ['farmer', 'buyer']],
    'deliveries' => ['/deliveries', ['farmer', 'buyer', 'transporter']],
    'storage provider: mine' => ['/storage/mine', ['storage_provider']],
    'storage provider: requests' => ['/storage-requests', ['storage_provider']],
    'storage provider: new' => ['/storage/new', ['storage_provider']],
    'transporter: profile' => ['/transport/profile', ['transporter']],
    'coop: overview' => ['/coop', ['coop_admin']],
    'coop: members' => ['/coop/members', ['coop_admin']],
    'coop: reports' => ['/coop/reports', ['coop_admin']],
    'coop: export' => ['/coop/export.csv', ['coop_admin']],
    'admin: overview' => ['/admin', ['platform_admin']],
    'admin: users' => ['/admin/users', ['platform_admin']],
    'admin: audit' => ['/admin/audit', ['platform_admin']],
    'admin: verify' => ['/admin/verify', ['platform_admin']],
    'admin: deletions' => ['/admin/deletions', ['platform_admin']],
    'admin: prices' => ['/admin/prices', ['platform_admin']],
    'shared: alerts' => ['/alerts', ALL],
    'shared: my data' => ['/my-data', ALL],
    'shared: more' => ['/more', ALL],
    'shared: storage directory' => ['/storage', ALL],
    'shared: transport directory' => ['/transport', ALL],
    'shared: api session' => ['/api/v1/session', ALL],
    'shared: api listings' => ['/api/v1/listings', ALL],
]);

it('enforces the role matrix on GET endpoints', function (string $path, array $allowed) {
    foreach (ALL as $r) {
        $role = Role::from($r);
        $u = in_array($r, ['coop_admin'], true) ? mfaUser($role, ['coop_id' => makeCoop()->id]) : ($r === 'platform_admin' ? mfaUser($role) : makeUser($role));
        $res = actingAsUser($u)->get($path);
        if (in_array($r, $allowed, true)) {
            expect($res->status())->toBeIn([200, 302], "$r should be allowed on $path (got {$res->status()})");
        } else {
            expect($res->status())->toBe(403, "$r must be forbidden on $path (got {$res->status()})");
        }
    }
})->with('get_families');

it('redirects guests to sign in for every protected GET', function (string $path) {
    $this->get($path)->assertRedirect('/login');
})->with(array_map(fn ($p) => [$p], ['/home', '/sell', '/my/listings', '/market', '/enquiries', '/deliveries', '/storage', '/transport', '/coop', '/admin', '/alerts', '/my-data', '/losses']));

it('answers guests on JSON endpoints with 401 instead of a redirect', function () {
    $this->getJson('/api/v1/session')->assertUnauthorized();
    $this->postJson('/api/v1/sync', ['ops' => []])->assertUnauthorized();
});

it('serves the public pages to guests', function (string $path) {
    $this->get($path)->assertOk();
})->with(array_map(fn ($p) => [$p], ['/', '/login', '/register', '/register/farmer', '/prices', '/prices?tab=user', '/offline', '/health', '/api/v1/prices', '/forgot-password']));

dataset('write_families', [
    'create listing' => ['post', '/listings', ['farmer']],
    'record loss' => ['post', '/losses', ['farmer']],
    'add harvest lot' => ['post', '/losses/lots', ['farmer']],
    'add calendar' => ['post', '/calendar', ['farmer']],
    'add farm' => ['post', '/farms', ['farmer']],
    'send enquiry' => ['post', '/enquiries', ['buyer']],
    'post need' => ['post', '/needs', ['buyer']],
    'create facility' => ['post', '/storage', ['storage_provider']],
    'save transporter profile' => ['post', '/transport/profile', ['transporter']],
    'request delivery' => ['post', '/deliveries', ['farmer', 'buyer']],
    'verify record' => ['post', '/admin/verify', ['platform_admin']],
]);

it('rejects writes from roles outside the family (403 before validation)', function (string $method, string $path, array $allowed) {
    foreach (ALL as $r) {
        if (in_array($r, $allowed, true)) {
            continue;
        }
        $role = Role::from($r);
        $u = $r === 'coop_admin' ? mfaUser($role, ['coop_id' => makeCoop()->id]) : ($r === 'platform_admin' ? mfaUser($role) : makeUser($role));
        actingAsUser($u)->{$method}($path, [])->assertForbidden();
    }
})->with('write_families');

it('lets admin-only write endpoints refuse non-admins', function () {
    $victim = makeUser(Role::Farmer);
    $f = makeUser(Role::Farmer);
    actingAsUser($f)->post("/admin/users/{$victim->id}/suspend")->assertForbidden();
    expect($victim->fresh()->suspended_at)->toBeNull();
});

it('does not let a platform admin be suspended', function () {
    $a = mfaUser(Role::PlatformAdmin);
    $b = mfaUser(Role::PlatformAdmin);
    actingAsUser($a)->post("/admin/users/{$b->id}/suspend")->assertForbidden();
});

it('forces MFA enrolment for coop and platform admins before any page', function () {
    $coop = makeUser(Role::CoopAdmin, ['coop_id' => makeCoop()->id]);
    $this->actingAs($coop)->get('/coop')->assertRedirect('/mfa/setup');
    $admin = makeUser(Role::PlatformAdmin);
    $this->actingAs($admin)->get('/admin')->assertRedirect('/mfa/setup');
    $enrolled = mfaUser(Role::PlatformAdmin);
    $this->actingAs($enrolled)->get('/admin')->assertRedirect('/mfa/challenge');
});
