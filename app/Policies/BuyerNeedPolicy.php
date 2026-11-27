<?php

namespace App\Policies;

use App\Models\BuyerNeed;
use App\Models\User;

class BuyerNeedPolicy
{
    public function manage(User $u, BuyerNeed $n): bool
    {
        return $u->id === $n->buyer_id;
    }
}
