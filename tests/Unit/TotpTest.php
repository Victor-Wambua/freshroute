<?php

use App\Services\Totp;

// RFC 6238 Appendix B test vectors (SHA-1, secret "12345678901234567890"), truncated to 6 digits.
it('matches the RFC 6238 test vectors', function (int $time, string $expected) {
    $secret = Totp::base32Encode('12345678901234567890');
    expect(Totp::code($secret, $time))->toBe($expected);
})->with([
    [59, '287082'], [1111111109, '081804'], [1111111111, '050471'], [1234567890, '005924'], [2000000000, '279037'],
]);

it('verifies within one step of drift and rejects outside', function () {
    $s = Totp::generateSecret();
    $now = 1700000000;
    expect(Totp::verify($s, Totp::code($s, $now), 1, $now))->toBeTrue()
        ->and(Totp::verify($s, Totp::code($s, $now - 30), 1, $now))->toBeTrue()
        ->and(Totp::verify($s, Totp::code($s, $now - 90), 1, $now))->toBeFalse()
        ->and(Totp::verify($s, 'abcdef', 1, $now))->toBeFalse();
});

it('round-trips base32', function () {
    $raw = random_bytes(20);
    expect(Totp::base32Decode(Totp::base32Encode($raw)))->toBe($raw);
});
