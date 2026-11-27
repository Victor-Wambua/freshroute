<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property array<mixed>|null $commodities
 * @property bool $active
 * @property Carbon|null $verified_at
 */
class StorageFacility extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['commodities' => 'array', 'active' => 'boolean', 'verified_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
