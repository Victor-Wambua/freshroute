<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function create(User $u): bool
    {
        return $u->hasRole(Role::Farmer);
    }

    /** Open listings are visible to any signed-in user; others only to the owner, their coop admin, or platform staff. */
    public function view(User $u, Listing $l): bool
    {
        return $l->status->isOpen() || $this->owns($u, $l)
            || ($u->hasRole(Role::CoopAdmin) && $u->coop_id && $l->user->coop_id === $u->coop_id)
            || $u->hasRole(Role::PlatformAdmin)
            || ($u->hasRole(Role::Buyer) && $l->enquiries()->where('buyer_id', $u->id)->exists());
    }

    public function update(User $u, Listing $l): bool
    {
        return $this->owns($u, $l);
    }

    public function delete(User $u, Listing $l): bool
    {
        return $this->owns($u, $l);
    }

    private function owns(User $u, Listing $l): bool
    {
        return $u->id === $l->user_id;
    }
}
