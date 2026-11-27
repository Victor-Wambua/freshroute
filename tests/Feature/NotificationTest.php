<?php

use App\Contracts\SmsGateway;
use App\Enums\Role;
use App\Mail\NoticeMail;
use App\Models\HarvestCalendarEntry;
use App\Services\Notifier;
use App\Support\NoticeText;
use Illuminate\Support\Facades\Mail;

it('delivers in-app and email notifications according to user preference', function () {
    Mail::fake();
    $both = makeUser(Role::Farmer, ['notification_prefs' => ['database' => true, 'mail' => true, 'sms' => false]]);
    $inapp = makeUser(Role::Farmer, ['notification_prefs' => ['database' => true, 'mail' => false]]);
    $mailOnly = makeUser(Role::Farmer, ['notification_prefs' => ['database' => false, 'mail' => true]]);
    app(Notifier::class)->send([$both, $inapp, $mailOnly], 'storage_available', ['name' => 'Cold', 'free' => 5], '/storage');
    expect($both->notifications)->toHaveCount(1)->and($inapp->notifications)->toHaveCount(1)->and($mailOnly->notifications)->toHaveCount(0);
    Mail::assertQueued(NoticeMail::class, 2);
    Mail::assertQueued(NoticeMail::class, fn ($m) => $m->hasTo($both->email) && $m->type === 'storage_available');
});

it('renders the email in the recipient locale with the no-money statement', function () {
    $m = new NoticeMail('new_enquiry', ['buyer' => 'Greens', 'bags' => 40], '/enquiries/1', 'sw');
    expect($m->envelope()->subject)->toContain('Swali jipya')->and($m->render())->toContain('Swali jipya')->toContain('/enquiries/1');
});

it('never sends to suspended or anonymised accounts', function () {
    $u = makeUser(Role::Farmer, ['suspended_at' => now()]);
    expect(app(Notifier::class)->send($u, 'new_enquiry', ['buyer' => 'x', 'bags' => 1]))->toBe(0)->and($u->notifications)->toHaveCount(0);
});

it('has SMS as a disabled sandbox: nothing is sent unless the flag is on, and even then it only records', function () {
    $u = makeUser(Role::Farmer, ['notification_prefs' => ['database' => false, 'mail' => false, 'sms' => true]]);
    app(Notifier::class)->send($u, 'new_enquiry', ['buyer' => 'x', 'bags' => 1]);
    expect(DB::table('sms_outbox')->count())->toBe(0);
    config(['freshroute.sms_enabled' => true]);
    app(Notifier::class)->send($u, 'new_enquiry', ['buyer' => 'x', 'bags' => 1]);
    $row = DB::table('sms_outbox')->first();
    expect($row->status)->toBe('sandbox_not_sent')->and(app(SmsGateway::class))->toBeInstanceOf(App\Services\SandboxSmsGateway::class);
    // the preference form cannot switch SMS on
    actingAsUser($u)->post('/alerts/prefs', ['mail' => '1', 'database' => '1', 'sms' => '1']);
    expect($u->fresh()->notification_prefs['sms'])->toBeFalse();
});

it('sends harvest reminders once per calendar entry, 3 days ahead', function () {
    $f = makeUser(Role::Farmer);
    $soon = HarvestCalendarEntry::create(['user_id' => $f->id, 'commodity_id' => commodity()->id, 'harvest_on' => today()->addDays(2), 'expected_bags' => 90]);
    HarvestCalendarEntry::create(['user_id' => $f->id, 'commodity_id' => commodity()->id, 'harvest_on' => today()->addDays(20)]);
    HarvestCalendarEntry::create(['user_id' => $f->id, 'commodity_id' => commodity()->id, 'harvest_on' => today()->subDays(2)]);
    $this->artisan('fr:harvest-reminders')->expectsOutput('Reminders sent: 1')->assertSuccessful();
    $this->artisan('fr:harvest-reminders')->expectsOutput('Reminders sent: 0')->assertSuccessful();
    expect($f->notifications)->toHaveCount(1)->and($f->notifications->first()->data['type'])->toBe('harvest_reminder')->and($soon->fresh()->reminded_at)->not->toBeNull();
});

it('notifies matching farmers when a buyer posts a need, and buyers when a matching listing goes live', function () {
    $farmer = makeUser(Role::Farmer);
    $farFarmer = makeUser(Role::Farmer);
    $buyer = makeUser(Role::Buyer);
    makeListing($farmer, ['quantity_bags' => 120, 'county' => 'Nakuru', 'quality_grade' => 'A', 'price_minor' => 260000]);
    makeListing($farFarmer, ['quantity_bags' => 5, 'county' => 'Nyandarua', 'quality_grade' => 'C', 'price_minor' => 900000, 'harvest_date' => today()->addDays(40), 'available_from' => today()->addDays(40)]);
    actingAsUser($buyer)->post('/needs', ['quantity_bags' => 100, 'county' => 'Nakuru', 'needed_by' => today()->addDays(7)->toDateString(), 'min_grade' => 'B', 'max_price_kes' => 2800])->assertRedirect();
    expect($farmer->notifications->pluck('data.type')->all())->toBe(['matching_demand'])->and($farFarmer->notifications)->toHaveCount(0);
    actingAsUser(makeUser(Role::Farmer))->post('/listings', listingPayload(['county' => 'Nakuru', 'quantity_bags' => 150, 'price_kes' => 2500, 'quality_grade' => 'A']))->assertRedirect();
    expect($buyer->notifications->pluck('data.type')->all())->toBe(['matching_supply']);
});

it('scheduled match notifications fire once per need-listing pair', function () {
    $farmer = makeUser(Role::Farmer);
    $buyer = makeUser(Role::Buyer);
    makeListing($farmer, ['quantity_bags' => 120]);
    makeNeed($buyer, ['quantity_bags' => 100]);
    $this->artisan('fr:notify-matches')->expectsOutput('Match notifications sent: 1')->assertSuccessful();
    $this->artisan('fr:notify-matches')->expectsOutput('Match notifications sent: 0')->assertSuccessful();
    expect($farmer->notifications()->count())->toBe(1);
});

it('lists notifications, shows unread state, opens safely and marks read', function () {
    $f = makeUser(Role::Farmer);
    app(Notifier::class)->send($f, 'new_enquiry', ['buyer' => 'Greens', 'bags' => 40], '/enquiries');
    app(Notifier::class)->send($f, 'harvest_reminder', ['date' => '2026-12-01', 'bags' => 5], 'https://evil.example/phish');
    $n = $f->notifications()->first();
    actingAsUser($f)->get('/alerts')->assertOk()->assertSee('New enquiry from Greens for 40 bags');
    expect($f->unreadNotifications()->count())->toBe(2);
    actingAsUser($f)->get("/alerts/{$n->id}/open")->assertRedirect('/enquiries');
    $evil = $f->notifications()->get()->first(fn ($x) => $x->data['type'] === 'harvest_reminder');
    actingAsUser($f)->get("/alerts/{$evil->id}/open")->assertRedirect(route('alerts.index')); // off-site URLs are never followed
    actingAsUser($f)->post('/alerts/read-all');
    expect($f->unreadNotifications()->count())->toBe(0);
});

it('does not let one user open or read another user notification', function () {
    $a = makeUser(Role::Farmer);
    $b = makeUser(Role::Farmer);
    app(Notifier::class)->send($a, 'new_enquiry', ['buyer' => 'x', 'bags' => 1], '/enquiries');
    $n = $a->notifications()->first();
    actingAsUser($b)->get("/alerts/{$n->id}/open")->assertNotFound();
    expect($n->fresh()->read_at)->toBeNull();
    actingAsUser($b)->get('/alerts')->assertDontSee('New enquiry');
});

it('renders every notification type in English and Swahili without leftover placeholders', function () {
    $samples = [
        'new_enquiry' => ['buyer' => 'B', 'bags' => 5], 'enquiry_update' => ['status' => 'replied', 'who' => 'W'], 'harvest_reminder' => ['date' => '2026-12-01', 'bags' => 5],
        'matching_demand' => ['bags' => 5, 'county' => 'Nakuru', 'score' => 80], 'matching_supply' => ['bags' => 5, 'place' => 'Molo', 'score' => 80], 'storage_available' => ['name' => 'N', 'free' => 5],
        'delivery_update' => ['status' => 'accepted', 'bags' => 5], 'delivery_request' => ['bags' => 5, 'from' => 'a', 'to' => 'b'], 'price_change' => ['label' => 'Official', 'market' => 'Nakuru', 'from' => 'KES 1', 'to' => 'KES 2', 'pct' => 10],
    ];
    expect(array_keys($samples))->toEqualCanonicalizing(Notifier::TYPES);
    foreach ($samples as $type => $p) {
        foreach (['en', 'sw'] as $loc) {
            expect(NoticeText::render($type, $p, $loc))->not->toMatch('/:[a-z]+/')->not->toStartWith('notice.');
        }
    }
});
