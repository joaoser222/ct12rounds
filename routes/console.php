<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:mark-overdue-cash')->dailyAt('00:10');
Schedule::command('gateway:sync-fiscal-invoices')->everyThirtyMinutes();
Schedule::command('loyalty:refresh')->dailyAt('03:00');
