<?php

namespace App\Console\Commands;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\Loan;
use App\Services\Loans\LoanFundings;
use App\Services\Partners\PartnerSlices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Brings a loan's funding rows (loan_fundings) in line with the cash actually paid out (owner, 17 Nov 2026: the three
 * tables drifted; Yvonne's 5,000 on #510 had no row, so she earned nothing from it). Only adds: every cash payout a
 * partner line paid for that has no row gets one; existing rows (a slice the house settled included) stay as they
 * are; our money's row takes what is left of the cash paid out; every row's share is worked out again. Then every
 * repayment is re-split and each line's "lent" recounted from the rows, so give all of a line's loans together.
 * Dry run unless --apply.
 *
 *   php artisan loans:sync-fundings 510 555 556 557 [--apply]
 */
class LoansSyncFundings extends Command
{
    protected $signature = 'loans:sync-fundings {loans* : loan ids} {--apply : save it (otherwise worked out and rolled back)}';

    protected $description = 'Give every cash payout a partner line made its funding row, our money the rest (dry run unless --apply)';

    public function handle(PartnerSlices $slices, LoanFundings $fundings): int
    {
        $plans = [];
        foreach ((array) $this->argument('loans') as $id) {
            $loan = Loan::withTrashed()->find($id);
            if (! $loan) {
                $this->error("No loan #{$id}.");

                return self::FAILURE;
            }
            $rows = $fundings->rows($loan->id);
            $cash = Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->whereNull('deleted_at')->orderBy('id')->get();
            $total = round((float) $cash->sum('amount'), 2);
            $houseLine = Investment::where('return_type', 'house')->orderBy('id')->value('id');
            $covered = $rows->pluck('disbursement_id')->filter()->map(fn ($d) => (int) $d)->all();
            $plan = $rows->reject(fn ($r) => $r->is_house)->map(fn ($r) => ['line' => $r->investment_id, 'amount' => (float) $r->amount, 'disbursement' => $r->disbursement_id])->values()->all();
            $added = [];
            foreach ($cash as $d) {
                if ($d->investment_id && (int) $d->investment_id !== (int) $houseLine && ! in_array((int) $d->id, $covered, true)) {
                    $plan[] = ['line' => (int) $d->investment_id, 'amount' => round((float) $d->amount, 2), 'disbursement' => $d->id];
                    $added[] = '#' . $d->id . ' ' . number_format((float) $d->amount, 2) . ' from line #' . $d->investment_id;
                }
            }
            $partners = round(array_sum(array_column($plan, 'amount')), 2);
            if ($total - $partners > 0.009) {
                $plan[] = ['line' => $houseLine, 'amount' => round($total - $partners, 2), 'disbursement' => null];
            }
            $before = $rows->isEmpty() ? 'no rows' : $rows->map(fn ($r) => '#' . ($r->investment_id ?? '-') . ' ' . number_format((float) $r->amount, 2))->implode(', ');
            if (! $added && abs((float) $rows->sum('amount') - $total) < 0.01) {
                $this->line("Loan #{$loan->id}: rows match the KES " . number_format($total, 2) . ' paid out. Nothing to do.');

                continue;
            }
            $plans[] = [$loan, $plan, $before, $added, $total];
        }
        if (! $plans) {
            return self::SUCCESS;
        }

        $work = function () use ($slices, $plans) {
            $out = [];
            $lines = [];
            $houseLine = Investment::where('return_type', 'house')->orderBy('id')->value('id');
            foreach ($plans as [$loan, $plan, , , $total]) {
                $existing = DB::table('loan_fundings')->where('loan_id', $loan->id)->get();
                $have = $existing->pluck('disbursement_id')->filter()->map(fn ($d) => (int) $d)->all();
                foreach ($plan as $p) {
                    if ($p['disbursement'] && $p['line'] && (int) $p['line'] !== (int) $houseLine && ! in_array((int) $p['disbursement'], $have, true)) {
                        DB::table('loan_fundings')->insert(['loan_id' => $loan->id, 'investment_id' => $p['line'], 'partner_id' => LoanFundings::partnerOf(Investment::find($p['line'])),
                            'amount' => $p['amount'], 'share' => 0, 'disbursement_id' => $p['disbursement'], 'source' => 'slices',
                            'notes' => 'funding row for a payout that had none (loans:sync-fundings)', 'created_at' => now(), 'updated_at' => now()]);
                        $lines[$p['line']] = true;
                    }
                }
                // our money takes what is left of the cash paid out
                $sum = round((float) DB::table('loan_fundings')->where('loan_id', $loan->id)->sum('amount'), 2);
                $rest = round($total - $sum, 2);
                if (abs($rest) > 0.009) {
                    $house = DB::table('loan_fundings')->where('loan_id', $loan->id)->where('investment_id', $houseLine)->whereNull('settled_on')->orderBy('id')->first();
                    if ($house && (float) $house->amount + $rest > 0) {
                        DB::table('loan_fundings')->where('id', $house->id)->update(['amount' => round((float) $house->amount + $rest, 2), 'updated_at' => now()]);
                    } elseif ($rest > 0) {
                        DB::table('loan_fundings')->insert(['loan_id' => $loan->id, 'investment_id' => $houseLine, 'partner_id' => null, 'amount' => $rest, 'share' => 0,
                            'disbursement_id' => null, 'source' => 'slices', 'notes' => 'our money: the rest of the cash paid out (loans:sync-fundings)', 'created_at' => now(), 'updated_at' => now()]);
                    } else {
                        throw new \DomainException('Loan #' . $loan->id . ': the rows add up to more than the cash paid out; check it on the loan page.');
                    }
                }
                foreach (DB::table('loan_fundings')->where('loan_id', $loan->id)->get() as $r) {
                    DB::table('loan_fundings')->where('id', $r->id)->update(['share' => $total > 0 ? round((float) $r->amount / $total, 6) : 1]);
                }
                foreach ($existing->pluck('investment_id')->filter() as $l) {
                    $lines[$l] = true;
                }
                $out[$loan->id] = DB::table('loan_fundings')->where('loan_id', $loan->id)->orderBy('id')->get()
                    ->map(fn ($r) => '#' . ($r->investment_id ?? '-') . ' ' . number_format((float) $r->amount, 2) . ($r->settled_on ? ' (settled)' : ''))->all();
                \App\Services\AuditTrail::record('loans', $loan->id, 'update', ['fundings' => $existing->map(fn ($r) => [$r->investment_id, (float) $r->amount])->all()],
                    ['event' => 'loans:sync-fundings', 'fundings' => $out[$loan->id]]);
                foreach ($loan->cycles()->pluck('id') as $cycleId) {
                    app(\App\Services\Loans\RepaymentSplit::class)->syncCycle((int) $cycleId);
                }
            }
            $pl = app(\App\Services\Partners\PartnerLines::class);
            $after = [];
            foreach (array_keys($lines) as $id) {
                $l = Investment::find($id);
                if (! $l || $l->isHouseLine()) {
                    continue;
                }
                $slices->syncDraws($l);
                app(\App\Services\Loans\RepaymentSplit::class)->syncLineReturns($l->fresh());
                $s = $pl->status($l->fresh());
                $after[] = '#' . $l->id . ' lent ' . number_format($s['lent'], 2) . ', free ' . number_format($s['idle'], 2);
            }

            return ['changes' => $out, 'lines' => $after];
        };
        foreach ($plans as [$loan, , $before, $added, $total]) {
            $this->line("Loan #{$loan->id} · paid out KES " . number_format($total, 2) . " · before: {$before}" . ($added ? ' · adds ' . implode('; ', $added) : ''));
        }
        try {
            if ($this->option('apply')) {
                $before = DB::table('partner_transactions')->max('id') ?? 0;
                $r = DB::transaction($work);
                $journal = DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('t.id', '>', $before)->orderBy('t.id')
                    ->get(['p.name', 't.wallet_slug', 't.type', 't.amount', 't.loan_id', 't.reference', 't.transaction_date']);
            } else {
                $o = $slices->preview($work);
                [$r, $journal] = [$o['result'], $o['journal']];
            }
        } catch (\DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        foreach ($r['changes'] as $id => $c) {
            $this->line("Loan #{$id} rows after: " . implode('; ', $c));
        }
        $this->line('Lines after: ' . implode(' · ', $r['lines']));
        $this->table(['Partner', 'Wallet', 'Type', 'Amount', 'Loan', 'Date', 'Reference'], collect($journal)->map(fn ($t) => [$t->name, $t->wallet_slug, $t->type,
            number_format((float) $t->amount, 2), $t->loan_id, substr((string) $t->transaction_date, 0, 10), $t->reference])->all());
        $this->option('apply') ? $this->info('Saved.') : $this->warn('Dry run: nothing saved. Add --apply to save.');

        return self::SUCCESS;
    }
}
