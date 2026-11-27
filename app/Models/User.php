<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = ['id', 'role'];

    protected $hidden = ['password', 'remember_token', 'totp_secret', 'recovery_codes'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'consent_at' => 'datetime',
            'notification_prefs' => 'array',
            'totp_secret' => 'encrypted',
            'totp_confirmed_at' => 'datetime',
            'recovery_codes' => 'encrypted:array',
            'suspended_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'anonymised_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function coop(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class, 'buyer_id');
    }

    public function needs(): HasMany
    {
        return $this->hasMany(BuyerNeed::class, 'buyer_id');
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(StorageFacility::class);
    }

    public function transporterProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TransporterProfile::class);
    }

    public function lossEvents(): HasMany
    {
        return $this->hasMany(LossEvent::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(HarvestLot::class);
    }

    public function calendar(): HasMany
    {
        return $this->hasMany(HarvestCalendarEntry::class);
    }

    public function savedSuppliers(): HasMany
    {
        return $this->hasMany(SavedSupplier::class, 'buyer_id');
    }

    public function needsMfa(): bool
    {
        return $this->role->requiresMfa();
    }

    public function hasMfaEnabled(): bool
    {
        return $this->totp_confirmed_at !== null;
    }

    public function prefers(string $channel): bool
    {
        $prefs = $this->notification_prefs ?? ['mail' => true, 'database' => true];

        return (bool) ($prefs[$channel] ?? false);
    }

    public function displayName(): string
    {
        return $this->business_name ?: $this->name;
    }
}
