<?php

namespace App\Console\Commands;

use Database\Seeders\SyntheticDataSeeder;
use Illuminate\Console\Command;

class SeedSynthetic extends Command
{
    protected $signature = 'fr:seed-synthetic';

    protected $description = 'Load SYNTHETIC demonstration data (labelled origin=synthetic everywhere). Refuses in production.';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => SyntheticDataSeeder::class, '--force' => true]);
        $this->warn('Synthetic demonstration data loaded. Demo password: '.SyntheticDataSeeder::PASSWORD);

        return self::SUCCESS;
    }
}
