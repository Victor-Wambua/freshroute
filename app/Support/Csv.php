<?php

namespace App\Support;

class Csv
{
    /** Neutralise spreadsheet formula injection in user-controlled cells. */
    public static function cell(mixed $v): mixed
    {
        if (is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$v;
        }

        return $v;
    }

    public static function line(array $cells): string
    {
        $h = fopen('php://temp', 'r+');
        fputcsv($h, array_map([self::class, 'cell'], $cells), ',', '"', '');
        rewind($h);

        return stream_get_contents($h);
    }
}
