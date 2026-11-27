<?php

use App\Enums\Role;
use App\Models\Commodity;
use App\Services\MatchScorer;

function scoreFor(array $listing = [], array $need = []): array
{
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $l = makeListing($f, $listing);
    $n = makeNeed($b, $need);

    return app(MatchScorer::class)->score($l->fresh(), $n->fresh());
}

it('gives a perfect score to a listing that satisfies every dimension', function () {
    $r = scoreFor(['quantity_bags' => 150, 'county' => 'Nakuru', 'quality_grade' => 'A', 'price_minor' => 250000], ['max_price_minor' => 280000, 'min_grade' => 'B']);
    expect($r['score'])->toBe(100)->and(collect($r['parts'])->pluck('key')->all())->toBe(['quantity', 'price', 'location', 'grade', 'timing']);
});

it('explains every point: parts sum to the total and never exceed their maximum', function () {
    $r = scoreFor(['quantity_bags' => 40, 'county' => 'Nyandarua', 'quality_grade' => 'C', 'price_minor' => 300000, 'harvest_date' => today()->addDays(10), 'available_from' => today()->addDays(10)], ['max_price_minor' => 280000, 'min_grade' => 'B', 'needed_by' => today()->addDays(7)]);
    expect(array_sum(array_column($r['parts'], 'points')))->toBe($r['score']);
    foreach ($r['parts'] as $p) {
        expect($p['points'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual($p['max'])->and($p['text'])->not->toBeEmpty();
    }
    $byKey = collect($r['parts'])->keyBy('key');
    expect($byKey['quantity']['points'])->toBe(12)->and($byKey['price']['points'])->toBe(10)->and($byKey['location']['points'])->toBe(8)->and($byKey['grade']['points'])->toBe(0)->and($byKey['timing']['points'])->toBe(4);
    expect($r['score'])->toBe(34);
});

it('scores quantity proportionally to coverage', function (int $bags, int $expected) {
    $r = scoreFor(['quantity_bags' => $bags], ['quantity_bags' => 100]);
    expect(collect($r['parts'])->firstWhere('key', 'quantity')['points'])->toBe($expected);
})->with([[100, 30], [200, 30], [50, 15], [10, 3], [1, 0]]);

it('scores price: within limit, slightly over, far over, and no limit', function (?int $max, int $ask, int $expected) {
    $r = scoreFor(['price_minor' => $ask], ['max_price_minor' => $max]);
    expect(collect($r['parts'])->firstWhere('key', 'price')['points'])->toBe($expected);
})->with([[280000, 270000, 25], [280000, 280000, 25], [280000, 300000, 10], [280000, 400000, 0], [null, 999000, 15]]);

it('scores location, grade and timing', function () {
    $loc = fn ($c) => collect(scoreFor(['county' => $c], ['county' => 'Nakuru'])['parts'])->firstWhere('key', 'location')['points'];
    expect($loc('Nakuru'))->toBe(20)->and($loc('Nyandarua'))->toBe(8);
    $grade = fn ($g) => collect(scoreFor(['quality_grade' => $g], ['min_grade' => 'B'])['parts'])->firstWhere('key', 'grade')['points'];
    expect($grade('A'))->toBe(15)->and($grade('B'))->toBe(15)->and($grade('C'))->toBe(0);
    $time = fn ($d) => collect(scoreFor(['harvest_date' => today()->addDays($d), 'available_from' => today()->addDays($d)], ['needed_by' => today()->addDays(7)])['parts'])->firstWhere('key', 'timing')['points'];
    expect($time(5))->toBe(10)->and($time(7))->toBe(10)->and($time(10))->toBe(4)->and($time(30))->toBe(0);
});

it('never matches different commodities, closed listings or closed needs', function () {
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $scorer = app(MatchScorer::class);
    $l = makeListing($f);
    $n = makeNeed($b);
    expect($scorer->score($l->fresh(), $n->fresh()))->not->toBeNull();
    $l->update(['status' => 'sold']);
    expect($scorer->score($l->fresh(), $n->fresh()))->toBeNull();
    $l->update(['status' => 'active']);
    $n->update(['status' => 'closed']);
    expect($scorer->score($l->fresh(), $n->fresh()))->toBeNull();
    $n->update(['status' => 'open', 'commodity_id' => Commodity::create(['slug' => 'maize', 'name_en' => 'Maize', 'name_sw' => 'Mahindi', 'unit_en' => 'bag', 'unit_sw' => 'gunia', 'unit_kg' => 90])->id]);
    expect($scorer->score($l->fresh(), $n->fresh()))->toBeNull();
});

it('ranks matches best first for a need and for a listing', function () {
    $b = makeUser(Role::Buyer);
    $need = makeNeed($b, ['quantity_bags' => 100, 'county' => 'Nakuru', 'min_grade' => 'B', 'max_price_minor' => 280000]);
    $good = makeListing(makeUser(Role::Farmer), ['quantity_bags' => 100, 'county' => 'Nakuru', 'quality_grade' => 'A', 'price_minor' => 260000]);
    $meh = makeListing(makeUser(Role::Farmer), ['quantity_bags' => 30, 'county' => 'Nyandarua', 'quality_grade' => 'B', 'price_minor' => 290000]);
    $m = app(MatchScorer::class)->matchesForNeed($need);
    expect($m->pluck('listing.id')->all())->toBe([$good->id, $meh->id])->and($m[0]['score'])->toBeGreaterThan($m[1]['score']);
    expect(app(MatchScorer::class)->matchesForListing($good)->first()['need']->id)->toBe($need->id);
});

it('renders match explanations on the farmer listing page and the buyer need page', function () {
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $l = makeListing($f, ['quantity_bags' => 100, 'county' => 'Nakuru']);
    $n = makeNeed($b, ['quantity_bags' => 100]);
    actingAsUser($f)->get("/listings/{$l->id}")->assertSee('Quantity: 100 bags available for 100 needed')->assertSee('/100', false);
    actingAsUser($b)->get("/needs/{$n->id}")->assertSee('Location: both in Nakuru');
    actingAsUser(makeUser(Role::Buyer))->get("/needs/{$n->id}")->assertForbidden();
});
