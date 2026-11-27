<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HarvestLot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['harvested_on' => 'date'];
    }

    public function events(): HasMany
    {
        return $this->hasMany(LossEvent::class, 'lot_id');
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}
