<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceSource extends Model
{
    protected $guarded = ['id'];

    /** @return HasMany<OfficialPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(OfficialPrice::class);
    }
}
