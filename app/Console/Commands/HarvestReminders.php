<?php

namespace App\Console\Commands;

use App\Models\HarvestCalendarEntry;
use App\Services\Notifier;
use Illuminate\Console\Command;

class HarvestReminders extends Command
{
    protected $signature = 'fr:harvest-reminders {--days=3 : remind this many days ahead}';

    protected $description = 'Send harvest reminders for calendar entries due soon (once per entry)';

    public function handle(Notifier $notifier): int
    {
        $n = 0;
        $entries = HarvestCalendarEntry::with('user')->whereNull('reminded_at')
            ->whereBetween('harvest_on', [today(), today()->addDays((int) $this->option('days'))])->get();
        foreach ($entries as $e) {
            $notifier->send($e->user, 'harvest_reminder', ['date' => $e->harvest_on->toDateString(), 'bags' => $e->expected_bags ?? '-'], route('calendar.index', [], false));
            $e->update(['reminded_at' => now()]);
            $n++;
        }
        $this->info("Reminders sent: $n");

        return self::SUCCESS;
    }
}
