<?php

use App\Models\DeliveryRequest;
use App\Models\Enquiry;
use App\Models\Listing;
use App\Models\StorageFacility;
use App\Models\User;
use Database\Seeders\SyntheticDataSeeder;

beforeEach(fn () => $this->seed(SyntheticDataSeeder::class));

function seeded(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

it('renders every page for every role against the synthetic dataset without errors', function () {
    $listing = Listing::first();
    $enq = Enquiry::first();
    $deliv = DeliveryRequest::first();
    $facility = StorageFacility::first();
    $matrix = [
        'wanjiru@freshroute.test' => ['/home', '/sell', '/my/listings', "/listings/{$listing->id}", "/listings/{$listing->id}/edit", '/calendar', '/calendar?m=2027-01', '/farms', '/losses', '/losses/report', '/enquiries', "/enquiries/{$enq->id}", '/deliveries', '/storage', '/transport', '/prices', '/prices?tab=user', '/prices?market=Molo', '/alerts', '/my-data', '/more'],
        'njeri@freshroute.test' => ['/home', '/market', '/market?q=Molo&county=Nakuru&min_bags=50&grade[]=A&sort=price_desc', "/market/{$listing->id}", "/market/compare?ids[]=1&ids[]=2&ids[]=3", '/saved', '/needs', '/needs/new', '/enquiries', '/deliveries', '/storage', '/transport', '/prices', '/alerts', '/my-data', '/more'],
        'njoro-cold@freshroute.test' => ['/home', '/storage/mine', '/storage/new', "/storage/{$facility->id}/edit", '/storage-requests', '/storage', '/prices', '/alerts', '/my-data', '/more'],
        'kariuki@freshroute.test' => ['/home', '/transport/profile', '/deliveries', "/deliveries/{$deliv->id}", '/transport', '/prices', '/alerts', '/my-data', '/more'],
        'hannah@freshroute.test' => ['/home', '/coop', '/coop/members', '/coop/members?q=an', '/coop/reports', '/prices', '/alerts', '/my-data', '/more', '/mfa/setup'],
        'admin@freshroute.test' => ['/home', '/admin', '/admin/users', '/admin/users?q=njeri', '/admin/verify', '/admin/audit', '/admin/audit?action=auth', '/admin/deletions', '/admin/prices', '/prices', '/more'],
    ];
    foreach ($matrix as $email => $paths) {
        $u = seeded($email);
        foreach (['en', 'sw'] as $loc) {
            $u->update(['locale' => $loc]);
            foreach ($paths as $path) {
                $res = actingAsUser($u->fresh())->get($path);
                expect($res->status())->toBeIn([200, 302], "$email $path ($loc) -> {$res->status()}");
                if ($res->status() === 200) {
                    $html = $res->getContent();
                    expect($html)->not->toMatch('/(?<![\w.])(ui|notice|pwa)\.[a-z_]+[a-z]/', "untranslated key on $path ($loc)");
                    expect($html)->toContain('<html lang="'.$loc.'"');
                }
            }
        }
    }
});

it('shows the synthetic labelling banner and origin badges in the UI', function () {
    $u = seeded('njeri@freshroute.test');
    actingAsUser($u)->get('/market')->assertSee('Synthetic demonstration data')->assertSee('Synthetic');
    $this->get('/prices')->assertSee('Synthetic');
});

it('has every translation key used in code and views in both languages', function () {
    $files = collect(File::allFiles(resource_path('views')))->merge(File::allFiles(app_path()))->map(fn ($f) => $f->getContents())->implode("\n");
    preg_match_all("/['\"](ui|notice|pwa)\.([a-z0-9_]+)['\"]/i", $files, $m, PREG_SET_ORDER);
    $en = ['ui' => require lang_path('en/ui.php'), 'notice' => require lang_path('en/notice.php'), 'pwa' => require lang_path('en/pwa.php')];
    $sw = ['ui' => require lang_path('sw/ui.php'), 'notice' => require lang_path('sw/notice.php'), 'pwa' => require lang_path('sw/pwa.php')];
    foreach ($m as [, $group, $key]) {
        if (str_ends_with($key, '_')) {
            continue; // dynamic prefix, covered below
        }
        expect($en[$group])->toHaveKey($key)->and($sw[$group])->toHaveKey($key);
    }
    foreach (['en', 'sw'] as $loc) {
        foreach (['ui', 'notice', 'pwa'] as $g) {
            expect(array_keys(${$loc}[$g]))->toEqualCanonicalizing(array_keys(($loc === 'en' ? $sw : $en)[$g]));
        }
    }
    // dynamic families
    $families = [
        'role_' => ['farmer', 'coop_admin', 'buyer', 'storage_provider', 'transporter', 'platform_admin'], 'ls_' => ['draft', 'active', 'reserved', 'sold', 'expired', 'withdrawn'],
        'act_' => ['active', 'reserved', 'sold', 'expired', 'withdrawn'], 'es_' => ['sent', 'viewed', 'replied', 'agreed', 'declined', 'withdrawn'], 'ds_' => ['requested', 'accepted', 'picked_up', 'delivered', 'declined', 'cancelled'],
        'dact_' => ['accepted', 'declined', 'picked_up', 'delivered', 'cancelled'], 'stage_' => config('freshroute.stages'), 'cat_' => config('freshroute.loss_categories'), 'reason_' => config('freshroute.loss_reasons'),
        'buyer_type_' => config('freshroute.buyer_types'), 'veh_' => config('freshroute.vehicle_types'), 'grade_' => config('freshroute.grades'), 'licence_' => config('freshroute.licence_statuses'), 'sr_' => ['sent', 'accepted', 'declined'],
        'sr_act_' => ['accepted', 'declined'], 'need_' => ['open', 'closed'], 'sort_' => ['harvest', 'price_asc', 'price_desc', 'qty'], 'verify_' => ['coop', 'storage', 'transporter'], 'ds_' => App\Services\DataExporter::DATASETS + ['requested', 'accepted', 'picked_up', 'delivered', 'declined', 'cancelled'],
    ];
    foreach ($families as $prefix => $vals) {
        foreach ($vals as $v) {
            expect($en['ui'])->toHaveKey($prefix.$v)->and($sw['ui'])->toHaveKey($prefix.$v);
        }
    }
    foreach (['403', '404', '419', '429', '500', '503'] as $c) {
        expect($en['ui'])->toHaveKey("err_$c")->toHaveKey("err_{$c}_b");
    }
});

it('marks the Swahili files as draft needing native review', function () {
    foreach (['ui', 'notice', 'pwa'] as $g) {
        expect(file_get_contents(lang_path("sw/$g.php")))->toMatch('/native-speaker review|MUST be reviewed/');
    }
});

it('renders friendly error pages', function () {
    $this->get('/definitely-not-a-page')->assertNotFound()->assertSee('Page not found');
    actingAsUser(seeded('njeri@freshroute.test'))->get('/admin')->assertForbidden()->assertSee('Not allowed');
});
