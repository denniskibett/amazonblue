<?php

namespace App\Console\Commands;

use App\Services\BorrowerAnalytics;
use Illuminate\Console\Command;

/** Recomputes every borrower's behaviour profile (owner, 23 Oct 2026). Derived data only; safe to run any time. */
class AnalyticsProfiles extends Command
{
    protected $signature = 'analytics:profiles';

    protected $description = "Recompute each borrower's behaviour profile (on-time rate, comfortable amount, weekend pattern, score, suggested limit)";

    public function handle(BorrowerAnalytics $analytics): int
    {
        $start = microtime(true);
        $n = $analytics->refreshProfiles();
        $this->info("{$n} borrower profiles written in " . round(microtime(true) - $start, 1) . 's.');

        return self::SUCCESS;
    }
}
