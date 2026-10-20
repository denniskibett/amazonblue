<?php

namespace App\Services\Loans;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use App\Services\CustomerWallets;
use App\Services\PartnerLedger;
use Illuminate\Support\Facades\DB;

/**
 * Splits each cash repayment between the parties (Stage 4 Phase 3, owner decisions of 20-21 Oct 2026).
 *
 * The cycle's waterfall says what each repayment paid of penalty, interest and principal
 * (CycleBalance allocations). Then:
 *  - brokered loan (loans.broker_status = 1, loans.partner_id = the broker):
 *      interest: the broker earns loans.broker_rate % when AC funds the loan (introducer, 40%),
 *                or 100 - brokers.interest_broker % when the broker's own loan_share line funds it (60%);
 *      penalty:  the broker earns loans.broker_penalty_rate % (50%);
 *  - loan funded by a non-broker partner's loan_share line: the partner earns partners.profit_share_rate % of
 *    the interest;
 *  - principal paid on a loan funded by a partner line (loan_share or fixed_rate) goes back to that line
 *    (investment_fundings.amount_returned), which frees the partner's capital ("available");
 *  - AC keeps the rest, recorded on the house account (owner, 21 Oct 2026) so that for every repayment
 *    broker + partner + house = the interest and penalty it paid. Book entries (rollover, discount, write-off)
 *    earn nobody anything.
 *
 * Shares are posted to the partner journal (partner_transactions + the partner's wallet, which the partner
 * portal reads; the ledger follows from the journal). Posting is by difference: a repayment that is edited,
 * deleted or reversed moves its shares by exactly the change, with new rows; nothing is edited.
 */
class RepaymentSplit
{
    public const PREFIX = 'SPLIT-R';

    public static function enabled(): bool
    {
        return (bool) config('lending.split.enabled') && \App\Services\LoanCalculator::v2();
    }

    /** The shares a repayment should have produced: [ [partner_id, type, wallet, amount, why], ... ]. */
    public function plan(Repayment $r): array
    {
        if (CustomerWallets::isGone($r) || CustomerWallets::kindOf($r) !== 'cash' || ! $r->loan_cycle_id) {
            return [];
        }
        $loan = Loan::withTrashed()->find($r->loan_id);
        $cycle = LoanCycle::find($r->loan_cycle_id);
        if (! $loan || ! $cycle) {
            return [];
        }
        $a = CycleBalance::at($cycle)['allocations'][$r->id] ?? null;
        if (! $a) {
            return [];
        }

        [$funder, $line] = $this->funder($loan);
        $out = [];
        $interestOut = 0.0;   // what others take of this repayment's interest / penalty; the house keeps the rest
        $penaltyOut = 0.0;
        if ($loan->broker_status && $loan->partner_id) {
            $broker = (int) $loan->partner_id;
            $interestRate = $funder === $broker && $line?->return_type === 'loan_share'
                ? 100 - (float) (DB::table('brokers as b')->join('partners as p', 'p.user_id', '=', 'b.user_id')->where('p.id', $broker)->value('b.interest_broker') ?? 40)
                : (float) $loan->broker_rate;
            $penaltyRate = (float) ($loan->broker_penalty_rate ?? config('lending.split.default_broker_penalty_rate', 50));
            $out[] = [$broker, 'broker_fee_earned', 'broker_fee', $i = round($a['interest'] * $interestRate / 100, 2), "{$interestRate}% of interest"];
            $out[] = [$broker, 'penalty_share', 'broker_fee', $p = round($a['penalty'] * $penaltyRate / 100, 2), "{$penaltyRate}% of penalty"];
            $interestOut += $i;
            $penaltyOut += $p;
        } elseif ($funder && $line?->return_type === 'loan_share') {
            $rate = (float) Partner::whereKey($funder)->value('profit_share_rate');
            $out[] = [$funder, 'profit_distribution', 'interest', $i = round($a['interest'] * $rate / 100, 2), "{$rate}% of interest (funder)"];
            $interestOut += $i;
        }
        if ($house = $this->house()) {
            $out[] = [$house, 'profit_distribution', 'interest', round($a['interest'] - $interestOut, 2), 'AC: interest after partners'];
            $out[] = [$house, 'penalty_share', 'interest', round($a['penalty'] - $penaltyOut, 2), 'AC: penalty after partners'];
        }

        return array_values(array_filter($out, fn ($s) => abs($s[3]) >= 0.005));
    }

    /** Interest + penalty a cash repayment paid (what its split shares out). */
    public function collected(Repayment $r): float
    {
        if (CustomerWallets::isGone($r) || CustomerWallets::kindOf($r) !== 'cash' || ! $r->loan_cycle_id || ! ($cycle = LoanCycle::find($r->loan_cycle_id))) {
            return 0.0;
        }
        $a = CycleBalance::at($cycle)['allocations'][$r->id] ?? null;

        return $a ? round($a['interest'] + $a['penalty'], 2) : 0.0;
    }

    /** Brings the journal for one repayment to its plan. Returns the rows posted. */
    public function sync(Repayment $r): array
    {
        $posted = [];
        $target = [];
        foreach ($this->plan($r) as [$partner, $type, $wallet, $amount]) {
            $target["{$partner}|{$type}|{$wallet}"] = ($target["{$partner}|{$type}|{$wallet}"] ?? 0) + $amount;
        }
        $existing = PartnerTransaction::where('repayment_id', $r->id)->where('reference', 'like', self::PREFIX . $r->id . '-%')
            ->selectRaw('partner_id, type, wallet_slug, sum(amount) as total, count(*) as n')->groupBy('partner_id', 'type', 'wallet_slug')->get()
            ->keyBy(fn ($e) => "{$e->partner_id}|{$e->type}|{$e->wallet_slug}");

        foreach (array_unique(array_merge(array_keys($target), $existing->keys()->all())) as $key) {
            $diff = round(($target[$key] ?? 0) - (float) ($existing[$key]->total ?? 0), 2);
            if (abs($diff) < 0.005) {
                continue;
            }
            [$partnerId, $type, $wallet] = explode('|', $key);
            $seq = (int) ($existing[$key]->n ?? 0) + 1;
            $posted[] = app(PartnerLedger::class)->post(
                Partner::findOrFail($partnerId), $wallet, $type, $diff,
                loanId: $r->loan_id, repaymentId: $r->id,
                reference: self::PREFIX . $r->id . '-' . strtoupper($type) . '-' . $partnerId . '-' . $seq,
                notes: ($diff < 0 ? 'Adjustment: ' : '') . ucfirst(str_replace('_', ' ', $type)) . ' on repayment #' . $r->id . ' (loan #' . $r->loan_id . ')',
                transactionDate: optional($r->repayment_date)->toDateString(),
                force: true,
            );
        }

        return $posted;
    }

    /** Re-splits every repayment of a cycle (one payment's split depends on the payments before it). */
    public function syncCycle(int $cycleId): void
    {
        foreach (Repayment::withTrashed()->where('loan_cycle_id', $cycleId)->orderBy('id')->get() as $r) {
            $this->sync($r);
        }
        $loanId = LoanCycle::whereKey($cycleId)->value('loan_id');
        if ($loanId) {
            $this->syncReturns($loanId);
        }
    }

    /**
     * Principal paid back on loans a partner line funded goes back to that line: amount_returned is set to the
     * principal repaid on those loans (a recomputed total, never an increment). House lines are left alone.
     */
    public function syncReturns(int $loanId): void
    {
        [$funder, $line] = $this->funder(Loan::withTrashed()->find($loanId));
        if (! $funder || ! $line || ! in_array($line->return_type, ['loan_share', 'fixed_rate'], true)) {
            return;
        }
        $loanIds = Disbursement::where('investment_id', $line->id)->where('kind', 'cash')->distinct()->pluck('loan_id');
        $principal = 0.0;
        foreach (LoanCycle::whereIn('loan_id', $loanIds)->get() as $cycle) {
            foreach (CycleBalance::at($cycle)['allocations'] as $repaymentId => $a) {
                if (CustomerWallets::kindOf(Repayment::withTrashed()->find($repaymentId)) === 'cash') {
                    $principal += $a['principal'];
                }
            }
        }
        $funding = InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $funder)->orderBy('id')->first();
        $funding?->forceFill(['amount_returned' => round($principal, 2)])->save();
    }

    private ?int $house = null;

    /** The house partner (AC) whose journal records AC's share. */
    private function house(): ?int
    {
        return $this->house ??= Partner::where('is_house_account', true)
            ->orderByRaw("account_type = 'principal' desc")->orderBy('id')->value('id');
    }

    /** The partner whose line funded the loan's cash, and the line: [partner_id|null, Investment|null]. */
    private function funder(?Loan $loan): array
    {
        if (! $loan) {
            return [null, null];
        }
        $investmentId = Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->whereNotNull('investment_id')->orderBy('id')->value('investment_id');
        $line = $investmentId ? Investment::find($investmentId) : null;
        if (! $line || $line->isHouseLine()) {
            return [null, $line];
        }
        $partner = InvestmentFunding::where('investment_id', $line->id)->whereHas('partner', fn ($q) => $q->where('is_house_account', false))
            ->orderBy('id')->value('partner_id');

        return [$partner ? (int) $partner : null, $line];
    }
}
