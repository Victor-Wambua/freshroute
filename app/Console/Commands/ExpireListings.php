<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Services\Transitions;
use Illuminate\Console\Command;

class ExpireListings extends Command
{
    protected $signature = 'fr:expire-listings';

    protected $description = 'Expire active listings whose availability window has ended';

    public function handle(Transitions $t): int
    {
        $n = 0;
        foreach (Listing::open()->whereNotNull('available_to')->where('available_to', '<', today())->get() as $l) {
            $t->listing($l, ListingStatus::Expired);
            $n++;
        }
        $this->info("Expired: $n");

        return self::SUCCESS;
    }
}
