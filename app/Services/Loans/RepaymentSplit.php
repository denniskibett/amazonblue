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

        return $this->shares($loan, $a, optional($r->repayment_date)->toDateString());
    }

    /**
     * The shares of one allocation [penalty, interest, principal] on a loan: [ [partner_id, type, wallet, amount, why], ... ].
     * $date (the repayment's day) matters for part-funded loans: a slice the house settled before it counts as the house's.
     */
    public function shares(Loan $loan, array $a, ?string $date = null): array
    {
        $out = [];
        $interestOut = 0.0;   // what others take of this repayment's interest / penalty; the house keeps the rest
        $penaltyOut = 0.0;
        $brokered = $loan->broker_status && $loan->partner_id;
        $broker = $brokered ? (int) $loan->partner_id : null;
        // part funding (owner, 1 Nov 2026): each funder's slice of the interest follows the single-funder rules
        foreach ($this->slices($loan, (float) $a['interest'], (float) $a['penalty'], $date) as [$funder, $line, $interest, $penalty, $of]) {
            if ($brokered) {
                $interestRate = $funder === $broker && $line?->return_type === 'loan_share'
                    ? 100 - (float) (DB::table('brokers as b')->join('partners as p', 'p.user_id', '=', 'b.user_id')->where('p.id', $broker)->value('b.interest_broker') ?? 40)
                    : (float) $loan->broker_rate;
                $out[] = [$broker, 'broker_fee_earned', 'broker_fee', $i = round($interest * $interestRate / 100, 2), "{$interestRate}% of interest{$of}"];
                $interestOut += $i;
            } elseif ($funder && $line?->return_type === 'loan_share') {
                // the line's own share (owner, 3 Nov 2026: 60% when the money is the partner's and the client ours),
                // else the partner's profit-share rate as before
                $rate = (float) ($line->partner_share_rate ?? Partner::whereKey($funder)->value('profit_share_rate'));
                $out[] = [$funder, 'profit_distribution', 'interest', $i = round($interest * $rate / 100, 2), "{$rate}% of interest (funder{$of})"];
                $interestOut += $i;
                // and its share of the penalty on its slice (50/50); lines recorded before this have none
                if ($line->partner_penalty_rate !== null && $penalty > 0) {
                    $prate = (float) $line->partner_penalty_rate;
                    $out[] = [$funder, 'penalty_share', 'interest', $p = round($penalty * $prate / 100, 2), "{$prate}% of penalty (funder{$of})"];
                    $penaltyOut += $p;
                }
            }
        }
        // penalty stays whole: the broker's rate of all of it (owner, 1 Nov 2026), not pro rata
        if ($brokered) {
            $penaltyRate = (float) ($loan->broker_penalty_rate ?? config('lending.split.default_broker_penalty_rate', 50));
            $out[] = [$broker, 'penalty_share', 'broker_fee', $p = round($a['penalty'] * $penaltyRate / 100, 2), "{$penaltyRate}% of penalty"];
            $penaltyOut += $p;
        }
        if ($house = $this->house()) {
            $out[] = [$house, 'profit_distribution', 'interest', round($a['interest'] - $interestOut, 2), 'AC: interest after partners'];
            $out[] = [$house, 'penalty_share', 'interest', round($a['penalty'] - $penaltyOut, 2), 'AC: penalty after partners'];
        }

        return array_values(array_filter($out, fn ($s) => abs($s[3]) >= 0.005));
    }

    /**
     * Who funded the loan and their slice of $interest and $penalty: [ [partner_id|null, line|null, interest, penalty, label], ... ].
     * One funder (recorded or read from the first cash disbursement): the whole of both, no label, as before.
     * On $date a slice the house settled earlier is the house's (LoanFundings::rows).
     */
    private function slices(Loan $loan, float $interest, float $penalty = 0.0, ?string $date = null): array
    {
        $fundings = app(LoanFundings::class);
        $rows = $fundings->rows($loan->id, $date);
        if ($rows->isEmpty()) {
            [$funder, $line] = $this->funder($loan);

            return [[$funder, $line, $interest, $penalty, '']];
        }
        $many = $rows->count() > 1;
        $pen = $fundings->split($rows, $penalty);
        $slices = [];
        foreach ($fundings->split($rows, $interest) as $i => [$row, $part]) {
            $line = $row->investment_id && ! $row->is_house ? Investment::find($row->investment_id) : null;
            $funder = $row->is_house ? null : (int) $row->partner_id;
            $label = $many ? ', ' . ($row->is_house ? 'house' : (Partner::whereKey($funder)->value('name') ?? 'partner')) . ' ' . round((float) $row->share * 100, 2) . '% of the loan' : '';
            $slices[] = [$funder, $line, $part, (float) $pen[$i][1], $label];
        }

        return $slices;
    }

    /** Who put in what of a part-funded loan, with their slice of an allocation (for the preview). Empty for one funder. */
    public function funders(Loan $loan, array $a): array
    {
        $rows = app(LoanFundings::class)->rows($loan->id);
        if ($rows->count() < 2) {
            return [];
        }
        $fundings = app(LoanFundings::class);
        $interest = $fundings->split($rows, (float) $a['interest']);
        $principal = $fundings->split($rows, (float) $a['principal']);

        return $rows->values()->map(fn ($r, $i) => [
            'name' => $r->is_house ? 'House' : (Partner::whereKey($r->partner_id)->value('name') ?? 'Partner'),
            'line' => $r->investment_id ? Investment::whereKey($r->investment_id)->value('name') : null,
            'amount' => (float) $r->amount, 'pct' => round((float) $r->share * 100, 2),
            'interest' => $interest[$i][1], 'principal' => $principal[$i][1],
        ])->all();
    }

    /**
     * What a payment of $amount on $date would do, without saving anything (owner, 23 Oct 2026: split preview
     * on Receive payment). Same waterfall and share rules as a real repayment. Returns the allocation (penalty,
     * interest, principal, overpaid), the cycle balance before and after, and the shares with partner names.
     */
    public function preview(LoanCycle $cycle, float $amount, ?string $date = null): array
    {
        $date ??= now()->toDateString();
        $before = CycleBalance::at($cycle, $date);
        $fake = PHP_INT_MAX;
        $payments = CycleBalance::paymentsFor($cycle);
        $payments[] = ['id' => $fake, 'date' => $date, 'amount' => $amount, 'kind' => 'cash'];
        $terms = CycleBalance::termsFor($cycle);
        if ($before['closed_on'] ?? null) {
            $terms['penalty_until'] = $before['closed_on'];
        }
        $after = CycleBalance::compute($terms, $payments, $date);
        $a = $after['allocations'][$fake] ?? ['penalty' => 0.0, 'interest' => 0.0, 'principal' => 0.0, 'overpaid' => 0.0];
        $loan = Loan::withTrashed()->find($cycle->loan_id);
        $shares = $loan ? $this->shares($loan, $a) : [];
        $funders = $loan ? $this->funders($loan, $a) : [];
        $names = Partner::whereIn('id', array_column($shares, 0))->pluck('name', 'id');

        return [
            'allocation' => $a,
            'owed_before' => (float) ($before['owed_on_close'] ?? $before['final_outstanding'] ?? 0),
            'owed_after' => (float) ($after['final_outstanding'] ?? 0),
            'shares' => array_map(fn ($sh) => ['partner' => $names[$sh[0]] ?? '#' . $sh[0], 'house' => $sh[0] === $this->house(),
                'type' => $sh[1], 'amount' => $sh[3], 'why' => $sh[4]], $shares),
            'funders' => $funders,
            'split_on' => self::enabled(),
        ];
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
        // part funded: every partner line on it gets its share of the principal back
        $fundings = app(LoanFundings::class);
        if ($fundings->isPartFunded($loanId)) {
            foreach ($fundings->rows($loanId)->reject(fn ($r) => $r->is_house || ! $r->investment_id) as $row) {
                if ($line = Investment::find($row->investment_id)) {
                    $this->syncLineReturns($line, (int) $row->partner_id);
                }
            }

            return;
        }
        [$funder, $line] = $this->funder(Loan::withTrashed()->find($loanId));
        if (! $funder || ! $line || ! in_array($line->return_type, ['loan_share', 'fixed_rate'], true)) {
            return;
        }
        $this->syncLineReturns($line, $funder);
    }

    /** Recomputes the principal paid back to one partner line from every loan it lent to (also after a loan moves off it). */
    public function syncLineReturns(Investment $line, ?int $funder = null): void
    {
        $funder ??= InvestmentFunding::where('investment_id', $line->id)->whereHas('partner', fn ($q) => $q->where('is_house_account', false))
            ->orderBy('id')->value('partner_id');
        if (! $funder || ! in_array($line->return_type, ['loan_share', 'fixed_rate'], true)) {
            return;
        }
        $fundings = app(LoanFundings::class);
        $loanIds = $fundings->loanIdsFor($line->id);
        $partFunded = $fundings->partFundedLoanIds($line->id)->all();
        $principal = 0.0;
        $byLoan = [];
        foreach (LoanCycle::whereIn('loan_id', $loanIds)->get() as $cycle) {
            foreach (CycleBalance::at($cycle)['allocations'] as $repaymentId => $a) {
                $rp = Repayment::withTrashed()->find($repaymentId);
                if (CustomerWallets::kindOf($rp) === 'cash') {
                    if (in_array($cycle->loan_id, $partFunded)) {
                        $day = optional($rp->repayment_date)->toDateString();
                        $byLoan[$cycle->loan_id][$day] = ($byLoan[$cycle->loan_id][$day] ?? 0) + $a['principal'];
                    } else {
                        $principal += $a['principal'];
                    }
                }
            }
        }
        // part-funded loans (owner, 1 Nov 2026): this line's pro-rata slice of the principal paid back, on each day's
        // rows, so principal paid after the house settled the slice is the house's (owner, 3 Nov 2026)
        $backByRow = [];
        foreach ($byLoan as $loanId => $days) {
            foreach ($days as $day => $paid) {
                foreach ($fundings->split($fundings->rows($loanId, $day ?: null), round($paid, 2)) as [$row, $part]) {
                    if ((int) $row->investment_id === $line->id && ! $row->is_house) {
                        $principal += $part;
                        $backByRow[$row->id] = ($backByRow[$row->id] ?? 0) + $part;
                    }
                }
            }
        }
        // a slice the house settled gave the partner the rest of their capital back that day
        foreach ($fundings->settledSlices($line->id) as $row) {
            $principal += max(0, round((float) $row->amount - ($backByRow[$row->id] ?? 0), 2));
        }
        $funding = InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $funder)->orderBy('id')->first();
        $funding?->forceFill(['amount_returned' => round($principal, 2)])->save();

        // a partner line whose loans are all closed (short term) or that has matured closes and queues its payout
        if ($line->return_type === 'loan_share') {
            app(\App\Services\Partners\PartnerLines::class)->closeIfDone($line->fresh());
        }
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
