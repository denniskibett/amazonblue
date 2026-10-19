<?php

namespace App\Console\Commands;

use App\Models\Investment;
use App\Models\InvestmentFunding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off correction for house funding rows created with amount_committed = 0 (the disbursement
 * screen's auto-link). With nothing committed, the first draw marked the row settled and every later
 * draw went untracked: house investment #1's row shows 20,000 drawn while 186,000 was disbursed on it.
 *
 * Sets the row's committed AND drawn amounts to what was actually disbursed against the investment
 * (non-deleted disbursements, less what other funding rows already account for), keeps it settled and
 * appends a dated correction note. Nothing is deleted. Later draws on a house line go to an open house
 * row (committed 0) that never auto-settles (Investment::recordFundingDisbursement).
 */
class InvestmentsFixHouseFunding extends Command
{
    protected $signature = 'investments:fix-house-funding {--dry-run : Show the corrections without saving them}';

    protected $description = 'Correct house funding rows with nothing committed so they show what was actually drawn.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $rows = InvestmentFunding::with('partner', 'investment')
            ->where('amount_committed', 0)
            ->whereHas('partner', fn ($q) => $q->where('is_house_account', true))
            ->where('notes', 'like', 'Auto-linked to House Principal%')
            ->orderBy('id')->get();

        $changes = [];
        foreach ($rows as $f) {
            /** @var Investment $inv */
            $inv = $f->investment;
            $drawn = (float) $inv->disbursements()->whereNull('deleted_at')->sum('amount');
            $byOthers = (float) $inv->fundings()->whereKeyNot($f->id)->sum('amount_disbursed');
            $target = round(max(0, $drawn - $byOthers), 2);
            if ($target <= 0 || abs($target - (float) $f->amount_disbursed) < 0.005 && (float) $f->amount_committed === $target) {
                continue;
            }
            $changes[] = [$f, $target, $drawn];
        }

        if (! $changes) {
            $this->info('No house funding rows to correct.');

            return self::SUCCESS;
        }

        $this->table(['funding', 'investment', 'committed', 'drawn (row)', 'drawn (disbursements)', 'set committed + drawn to'], array_map(fn ($c) => [
            '#' . $c[0]->id, '#' . $c[0]->investment_id . ' ' . $c[0]->investment->name,
            number_format((float) $c[0]->amount_committed, 2), number_format((float) $c[0]->amount_disbursed, 2),
            number_format($c[2], 2), number_format($c[1], 2),
        ], $changes));

        if ($dry) {
            $this->info('Dry run: nothing saved.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as [$f, $target]) {
                $note = sprintf('[Correction %s] committed %s -> %s and drawn %s -> %s: the row had nothing committed, so it settled after the first draw and later draws went untracked (audit C, investments). Committed set to what was actually drawn.',
                    now()->toDateString(), number_format((float) $f->amount_committed, 2), number_format($target, 2),
                    number_format((float) $f->amount_disbursed, 2), number_format($target, 2));
                $f->update([
                    'amount_committed' => $target,
                    'amount_disbursed' => $target,
                    'status' => 'settled',
                    'notes' => trim(($f->notes ? $f->notes . "\n" : '') . $note),
                ]);
                $f->investment->syncLegacyFunding();
            }
        });
        $this->info(count($changes) . ' funding row(s) corrected.');

        return self::SUCCESS;
    }
}
