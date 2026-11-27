<?php

use App\Enums\Role;
use App\Models\HarvestLot;
use App\Models\LossEvent;
use App\Services\LossReconciler;
use App\Services\LossReport;
use Illuminate\Validation\ValidationException;

function lossPayload(array $over = []): array
{
    return $over + ['stage' => 'storage', 'category' => 'spoiled', 'bags' => 12, 'reason' => 'rot'];
}

it('records a loss event with stage, reason and category and audits it', function () {
    $f = makeUser(Role::Farmer);
    actingAsUser($f)->post('/losses', lossPayload())->assertRedirect('/losses');
    $e = LossEvent::first();
    expect($e->stage)->toBe('storage')->and($e->bags)->toBe(12)->and($e->commodity_id)->toBe(commodity()->id)->and($e->occurred_on->toDateString())->toBe(today()->toDateString());
    $this->assertDatabaseHas('audit_logs', ['action' => 'loss.recorded']);
});

it('validates loss input', function (array $over, string $field) {
    actingAsUser(makeUser(Role::Farmer))->post('/losses', lossPayload($over))->assertSessionHasErrors($field);
    expect(LossEvent::count())->toBe(0);
})->with([
    'bad stage' => [['stage' => 'dreaming'], 'stage'], 'bad category' => [['category' => 'lost'], 'category'], 'zero bags' => [['bags' => 0], 'bags'],
    'bad reason' => [['reason' => 'aliens'], 'reason'], 'future date' => [['occurred_on' => today()->addDay()->toDateString()], 'occurred_on'], 'missing stage' => [['stage' => ''], 'stage'],
]);

it('reconciles a lot: harvested = sold + damaged + spoiled + rejected + unaccounted, always', function () {
    $f = makeUser(Role::Farmer);
    $lot = HarvestLot::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $f->id, 'commodity_id' => commodity()->id, 'harvested_on' => today()->subDays(10), 'harvested_bags' => 200, 'sold_bags' => 150]);
    $rec = app(LossReconciler::class);
    expect($rec->summary($lot))->toMatchArray(['harvested' => 200, 'sold' => 150, 'unaccounted' => 50, 'balanced' => true]);
    foreach ([['harvest', 'damaged', 6], ['storage', 'spoiled', 12], ['market', 'rejected', 5]] as [$stage, $cat, $bags]) {
        actingAsUser($f)->post('/losses', lossPayload(['stage' => $stage, 'category' => $cat, 'bags' => $bags, 'lot_id' => $lot->id]))->assertSessionDoesntHaveErrors();
        $s = $rec->summary($lot->fresh());
        expect($s['harvested'])->toBe($s['sold'] + $s['damaged'] + $s['spoiled'] + $s['rejected'] + $s['unaccounted'])->and($s['unaccounted'])->toBeGreaterThanOrEqual(0);
    }
    expect($rec->summary($lot->fresh()))->toMatchArray(['damaged' => 6, 'spoiled' => 12, 'rejected' => 5, 'unaccounted' => 27]);
});

it('rejects a loss that would make the lot arithmetic exceed what was harvested', function () {
    $f = makeUser(Role::Farmer);
    $lot = HarvestLot::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $f->id, 'commodity_id' => commodity()->id, 'harvested_on' => today()->subDays(10), 'harvested_bags' => 100, 'sold_bags' => 90]);
    actingAsUser($f)->post('/losses', lossPayload(['bags' => 11, 'lot_id' => $lot->id]))->assertSessionHasErrors('bags');
    actingAsUser($f)->post('/losses', lossPayload(['bags' => 10, 'lot_id' => $lot->id]))->assertSessionDoesntHaveErrors();
    actingAsUser($f)->post('/losses', lossPayload(['bags' => 1, 'lot_id' => $lot->id, 'category' => 'damaged']))->assertSessionHasErrors('bags');
    expect(LossEvent::count())->toBe(1);
    expect(fn () => app(LossReconciler::class)->build(10, 8, 2, 1, 0))->toThrow(ValidationException::class);
});

it('rejects a lot where sold exceeds harvested', function () {
    actingAsUser(makeUser(Role::Farmer))->post('/losses/lots', ['harvested_on' => today()->toDateString(), 'harvested_bags' => 50, 'sold_bags' => 60])->assertSessionHasErrors('sold_bags');
    expect(HarvestLot::count())->toBe(0);
});

it('will not attach a loss to someone else\'s lot', function () {
    $a = makeUser(Role::Farmer);
    $b = makeUser(Role::Farmer);
    $lot = HarvestLot::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $a->id, 'commodity_id' => commodity()->id, 'harvested_on' => today()->subDay(), 'harvested_bags' => 100]);
    actingAsUser($b)->post('/losses', lossPayload(['lot_id' => $lot->id]))->assertSessionHasErrors('lot_id');
});

it('builds reports by commodity and stage and keeps unlinked events out of the rate', function () {
    $f = makeUser(Role::Farmer);
    $lot = HarvestLot::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $f->id, 'commodity_id' => commodity()->id, 'harvested_on' => today()->subDays(5), 'harvested_bags' => 200, 'sold_bags' => 100]);
    foreach ([['harvest', 'damaged', 10, $lot->id], ['storage', 'spoiled', 30, $lot->id], ['storage', 'spoiled', 20, null], ['transport', 'damaged', 40, null]] as $i => [$st, $cat, $b, $lid]) {
        LossEvent::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $f->id, 'commodity_id' => commodity()->id, 'lot_id' => $lid, 'stage' => $st, 'category' => $cat, 'bags' => $b, 'reason' => 'rot', 'occurred_on' => today()->subDays($i)]);
    }
    $r = app(LossReport::class)->build([$f->id]);
    expect($r['total_lost_bags'])->toBe(100)->and($r['by_stage']['storage']['bags'])->toBe(50)->and($r['by_stage']['storage']['share'])->toBe(50.0)
        ->and($r['by_stage']['transport']['bags'])->toBe(40)->and($r['by_stage']['market']['bags'])->toBe(0)
        ->and($r['harvested_bags_in_lots'])->toBe(200)->and($r['lot_linked_loss_bags'])->toBe(40)->and($r['loss_rate_pct'])->toBe(20.0)
        ->and($r['by_commodity'])->toBe(['Irish potato' => 100])->and($r['label'])->toBe('farmer-reported');
    expect(array_sum(array_column($r['by_stage'], 'bags')))->toBe($r['total_lost_bags']);
});

it('exports the loss report as CSV with the self-reported label and only own data', function () {
    $a = makeUser(Role::Farmer);
    $b = makeUser(Role::Farmer);
    foreach ([[$a, 7], [$b, 999]] as [$u, $bags]) {
        LossEvent::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $u->id, 'commodity_id' => commodity()->id, 'stage' => 'market', 'category' => 'rejected', 'bags' => $bags, 'reason' => 'greening', 'occurred_on' => today()]);
    }
    $res = actingAsUser($a)->get('/losses/export.csv');
    $res->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $res->getContent();
    expect($csv)->toContain('commodity,stage,category,reason,bags,events,data_label')->toContain('"Irish potato",market,rejected,greening,7,1,farmer-reported')->not->toContain('999');
});

it('scopes the cooperative loss report to its own members and consenting farmers only', function () {
    $coop = makeCoop();
    $other = makeCoop();
    $admin = mfaUser(Role::CoopAdmin, ['coop_id' => $coop->id]);
    $member = makeUser(Role::Farmer, ['coop_id' => $coop->id]);
    $outsider = makeUser(Role::Farmer, ['coop_id' => $other->id]);
    $solo = makeUser(Role::Farmer);
    foreach ([[$member, 11], [$outsider, 222], [$solo, 333]] as [$u, $bags]) {
        LossEvent::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $u->id, 'commodity_id' => commodity()->id, 'stage' => 'storage', 'category' => 'spoiled', 'bags' => $bags, 'reason' => 'rot', 'occurred_on' => today()]);
    }
    $res = actingAsUser($admin)->get('/coop/reports');
    expect($res->viewData('report')['total_lost_bags'])->toBe(11);
    expect(actingAsUser($admin)->get('/coop/export.csv')->getContent())->not->toContain('222')->not->toContain('333');
    actingAsUser($admin)->get('/coop/members')->assertSee($member->name)->assertDontSee($outsider->name)->assertDontSee($solo->name);
});
