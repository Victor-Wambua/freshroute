<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;

class FarmPolicy
{
    public function manage(User $u, Farm $f): bool
    {
        return $u->id === $f->user_id;
    }
}
