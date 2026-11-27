<?php

use App\Enums\Role;
use App\Models\DeletionRequest;
use App\Models\Enquiry;
use App\Models\ListingPhoto;
use App\Models\LossEvent;
use App\Models\User;
use App\Services\AccountDeletion;
use Illuminate\Support\Facades\Storage;

function farmerWithData(): array
{
    $f = makeUser(Role::Farmer, ['name' => 'Wanjiru Private', 'email' => 'wanjiru.private@test.example']);
    $l = makeListing($f, ['storage_requirements' => 'secret shed']);
    $b = makeUser(Role::Buyer, ['business_name' => 'Other Buyer']);
    makeEnquiry($l, $b, ['message' => 'buyer note']);
    LossEvent::create(['client_uuid' => (string) Str::uuid(), 'user_id' => $f->id, 'commodity_id' => commodity()->id, 'stage' => 'storage', 'category' => 'spoiled', 'bags' => 9, 'reason' => 'rot', 'occurred_on' => today()]);

    return [$f, $l, $b];
}

it('exports everything about the user as JSON, and only the user\'s own records', function () {
    [$f, $l, $b] = farmerWithData();
    $other = makeUser(Role::Farmer, ['name' => 'Someone Else']);
    makeListing($other, ['place' => 'OTHER-PLACE']);
    $res = actingAsUser($f)->get('/my-data/export.json')->assertOk();
    $j = $res->json();
    expect($j['profile'][0]['email'])->toBe('wanjiru.private@test.example')->and($j['listings'])->toHaveCount(1)->and($j['losses'])->toHaveCount(1)->and($j['enquiries'])->toHaveCount(1);
    expect($res->getContent())->not->toContain('OTHER-PLACE')->not->toContain('password')->not->toContain('totp_secret')->not->toContain('remember_token');
    expect($res->headers->get('content-disposition'))->toContain('attachment');
});

it('exports each dataset as CSV with headers and neutralised formulas', function () {
    $this->withoutMiddleware(Illuminate\Routing\Middleware\ThrottleRequests::class);
    [$f] = farmerWithData();
    makeListing($f, ['variety' => '=HYPERLINK("http://evil","x")']);
    foreach (App\Services\DataExporter::DATASETS as $ds) {
        $res = actingAsUser($f)->get("/my-data/export.csv?dataset={$ds}")->assertOk();
        expect($res->headers->get('content-type'))->toContain('text/csv');
    }
    $csv = actingAsUser($f)->get('/my-data/export.csv?dataset=listings')->getContent();
    expect($csv)->toContain('quantity_bags')->toContain("'=HYPERLINK")->not->toContain(',=HYPERLINK');
    actingAsUser($f)->get('/my-data/export.csv?dataset=users')->assertSessionHasErrors('dataset');
    actingAsUser($f)->get('/my-data/export.csv')->assertSessionHasErrors('dataset');
});

it('exports for every role without error', function () {
    foreach ([Role::Farmer, Role::Buyer, Role::StorageProvider, Role::Transporter] as $role) {
        $u = makeUser($role);
        actingAsUser($u)->get('/my-data/export.json')->assertOk();
    }
    actingAsUser(mfaUser(Role::CoopAdmin, ['coop_id' => makeCoop()->id]))->get('/my-data/export.json')->assertOk();
});

it('requires the password and an explicit confirmation to request deletion', function () {
    $f = makeUser(Role::Farmer);
    actingAsUser($f)->post('/my-data/delete', ['confirm' => 'DELETE', 'password' => 'wrong'])->assertSessionHasErrors('password');
    actingAsUser($f)->post('/my-data/delete', ['confirm' => 'yes', 'password' => TEST_PASSWORD])->assertSessionHasErrors('confirm');
    expect(DeletionRequest::count())->toBe(0);
    actingAsUser($f)->post('/my-data/delete', ['confirm' => 'DELETE', 'password' => TEST_PASSWORD])->assertRedirect();
    $r = DeletionRequest::first();
    expect($r->status)->toBe('requested')->and($r->due_at->isSameDay(now()->addDays(7)))->toBeTrue()->and($f->fresh()->deletion_requested_at)->not->toBeNull();
    actingAsUser($f)->post('/my-data/delete', ['confirm' => 'DELETE', 'password' => TEST_PASSWORD]);
    expect(DeletionRequest::count())->toBe(1);
});

it('lets the user cancel during the grace period, and the scheduled job then leaves the account alone', function () {
    $f = makeUser(Role::Farmer);
    app(AccountDeletion::class)->request($f);
    actingAsUser($f)->delete('/my-data/delete')->assertRedirect();
    expect(DeletionRequest::first()->status)->toBe('cancelled')->and($f->fresh()->deletion_requested_at)->toBeNull();
    $this->travel(30)->days();
    $this->artisan('fr:process-deletions')->expectsOutput('Anonymised: 0')->assertSuccessful();
    expect($f->fresh()->anonymised_at)->toBeNull();
});

it('does not delete before the grace period ends, then anonymises personal data but keeps aggregate records', function () {
    Storage::fake('local');
    [$f, $l, $b] = farmerWithData();
    $photo = ListingPhoto::create(['listing_id' => $l->id, 'path' => 'listing-photos/x.jpg', 'mime' => 'image/jpeg', 'bytes' => 1, 'sha256' => str_repeat('a', 64)]);
    Storage::disk('local')->put('listing-photos/x.jpg', 'bytes');
    app(AccountDeletion::class)->request($f);
    $this->artisan('fr:process-deletions')->expectsOutput('Anonymised: 0');
    expect($f->fresh()->anonymised_at)->toBeNull();
    $this->travel(8)->days();
    $this->artisan('fr:process-deletions')->expectsOutput('Anonymised: 1');
    $f = $f->fresh();
    expect($f->name)->toBe('Deleted user')->and($f->email)->toEndWith('@deleted.invalid')->and($f->email)->not->toContain('wanjiru')->and($f->phone)->toBeNull()->and($f->anonymised_at)->not->toBeNull()->and($f->suspended_at)->not->toBeNull();
    expect(ListingPhoto::count())->toBe(0)->and($l->fresh()->status->value)->toBe('withdrawn')->and($l->fresh()->storage_requirements)->toBeNull();
    Storage::disk('local')->assertMissing('listing-photos/x.jpg');
    expect(LossEvent::where('user_id', $f->id)->count())->toBe(1);          // anonymised aggregate record kept
    expect(Enquiry::first()->buyer_id)->toBe($b->id);                       // the other party's record is untouched
    $this->post('/login', ['email' => 'wanjiru.private@test.example', 'password' => TEST_PASSWORD])->assertSessionHasErrors('email');
    expect(DeletionRequest::first()->status)->toBe('completed');
    $this->assertDatabaseHas('audit_logs', ['action' => 'account.anonymised']);
});

it('lets a platform admin process a deletion early, and others not', function () {
    $f = makeUser(Role::Farmer);
    $req = app(AccountDeletion::class)->request($f);
    actingAsUser(makeUser(Role::Buyer))->post("/admin/deletions/{$req->id}/process")->assertForbidden();
    actingAsUser(mfaUser(Role::PlatformAdmin))->post("/admin/deletions/{$req->id}/process")->assertRedirect();
    expect($f->fresh()->anonymised_at)->not->toBeNull();
});

it('records exports and deletion requests in the audit log', function () {
    $f = makeUser(Role::Farmer);
    actingAsUser($f)->get('/my-data/export.json');
    app(AccountDeletion::class)->request($f);
    expect(App\Models\AuditLog::pluck('action')->all())->toContain('export.json', 'account.deletion_requested');
});

it('rate limits exports', function () {
    $f = makeUser(Role::Farmer);
    foreach (range(1, 10) as $_) {
        actingAsUser($f)->get('/my-data/export.json')->assertOk();
    }
    actingAsUser($f)->get('/my-data/export.json')->assertStatus(429);
});
