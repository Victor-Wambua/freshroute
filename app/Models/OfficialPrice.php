<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialPrice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['collected_on' => 'date', 'retrieved_at' => 'datetime'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(PriceSource::class, 'price_source_id');
    }
}
