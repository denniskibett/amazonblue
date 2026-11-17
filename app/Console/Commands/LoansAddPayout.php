<?php

namespace App\Console\Commands;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\DisbursementService;
use App\Services\Loans\RepaymentSplit;
use App\Services\Partners\PartnerSlices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * A payout already sent to a borrower, recorded after the fact from a partner line or our money (owner, 17 Nov 2026:
 * the rest of Yvonne's line #14 went to Dennis Kibet's #557 and IMAGINE-NATION's loan). The same steps as Add
 * Disbursement: the disbursement, its line and the line's draw. With --raise, a further payout on a loan already out
 * also raises what the borrower owes (loan amount, cycle principal and its interest at the cycle's rate), which the
 * app has no screen for. Dry run unless --apply; the same reference on the loan twice is refused.
 *
 *   php artisan loans:add-payout 557 --amount=750 --date=2026-11-14 --mode=bank_to_mpesa --bank-ref=5896TSZY3425 --mpesa-ref=UKEGU2K25F --line=14 --raise [--apply]
 */
class LoansAddPayout extends Command
{
    protected $signature = 'loans:add-payout {loan}
        {--amount= : what was sent}
        {--date= : the day it was sent (Y-m-d)}
        {--mode=bank_transfer : mpesa, bank_to_mpesa, bank_transfer, pesalink, cash, other}
        {--ref= : the reference (when there is one)}
        {--bank-ref= : the bank reference}
        {--mpesa-ref= : the M-Pesa reference}
        {--line= : the partner line that paid for it (default our money)}
        {--raise : also raise what the borrower owes by the amount}
        {--note= : a note on the disbursement}
        {--apply : save it (otherwise worked out and rolled back)}';

    protected $description = 'Record a payout already sent on a loan, from a partner line or our money (dry run unless --apply)';

    public function handle(DisbursementService $payouts, PartnerSlices $slices, RepaymentSplit $split): int
    {
        $loan = Loan::find($this->argument('loan'));
        $amount = round((float) $this->option('amount'), 2);
        $date = (string) $this->option('date');
        $ref = strtoupper(trim((string) ($this->option('bank-ref') ?: $this->option('mpesa-ref') ?: $this->option('ref'))));
        if (! $loan || $amount <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $ref === '') {
            $this->error('Give a loan id, --amount above 0, --date=Y-m-d and a reference (--ref, --bank-ref or --mpesa-ref).');

            return self::FAILURE;
        }
        if (Disbursement::where('loan_id', $loan->id)->whereNull('deleted_at')->where(fn ($q) => $q->where('transaction', $ref)->orWhere('bank_ref', $ref)->orWhere('mpesa_ref', $ref))->exists()) {
            $this->warn("Loan #{$loan->id} already has a payout with reference {$ref}: nothing to do.");

            return self::SUCCESS;
        }
        $line = $this->option('line') ? Investment::find($this->option('line')) : Investment::where('return_type', 'house')->orderBy('id')->first();
        if (! $line) {
            $this->error('No such line.');

            return self::FAILURE;
        }

        $work = function () use ($payouts, $split, $loan, $amount, $date, $ref, $line) {
            $loan = Loan::lockForUpdate()->findOrFail($loan->id);
            $cycle = $loan->getCurrentCycle();
            $line->assertCanDisburse($amount);
            $before = ['amount' => (float) $loan->amount, 'principal' => (float) ($cycle?->principal ?? 0), 'balance' => (float) ($cycle?->new_balance ?? 0)];
            $d = $payouts->createDisbursement($loan, ['amount' => $amount, 'disburse_date' => $date, 'transaction' => $ref,
                'bank_ref' => strtoupper((string) $this->option('bank-ref')) ?: null, 'mpesa_ref' => strtoupper((string) $this->option('mpesa-ref')) ?: null,
                'mode' => (string) $this->option('mode'), 'notes' => $this->option('note') ?: 'Recorded after the fact (loans:add-payout)'], $cycle);
            $d->update(['investment_id' => $line->id, 'funding_source' => $line->isHouseLine() ? 'internal' : 'partner']);
            $line->recordFundingDisbursement($amount);
            // its funding row too, so loan_fundings keeps matching the cash paid out (owner, 17 Nov 2026)
            \Illuminate\Support\Facades\Artisan::call('loans:sync-fundings', ['loans' => [$loan->id], '--apply' => true]);

            if ($this->option('raise') && ($cycle = LoanCycle::find($d->loan_cycle_id))) {
                // what they owe goes up by what they got, at the cycle's own rate
                $principal = round((float) $cycle->lentPrincipal() + $amount, 2);
                $interest = round($principal * (float) $cycle->interest_rate / 100, 2);
                $cycle->forceFill(['principal' => $principal, 'interest_capitalized' => $interest, 'new_balance' => round($principal + $interest, 2),
                    'closing_balance' => round($principal + $interest, 2)])->save();
                $loan->forceFill(['amount' => round((float) $loan->amount + $amount, 2), 'original_amount' => round((float) ($loan->original_amount ?: $loan->amount) + $amount, 2),
                    'capitalized_interest' => $interest])->save();
                $split->syncCycle((int) $cycle->id);
            }
            if ($loan->status === 'approved') {
                $loan->forceFill(['status' => 'disbursed'])->save();
            }
            $cycle = LoanCycle::find($d->loan_cycle_id);
            $s = app(\App\Services\Partners\PartnerLines::class)->status($line->fresh());

            return ['d' => $d->id, 'before' => $before, 'after' => ['amount' => (float) $loan->fresh()->amount, 'principal' => (float) $cycle->lentPrincipal(),
                'interest' => (float) $cycle->interest_capitalized, 'balance' => (float) $cycle->new_balance, 'due' => substr((string) $cycle->due_date, 0, 10)],
                'line' => $line->id . ' ' . $line->name, 'free' => $line->isHouseLine() ? null : $s['idle']];
        };

        $this->line("Loan #{$loan->id} · KES " . number_format($amount, 2) . " on {$date} · {$this->option('mode')} {$ref}");
        try {
            if (! $this->option('apply')) {
                $out = $slices->preview($work);
                $this->report($out['result']);
                $this->warn('Dry run: nothing saved. Add --apply to save.');

                return self::SUCCESS;
            }
            $r = DB::transaction($work);
        } catch (\DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->report($r);
        $this->info('Saved.');

        return self::SUCCESS;
    }

    private function report(array $r): void
    {
        $b = $r['before'];
        $a = $r['after'];
        $this->line("Disbursement #{$r['d']} from line #{$r['line']}" . ($r['free'] !== null ? ' · line has KES ' . number_format($r['free'], 2) . ' free after' : ''));
        $this->line('Loan amount ' . number_format($b['amount'], 2) . ' → ' . number_format($a['amount'], 2) . ' · cycle principal ' . number_format($b['principal'], 2) . ' → '
            . number_format($a['principal'], 2) . ' · interest ' . number_format($a['interest'], 2) . ' · owed ' . number_format($a['balance'], 2) . ' · due ' . $a['due']);
    }
}
