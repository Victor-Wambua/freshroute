<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fr:harvest-reminders')->dailyAt('06:00');
Schedule::command('fr:notify-matches')->dailyAt('07:00');
Schedule::command('fr:expire-listings')->dailyAt('00:10');
Schedule::command('fr:process-deletions')->dailyAt('02:00');
Schedule::command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping();
