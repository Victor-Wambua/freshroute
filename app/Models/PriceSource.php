<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceSource extends Model
{
    protected $guarded = ['id'];

    public function prices(): HasMany
    {
        return $this->hasMany(OfficialPrice::class);
    }
}
