<?php

use App\Enums\DeliveryStatus;
use App\Enums\Role;
use App\Models\DeliveryRequest;
use App\Models\StorageFacility;
use App\Models\StorageRequest;
use App\Models\TransporterProfile;

function deliveryPayload(int $profileId, array $over = []): array
{
    return $over + ['transporter_profile_id' => $profileId, 'bags' => 40, 'pickup_place' => 'Molo', 'dropoff_place' => 'Nakuru market', 'pickup_date' => today()->addDays(2)->toDateString()];
}

it('registers a transporter profile and lists it', function () {
    $t = makeUser(Role::Transporter);
    actingAsUser($t)->post('/transport/profile', ['company_name' => 'Kariuki Haulage', 'vehicle_type' => 'canter', 'capacity_bags' => 60, 'routes' => 'Ol Kalou - Nakuru', 'price_kes' => 14000, 'availability_note' => 'Mon-Sat'])->assertRedirect();
    $p = TransporterProfile::first();
    expect($p->base_price_minor)->toBe(1400000)->and($p->user_id)->toBe($t->id);
    actingAsUser(makeUser(Role::Farmer))->get('/transport')->assertSee('Kariuki Haulage');
    // updating again updates rather than duplicates
    actingAsUser($t)->post('/transport/profile', ['company_name' => 'Kariuki Haulage', 'vehicle_type' => 'lorry', 'capacity_bags' => 200, 'routes' => 'x', 'price_kes' => 1, 'active' => '1']);
    expect(TransporterProfile::count())->toBe(1)->and(TransporterProfile::first()->vehicle_type)->toBe('lorry');
});

it('validates the transporter profile', function () {
    actingAsUser(makeUser(Role::Transporter))->post('/transport/profile', ['company_name' => '', 'vehicle_type' => 'rocket', 'capacity_bags' => 0, 'routes' => '', 'price_kes' => -1])
        ->assertSessionHasErrors(['company_name', 'vehicle_type', 'capacity_bags', 'routes', 'price_kes']);
});

it('creates a delivery request, notifies the carrier and tracks status through the happy path', function () {
    $farmer = makeUser(Role::Farmer);
    $carrier = makeUser(Role::Transporter);
    $p = makeTransporter($carrier);
    $listing = makeListing($farmer);
    actingAsUser($farmer)->post('/deliveries', deliveryPayload($p->id, ['listing_id' => $listing->id]))->assertRedirect();
    $d = DeliveryRequest::first();
    expect($d->status)->toBe(DeliveryStatus::Requested)->and($carrier->notifications()->count())->toBe(1);
    foreach (['accepted', 'picked_up', 'delivered'] as $s) {
        actingAsUser($carrier)->post("/deliveries/{$d->id}/status", ['status' => $s])->assertRedirect();
        expect($d->fresh()->status->value)->toBe($s);
    }
    expect($farmer->notifications()->count())->toBe(3);
    actingAsUser($carrier)->post("/deliveries/{$d->id}/status", ['status' => 'accepted'])->assertSessionHasErrors('status');
});

it('implements the delivery state machine exhaustively', function () {
    $allowed = ['requested' => ['accepted', 'declined', 'cancelled'], 'accepted' => ['picked_up', 'cancelled'], 'picked_up' => ['delivered'], 'delivered' => [], 'declined' => [], 'cancelled' => []];
    foreach (DeliveryStatus::cases() as $from) {
        foreach (DeliveryStatus::cases() as $to) {
            expect($from->canTransitionTo($to))->toBe(in_array($to->value, $allowed[$from->value], true), "{$from->value} -> {$to->value}");
        }
    }
});

it('enforces who may change delivery status', function () {
    $farmer = makeUser(Role::Farmer);
    $carrier = makeUser(Role::Transporter);
    $other = makeUser(Role::Transporter);
    $p = makeTransporter($carrier);
    makeTransporter($other, ['company_name' => 'Other']);
    $d = DeliveryRequest::create(['client_uuid' => (string) Str::uuid(), 'requester_id' => $farmer->id, 'transporter_profile_id' => $p->id, 'bags' => 10, 'pickup_place' => 'a', 'dropoff_place' => 'b', 'pickup_date' => today()->addDay(), 'status' => 'requested']);
    actingAsUser($farmer)->post("/deliveries/{$d->id}/status", ['status' => 'accepted'])->assertForbidden();       // requester cannot accept
    actingAsUser($other)->post("/deliveries/{$d->id}/status", ['status' => 'accepted'])->assertForbidden();         // another carrier cannot
    actingAsUser($other)->get("/deliveries/{$d->id}")->assertForbidden();
    actingAsUser(makeUser(Role::Farmer))->get("/deliveries/{$d->id}")->assertForbidden();
    actingAsUser($carrier)->post("/deliveries/{$d->id}/status", ['status' => 'cancelled'])->assertForbidden();      // carrier cannot cancel on behalf of requester
    actingAsUser($farmer)->post("/deliveries/{$d->id}/status", ['status' => 'cancelled'])->assertRedirect();
    expect($d->fresh()->status)->toBe(DeliveryStatus::Cancelled);
});

it('validates delivery requests: capacity, dates, listing ownership', function () {
    $farmer = makeUser(Role::Farmer);
    $p = makeTransporter(makeUser(Role::Transporter), ['capacity_bags' => 50]);
    $theirs = makeListing(makeUser(Role::Farmer));
    actingAsUser($farmer)->post('/deliveries', deliveryPayload($p->id, ['bags' => 51]))->assertSessionHasErrors('bags');
    actingAsUser($farmer)->post('/deliveries', deliveryPayload($p->id, ['pickup_date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('pickup_date');
    actingAsUser($farmer)->post('/deliveries', deliveryPayload($p->id, ['listing_id' => $theirs->id]))->assertSessionHasErrors('listing_id');
    actingAsUser($farmer)->post('/deliveries', deliveryPayload(99999))->assertSessionHasErrors('transporter_profile_id');
    expect(DeliveryRequest::count())->toBe(0);
});

it('hides inactive transporters', function () {
    $p = makeTransporter(makeUser(Role::Transporter), ['active' => false]);
    actingAsUser(makeUser(Role::Farmer))->post('/deliveries', deliveryPayload($p->id))->assertStatus(404);
});

it('lets a storage provider register and manage only their own facilities', function () {
    $s = makeUser(Role::StorageProvider);
    $other = makeUser(Role::StorageProvider);
    actingAsUser($s)->post('/storage', ['name' => 'Njoro Cold', 'county' => 'Nakuru', 'place' => 'Njoro', 'capacity_bags' => 4000, 'free_bags' => 1200, 'temp_min_c' => 4, 'temp_max_c' => 8, 'price_kes' => 40, 'contact_phone' => '0722000002'])->assertRedirect('/storage/mine');
    $f = StorageFacility::first();
    expect($f->temperature_status)->toBe('self_declared')->and($f->price_minor)->toBe(4000)->and($f->user_id)->toBe($s->id);
    actingAsUser($other)->get("/storage/{$f->id}/edit")->assertForbidden();
    actingAsUser($other)->put("/storage/{$f->id}", ['name' => 'hijack', 'county' => 'Nakuru', 'place' => 'x', 'capacity_bags' => 1, 'free_bags' => 0, 'price_kes' => 1])->assertForbidden();
    expect($f->fresh()->name)->toBe('Njoro Cold');
    actingAsUser($other)->get('/storage/mine')->assertDontSee('Njoro Cold');
});

it('validates storage input: free space cannot exceed capacity, temperature range', function () {
    $s = makeUser(Role::StorageProvider);
    $ok = ['name' => 'X', 'county' => 'Nakuru', 'place' => 'x', 'capacity_bags' => 100, 'free_bags' => 50, 'price_kes' => 10];
    actingAsUser($s)->post('/storage', ['free_bags' => 101] + $ok)->assertSessionHasErrors('free_bags');
    actingAsUser($s)->post('/storage', ['temp_min_c' => 8, 'temp_max_c' => 4] + $ok)->assertSessionHasErrors('temp_max_c');
    actingAsUser($s)->post('/storage', ['contact_phone' => 'abc'] + $ok)->assertSessionHasErrors('contact_phone');
});

it('keeps temperature self-declared until staff verify, and resets it when the claim changes', function () {
    $s = makeUser(Role::StorageProvider);
    $admin = mfaUser(Role::PlatformAdmin);
    $f = makeFacility($s, ['temp_min_c' => 4, 'temp_max_c' => 8]);
    actingAsUser($s)->post('/admin/verify', ['type' => 'storage', 'id' => $f->id])->assertForbidden();
    actingAsUser($admin)->post('/admin/verify', ['type' => 'storage', 'id' => $f->id])->assertRedirect();
    expect($f->fresh()->temperature_status)->toBe('verified');
    actingAsUser($s)->put("/storage/{$f->id}", ['name' => 'Cold Store', 'county' => 'Nakuru', 'place' => 'Njoro', 'capacity_bags' => 1000, 'free_bags' => 500, 'temp_min_c' => 2, 'temp_max_c' => 6, 'price_kes' => 40]);
    expect($f->fresh()->temperature_status)->toBe('self_declared')->and($f->fresh()->verified_at)->toBeNull();
    actingAsUser(makeUser(Role::Farmer))->get('/storage')->assertSee('Self-declared')->assertDontSee('>Verified<', false);
});

it('handles space requests and notifies farmers when full storage frees up', function () {
    $s = makeUser(Role::StorageProvider);
    $farmer = makeUser(Role::Farmer, ['county' => 'Nakuru']);
    $far = makeUser(Role::Farmer, ['county' => 'Nyandarua']);
    $f = makeFacility($s, ['free_bags' => 0]);
    actingAsUser($farmer)->post("/storage/{$f->id}/request", ['bags' => 5, 'from_date' => today()->addDay()->toDateString()])->assertSessionHasErrors('bags'); // full
    actingAsUser($s)->put("/storage/{$f->id}", ['name' => 'Cold Store', 'county' => 'Nakuru', 'place' => 'Njoro', 'capacity_bags' => 1000, 'free_bags' => 200, 'price_kes' => 40]);
    expect($farmer->notifications->pluck('data.type')->all())->toBe(['storage_available'])->and($far->notifications()->count())->toBe(0);
    actingAsUser($farmer)->post("/storage/{$f->id}/request", ['bags' => 50, 'from_date' => today()->addDay()->toDateString()])->assertRedirect();
    $r = StorageRequest::first();
    actingAsUser($farmer)->post("/storage-requests/{$r->id}/status", ['status' => 'accepted'])->assertForbidden();
    actingAsUser(makeUser(Role::StorageProvider))->post("/storage-requests/{$r->id}/status", ['status' => 'accepted'])->assertForbidden();
    actingAsUser($s)->post("/storage-requests/{$r->id}/status", ['status' => 'accepted'])->assertRedirect();
    expect($r->fresh()->status)->toBe('accepted');
    actingAsUser($s)->post("/storage-requests/{$r->id}/status", ['status' => 'declined'])->assertStatus(422);
});
