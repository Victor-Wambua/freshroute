<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Platform admins and cooperative admins must enrol in TOTP and pass a challenge each session. */
class EnsureMfa
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->needsMfa() || $request->routeIs('mfa.*', 'logout')) {
            return $next($request);
        }
        if (! $user->hasMfaEnabled()) {
            return redirect()->route('mfa.setup');
        }
        if (! $request->session()->get('mfa_passed')) {
            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }
}
