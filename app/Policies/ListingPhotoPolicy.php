<?php

namespace App\Policies;

use App\Models\ListingPhoto;
use App\Models\User;

class ListingPhotoPolicy
{
    public function view(User $u, ListingPhoto $p): bool
    {
        return $u->can('view', $p->listing);
    }

    public function delete(User $u, ListingPhoto $p): bool
    {
        return $u->id === $p->listing->user_id;
    }
}
