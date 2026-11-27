<?php

namespace Database\Seeders;

use App\Enums\EnquiryStatus;
use App\Enums\Role;
use App\Models\BuyerNeed;
use App\Models\Commodity;
use App\Models\Cooperative;
use App\Models\CropProfile;
use App\Models\DeliveryRequest;
use App\Models\Enquiry;
use App\Models\Farm;
use App\Models\HarvestLot;
use App\Models\Listing;
use App\Models\LossEvent;
use App\Models\PriceSource;
use App\Models\StorageFacility;
use App\Models\TransporterProfile;
use App\Models\User;
use App\Models\UserPrice;
use App\Services\Price\CsvPriceFeedAdapter;
use App\Services\Price\PriceImporter;
use Illuminate\Database\Seeder;

/**
 * SYNTHETIC DEMONSTRATION DATA. Every person, business, quantity and price created here is invented.
 * Rows carry origin = 'synthetic' so the UI can label them. Never run against a production database.
 */
class SyntheticDataSeeder extends Seeder
{
    public const PASSWORD = 'Demo-pass-2026';

    public const DEMO_TOTP_SECRET = 'JBSWY3DPEHPK3PXP'; // public demo secret, local/testing only

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Refusing to seed synthetic data in production.');
        }

        $potato = Commodity::firstOrCreate(['slug' => 'irish-potato'], [
            'name_en' => 'Irish potato', 'name_sw' => 'Viazi mviringo', 'unit_en' => '50 kg bag', 'unit_sw' => 'gunia la kg 50', 'unit_kg' => 50,
        ]);

        $this->prices($potato);

        $kin = Cooperative::firstOrCreate(['name' => 'Kinangop Potato Growers'], ['county' => 'Nyandarua', 'place' => 'Kinangop', 'registration_no' => 'SYN-001', 'verified_at' => now(), 'origin' => 'synthetic']);
        $molo = Cooperative::firstOrCreate(['name' => 'Molo Growers'], ['county' => 'Nakuru', 'place' => 'Molo', 'registration_no' => 'SYN-002', 'origin' => 'synthetic']);

        $admin = $this->user('admin@freshroute.test', 'Platform Admin', Role::PlatformAdmin, ['totp' => true]);
        $coopAdmin = $this->user('hannah@freshroute.test', 'Hannah Wafula', Role::CoopAdmin, ['coop_id' => $kin->id, 'county' => 'Nyandarua', 'place' => 'Kinangop', 'phone' => '0712000001', 'totp' => true]);

        $farmers = [];
        foreach ([
            ['wanjiru@freshroute.test', 'Wanjiru Kamau', 'Nyandarua', 'Ol Kalou', $kin->id, 'Shangi', 120, 2600, '+3 days', 'A'],
            ['peter@freshroute.test', 'Peter Mwangi', 'Nyandarua', 'Kinangop', $kin->id, 'Shangi', 300, 2750, '+1 days', 'A'],
            ['joyce@freshroute.test', 'Joyce Chebet', 'Nakuru', 'Molo', $molo->id, 'Dutch Robijn', 80, 2300, '+10 days', 'B'],
            ['samuel@freshroute.test', 'Samuel Kiprono', 'Nakuru', 'Njoro', null, 'Shangi', 450, 2680, '+5 days', 'A'],
            ['agnes@freshroute.test', 'Agnes Wairimu', 'Nyandarua', 'Ol Joro Orok', $kin->id, 'Unica', 60, 2200, '+20 days', 'B'],
            ['david@freshroute.test', 'David Otieno', 'Nakuru', 'Rongai', null, 'Shangi', 200, 2900, '+8 days', 'A'],
        ] as [$email, $name, $county, $place, $coop, $variety, $bags, $price, $when, $grade]) {
            $f = $this->user($email, $name, Role::Farmer, ['county' => $county, 'place' => $place, 'coop_id' => $coop, 'phone' => '07'.random_int(10000000, 29999999)]);
            $farm = Farm::firstOrCreate(['user_id' => $f->id, 'name' => $name.' farm'], ['county' => $county, 'place' => $place, 'area_acres' => random_int(2, 12), 'origin' => 'synthetic']);
            CropProfile::firstOrCreate(['farm_id' => $farm->id, 'commodity_id' => $potato->id], ['variety' => $variety, 'capacity_bags' => $bags * 3]);
            $date = now()->modify($when)->toDateString();
            Listing::firstOrCreate(['user_id' => $f->id, 'client_uuid' => $this->uuid($email.'L')], [
                'farm_id' => $farm->id, 'commodity_id' => $potato->id, 'variety' => $variety, 'quantity_bags' => $bags, 'harvest_date' => $date,
                'available_from' => $date, 'available_to' => now()->modify($when)->addDays(21)->toDateString(), 'county' => $county, 'place' => $place,
                'quality_grade' => $grade, 'price_minor' => $price * 100, 'storage_requirements' => 'Cool, dry, shaded', 'status' => 'active', 'origin' => 'synthetic',
            ]);
            $f->calendar()->firstOrCreate(['harvest_on' => $date, 'commodity_id' => $potato->id], ['expected_bags' => $bags, 'farm_id' => $farm->id]);
            $farmers[$email] = $f;
        }

        $njeri = $this->user('njeri@freshroute.test', 'Njeri Mwaura', Role::Buyer, ['business_name' => 'Mama Njeri Greens Ltd', 'buyer_type' => 'trader', 'county' => 'Nakuru', 'place' => 'Nakuru town']);
        $fries = $this->user('fries@freshroute.test', 'Brian Okello', Role::Buyer, ['business_name' => 'Nakuru Fries Kitchen', 'buyer_type' => 'hotel', 'county' => 'Nakuru', 'place' => 'Nakuru town']);

        $w = Listing::where('user_id', $farmers['wanjiru@freshroute.test']->id)->first();
        $p = Listing::where('user_id', $farmers['peter@freshroute.test']->id)->first();
        $e1 = Enquiry::firstOrCreate(['buyer_id' => $njeri->id, 'client_uuid' => $this->uuid('E1')], ['listing_id' => $w->id, 'quantity_bags' => 60, 'message' => 'Can collect Friday.', 'status' => EnquiryStatus::Sent->value, 'origin' => 'synthetic']);
        Enquiry::firstOrCreate(['buyer_id' => $fries->id, 'client_uuid' => $this->uuid('E2')], ['listing_id' => $w->id, 'quantity_bags' => 30, 'message' => 'Is grade A washed?', 'status' => EnquiryStatus::Replied->value, 'reply' => 'Unwashed, graded by hand.', 'origin' => 'synthetic']);
        $e3 = Enquiry::firstOrCreate(['buyer_id' => $njeri->id, 'client_uuid' => $this->uuid('E3')], ['listing_id' => $p->id, 'quantity_bags' => 100, 'message' => 'Delivery to Nakuru market.', 'status' => EnquiryStatus::Agreed->value, 'reply' => 'Agreed, ready on harvest day.', 'origin' => 'synthetic']);

        BuyerNeed::firstOrCreate(['buyer_id' => $njeri->id, 'client_uuid' => $this->uuid('N1')], [
            'commodity_id' => $potato->id, 'quantity_bags' => 100, 'county' => 'Nakuru', 'needed_by' => now()->addDays(9)->toDateString(), 'max_price_minor' => 280000, 'min_grade' => 'B', 'origin' => 'synthetic',
        ]);

        $storageUser = $this->user('njoro-cold@freshroute.test', 'Njoro Cold Store', Role::StorageProvider, ['business_name' => 'Njoro Cold Store', 'county' => 'Nakuru', 'place' => 'Njoro', 'phone' => '0722000002']);
        foreach ([
            [$storageUser, 'Njoro Cold Store', 'Nakuru', 'Njoro', 4000, 1200, 4, 8, 4000, 'self_declared'],
            [$this->user('kin-shed@freshroute.test', 'Kinangop Shed', Role::StorageProvider, ['business_name' => 'Kinangop Ventilated Shed', 'county' => 'Nyandarua', 'place' => 'Kinangop', 'phone' => '0722000003']), 'Kinangop Ventilated Shed', 'Nyandarua', 'Kinangop', 2500, 900, null, null, 1500, 'not_applicable'],
            [$this->user('olkalou-store@freshroute.test', 'Ol Kalou Store', Role::StorageProvider, ['business_name' => 'Ol Kalou Dry Store', 'county' => 'Nyandarua', 'place' => 'Ol Kalou', 'phone' => '0722000004']), 'Ol Kalou Dry Store', 'Nyandarua', 'Ol Kalou', 1800, 0, null, null, 1200, 'not_applicable'],
        ] as [$u, $name, $county, $place, $cap, $free, $tmin, $tmax, $price, $status]) {
            StorageFacility::firstOrCreate(['user_id' => $u->id, 'name' => $name], [
                'county' => $county, 'place' => $place, 'capacity_bags' => $cap, 'free_bags' => $free, 'temp_min_c' => $tmin, 'temp_max_c' => $tmax,
                'temperature_status' => $status, 'price_minor' => $price, 'contact_phone' => $u->phone, 'commodities' => ['irish-potato'], 'origin' => 'synthetic',
            ]);
        }

        $trs = [];
        foreach ([
            ['kariuki@freshroute.test', 'Kariuki Haulage', 'canter', 60, 'Ol Kalou - Nakuru; Kinangop - Nakuru', 14000, 'Available Mon-Sat'],
            ['rvl@freshroute.test', 'Rift Valley Lorries', 'lorry', 200, 'Nyandarua - Nakuru - Nairobi', 38000, 'Next free: in 5 days'],
            ['mwende@freshroute.test', 'Mwende Pickup Hire', 'pickup', 20, 'Molo - Njoro - Nakuru', 5500, 'Available today'],
        ] as [$email, $co, $veh, $cap, $routes, $price, $note]) {
            $u = $this->user($email, $co, Role::Transporter, ['business_name' => $co, 'county' => 'Nakuru', 'place' => 'Nakuru town', 'phone' => '07'.random_int(30000000, 39999999)]);
            $trs[] = TransporterProfile::firstOrCreate(['user_id' => $u->id], ['company_name' => $co, 'vehicle_type' => $veh, 'capacity_bags' => $cap, 'routes' => $routes, 'base_price_minor' => $price * 100, 'availability_note' => $note, 'origin' => 'synthetic']);
        }
        DeliveryRequest::firstOrCreate(['requester_id' => $njeri->id, 'client_uuid' => $this->uuid('D1')], [
            'transporter_profile_id' => $trs[0]->id, 'listing_id' => $p->id, 'bags' => 60, 'pickup_place' => 'Kinangop', 'dropoff_place' => 'Nakuru market', 'pickup_date' => now()->addDays(2)->toDateString(), 'status' => 'accepted', 'origin' => 'synthetic',
        ]);

        $wan = $farmers['wanjiru@freshroute.test'];
        $lot = HarvestLot::firstOrCreate(['user_id' => $wan->id, 'client_uuid' => $this->uuid('LOT1')], ['commodity_id' => $potato->id, 'harvested_on' => now()->subDays(20)->toDateString(), 'harvested_bags' => 200, 'sold_bags' => 150, 'origin' => 'synthetic']);
        foreach ([['harvest', 'damaged', 6, 'bruising'], ['storage', 'spoiled', 12, 'rot'], ['transport', 'damaged', 8, 'delay'], ['market', 'rejected', 5, 'greening']] as $i => [$stage, $cat, $bags, $reason]) {
            LossEvent::firstOrCreate(['user_id' => $wan->id, 'client_uuid' => $this->uuid('LOSS'.$i)], ['commodity_id' => $potato->id, 'lot_id' => $lot->id, 'stage' => $stage, 'category' => $cat, 'bags' => $bags, 'reason' => $reason, 'occurred_on' => now()->subDays(15 - $i)->toDateString(), 'origin' => 'synthetic']);
        }
        foreach (['Nakuru' => [2700, 2650], 'Molo' => [2500]] as $market => $prices) {
            foreach ($prices as $i => $kes) {
                UserPrice::firstOrCreate(['user_id' => $njeri->id, 'client_uuid' => $this->uuid('UP'.$market.$i)], ['commodity_id' => $potato->id, 'market' => $market, 'unit' => '50 kg bag', 'price_minor' => $kes * 100, 'observed_on' => now()->subDays($i + 1)->toDateString(), 'note' => 'Seen at stalls', 'origin' => 'synthetic']);
            }
        }
    }

    private function user(string $email, string $name, Role $role, array $x = []): User
    {
        $u = User::where('email', $email)->first();
        if ($u) {
            return $u;
        }
        $totp = $x['totp'] ?? false;
        unset($x['totp']);
        $u = new User($x + ['name' => $name, 'email' => $email, 'password' => self::PASSWORD, 'consent_at' => now(), 'origin' => 'synthetic', 'notification_prefs' => ['database' => true, 'mail' => true, 'sms' => false]]);
        $u->role = $role;
        if ($totp) {
            $u->totp_secret = self::DEMO_TOTP_SECRET;
            $u->totp_confirmed_at = now();
        }
        $u->save();

        return $u;
    }

    private function uuid(string $seed): string
    {
        $h = md5('freshroute-synthetic-'.$seed);

        return substr($h, 0, 8).'-'.substr($h, 8, 4).'-4'.substr($h, 13, 3).'-a'.substr($h, 17, 3).'-'.substr($h, 20, 12);
    }

    private function prices(Commodity $potato): void
    {
        $source = PriceSource::firstOrCreate(['name' => 'Synthetic sample bulletin (NOT real data)'], [
            'url' => 'https://example.invalid/synthetic-bulletin', 'licence_status' => 'synthetic', 'origin' => 'synthetic',
            'licence_note' => 'Invented rows that exercise the import pipeline. Not a real publication; do not cite.',
        ]);
        // Dates are relative to today so the demo never looks stale; rows go through the real importer.
        $tmp = tempnam(sys_get_temp_dir(), 'frp');
        $h = fopen($tmp, 'w');
        fputcsv($h, ['commodity', 'market', 'county', 'price_type', 'unit', 'price_kes', 'collected_on', 'source_url', 'licence_status'], ',', '"', '');
        $base = ['Nakuru' => 2650, 'Ol Kalou' => 2450, 'Molo' => 2500, 'Njoro' => 2550];
        mt_srand(4360);
        foreach ($base as $market => $b) {
            for ($w = 11; $w >= 0; $w--) {
                $p = $b + (int) (sin($w / 2) * 120) + mt_rand(-40, 40) + (11 - $w) * 8;
                fputcsv($h, ['irish-potato', $market, in_array($market, ['Nakuru', 'Molo', 'Njoro']) ? 'Nakuru' : 'Nyandarua', 'wholesale', '50 kg bag', $p, now()->subDays(1 + $w * 7)->toDateString(), 'https://example.invalid/synthetic-bulletin', 'synthetic'], ',', '"', '');
            }
        }
        fclose($h);
        app(PriceImporter::class)->import($source, new CsvPriceFeedAdapter($tmp));
        @unlink($tmp);
    }
}
