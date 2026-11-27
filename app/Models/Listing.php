<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\EnquiryStatus;
use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ListingStatus::class,
            'harvest_date' => 'date',
            'available_from' => 'date',
            'available_to' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function scopeOpen($q)
    {
        return $q->where('status', ListingStatus::Active->value);
    }

    /** Bags not yet promised to agreed enquiries. */
    public function availableBags(): int
    {
        $agreed = (int) $this->enquiries()->where('status', EnquiryStatus::Agreed->value)->sum('quantity_bags');

        return max(0, $this->quantity_bags - $agreed);
    }
}
