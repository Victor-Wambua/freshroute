<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\StorageFacility;
use App\Models\User;

class StorageFacilityPolicy
{
    public function create(User $u): bool
    {
        return $u->hasRole(Role::StorageProvider);
    }

    public function manage(User $u, StorageFacility $f): bool
    {
        return $u->id === $f->user_id;
    }
}
