<?php

use App\Enums\Role;
use App\Models\BuyerNeed;
use App\Models\Commodity;
use App\Models\Cooperative;
use App\Models\Enquiry;
use App\Models\Listing;
use App\Models\StorageFacility;
use App\Models\TransporterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Unit/Db');

const TEST_PASSWORD = 'Correct-horse-42';

function commodity(): Commodity
{
    return Commodity::firstOrCreate(['slug' => 'irish-potato'], [
        'name_en' => 'Irish potato', 'name_sw' => 'Viazi mviringo', 'unit_en' => '50 kg bag', 'unit_sw' => 'gunia la kg 50', 'unit_kg' => 50,
    ]);
}

function makeUser(Role $role = Role::Farmer, array $attrs = []): User
{
    static $n = 0;
    $n++;
    $u = new User($attrs + [
        'name' => 'Test '.$role->value.' '.$n, 'email' => $role->value.$n.'@test.example', 'password' => TEST_PASSWORD, 'phone' => '0712345'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
        'county' => 'Nakuru', 'place' => 'Molo', 'consent_at' => now(), 'notification_prefs' => ['database' => true, 'mail' => false, 'sms' => false],
    ]);
    $u->role = $role;
    $u->save();

    return $u;
}

/** A coop admin or platform admin who has already enrolled in TOTP and passed the challenge. */
function mfaUser(Role $role, array $attrs = []): User
{
    return makeUser($role, $attrs + ['totp_secret' => 'JBSWY3DPEHPK3PXP', 'totp_confirmed_at' => now()]);
}

function actingAsUser(User $u): TestCase
{
    $t = test()->actingAs($u);
    if ($u->needsMfa()) {
        $t->withSession(['mfa_passed' => true]);
    }

    return $t;
}

function makeListing(User $farmer, array $attrs = []): Listing
{
    return Listing::create($attrs + [
        'client_uuid' => (string) Str::uuid(), 'user_id' => $farmer->id, 'commodity_id' => commodity()->id, 'quantity_bags' => 100,
        'harvest_date' => today()->addDays(3), 'available_from' => today()->addDays(3), 'county' => 'Nakuru', 'place' => 'Molo', 'quality_grade' => 'A',
        'price_minor' => 260000, 'status' => 'active', 'origin' => 'user_submitted',
    ]);
}

function makeEnquiry(Listing $l, User $buyer, array $attrs = []): Enquiry
{
    return Enquiry::create($attrs + ['client_uuid' => (string) Str::uuid(), 'listing_id' => $l->id, 'buyer_id' => $buyer->id, 'quantity_bags' => 20, 'status' => 'sent']);
}

function makeFacility(User $provider, array $attrs = []): StorageFacility
{
    return StorageFacility::create($attrs + ['user_id' => $provider->id, 'name' => 'Cold Store', 'county' => 'Nakuru', 'place' => 'Njoro', 'capacity_bags' => 1000, 'free_bags' => 500, 'price_minor' => 4000]);
}

function makeTransporter(User $t, array $attrs = []): TransporterProfile
{
    return TransporterProfile::create($attrs + ['user_id' => $t->id, 'company_name' => 'Haulage Co', 'vehicle_type' => 'canter', 'capacity_bags' => 80, 'routes' => 'Molo - Nakuru', 'base_price_minor' => 1400000]);
}

function makeNeed(User $buyer, array $attrs = []): BuyerNeed
{
    return BuyerNeed::create($attrs + ['client_uuid' => (string) Str::uuid(), 'buyer_id' => $buyer->id, 'commodity_id' => commodity()->id, 'quantity_bags' => 100, 'county' => 'Nakuru', 'needed_by' => today()->addDays(7), 'min_grade' => 'B', 'status' => 'open']);
}

function makeCoop(array $attrs = []): Cooperative
{
    return Cooperative::create($attrs + ['name' => 'Test Coop '.Str::random(4), 'county' => 'Nakuru']);
}
