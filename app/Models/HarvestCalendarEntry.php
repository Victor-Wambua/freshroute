<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HarvestCalendarEntry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['harvest_on' => 'date', 'reminded_at' => 'datetime'];
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}
