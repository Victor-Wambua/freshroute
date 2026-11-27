<?php

namespace App\Policies;

use App\Models\DeliveryRequest;
use App\Models\User;

class DeliveryRequestPolicy
{
    public function view(User $u, DeliveryRequest $d): bool
    {
        return $u->id === $d->requester_id || $u->id === $d->transporter->user_id;
    }

    public function carrierUpdate(User $u, DeliveryRequest $d): bool
    {
        return $u->id === $d->transporter->user_id;
    }

    public function cancel(User $u, DeliveryRequest $d): bool
    {
        return $u->id === $d->requester_id;
    }
}
