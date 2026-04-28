<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('erp:sync-all')->everyThirtyMinutes();
Schedule::command('wallet:release-held-balances')->daily();
Schedule::command('sellers:recalculate-scores')->hourly();
Schedule::command('orders:cleanup-abandoned')->everyFiveMinutes();
