<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ListingStatus $status
 * @property Carbon|null $harvest_date
 * @property Carbon|null $available_from
 * @property Carbon|null $available_to
 */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Commodity, $this> */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    /** @return HasMany<ListingPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class);
    }

    /** @return HasMany<Enquiry, $this> */
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
