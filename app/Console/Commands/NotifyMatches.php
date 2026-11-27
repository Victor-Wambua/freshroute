<?php

namespace App\Console\Commands;

use App\Models\BuyerNeed;
use App\Services\MatchScorer;
use App\Services\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NotifyMatches extends Command
{
    protected $signature = 'fr:notify-matches';

    protected $description = 'Notify farmers of open buyer needs that match their listings (each pair once)';

    public function handle(MatchScorer $matcher, Notifier $notifier): int
    {
        $n = 0;
        $min = config('freshroute.match_notify_threshold');
        foreach (BuyerNeed::where('status', 'open')->where('needed_by', '>=', today())->get() as $need) {
            foreach ($matcher->matchesForNeed($need, 50) as $m) {
                $key = "match-notified:{$need->id}:{$m['listing']->id}";
                if ($m['score'] >= $min && Cache::add($key, 1, now()->addDays(30))) {
                    $notifier->send($m['listing']->user, 'matching_demand', ['bags' => $need->quantity_bags, 'county' => $need->county, 'score' => $m['score']], route('listings.show', $m['listing'], false));
                    $n++;
                }
            }
        }
        $this->info("Match notifications sent: $n");

        return self::SUCCESS;
    }
}
