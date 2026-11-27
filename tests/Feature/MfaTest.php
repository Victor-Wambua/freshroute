<?php

use App\Enums\Role;
use App\Services\Totp;
use Illuminate\Support\Facades\Hash;

it('walks a platform admin through enrolment, recovery codes, and the session challenge', function () {
    $admin = makeUser(Role::PlatformAdmin);
    $this->actingAs($admin)->get('/mfa/setup')->assertOk()->assertSee('Secret key');
    $secret = $admin->fresh()->totp_secret;
    expect($secret)->not->toBeNull();

    $this->actingAs($admin)->post('/mfa/setup', ['code' => '000000'])->assertSessionHasErrors('code');
    expect($admin->fresh()->hasMfaEnabled())->toBeFalse();

    $res = $this->actingAs($admin)->post('/mfa/setup', ['code' => Totp::code($secret)]);
    $res->assertRedirect('/mfa/setup');
    expect($admin->fresh()->hasMfaEnabled())->toBeTrue()->and(session('recovery_codes'))->toHaveCount(8);
    $this->actingAs($admin->fresh())->get('/admin')->assertOk();
});

it('requires a valid code at each new session and refuses replay of the same code', function () {
    $admin = mfaUser(Role::PlatformAdmin);
    $this->actingAs($admin)->get('/admin')->assertRedirect('/mfa/challenge');
    $this->actingAs($admin)->post('/mfa/challenge', ['code' => '111111'])->assertSessionHasErrors('code');
    $code = Totp::code('JBSWY3DPEHPK3PXP');
    $this->actingAs($admin)->post('/mfa/challenge', ['code' => $code])->assertRedirect();
    // the same code, on a fresh session, is a replay
    $this->flushSession();
    $this->actingAs($admin)->post('/mfa/challenge', ['code' => $code])->assertSessionHasErrors('code');
});

it('accepts a recovery code once and burns it', function () {
    $plain = 'abcde-fghij';
    $admin = mfaUser(Role::CoopAdmin, ['coop_id' => makeCoop()->id, 'recovery_codes' => [Hash::make($plain), Hash::make('zzzzz-yyyyy')]]);
    $this->actingAs($admin)->post('/mfa/challenge', ['code' => $plain])->assertRedirect();
    expect($admin->fresh()->recovery_codes)->toHaveCount(1);
    $this->flushSession();
    $this->actingAs($admin->fresh())->post('/mfa/challenge', ['code' => $plain])->assertSessionHasErrors('code');
});

it('does not require MFA for ordinary roles', function () {
    $f = makeUser(Role::Farmer);
    $this->actingAs($f)->get('/home')->assertOk();
});

it('rate limits MFA guessing', function () {
    $admin = mfaUser(Role::PlatformAdmin);
    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($admin)->post('/mfa/challenge', ['code' => '123456']);
    }
    $this->actingAs($admin)->post('/mfa/challenge', ['code' => '123456'])->assertStatus(429);
});
