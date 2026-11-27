<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $harvested_on
 */
class HarvestLot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['harvested_on' => 'date'];
    }

    /** @return HasMany<LossEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(LossEvent::class, 'lot_id');
    }

    /** @return BelongsTo<Commodity, $this> */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}
