<?php

namespace App\Console\Commands;

use App\Services\Sharet\SharetCollections;
use App\Services\Sharet\SharetPayouts;
use Illuminate\Console\Command;

/** Every 5 minutes: open Sharet pay links and payouts still "paying" are checked; a safety net for missed webhooks. */
class SharetSync extends Command
{
    protected $signature = 'sharet:sync {--limit=200}';

    protected $description = 'Check open Sharet pay links and payouts in flight, and record any that finished';

    public function handle(SharetCollections $collections, SharetPayouts $payouts): int
    {
        if (! SharetCollections::available()) {
            $this->info('Sharet Pay is off; nothing to do.');

            return self::SUCCESS;
        }
        $this->info('Pay links updated: ' . $collections->syncOpen((int) $this->option('limit')));
        $this->info('Payouts checked: ' . $payouts->syncPaying());

        return self::SUCCESS;
    }
}
