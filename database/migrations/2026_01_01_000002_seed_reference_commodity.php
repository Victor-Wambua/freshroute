<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Reference data (not demo data): the single MVP commodity. The schema itself is commodity-generic. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('commodities')->insertOrIgnore([
            'slug' => 'irish-potato', 'name_en' => 'Irish potato', 'name_sw' => 'Viazi mviringo',
            'unit_en' => '50 kg bag', 'unit_sw' => 'gunia la kg 50', 'unit_kg' => 50, 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('commodities')->where('slug', 'irish-potato')->delete();
    }
};
