<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $collected_on
 * @property Carbon|null $retrieved_at
 */
class OfficialPrice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['collected_on' => 'date', 'retrieved_at' => 'datetime'];
    }

    /** @return BelongsTo<PriceSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(PriceSource::class, 'price_source_id');
    }
}
