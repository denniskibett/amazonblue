<?php

namespace App\Console\Commands;

use App\Services\Sharet\SharetPayouts;
use Illuminate\Console\Command;

/**
 * 08:00, 12:00 and 16:00 Africa/Nairobi: approved loan disbursements and approved partner payouts set to go through
 * Sharet are sent. A second run in the same slot sends nothing. Does nothing while Sharet payouts are off.
 */
class SweepPayments extends Command
{
    protected $signature = 'payments:sweep {--slot= : Override the slot, e.g. 2026-11-09T08} {--dry-run : List what would be sent}';

    protected $description = 'Send approved disbursements and partner payouts through Sharet';

    public function handle(SharetPayouts $payouts): int
    {
        $rows = $payouts->sweep($this->option('slot') ?: null, (bool) $this->option('dry-run'));
        $this->table(['Payout', 'Kind', 'Status', 'KES', 'Note'], array_map(
            fn ($r) => [$r['id'], $r['kind'], $r['status'], isset($r['amount']) ? number_format($r['amount'], 2) : '', $r['message'] ?? ''],
            $rows
        ));

        return self::SUCCESS;
    }
}
