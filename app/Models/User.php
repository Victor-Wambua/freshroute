<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property Role $role
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property Carbon|null $consent_at
 * @property array<mixed>|null $notification_prefs
 * @property string|null $totp_secret
 * @property Carbon|null $totp_confirmed_at
 * @property array<int, string>|null $recovery_codes
 * @property Carbon|null $suspended_at
 * @property Carbon|null $deletion_requested_at
 * @property Carbon|null $anonymised_at
 * @property Carbon|null $last_active_at
 */
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

    /** @return BelongsTo<Cooperative, $this> */
    public function coop(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    /** @return HasMany<Farm, $this> */
    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    /** @return HasMany<Listing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** @return HasMany<Enquiry, $this> */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class, 'buyer_id');
    }

    /** @return HasMany<BuyerNeed, $this> */
    public function needs(): HasMany
    {
        return $this->hasMany(BuyerNeed::class, 'buyer_id');
    }

    /** @return HasMany<StorageFacility, $this> */
    public function facilities(): HasMany
    {
        return $this->hasMany(StorageFacility::class);
    }

    /** @return HasOne<TransporterProfile, $this> */
    public function transporterProfile(): HasOne
    {
        return $this->hasOne(TransporterProfile::class);
    }

    /** @return HasMany<LossEvent, $this> */
    public function lossEvents(): HasMany
    {
        return $this->hasMany(LossEvent::class);
    }

    /** @return HasMany<HarvestLot, $this> */
    public function lots(): HasMany
    {
        return $this->hasMany(HarvestLot::class);
    }

    /** @return HasMany<HarvestCalendarEntry, $this> */
    public function calendar(): HasMany
    {
        return $this->hasMany(HarvestCalendarEntry::class);
    }

    /** @return HasMany<SavedSupplier, $this> */
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
