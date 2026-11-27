<?php

use App\Enums\Role;
use App\Models\BuyerNeed;
use App\Models\Enquiry;
use App\Models\IdempotencyKey;
use App\Models\Listing;
use App\Models\LossEvent;
use App\Models\UserPrice;
use App\Services\SyncProcessor;
use Illuminate\Database\UniqueConstraintViolationException;

function op(string $type, array $payload, ?string $uuid = null, ?string $captured = null): array
{
    return ['uuid' => $uuid ?? (string) Str::uuid(), 'type' => $type, 'captured_at' => $captured ?? now()->toIso8601String(), 'payload' => $payload];
}

function listingOp(array $over = [], ?string $uuid = null, ?string $captured = null): array
{
    return op('listing', $over + ['quantity_bags' => 80, 'harvest_date' => today()->addDays(2)->toDateString(), 'quality_grade' => 'B', 'price_kes' => '2400', 'county' => 'Nakuru', 'place' => 'Molo'], $uuid, $captured);
}

it('creates records captured offline (listing, loss, enquiry, need, price) in one batch', function () {
    $f = makeUser(Role::Farmer);
    $res = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [
        listingOp(),
        op('loss', ['stage' => 'storage', 'category' => 'spoiled', 'bags' => 5, 'reason' => 'rot']),
        op('price', ['market' => 'Nakuru', 'price_kes' => '2700', 'observed_on' => today()->toDateString()]),
    ]])->assertOk();
    expect(collect($res->json('results'))->pluck('status')->all())->toBe(['created', 'created', 'created'])
        ->and(Listing::count())->toBe(1)->and(LossEvent::count())->toBe(1)->and(UserPrice::count())->toBe(1);
    $b = makeUser(Role::Buyer);
    $l = Listing::first();
    $r2 = actingAsUser($b)->postJson('/api/v1/sync', ['ops' => [
        op('enquiry', ['listing_id' => $l->id, 'quantity_bags' => 10]),
        op('need', ['quantity_bags' => 50, 'county' => 'Nakuru', 'needed_by' => today()->addDays(5)->toDateString(), 'min_grade' => 'B']),
    ]])->assertOk();
    expect(collect($r2->json('results'))->pluck('status')->all())->toBe(['created', 'created'])->and(Enquiry::count())->toBe(1)->and(BuyerNeed::count())->toBe(1);
});

it('is idempotent: replaying the same batch any number of times never duplicates', function () {
    $f = makeUser(Role::Farmer);
    $ops = [listingOp(), op('loss', ['stage' => 'market', 'category' => 'rejected', 'bags' => 3, 'reason' => 'greening'])];
    $first = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => $ops])->json('results');
    foreach (range(1, 4) as $_) {
        $again = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => $ops])->json('results');
        expect(collect($again)->pluck('status')->unique()->all())->toBe(['duplicate']);
        expect(collect($again)->pluck('id')->all())->toBe(collect($first)->pluck('id')->all());
    }
    expect(Listing::count())->toBe(1)->and(LossEvent::count())->toBe(1)->and(IdempotencyKey::count())->toBe(2);
});

it('is idempotent even when the idempotency record is lost (unique client_uuid on the entity)', function () {
    $f = makeUser(Role::Farmer);
    $o = listingOp();
    actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [$o]]);
    IdempotencyKey::query()->delete();
    $r = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [$o]])->json('results.0');
    expect(Listing::count())->toBe(1)->and($r['status'])->toBe('created'); // action returned the existing row
});

it('remembers rejections so a bad op is not retried forever, and reports the errors', function () {
    $f = makeUser(Role::Farmer);
    $bad = listingOp(['quantity_bags' => 0]);
    $r1 = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [$bad]])->json('results.0');
    expect($r1['status'])->toBe('rejected')->and($r1['errors'])->toHaveKey('quantity_bags');
    $r2 = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [$bad]])->json('results.0');
    expect($r2['status'])->toBe('rejected')->and(Listing::count())->toBe(0);
});

it('processes each op independently: one bad op does not block the others', function () {
    $f = makeUser(Role::Farmer);
    $res = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [listingOp(['price_kes' => 1]), listingOp(), ['nonsense' => true], op('hack', [])]])->json('results');
    expect(collect($res)->pluck('status')->all())->toBe(['rejected', 'created', 'rejected', 'rejected'])->and(Listing::count())->toBe(1);
});

it('validates offline listings against the capture day, not the sync day', function () {
    $f = makeUser(Role::Farmer);
    $captured = now()->subDays(2)->toIso8601String();
    $harvestYesterday = today()->subDay()->toDateString(); // valid on the capture day, past by sync time
    $r = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [listingOp(['harvest_date' => $harvestYesterday], null, $captured)]])->json('results.0');
    expect($r['status'])->toBe('created');
    $r2 = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [listingOp(['harvest_date' => today()->subDays(5)->toDateString()], null, $captured)]])->json('results.0');
    expect($r2['status'])->toBe('rejected');
    // a captured_at in the future cannot be used to bypass validation
    $r3 = actingAsUser($f)->postJson('/api/v1/sync', ['ops' => [listingOp(['harvest_date' => today()->subDay()->toDateString()], null, now()->addYear()->toIso8601String())]])->json('results.0');
    expect($r3['status'])->toBe('rejected');
});

it('enforces roles and ownership inside sync: a buyer cannot create listings, keys are per user', function () {
    $b = makeUser(Role::Buyer);
    $r = actingAsUser($b)->postJson('/api/v1/sync', ['ops' => [listingOp()]])->json('results.0');
    expect($r['status'])->toBe('rejected')->and(Listing::count())->toBe(0);
    $f1 = makeUser(Role::Farmer);
    $f2 = makeUser(Role::Farmer);
    $same = (string) Str::uuid();
    actingAsUser($f1)->postJson('/api/v1/sync', ['ops' => [listingOp([], $same)]]);
    $r2 = actingAsUser($f2)->postJson('/api/v1/sync', ['ops' => [listingOp([], $same)]])->json('results.0');
    expect($r2['status'])->toBe('created')->and(Listing::count())->toBe(2)->and(Listing::pluck('user_id')->sort()->values()->all())->toBe([$f1->id, $f2->id]);
});

it('caps batch size and rejects malformed bodies', function () {
    $f = makeUser(Role::Farmer);
    actingAsUser($f)->postJson('/api/v1/sync', ['ops' => range(1, 51)])->assertStatus(422);
    actingAsUser($f)->postJson('/api/v1/sync', [])->assertStatus(422);
    actingAsUser($f)->postJson('/api/v1/sync', ['ops' => 'x'])->assertStatus(422);
});

it('has a unique index backing idempotency at the database level', function () {
    $f = makeUser(Role::Farmer);
    $u = (string) Str::uuid();
    makeListing($f, ['client_uuid' => $u]);
    expect(fn () => makeListing($f, ['client_uuid' => $u]))->toThrow(UniqueConstraintViolationException::class);
});

it('exposes a session endpoint with a fresh CSRF token for the queue to use', function () {
    $f = makeUser(Role::Farmer);
    $j = actingAsUser($f)->getJson('/api/v1/session')->assertOk()->json();
    expect($j['authenticated'])->toBeTrue()->and($j['csrf'])->not->toBeEmpty()->and($j['role'])->toBe('farmer');
});

it('lists the sync types it supports', function () {
    expect(SyncProcessor::TYPES)->toBe(['listing', 'enquiry', 'loss', 'lot', 'need', 'price']);
});
