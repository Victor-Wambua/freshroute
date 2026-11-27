<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Locale (EN/SW), suspended-account lockout, and last-active stamp. */
class AppContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ($user->suspended_at || $user->anonymised_at)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('ui.err_suspended')]);
        }
        $locale = $user?->locale ?? $request->session()->get('locale', 'en');
        app()->setLocale(in_array($locale, ['en', 'sw'], true) ? $locale : 'en');
        if ($user && (! $user->last_active_at || $user->last_active_at->lt(now()->subMinutes(10)))) {
            $user->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
