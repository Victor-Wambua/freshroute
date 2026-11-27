<?php

use App\Enums\Role;
use App\Models\OfficialPrice;
use App\Models\PriceSource;
use App\Models\UserPrice;
use App\Services\Price\CsvPriceFeedAdapter;
use App\Services\Price\PriceFeedAdapter;
use App\Services\Price\PriceImporter;
use App\Services\Price\PriceRecord;
use App\Services\Price\PriceStats;

function priceCsv(array $rows): string
{
    $p = tempnam(sys_get_temp_dir(), 'pc');
    $h = fopen($p, 'w');
    fputcsv($h, ['commodity', 'market', 'county', 'price_type', 'unit', 'price_kes', 'collected_on', 'source_url', 'licence_status'], ',', '"', '');
    foreach ($rows as $r) {
        fputcsv($h, $r, ',', '"', '');
    }
    fclose($h);

    return $p;
}

function synthRow(array $over = []): array
{
    $row = ['irish-potato', 'Nakuru', 'Nakuru', 'wholesale', '50 kg bag', '2650', today()->subDays(2)->toDateString(), 'https://example.invalid/synthetic', 'synthetic'];
    foreach ($over as $i => $v) {
        $row[$i] = $v;
    }

    return $row;
}

function synthSource(array $over = []): PriceSource
{
    return PriceSource::create($over + ['name' => 'Synthetic test bulletin', 'url' => 'https://example.invalid/synthetic', 'licence_status' => 'synthetic', 'origin' => 'synthetic']);
}

it('imports synthetic rows through the real importer and records source, date and licence on every row', function () {
    $r = app(PriceImporter::class)->import(synthSource(), new CsvPriceFeedAdapter(priceCsv([synthRow(), synthRow([6 => today()->subDays(9)->toDateString(), 5 => '2600'])])));
    expect($r['batch']->rows_imported)->toBe(2)->and($r['batch']->rows_rejected)->toBe(0);
    foreach (OfficialPrice::all() as $p) {
        expect($p->source_url)->not->toBeEmpty()->and($p->collected_on)->not->toBeNull()->and($p->licence_status)->toBe('synthetic')->and($p->origin)->toBe('synthetic')->and($p->retrieved_at)->not->toBeNull();
    }
    expect(OfficialPrice::first()->price_minor)->toBe(265000);
});

it('refuses to import from a source whose licence is not confirmed', function (string $status) {
    $src = synthSource(['licence_status' => $status, 'origin' => 'officially_published', 'name' => 'Real-looking '.$status]);
    $r = app(PriceImporter::class)->import($src, new CsvPriceFeedAdapter(priceCsv([synthRow([8 => $status])])));
    expect($r['batch']->rows_imported)->toBe(0)->and($r['batch']->rows_rejected)->toBe(1)->and(OfficialPrice::count())->toBe(0)
        ->and(json_encode($r['batch']->errors))->toContain('does not permit import');
})->with(['unconfirmed', 'restricted']);

it('rejects rows missing source URL, collection date, unit, market or price', function (array $over, string $needle) {
    $r = app(PriceImporter::class)->import(synthSource(), new CsvPriceFeedAdapter(priceCsv([synthRow($over)])));
    expect($r['batch']->rows_imported)->toBe(0)->and($r['batch']->rows_rejected)->toBe(1)->and(OfficialPrice::count())->toBe(0)->and(json_encode($r['batch']->errors))->toContain($needle);
})->with([
    'no source url' => [[7 => ''], 'source url'],
    'bad source url' => [[7 => 'javascript:alert(1)'], 'source url'],
    'no date' => [[6 => ''], 'collected on'],
    'future date' => [[6 => '2999-01-01'], 'collected on'],
    'no unit' => [[4 => ''], 'unit'],
    'no market' => [[1 => ''], 'market'],
    'zero price' => [[5 => '0'], 'price'],
    'non numeric price' => [[5 => 'abc'], 'price'],
    'unknown commodity' => [[0 => 'cocoa'], 'commodity'],
    'no licence on row' => [[8 => ''], 'licence status'],
]);

it('refuses synthetic/official mismatches in either direction', function () {
    $official = synthSource(['licence_status' => 'open_licence', 'origin' => 'officially_published', 'name' => 'Open source']);
    $r1 = app(PriceImporter::class)->import($official, new CsvPriceFeedAdapter(priceCsv([synthRow([8 => 'synthetic'])])));
    $r2 = app(PriceImporter::class)->import(synthSource(), new CsvPriceFeedAdapter(priceCsv([synthRow([8 => 'open_licence'])])));
    expect($r1['batch']->rows_imported)->toBe(0)->and($r2['batch']->rows_imported)->toBe(0)->and(OfficialPrice::count())->toBe(0);
});

it('imports from a custom adapter implementing the interface, and is idempotent on re-import', function () {
    $feed = new class implements PriceFeedAdapter
    {
        public function fileName(): string
        {
            return 'memory';
        }

        public function records(): iterable
        {
            yield new PriceRecord('irish-potato', 'Molo', 'Nakuru', 'wholesale', '50 kg bag', 250000, today()->subDay()->toDateString(), 'https://example.invalid/x', 'synthetic');
        }
    };
    $src = synthSource();
    app(PriceImporter::class)->import($src, $feed);
    app(PriceImporter::class)->import($src, $feed);
    expect(OfficialPrice::count())->toBe(1);
});

it('keeps user-submitted prices out of official statistics, in every direction', function () {
    $src = synthSource();
    app(PriceImporter::class)->import($src, new CsvPriceFeedAdapter(priceCsv([synthRow(), synthRow([6 => today()->subDays(8)->toDateString(), 5 => '2550'])])));
    $c = commodity();
    $stats = app(PriceStats::class);
    $before = [$stats->officialLatest($c->id, 'Nakuru')->price_minor, $stats->officialAverage($c->id, 'Nakuru'), $stats->officialSeries($c->id, 'Nakuru')->pluck('price_minor')->all()];

    $u = makeUser(Role::Farmer);
    foreach ([100000, 5000000, 4999999, 50000] as $i => $p) {
        UserPrice::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $u->id, 'commodity_id' => $c->id, 'market' => 'Nakuru', 'unit' => '50 kg bag', 'price_minor' => $p, 'observed_on' => today()]);
    }
    $after = [$stats->officialLatest($c->id, 'Nakuru')->price_minor, $stats->officialAverage($c->id, 'Nakuru'), $stats->officialSeries($c->id, 'Nakuru')->pluck('price_minor')->all()];
    expect($after)->toBe($before)->and(OfficialPrice::count())->toBe(2);
    expect($stats->userMedian($c->id, 'Nakuru'))->toBe((int) round((100000 + 4999999) / 2));
    // and the public API never returns user rows
    $rows = $this->getJson('/api/v1/prices')->json('data');
    expect(collect($rows)->pluck('price_minor')->all())->not->toContain(5000000)->and(collect($rows)->pluck('origin')->unique()->all())->toBe(['synthetic']);
});

it('submitting a user price writes only to user_prices and shows only on the user tab', function () {
    $u = makeUser(Role::Farmer);
    actingAsUser($u)->post('/prices/user', ['market' => 'Nakuru', 'price_kes' => 2999, 'observed_on' => today()->toDateString(), 'note' => 'stall price'])->assertRedirect();
    expect(UserPrice::count())->toBe(1)->and(OfficialPrice::count())->toBe(0);
    $this->get('/prices?market=Nakuru')->assertDontSee('2,999');
    $this->get('/prices?tab=user&market=Nakuru')->assertSee('2,999')->assertSee('User-submitted')->assertSee('not official', false);
});

it('validates user price submissions and requires sign-in', function () {
    actingAsUser(makeUser(Role::Buyer))->post('/prices/user', ['market' => 'Mars', 'price_kes' => 5, 'observed_on' => today()->addDay()->toDateString()])->assertSessionHasErrors(['market', 'price_kes', 'observed_on']);
    auth()->logout();
    $this->post('/prices/user', [])->assertRedirect('/login');
});

it('shows source, date and licence badge on official prices and warns when stale', function () {
    $src = synthSource();
    app(PriceImporter::class)->import($src, new CsvPriceFeedAdapter(priceCsv([synthRow([6 => today()->subDays(20)->toDateString()])])));
    $this->get('/prices?market=Nakuru')->assertSee('Synthetic test bulletin')->assertSee('Synthetic (invented)')->assertSee('example.invalid')->assertSee(today()->subDays(20)->toDateString())
        ->assertSee('more than 7 days old');
    expect(app(PriceStats::class)->isStale(OfficialPrice::first()))->toBeTrue();
});

it('renders the history chart as inline SVG with a text alternative and no external requests', function () {
    $rows = [];
    foreach (range(0, 5) as $w) {
        $rows[] = synthRow([6 => today()->subDays(1 + $w * 7)->toDateString(), 5 => (string) (2500 + $w * 20)]);
    }
    app(PriceImporter::class)->import(synthSource(), new CsvPriceFeedAdapter(priceCsv($rows)));
    $html = $this->get('/prices?market=Nakuru')->getContent();
    expect($html)->toContain('<svg class="chart"')->toContain('role="img"')->toContain('<desc')->toContain('<path class="line')->not->toMatch('/https?:\/\/(?!localhost)[^"\']*\.(js|css)/');
    expect(substr_count($html, '<circle class="dot"'))->toBe(6);
});

it('notifies farmers and buyers of material official price changes only (prefixed with the label)', function () {
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $t = makeUser(Role::Transporter);
    $src = synthSource();
    app(PriceImporter::class)->import($src, new CsvPriceFeedAdapter(priceCsv([synthRow([6 => today()->subDays(14)->toDateString(), 5 => '2500'])])));
    $f2 = priceCsv([synthRow([6 => today()->subDays(7)->toDateString(), 5 => '2800'])]);
    $this->artisan('fr:prices:import', ['file' => $f2, '--source' => $src->id])->assertSuccessful();
    expect($f->notifications)->toHaveCount(1)->and($b->notifications)->toHaveCount(1)->and($t->notifications)->toHaveCount(0);
    expect(App\Support\NoticeText::render('price_change', $f->notifications->first()->data['params']))->toStartWith('Synthetic price change');
    // a 2% move is not notified
    $f3 = priceCsv([synthRow([6 => today()->subDays(1)->toDateString(), 5 => '2830'])]);
    $this->artisan('fr:prices:import', ['file' => $f3, '--source' => $src->id])->assertSuccessful();
    expect($f->fresh()->notifications)->toHaveCount(1);
});

it('fails the import command for an unlicensed source and reports why', function () {
    $src = synthSource(['licence_status' => 'unconfirmed', 'origin' => 'officially_published', 'name' => 'KAMIS-like (unconfirmed)']);
    $this->artisan('fr:prices:import', ['file' => priceCsv([synthRow()]), '--source' => $src->id])->assertFailed();
    expect(OfficialPrice::count())->toBe(0);
});
