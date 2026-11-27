<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => DeliveryStatus::class, 'pickup_date' => 'date'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(TransporterProfile::class, 'transporter_profile_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
