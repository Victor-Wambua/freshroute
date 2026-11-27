<?php

use App\Enums\EnquiryStatus;
use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\Listing;
use App\Services\Transitions;
use Illuminate\Validation\ValidationException;

it('creates a listing with all spec fields, stores price in minor units, and publishes it', function () {
    $f = makeUser(Role::Farmer);
    actingAsUser($f)->post('/listings', listingPayload(['available_to' => today()->addDays(20)->toDateString()]))->assertRedirect();
    $l = Listing::first();
    expect($l->price_minor)->toBe(260000)->and($l->status)->toBe(ListingStatus::Active)->and($l->quality_grade)->toBe('A')
        ->and($l->available_to->toDateString())->toBe(today()->addDays(20)->toDateString())->and($l->user_id)->toBe($f->id)
        ->and($l->storage_requirements)->toBe('Cool and dry');
    $this->assertDatabaseHas('audit_logs', ['action' => 'listing.created', 'subject_id' => $l->id]);
});

it('validates listing input server-side', function (array $over, string $field) {
    actingAsUser(makeUser(Role::Farmer))->post('/listings', listingPayload($over))->assertSessionHasErrors($field);
    expect(Listing::count())->toBe(0);
})->with([
    'zero bags' => [['quantity_bags' => 0], 'quantity_bags'],
    'too many bags' => [['quantity_bags' => 5001], 'quantity_bags'],
    'past harvest date' => [['harvest_date' => today()->subDay()->toDateString()], 'harvest_date'],
    'bad grade' => [['quality_grade' => 'Z'], 'quality_grade'],
    'price too low' => [['price_kes' => 100], 'price_kes'],
    'price too high' => [['price_kes' => 25000], 'price_kes'],
    'county outside MVP' => [['county' => 'Kisumu'], 'county'],
    'window before harvest' => [['available_to' => today()->toDateString()], 'available_to'],
    'missing place' => [['place' => ''], 'place'],
]);

it('is idempotent on client_uuid when the form is double submitted', function () {
    $f = makeUser(Role::Farmer);
    $uuid = (string) Str::uuid();
    actingAsUser($f)->post('/listings', listingPayload(['client_uuid' => $uuid]));
    actingAsUser($f)->post('/listings', listingPayload(['client_uuid' => $uuid]));
    expect(Listing::count())->toBe(1);
});

it('lets the owner edit and refuses everyone else', function () {
    $a = makeUser(Role::Farmer);
    $b = makeUser(Role::Farmer);
    $l = makeListing($a);
    actingAsUser($a)->put("/listings/{$l->id}", listingPayload(['price_kes' => 2700, 'quantity_bags' => 90]))->assertRedirect();
    expect($l->fresh()->price_minor)->toBe(270000)->and($l->fresh()->quantity_bags)->toBe(90);
    actingAsUser($b)->put("/listings/{$l->id}", listingPayload(['price_kes' => 9999]))->assertForbidden();
    actingAsUser($b)->get("/listings/{$l->id}/edit")->assertForbidden();
    actingAsUser($b)->post("/listings/{$l->id}/status", ['status' => 'withdrawn'])->assertForbidden();
    actingAsUser($b)->delete("/listings/{$l->id}")->assertForbidden();
    expect($l->fresh()->price_minor)->toBe(270000)->and($l->fresh()->status)->toBe(ListingStatus::Active);
});

it('does not let an owner reduce a listing below bags already agreed', function () {
    $a = makeUser(Role::Farmer);
    $l = makeListing($a, ['quantity_bags' => 100]);
    makeEnquiry($l, makeUser(Role::Buyer), ['quantity_bags' => 60, 'status' => 'agreed']);
    actingAsUser($a)->put("/listings/{$l->id}", listingPayload(['quantity_bags' => 50]))->assertSessionHasErrors('quantity_bags');
    expect($l->fresh()->availableBags())->toBe(40);
});

it('implements the listing state machine exhaustively', function () {
    $allowed = [
        'draft' => ['active', 'withdrawn'], 'active' => ['reserved', 'sold', 'expired', 'withdrawn'], 'reserved' => ['active', 'sold', 'withdrawn'],
        'expired' => ['active', 'withdrawn'], 'sold' => [], 'withdrawn' => [],
    ];
    foreach (ListingStatus::cases() as $from) {
        foreach (ListingStatus::cases() as $to) {
            $expected = in_array($to->value, $allowed[$from->value], true);
            expect($from->canTransitionTo($to))->toBe($expected, "{$from->value} -> {$to->value}");
        }
    }
});

it('refuses illegal listing transitions through the service and the HTTP endpoint', function () {
    $f = makeUser(Role::Farmer);
    $l = makeListing($f, ['status' => 'sold']);
    expect(fn () => app(Transitions::class)->listing($l, ListingStatus::Active, $f))->toThrow(ValidationException::class);
    actingAsUser($f)->post("/listings/{$l->id}/status", ['status' => 'active'])->assertSessionHasErrors('status');
    actingAsUser($f)->post("/listings/{$l->id}/status", ['status' => 'bogus'])->assertSessionHasErrors('status');
    expect($l->fresh()->status)->toBe(ListingStatus::Sold);
});

it('only allows deleting a draft or withdrawn listing that has no enquiries', function () {
    $f = makeUser(Role::Farmer);
    $live = makeListing($f);
    actingAsUser($f)->delete("/listings/{$live->id}")->assertSessionHasErrors('status');
    $draft = makeListing($f, ['status' => 'draft']);
    actingAsUser($f)->delete("/listings/{$draft->id}")->assertRedirect('/my/listings');
    expect(Listing::find($draft->id))->toBeNull()->and(Listing::find($live->id))->not->toBeNull();
    $withdrawn = makeListing($f, ['status' => 'withdrawn']);
    makeEnquiry($withdrawn, makeUser(Role::Buyer));
    actingAsUser($f)->delete("/listings/{$withdrawn->id}")->assertSessionHasErrors('status');
});

it('hides non-open listings from buyers but shows open ones', function () {
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $open = makeListing($f);
    $draft = makeListing($f, ['status' => 'draft']);
    actingAsUser($b)->get("/market/{$open->id}")->assertOk();
    actingAsUser($b)->get("/market/{$draft->id}")->assertForbidden();
});

it('expires listings whose availability window has ended', function () {
    $f = makeUser(Role::Farmer);
    $old = makeListing($f, ['available_to' => today()->subDay()]);
    $keep = makeListing($f, ['available_to' => today()->addDay()]);
    $this->artisan('fr:expire-listings')->assertSuccessful();
    expect($old->fresh()->status)->toBe(ListingStatus::Expired)->and($keep->fresh()->status)->toBe(ListingStatus::Active);
});

it('searches and filters the buyer marketplace by place, county, quantity and grade', function () {
    $b = makeUser(Role::Buyer);
    $f = makeUser(Role::Farmer);
    makeListing($f, ['place' => 'Molo', 'county' => 'Nakuru', 'quantity_bags' => 80, 'quality_grade' => 'B', 'price_minor' => 230000]);
    makeListing($f, ['place' => 'Kinangop', 'county' => 'Nyandarua', 'quantity_bags' => 300, 'quality_grade' => 'A', 'price_minor' => 275000]);
    makeListing($f, ['place' => 'Njoro', 'county' => 'Nakuru', 'quantity_bags' => 450, 'quality_grade' => 'A', 'price_minor' => 268000]);
    $count = fn (string $qs) => (int) (actingAsUser($b)->get('/market?'.$qs)->viewData('listings')->total());
    expect($count(''))->toBe(3)->and($count('county=Nakuru'))->toBe(2)->and($count('min_bags=300'))->toBe(2)->and($count('grade[]=B'))->toBe(1)
        ->and($count('q=Kinangop'))->toBe(1)->and($count('q=zzz'))->toBe(0)->and($count('county=Nakuru&grade[]=A&min_bags=100'))->toBe(1);
    $sorted = actingAsUser($b)->get('/market?sort=price_asc')->viewData('listings')->pluck('price_minor')->all();
    expect($sorted)->toBe([230000, 268000, 275000]);
});

it('treats LIKE wildcards in search as literals', function () {
    $b = makeUser(Role::Buyer);
    makeListing(makeUser(Role::Farmer), ['place' => 'Molo']);
    expect(actingAsUser($b)->get('/market?q=%25')->viewData('listings')->total())->toBe(0);
});

it('compares up to three listings', function () {
    $b = makeUser(Role::Buyer);
    $f = makeUser(Role::Farmer);
    $ids = collect(range(1, 4))->map(fn () => makeListing($f)->id)->all();
    $res = actingAsUser($b)->get('/market/compare?'.http_build_query(['ids' => $ids]));
    expect($res->viewData('listings'))->toHaveCount(3);
});

it('manages saved suppliers per buyer', function () {
    $f = makeUser(Role::Farmer);
    $b1 = makeUser(Role::Buyer);
    $b2 = makeUser(Role::Buyer);
    actingAsUser($b1)->post("/saved/{$f->id}")->assertRedirect();
    expect($b1->savedSuppliers()->count())->toBe(1)->and($b2->savedSuppliers()->count())->toBe(0);
    actingAsUser($b1)->get('/saved')->assertOk()->assertSee($f->name);
    actingAsUser($b2)->get('/saved')->assertOk()->assertDontSee($f->name);
    actingAsUser($b1)->post("/saved/{$f->id}");
    expect($b1->savedSuppliers()->count())->toBe(0);
    actingAsUser($b1)->post('/saved/'.makeUser(Role::Buyer)->id)->assertNotFound();
});

it('keeps the farmer phone hidden until the farmer replies', function () {
    $f = makeUser(Role::Farmer, ['phone' => '0799123456']);
    $b = makeUser(Role::Buyer);
    $l = makeListing($f);
    actingAsUser($b)->get("/market/{$l->id}")->assertDontSee('0799123456');
    $e = makeEnquiry($l, $b, ['status' => 'replied']);
    actingAsUser($b)->get("/market/{$l->id}")->assertSee('0799123456');
});

it('renders a localised, accessible listing page with origin badges', function () {
    $f = makeUser(Role::Farmer, ['origin' => 'synthetic']);
    $l = makeListing($f, ['origin' => 'synthetic']);
    actingAsUser(makeUser(Role::Buyer))->get("/market/{$l->id}")->assertSee('Synthetic');
});
