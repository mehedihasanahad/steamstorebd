<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// A scheduled campaign is only an intention until this runs.
Schedule::command('campaigns:send-due')->everyMinute()->withoutOverlapping();
