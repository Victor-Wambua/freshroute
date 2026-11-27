<?php

namespace App\Enums;

enum Role: string
{
    case Farmer = 'farmer';
    case CoopAdmin = 'coop_admin';
    case Buyer = 'buyer';
    case StorageProvider = 'storage_provider';
    case Transporter = 'transporter';
    case PlatformAdmin = 'platform_admin';

    public static function registrable(): array
    {
        return [self::Farmer, self::CoopAdmin, self::Buyer, self::StorageProvider, self::Transporter];
    }

    public function requiresMfa(): bool
    {
        return in_array($this->value, config('freshroute.mfa_roles'), true);
    }

    public function label(): string
    {
        return __('ui.role_'.$this->value);
    }
}
