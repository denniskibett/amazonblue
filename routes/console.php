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

// engine v2 + ledger: book each open cycle's penalty for the day (does nothing while either switch is off)
Schedule::command('ledger:accrue')
    ->dailyAt('01:15')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ledger-accrue.log'));

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

// fixed-rate partners: their monthly simple interest for the month that just ended (owner, 21 Oct 2026)
Schedule::command('partners:accrue-fixed')
    ->monthlyOn(1, '02:30')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/partners-fixed.log'));

// audit_logs retention (decision 23, config lending.retention); deletes only rows past their limit
Schedule::command('audit:prune')
    ->monthlyOn(1, '03:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/audit-prune.log'));
