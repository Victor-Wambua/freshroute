<?php

namespace App\Contracts;

interface SmsGateway
{
    public function send(?int $userId, string $to, string $body): void;
}
