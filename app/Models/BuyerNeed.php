<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $needed_by
 */
class BuyerNeed extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['needed_by' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return BelongsTo<Commodity, $this> */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}
