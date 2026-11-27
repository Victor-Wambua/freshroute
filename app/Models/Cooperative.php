<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $verified_at
 */
class Cooperative extends Model
{
    protected $guarded = ['id'];

    /** @return HasMany<User, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'coop_id');
    }

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }
}
