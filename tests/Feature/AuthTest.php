<?php

use App\Enums\Role;
use App\Models\Cooperative;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

function farmerPayload(array $over = []): array
{
    return $over + [
        'name' => 'Wanjiru Kamau', 'email' => 'wanjiru@test.example', 'phone' => '0712345678', 'password' => TEST_PASSWORD, 'password_confirmation' => TEST_PASSWORD,
        'county' => 'Nyandarua', 'place' => 'Ol Kalou', 'consent' => '1', 'farm_name' => 'Home farm', 'area_acres' => '3.5', 'variety' => 'Shangi', 'capacity_bags' => 400,
    ];
}

it('registers a farmer with a farm and crop profile, and records consent', function () {
    $coop = makeCoop();
    $this->post('/register/farmer', farmerPayload(['coop_id' => $coop->id]))->assertRedirect('/home');
    $u = User::where('email', 'wanjiru@test.example')->first();
    expect($u->role)->toBe(Role::Farmer)->and($u->consent_at)->not->toBeNull()->and($u->coop_id)->toBe($coop->id);
    $farm = Farm::where('user_id', $u->id)->first();
    expect($farm->crops)->toHaveCount(1)->and($farm->crops->first()->capacity_bags)->toBe(400);
    $this->assertAuthenticatedAs($u);
    $this->assertDatabaseHas('audit_logs', ['action' => 'auth.registered', 'user_id' => $u->id]);
});

it('registers a coop admin with a new unverified coop and sends them to MFA setup', function () {
    $this->post('/register/coop_admin', ['name' => 'Hannah', 'email' => 'h@test.example', 'phone' => '0722111222', 'password' => TEST_PASSWORD, 'password_confirmation' => TEST_PASSWORD,
        'county' => 'Nyandarua', 'place' => 'Kinangop', 'consent' => '1', 'coop_name' => 'Kinangop Growers', 'registration_no' => 'X1'])->assertRedirect('/home');
    $u = User::where('email', 'h@test.example')->first();
    expect($u->role)->toBe(Role::CoopAdmin)->and($u->coop->verified_at)->toBeNull();
    $this->get('/coop')->assertRedirect('/mfa/setup');
});

it('registers buyers, storage providers and transporters and routes provider onboarding', function () {
    $base = ['phone' => '0733000111', 'password' => TEST_PASSWORD, 'password_confirmation' => TEST_PASSWORD, 'county' => 'Nakuru', 'place' => 'Njoro', 'consent' => '1'];
    $this->post('/register/buyer', $base + ['name' => 'B', 'email' => 'b@test.example', 'business_name' => 'Greens Ltd', 'buyer_type' => 'trader'])->assertRedirect('/home');
    auth()->logout();
    $this->post('/register/storage_provider', $base + ['name' => 'S', 'email' => 's@test.example', 'business_name' => 'Cold Co'])->assertRedirect('/storage/new');
    auth()->logout();
    $this->post('/register/transporter', $base + ['name' => 'T', 'email' => 't@test.example', 'business_name' => 'Haul Co'])->assertRedirect('/transport/profile');
    expect(User::count())->toBe(3);
});

it('cannot register as platform admin or an unknown role', function () {
    $this->get('/register/platform_admin')->assertNotFound();
    $this->post('/register/platform_admin', farmerPayload())->assertNotFound();
    expect(User::count())->toBe(0);
});

it('does not mass-assign role through the registration form', function () {
    $this->post('/register/farmer', farmerPayload(['role' => 'platform_admin']));
    expect(User::first()->role)->toBe(Role::Farmer);
});

it('validates registration input', function (array $over, string $field) {
    $this->post('/register/farmer', farmerPayload($over))->assertSessionHasErrors($field);
    expect(User::count())->toBe(0);
})->with([
    'bad phone' => [['phone' => '12345'], 'phone'],
    'weak password' => [['password' => 'short1', 'password_confirmation' => 'short1'], 'password'],
    'no digits' => [['password' => 'onlyletterspassword', 'password_confirmation' => 'onlyletterspassword'], 'password'],
    'mismatch' => [['password_confirmation' => 'different-1234'], 'password'],
    'no consent' => [['consent' => null], 'consent'],
    'county outside MVP' => [['county' => 'Mombasa'], 'county'],
    'bad email' => [['email' => 'nope'], 'email'],
    'zero capacity' => [['capacity_bags' => 0], 'capacity_bags'],
]);

it('rejects a duplicate email', function () {
    makeUser(Role::Buyer, ['email' => 'wanjiru@test.example']);
    $this->post('/register/farmer', farmerPayload())->assertSessionHasErrors('email');
});

it('signs in and out, regenerates the session and audits it', function () {
    $u = makeUser(Role::Farmer, ['email' => 'f@test.example']);
    $this->post('/login', ['email' => 'f@test.example', 'password' => TEST_PASSWORD])->assertRedirect('/home');
    $this->assertAuthenticatedAs($u);
    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
    $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $u->id]);
});

it('rejects a wrong password with a generic message and audits the failure without the email', function () {
    makeUser(Role::Farmer, ['email' => 'f@test.example']);
    $this->post('/login', ['email' => 'f@test.example', 'password' => 'wrong-password-1'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $log = App\Models\AuditLog::where('action', 'auth.login_failed')->first();
    expect(json_encode($log->meta))->not->toContain('f@test.example');
});

it('rate limits repeated login attempts', function () {
    makeUser(Role::Farmer, ['email' => 'f@test.example']);
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'f@test.example', 'password' => 'bad-password-1']);
    }
    $this->post('/login', ['email' => 'f@test.example', 'password' => TEST_PASSWORD])->assertStatus(429);
    RateLimiter::clear('f@test.example|127.0.0.1');
});

it('blocks suspended accounts at login and mid-session', function () {
    $u = makeUser(Role::Farmer, ['email' => 'f@test.example', 'suspended_at' => now()]);
    $this->post('/login', ['email' => 'f@test.example', 'password' => TEST_PASSWORD])->assertSessionHasErrors('email');
    $this->actingAs($u)->get('/home')->assertRedirect('/login');
});

it('does not reveal whether an email is registered when requesting a reset', function () {
    Mail::fake();
    makeUser(Role::Farmer, ['email' => 'known@test.example']);
    $a = $this->post('/forgot-password', ['email' => 'known@test.example']);
    $b = $this->post('/forgot-password', ['email' => 'unknown@test.example']);
    expect(session('status'))->not->toBeNull();
    $a->assertSessionHas('status');
    $b->assertSessionHas('status');
});

it('resets a password with a valid token', function () {
    $u = makeUser(Role::Farmer, ['email' => 'known@test.example']);
    $token = Password::createToken($u);
    $this->post('/reset-password', ['token' => $token, 'email' => 'known@test.example', 'password' => 'Brand-new-pass-9', 'password_confirmation' => 'Brand-new-pass-9'])->assertRedirect('/login');
    $this->post('/login', ['email' => 'known@test.example', 'password' => 'Brand-new-pass-9'])->assertRedirect('/home');
});

it('keeps passwords hashed and secrets encrypted at rest', function () {
    $u = mfaUser(Role::PlatformAdmin);
    $raw = DB::table('users')->where('id', $u->id)->first();
    expect($raw->password)->not->toContain(TEST_PASSWORD)->and(str_starts_with($raw->password, '$2y$'))->toBeTrue()
        ->and($raw->totp_secret)->not->toContain('JBSWY3DPEHPK3PXP');
});

it('switches language and persists it for a signed-in user', function () {
    $u = makeUser(Role::Farmer);
    $this->actingAs($u)->get('/locale/sw')->assertRedirect();
    expect($u->fresh()->locale)->toBe('sw');
    $this->actingAs($u->fresh())->get('/home')->assertSee('Habari');
    $this->get('/locale/fr')->assertNotFound();
});
