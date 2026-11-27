<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\DB;

/**
 * SANDBOX ONLY. No SMS provider is contracted; this records the message in sms_outbox and
 * never contacts anyone. Outstanding dependency: a licensed Kenyan SMS aggregator and sender ID.
 */
class SandboxSmsGateway implements SmsGateway
{
    public function send(?int $userId, string $to, string $body): void
    {
        DB::table('sms_outbox')->insert(['user_id' => $userId, 'to' => $to, 'body' => $body, 'status' => 'sandbox_not_sent', 'created_at' => now(), 'updated_at' => now()]);
    }
}
