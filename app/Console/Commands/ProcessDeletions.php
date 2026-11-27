<?php

namespace App\Console\Commands;

use App\Services\AccountDeletion;
use Illuminate\Console\Command;

class ProcessDeletions extends Command
{
    protected $signature = 'fr:process-deletions';

    protected $description = 'Anonymise accounts whose deletion grace period has ended';

    public function handle(AccountDeletion $d): int
    {
        $this->info('Anonymised: '.$d->processDue());

        return self::SUCCESS;
    }
}
