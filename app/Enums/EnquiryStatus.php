<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Replied = 'replied';
    case Agreed = 'agreed';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::Sent => [self::Viewed, self::Replied, self::Declined, self::Withdrawn],
            self::Viewed => [self::Replied, self::Declined, self::Withdrawn],
            self::Replied => [self::Agreed, self::Declined, self::Withdrawn],
            self::Agreed, self::Declined, self::Withdrawn => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    public function isTerminal(): bool
    {
        return $this->next() === [];
    }

    /** Step index for the 3-step tracker (Sent, Replied, Agreed). */
    public function step(): int
    {
        return match ($this) {
            self::Sent, self::Viewed => 1,
            self::Replied => 2,
            self::Agreed => 3,
            default => 0,
        };
    }
}
