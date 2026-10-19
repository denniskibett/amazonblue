<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Loan status / NPL jobs
|--------------------------------------------------------------------------
| Needs ONE cron entry on the server (CyberPanel > Cron Jobs, site user):
|   * * * * * cd /home/<site>/public_html && php artisan schedule:run >> /dev/null 2>&1
| Check with: php artisan schedule:list
*/
Schedule::command('loans:update-statuses')
    ->dailyAt('01:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/loan-statuses.log'));

Schedule::command('loans:detect-npl')
    ->dailyAt('01:30')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/loan-npl.log'));

// read-only report: repaid loans that still owe, active cycles after repaid, misdated/misplaced repayments (Phase 0.11)
Schedule::command('loans:check-integrity --csv --notify')
    ->dailyAt('02:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/loan-integrity.log'));
