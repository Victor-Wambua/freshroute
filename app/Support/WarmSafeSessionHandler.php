<?php

namespace App\Support;

use Illuminate\Session\DatabaseSessionHandler;

/**
 * Database session handler that ignores writes from the service worker's background cache-warming
 * requests (header X-FR-Warm). Those requests are read-only by design: without this, a warm-up request
 * that started before a form submit could save a stale copy of the session afterwards and silently
 * drop flash messages or MFA state (a lost update), or age out a one-time flash such as recovery codes.
 */
class WarmSafeSessionHandler extends DatabaseSessionHandler
{
    public function write($sessionId, $data): bool
    {
        if (request()->headers->has('X-FR-Warm')) {
            return true;
        }

        return parent::write($sessionId, $data);
    }
}
