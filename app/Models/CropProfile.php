<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropProfile extends Model
{
    protected $guarded = ['id'];

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}
