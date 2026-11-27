<?php

namespace App\Support;

class Money
{
    /** Integer minor units (cents of KES) to a display string. */
    public static function kes(?int $minor): string
    {
        if ($minor === null) {
            return '-';
        }

        $whole = intdiv($minor, 100);
        $cents = $minor % 100;

        return 'KES '.number_format($whole).($cents ? '.'.str_pad((string) $cents, 2, '0', STR_PAD_LEFT) : '');
    }

    /** Whole or decimal shillings from a form to integer minor units, without float drift. */
    public static function toMinor(int|float|string $kes): int
    {
        return (int) round(((float) $kes) * 100);
    }
}
