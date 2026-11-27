<?php

namespace App\Support;

/** Geometry for the server-rendered inline SVG line chart (no JS, no CDN). */
class Chart
{
    /**
     * @param  list<array{label: string, value: int}>  $series  oldest first; value in minor units
     * @return array{w: int, h: int, points: list<array>, path: string, ticks: list<array>, min: int, max: int}|null
     */
    public static function line(array $series, int $w = 640, int $h = 260): ?array
    {
        if (count($series) < 1) {
            return null;
        }
        $padL = 64;
        $padR = 16;
        $padT = 16;
        $padB = 36;
        $vals = array_column($series, 'value');
        $min = (int) (floor(min($vals) / 10000) * 10000);
        $max = (int) (ceil(max($vals) / 10000) * 10000);
        if ($max === $min) {
            $max = $min + 10000;
        }
        $n = count($series);
        $pts = [];
        foreach ($series as $i => $s) {
            $x = $n === 1 ? $padL + ($w - $padL - $padR) / 2 : $padL + ($w - $padL - $padR) * $i / ($n - 1);
            $y = $padT + ($h - $padT - $padB) * (1 - ($s['value'] - $min) / ($max - $min));
            $pts[] = ['x' => round($x, 1), 'y' => round($y, 1), 'label' => $s['label'], 'value' => $s['value']];
        }
        $ticks = [];
        for ($i = 0; $i <= 4; $i++) {
            $v = $min + ($max - $min) * $i / 4;
            $ticks[] = ['y' => round($padT + ($h - $padT - $padB) * (1 - $i / 4), 1), 'label' => number_format($v / 100)];
        }
        $path = '';
        foreach ($pts as $i => $p) {
            $path .= ($i === 0 ? 'M' : 'L').$p['x'].' '.$p['y'].' ';
        }

        return ['w' => $w, 'h' => $h, 'points' => $pts, 'path' => trim($path), 'ticks' => $ticks, 'min' => $min, 'max' => $max, 'padL' => $padL, 'padR' => $padR, 'padB' => $padB];
    }
}
