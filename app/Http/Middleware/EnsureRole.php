<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, array_map(fn ($r) => Role::from($r), $roles), true), 403);

        return $next($request);
    }
}
