<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['from_date' => 'date'];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(StorageFacility::class, 'facility_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
