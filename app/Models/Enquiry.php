<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquiry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => EnquiryStatus::class, 'responded_at' => 'datetime'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
