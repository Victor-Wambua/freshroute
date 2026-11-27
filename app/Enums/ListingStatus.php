<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::Draft => [self::Active, self::Withdrawn],
            self::Active => [self::Reserved, self::Sold, self::Expired, self::Withdrawn],
            self::Reserved => [self::Active, self::Sold, self::Withdrawn],
            self::Expired => [self::Active, self::Withdrawn],
            self::Sold, self::Withdrawn => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    public function isOpen(): bool
    {
        return $this === self::Active;
    }
}
