<?php

namespace App\Console\Commands;

use App\Models\Investment;
use App\Services\Partners\PartnerLines;
use Illuminate\Console\Command;

/**
 * Nightly (owner, 23 Oct 2026): close partner lines that are done (all their loans closed; long-term lines also
 * past their pay-back date) and queue their payouts on Requests. Repayments already do this as they arrive; this
 * catches lines that mature on a quiet day. --dry-run lists what would close.
 */
class PartnersLines extends Command
{
    protected $signature = 'partners:lines {--dry-run : List what would close without changing anything}';

    protected $description = 'Close finished partner funding lines and queue their payouts on Requests';

    public function handle(PartnerLines $lines): int
    {
        $rows = [];
        foreach (Investment::where('status', 'active')->where('return_type', 'loan_share')->orderBy('id')->get() as $line) {
            if ($line->isHouseLine()) {
                continue;
            }
            $s = $lines->status($line);
            $closed = ! $this->option('dry-run') && $s['closable'] ? $lines->closeIfDone($line) : false;
            $rows[] = [$line->id, $line->name, $line->facility_type, optional($line->maturity_date)->toDateString() ?? '—', number_format($s['committed'], 2),
                number_format($s['out'], 2), number_format($s['earned'], 2), $s['open_loans'] . '/' . $s['loans'],
                $s['closable'] ? ($this->option('dry-run') ? 'would close' : ($closed ? 'closed' : 'no change')) : 'open'];
        }
        $this->table(['#', 'Line', 'Term', 'Pay back by', 'Capital', 'Lent out now', 'Earned', 'Open loans', 'Result'], $rows);

        return self::SUCCESS;
    }
}
