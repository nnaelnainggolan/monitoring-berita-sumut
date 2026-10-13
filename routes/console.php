<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('issues:remind')->hourly()->between('08:00', '18:00')->withoutOverlapping();

Schedule::command('reports:daily')->dailyAt('07:30')->withoutOverlapping();
Schedule::command('workflow:mark-stale')->everyFiveMinutes()->withoutOverlapping();
