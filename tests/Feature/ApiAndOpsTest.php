<?php

use App\Enums\Role;
use App\Models\Listing;
use App\Models\OfficialPrice;
use App\Models\PriceSource;
use App\Models\User;
use Database\Seeders\SyntheticDataSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;

it('reports health with database status and no sensitive details', function () {
    $this->getJson('/health')->assertOk()->assertJson(['status' => 'ok', 'database' => true, 'app' => 'freshroute'])->assertJsonMissing(['version' => PHP_VERSION]);
    $this->get('/up')->assertOk();
});

it('documents every API route in docs/openapi.yaml and documents nothing that does not exist', function () {
    $spec = Yaml::parseFile(base_path('docs/openapi.yaml'));
    $documented = [];
    foreach ($spec['paths'] as $path => $ops) {
        foreach (array_keys($ops) as $method) {
            $documented[] = strtoupper($method).' '.ltrim($path, '/');
        }
    }
    $actual = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => str_starts_with($r->uri(), 'api/v1/'))
        ->flatMap(fn ($r) => collect($r->methods())->reject(fn ($m) => in_array($m, ['HEAD']))->map(fn ($m) => $m.' '.$r->uri()))->values()->all();
    sort($documented);
    sort($actual);
    expect($documented)->toBe($actual);
});

it('serves official prices publicly with source, licence and origin on every row, and no user data', function () {
    $src = PriceSource::create(['name' => 'Synthetic', 'url' => 'https://example.invalid/s', 'licence_status' => 'synthetic', 'origin' => 'synthetic']);
    OfficialPrice::create(['commodity_id' => commodity()->id, 'price_source_id' => $src->id, 'market' => 'Nakuru', 'unit' => '50 kg bag', 'price_minor' => 265000, 'collected_on' => today(), 'source_url' => 'https://example.invalid/s', 'licence_status' => 'synthetic', 'origin' => 'synthetic', 'retrieved_at' => now()]);
    $j = $this->getJson('/api/v1/prices')->assertOk()->json();
    expect($j['data'][0])->toHaveKeys(['market', 'unit', 'price_minor', 'currency', 'collected_on', 'source', 'source_url', 'licence_status', 'origin'])->and($j['data'][0]['origin'])->toBe('synthetic');
});

it('sets security headers including a strict CSP without third-party origins', function () {
    $res = $this->get('/');
    $csp = $res->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")->toContain("frame-ancestors 'none'")->toContain("object-src 'none'")->not->toContain('http')->not->toContain('unsafe-eval');
    $res->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('loads no third-party scripts, styles or fonts on any public page', function (string $path) {
    $html = $this->get($path)->getContent();
    preg_match_all('/(?:src|href)="(https?:\/\/[^"]+)"/', $html, $m);
    foreach ($m[1] as $url) {
        expect(parse_url($url, PHP_URL_HOST))->toBeIn(['localhost', '127.0.0.1']);
    }
})->with(['/', '/login', '/register/farmer', '/prices']);

it('escapes user-controlled output (stored XSS)', function () {
    $f = makeUser(Role::Farmer, ['name' => '<script>alert(1)</script>']);
    $l = makeListing($f, ['place' => '<img src=x onerror=alert(1)>', 'variety' => '"><svg onload=alert(1)>']);
    $b = makeUser(Role::Buyer);
    $html = actingAsUser($b)->get('/market')->getContent();
    expect($html)->not->toContain('<script>alert(1)')->not->toContain('<img src=x onerror')->not->toContain('<svg onload');
    $html2 = actingAsUser($b)->get("/market/{$l->id}")->getContent();
    expect($html2)->not->toContain('<img src=x onerror')->toContain('&lt;img src=x onerror');
});

it('protects against SQL injection in search and sort parameters', function () {
    $b = makeUser(Role::Buyer);
    makeListing(makeUser(Role::Farmer));
    actingAsUser($b)->get("/market?q=' OR 1=1 --")->assertOk();
    actingAsUser($b)->get('/market?sort=price;DROP TABLE listings')->assertSessionHasErrors('sort');
    actingAsUser($b)->get('/market?county=Nakuru%27%20OR%201=1')->assertSessionHasErrors('county');
    expect(Listing::count())->toBe(1);
});

it('has CSRF protection in the web middleware group (the 419 behaviour is verified end to end in Playwright)', function () {
    $groups = app(Kernel::class)->getMiddlewareGroups()['web'];
    expect(collect($groups)->contains(fn ($m) => is_string($m) && str_contains($m, 'PreventRequestForgery')))->toBeTrue();
});

it('registers the scheduled commands', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('fr:harvest-reminders')->expectsOutputToContain('fr:notify-matches')->expectsOutputToContain('fr:expire-listings')->expectsOutputToContain('fr:process-deletions')->assertSuccessful();
});

it('refuses to seed synthetic data in production', function () {
    app()->detectEnvironment(fn () => 'production');
    expect(fn () => (new SyntheticDataSeeder)->run())->toThrow(RuntimeException::class);
});

it('the synthetic seeder is idempotent and labels everything synthetic', function () {
    $this->seed(SyntheticDataSeeder::class);
    $counts = [User::count(), Listing::count(), OfficialPrice::count()];
    $this->seed(SyntheticDataSeeder::class);
    expect([User::count(), Listing::count(), OfficialPrice::count()])->toBe($counts);
    expect(User::where('origin', '!=', 'synthetic')->count())->toBe(0)->and(Listing::where('origin', '!=', 'synthetic')->count())->toBe(0)
        ->and(OfficialPrice::where('origin', '!=', 'synthetic')->count())->toBe(0)->and(OfficialPrice::where('licence_status', '!=', 'synthetic')->count())->toBe(0);
});
