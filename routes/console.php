<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// A scheduled campaign is only an intention until this runs.
Schedule::command('campaigns:send-due')->everyMinute()->withoutOverlapping();

// The nightly competitor price sweep. It runs in the background because a
// full catalogue takes minutes to walk and campaigns:send-due is waiting
// behind it on the same tick.
if (config('competitor.schedule.enabled')) {
    Schedule::command('competitor:check-prices')
        ->dailyAt((string) config('competitor.schedule.time'))
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();
}
