<?php

use App\Enums\EnquiryStatus;
use App\Enums\Role;
use App\Models\Enquiry;
use App\Services\Transitions;
use Illuminate\Validation\ValidationException;

it('lets a buyer enquire and notifies the farmer', function () {
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer, ['business_name' => 'Greens Ltd']);
    $l = makeListing($f, ['quantity_bags' => 100]);
    actingAsUser($b)->post('/enquiries', ['listing_id' => $l->id, 'quantity_bags' => 40, 'message' => 'Friday pickup?'])->assertRedirect();
    $e = Enquiry::first();
    expect($e->status)->toBe(EnquiryStatus::Sent)->and($e->buyer_id)->toBe($b->id);
    expect($f->notifications)->toHaveCount(1)->and($f->notifications->first()->data['type'])->toBe('new_enquiry');
});

it('validates enquiry quantity against what is still available', function () {
    $b = makeUser(Role::Buyer);
    $l = makeListing(makeUser(Role::Farmer), ['quantity_bags' => 100]);
    makeEnquiry($l, makeUser(Role::Buyer), ['quantity_bags' => 70, 'status' => 'agreed']);
    actingAsUser($b)->post('/enquiries', ['listing_id' => $l->id, 'quantity_bags' => 31])->assertSessionHasErrors('quantity_bags');
    actingAsUser($b)->post('/enquiries', ['listing_id' => $l->id, 'quantity_bags' => 30])->assertSessionDoesntHaveErrors();
    actingAsUser($b)->post('/enquiries', ['listing_id' => $l->id, 'quantity_bags' => 0])->assertSessionHasErrors('quantity_bags');
});

it('refuses enquiries on closed listings and on a farmer own listing', function () {
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $sold = makeListing($f, ['status' => 'sold']);
    actingAsUser($b)->post('/enquiries', ['listing_id' => $sold->id, 'quantity_bags' => 5])->assertSessionHasErrors('listing_id');
    actingAsUser($f)->post('/enquiries', ['listing_id' => $sold->id, 'quantity_bags' => 5])->assertForbidden();
});

it('implements the enquiry state machine exhaustively', function () {
    $allowed = [
        'sent' => ['viewed', 'replied', 'declined', 'withdrawn'], 'viewed' => ['replied', 'declined', 'withdrawn'], 'replied' => ['agreed', 'declined', 'withdrawn'],
        'agreed' => [], 'declined' => [], 'withdrawn' => [],
    ];
    foreach (EnquiryStatus::cases() as $from) {
        foreach (EnquiryStatus::cases() as $to) {
            expect($from->canTransitionTo($to))->toBe(in_array($to->value, $allowed[$from->value], true), "{$from->value} -> {$to->value}");
        }
    }
});

it('runs the full enquiry flow: view, reply, agree, with notifications to the buyer', function () {
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $l = makeListing($f);
    $e = makeEnquiry($l, $b);
    actingAsUser($f)->get("/enquiries/{$e->id}")->assertOk();
    expect($e->fresh()->status)->toBe(EnquiryStatus::Viewed);
    actingAsUser($f)->post("/enquiries/{$e->id}/reply", ['reply' => 'Yes, ready Friday.'])->assertRedirect();
    expect($e->fresh()->status)->toBe(EnquiryStatus::Replied)->and($e->fresh()->reply)->toBe('Yes, ready Friday.');
    actingAsUser($f)->post("/enquiries/{$e->id}/status", ['status' => 'agreed'])->assertRedirect();
    expect($e->fresh()->status)->toBe(EnquiryStatus::Agreed)->and($l->fresh()->availableBags())->toBe(80);
    expect($b->notifications->pluck('data.type')->unique()->all())->toBe(['enquiry_update']);
});

it('forbids agreeing before replying and any change after a terminal state', function () {
    $f = makeUser(Role::Farmer);
    $e = makeEnquiry(makeListing($f), makeUser(Role::Buyer));
    actingAsUser($f)->post("/enquiries/{$e->id}/status", ['status' => 'agreed'])->assertSessionHasErrors('status');
    $e->update(['status' => 'declined']);
    actingAsUser($f)->post("/enquiries/{$e->id}/reply", ['reply' => 'x'])->assertSessionHasErrors('status');
    expect($e->fresh()->status)->toBe(EnquiryStatus::Declined);
});

it('does not let agreement promise more bags than remain', function () {
    $f = makeUser(Role::Farmer);
    $l = makeListing($f, ['quantity_bags' => 100]);
    makeEnquiry($l, makeUser(Role::Buyer), ['quantity_bags' => 80, 'status' => 'agreed']);
    $e = makeEnquiry($l, makeUser(Role::Buyer), ['quantity_bags' => 50, 'status' => 'replied']);
    expect(fn () => app(Transitions::class)->enquiry($e, EnquiryStatus::Agreed, $f))->toThrow(ValidationException::class);
});

it('isolates enquiries: only the buyer and the listing owner can open or act on one', function () {
    $f = makeUser(Role::Farmer);
    $otherF = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $otherB = makeUser(Role::Buyer);
    $admin = mfaUser(Role::PlatformAdmin);
    $e = makeEnquiry(makeListing($f), $b, ['message' => 'secret-negotiation']);
    foreach ([$otherF, $otherB, $admin] as $intruder) {
        actingAsUser($intruder)->get("/enquiries/{$e->id}")->assertForbidden();
        actingAsUser($intruder)->post("/enquiries/{$e->id}/status", ['status' => 'declined'])->assertStatus(in_array($intruder->role, [Role::PlatformAdmin]) ? 403 : 403);
    }
    actingAsUser($otherF)->post("/enquiries/{$e->id}/reply", ['reply' => 'hijack'])->assertForbidden();
    actingAsUser($otherB)->post("/enquiries/{$e->id}/status", ['status' => 'withdrawn'])->assertForbidden();
    actingAsUser($b)->post("/enquiries/{$e->id}/status", ['status' => 'agreed'])->assertForbidden(); // buyer cannot agree on the farmer's behalf
    expect($e->fresh()->status)->toBe(EnquiryStatus::Sent);
    actingAsUser($otherF)->get('/enquiries')->assertDontSee('secret-negotiation');
    actingAsUser($b)->get('/enquiries')->assertSee('Sent');
});

it('lets the buyer withdraw and notifies the farmer', function () {
    $f = makeUser(Role::Farmer);
    $b = makeUser(Role::Buyer);
    $e = makeEnquiry(makeListing($f), $b);
    actingAsUser($b)->post("/enquiries/{$e->id}/status", ['status' => 'withdrawn'])->assertRedirect();
    expect($e->fresh()->status)->toBe(EnquiryStatus::Withdrawn)->and($f->notifications()->count())->toBe(1);
});
