<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\User;

/** Enquiry content is private to the buyer and the listing owner. Even platform admins cannot read it. */
class EnquiryPolicy
{
    public function create(User $u): bool
    {
        return $u->hasRole(Role::Buyer);
    }

    public function view(User $u, Enquiry $e): bool
    {
        return $u->id === $e->buyer_id || $u->id === $e->listing->user_id;
    }

    public function respond(User $u, Enquiry $e): bool
    {
        return $u->id === $e->listing->user_id;
    }

    public function withdraw(User $u, Enquiry $e): bool
    {
        return $u->id === $e->buyer_id;
    }
}
