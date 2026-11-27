<?php

namespace App\Http\Controllers;

use App\Services\Totp;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MfaController extends Controller
{
    public function setup(Request $request)
    {
        $user = $request->user();
        if (! $user->totp_secret || ! $user->hasMfaEnabled()) {
            if (! $user->totp_secret) {
                $user->forceFill(['totp_secret' => Totp::generateSecret()])->save();
            }
        }

        return view('auth.mfa-setup', [
            'secret' => $user->hasMfaEnabled() ? null : $user->totp_secret,
            'uri' => $user->hasMfaEnabled() ? null : Totp::uri($user->totp_secret, $user->email),
            'enabled' => $user->hasMfaEnabled(),
            'codes' => session('recovery_codes'),
        ]);
    }

    public function confirm(Request $request)
    {
        $user = $request->user();
        $request->validate(['code' => ['required', 'string', 'max:10']]);
        if ($user->hasMfaEnabled() || ! $user->totp_secret || ! $this->checkCode($user->id, $user->totp_secret, $request->input('code'))) {
            return back()->withErrors(['code' => __('ui.err_mfa')]);
        }
        $plain = collect(range(1, 8))->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))->all();
        $user->forceFill([
            'totp_confirmed_at' => now(),
            'recovery_codes' => array_map(fn ($c) => Hash::make($c), $plain),
        ])->save();
        $request->session()->put('mfa_passed', true);
        Audit::log('mfa.enabled', $user);

        return redirect()->route('mfa.setup')->with('recovery_codes', $plain);
    }

    public function challenge()
    {
        return view('auth.mfa-challenge');
    }

    public function verify(Request $request)
    {
        $user = $request->user();
        $request->validate(['code' => ['required', 'string', 'max:20']]);
        $code = trim($request->input('code'));
        $ok = $user->totp_secret && $this->checkCode($user->id, $user->totp_secret, $code);

        if (! $ok && ($codes = $user->recovery_codes)) {
            foreach ($codes as $i => $hash) {
                if (Hash::check(strtolower($code), $hash)) {
                    unset($codes[$i]);
                    $user->forceFill(['recovery_codes' => array_values($codes)])->save();
                    Audit::log('mfa.recovery_code_used', $user, ['remaining' => count($codes)]);
                    $ok = true;
                    break;
                }
            }
        }
        if (! $ok) {
            Audit::log('mfa.failed', $user);

            return back()->withErrors(['code' => __('ui.err_mfa')]);
        }
        $request->session()->put('mfa_passed', true);

        return redirect()->intended(route('home'));
    }

    /** A code is valid once: replay inside its window is refused. */
    private function checkCode(int $userId, string $secret, string $code): bool
    {
        if (! Totp::verify($secret, $code)) {
            return false;
        }

        return Cache::add('totp-used:'.$userId.':'.preg_replace('/\D/', '', $code), 1, 120);
    }
}
