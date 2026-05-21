<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('statements:generate')->monthlyOn(1, '00:00');
Schedule::command('loans:auto-repay')->daily();

// Distributed Database auto-healing synchronization
Schedule::command('db:sync-distributed')->everyMinute();

// Distributed Database monthly automated backups
Schedule::command('db:backup')->monthlyOn(1, '00:00');

// Update IQD/USD exchange rate every hour
Schedule::command('rates:update')->hourly();
