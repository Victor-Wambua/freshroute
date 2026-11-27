<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Services\SandboxSmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, SandboxSmsGateway::class);
    }

    public function boot(): void
    {
        $key = fn (Request $r) => $r->user()?->id ?: $r->ip();
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('register', fn (Request $r) => Limit::perHour(15)->by($r->ip()));
        RateLimiter::for('mfa', fn (Request $r) => Limit::perMinute(5)->by($key($r)));
        RateLimiter::for('writes', fn (Request $r) => Limit::perMinute(60)->by($key($r)));
        RateLimiter::for('sync', fn (Request $r) => Limit::perMinute(30)->by($key($r)));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perHour(30)->by($key($r)));
        RateLimiter::for('exports', fn (Request $r) => Limit::perMinute(10)->by($key($r)));
        RateLimiter::for('public', fn (Request $r) => Limit::perMinute(120)->by($r->ip()));
    }
}
