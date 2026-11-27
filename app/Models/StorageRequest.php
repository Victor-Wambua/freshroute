<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $from_date
 */
class StorageRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['from_date' => 'date'];
    }

    /** @return BelongsTo<StorageFacility, $this> */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(StorageFacility::class, 'facility_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
