<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cooperative extends Model
{
    protected $guarded = ['id'];

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'coop_id');
    }

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }
}
