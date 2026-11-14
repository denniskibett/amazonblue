<?php

namespace App\Console\Commands;

use App\Models\FacilitySplit;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\Repayment;
use App\Services\AuditTrail;
use App\Services\Loans\FacilitySplitter;
use App\Services\Loans\RepaymentSplit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sets a facility's own split (owner, 14 Nov 2026). Shows the figures; --apply saves it and re-splits the payments
 * from its start day. Loan 458 (ISIRO facility, agreed 14 Nov 2026):
 *
 *   php artisan loans:facility-split 458 --party=house:200000:50:150000 --party=5:44000:50:70000 \
 *       --pi-target=421632 --cap=1120000 --from=2026-11-14 --drop-broker-fee --apply
 *
 * --drop-broker-fee: the facility is a partner split, not a brokered loan (owner, 14 Nov 2026), so the loan stops
 * being brokered and every earlier payment is re-split; broker fees already posted are reversed by adjustment rows.
 *
 * --party=partner:principal:penalty%:received_before ("house" for the house account). Each party's
 * principal plus interest is its principal's share of --pi-target.
 */
class LoansFacilitySplit extends Command
{
    protected $signature = 'loans:facility-split {loan}
        {--party=* : partner:principal:penalty%:received_before (partner is an id or "house")}
        {--pi-target= : principal plus interest the parties share first}
        {--cap= : total to collect, counting what the parties already received}
        {--from= : payments on or after this day (Y-m-d) are split by this rule}
        {--note= : why}
        {--drop-broker-fee : the loan is no longer brokered; earlier broker fees are reversed}
        {--apply : save it and re-split the payments from --from}';

    protected $description = 'Set a facility\'s own split: principal plus interest first, then penalty, with catch-up';

    public function handle(FacilitySplitter $splitter): int
    {
        $loan = Loan::find($this->argument('loan'));
        if (! $loan) {
            $this->error('No such loan.');

            return self::FAILURE;
        }
        $house = Partner::where('is_house_account', true)->orderByRaw("account_type = 'principal' desc")->orderBy('id')->value('id');
        $parties = [];
        foreach ((array) $this->option('party') as $spec) {
            $bits = explode(':', $spec);
            if (count($bits) !== 4) {
                $this->error("--party={$spec}: use partner:principal:penalty%:received_before");

                return self::FAILURE;
            }
            [$who, $principal, $pct, $before] = $bits;
            $id = $who === 'house' ? $house : (int) $who;
            if (! $id || ! Partner::whereKey($id)->exists()) {
                $this->error("--party={$spec}: no such partner");

                return self::FAILURE;
            }
            $parties[] = ['partner_id' => $id, 'principal' => (float) $principal, 'penalty_pct' => (float) $pct, 'received_before' => (float) $before];
        }
        $target = (float) $this->option('pi-target');
        $cap = (float) $this->option('cap');
        $from = (string) $this->option('from');
        [$parties, $problems] = $splitter->prepare($parties, $target, $cap, $from);
        if ($problems) {
            foreach ($problems as $p) {
                $this->error($p);
            }

            return self::FAILURE;
        }

        // the figures, worked out on an unsaved copy
        $status = $splitter->status($splitter->draft($loan, $parties, $target, $cap, $from));
        $this->info("Loan #{$loan->id}: principal plus interest " . number_format($target, 2) . ', total ' . number_format($cap, 2) . ", from {$from}");
        $this->table(['Party', 'Principal', 'P+I', 'Penalty', 'Final total', 'Received before', 'Since ' . $from, 'Still to come'],
            array_map(fn ($r) => [$r['name'], number_format($r['principal'], 2), number_format($r['pi_entitled'], 2), number_format($r['penalty_entitled'], 2),
                number_format($r['entitled'], 2), number_format($r['received_before'], 2), number_format($r['received_since'], 2), number_format($r['to_come'], 2)], $status['parties']));
        $this->line('Collected since ' . $from . ': ' . number_format($status['collected_since'], 2) . ' · still to come in all: ' . number_format($status['to_come'], 2));

        $drop = (bool) $this->option('drop-broker-fee');
        if ($drop) {
            $this->showBrokerReversal($loan);
        }

        if (! $this->option('apply')) {
            $this->comment('Nothing saved. Add --apply to save it.');

            return self::SUCCESS;
        }

        $n = $splitter->save($loan, $parties, $target, $cap, $from, $this->option('note'), $drop);
        $this->info(RepaymentSplit::enabled()
            ? 'Saved. Re-split the payments' . ($drop ? '' : " from {$from}") . ": {$n} journal rows posted."
            : 'Saved. The repayment split is switched off here, so nothing was posted.');

        return self::SUCCESS;
    }

    /** What dropping the broker would change in the journal, worked out and rolled back. */
    private function showBrokerReversal(Loan $loan): void
    {
        if (! $loan->broker_status) {
            $this->line('Loan #' . $loan->id . ' is not brokered; nothing to reverse.');

            return;
        }
        if (! RepaymentSplit::enabled()) {
            $this->line('The repayment split is switched off here, so no journal rows would change.');

            return;
        }
        $rows = [];
        DB::beginTransaction();
        try {
            $loan->forceFill(['broker_status' => 0])->save();
            $split = app(RepaymentSplit::class);
            foreach (Repayment::withTrashed()->where('loan_id', $loan->id)->orderBy('id')->get() as $r) {
                foreach ($split->diff($r) as [$partner, $type, $wallet, $change]) {
                    $rows[] = ['#' . $r->id . ' ' . optional($r->repayment_date)->format('j M Y'), Partner::whereKey($partner)->value('name') ?? '#' . $partner,
                        str_replace('_', ' ', $type), $wallet, number_format($change, 2)];
                }
            }
        } finally {
            DB::rollBack();
            $loan->refresh();   // the rollback undid the change; the model must not keep it
        }
        $this->info('Dropping the broker on loan #' . $loan->id . ' changes the journal by:');
        $rows ? $this->table(['Repayment', 'Partner', 'Type', 'Wallet', 'Change'], $rows) : $this->line('  nothing');
    }
}
