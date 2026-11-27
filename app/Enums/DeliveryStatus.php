<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Requested = 'requested';
    case Accepted = 'accepted';
    case PickedUp = 'picked_up';
    case Delivered = 'delivered';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::Requested => [self::Accepted, self::Declined, self::Cancelled],
            self::Accepted => [self::PickedUp, self::Cancelled],
            self::PickedUp => [self::Delivered],
            self::Delivered, self::Declined, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    public function step(): int
    {
        return match ($this) {
            self::Requested => 1,
            self::Accepted => 2,
            self::PickedUp => 3,
            self::Delivered => 4,
            default => 0,
        };
    }
}
