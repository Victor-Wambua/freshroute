<?php

use App\Support\Chart;
use App\Support\Csv;
use App\Support\Money;

it('keeps money in integer minor units without float drift', function () {
    expect(Money::toMinor('2600'))->toBe(260000)
        ->and(Money::toMinor(19.99))->toBe(1999)
        ->and(Money::kes(260000))->toBe('KES 2,600')
        ->and(Money::kes(260050))->toBe('KES 2,600.50')
        ->and(Money::kes(null))->toBe('-');
});

it('neutralises CSV formula injection', function () {
    expect(Csv::cell('=cmd|calc'))->toBe("'=cmd|calc")
        ->and(Csv::cell('+1'))->toBe("'+1")
        ->and(Csv::cell('@SUM(A1)'))->toBe("'@SUM(A1)")
        ->and(Csv::cell('plain'))->toBe('plain')
        ->and(Csv::cell(5))->toBe(5);
    expect(Csv::line(['a,b', '=1']))->toBe("\"a,b\",'=1\n");
});

it('builds chart geometry inside the viewbox', function () {
    $c = Chart::line([['label' => 'a', 'value' => 250000], ['label' => 'b', 'value' => 270000], ['label' => 'c', 'value' => 260000]]);
    expect($c['points'])->toHaveCount(3);
    foreach ($c['points'] as $p) {
        expect($p['x'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual($c['w'])
            ->and($p['y'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual($c['h']);
    }
    expect($c['points'][1]['y'])->toBeLessThan($c['points'][0]['y']); // higher price, higher on screen
    expect(Chart::line([]))->toBeNull();
    expect(Chart::line([['label' => 'x', 'value' => 100000]])['points'])->toHaveCount(1);
});
