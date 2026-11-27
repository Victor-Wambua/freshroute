<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $occurred_on
 */
class LossEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['occurred_on' => 'date'];
    }

    /** @return BelongsTo<HarvestLot, $this> */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(HarvestLot::class, 'lot_id');
    }

    /** @return BelongsTo<Commodity, $this> */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
