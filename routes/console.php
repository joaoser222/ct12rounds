<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:mark-overdue-cash')
    ->dailyAt('00:10')
    ->withoutOverlapping();

// Expiry is capped at 2h so a hard crash does not block syncs for the
// default 24h; a run longer than that would overlap anyway.
Schedule::command('gateway:sync-fiscal-invoices')
    ->everyThirtyMinutes()
    ->withoutOverlapping(120);

Schedule::command('loyalty:refresh')
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('db:backup')
    ->dailyAt('03:30')
    ->onOneServer()
    ->withoutOverlapping();
