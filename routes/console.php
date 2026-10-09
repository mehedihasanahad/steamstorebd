<?php

use App\Services\Competitor\PriceWatchSchedule;
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
//
// Whether it runs and when are read from Site Settings, falling back to
// .env -- see PriceWatchSchedule. This file is re-read on every schedule
// tick, so an hour changed on the admin screen is in force a minute later
// without a deploy or a restart.
if (PriceWatchSchedule::enabled()) {
    Schedule::command('competitor:check-prices')
        ->dailyAt(PriceWatchSchedule::time())
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();
}
