<?php

namespace App\Services\Loans;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Services\AuditTrail;
use App\Services\CustomerWallets;
use App\Services\Ledger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Owner, 22 Oct 2026: balance every loan without inventing money.
 *
 *  - The loan stays as it was lent: cycle 1 keeps its principal and nothing is "added from the cash" (no rebuild).
 *  - The money that went out matches the loan: cycle 1 is funded by the cash (or rollover) sent around its start;
 *    a shortfall is recorded as the disbursement that was never entered. New money sent later belongs to the cycle
 *    running on its date and is part of that cycle's principal (it is never counted twice: see Ledger::planLoanCycle).
 *  - Every payment belongs to the cycle running on its date (a payment on a rollover day pays the closing cycle).
 *  - Each cycle balances itself: the next cycle carries exactly what it still owed when it closed, plus the new money.
 *  - No penalty days, except the days the borrower's own cash paid for (cash above principal + interest is penalty).
 *  - A closed loan ends at exactly 0: a write-off or ROLL OVER row larger than the debt is cut first, then the extra
 *    cash becomes penalty days (the last part-day by its penalty rate); cash short of the debt is written off.
 *
 * Never changed: cash amounts and dates, cycle 1's principal, loans on owner-agreed terms (forbearance, a waiver,
 * a payment plan). Everything is audited and the ledger, wallets and split re-book from the rows.
 */
class LoanBalancer
{
    public const WINDOW = 3;

    public const CLOSED = [Loan::STATUS_REPAID, 'written_off'];

    /** Ledger transfers worked out by run(), posted after the rows are saved: [source, id, from, to, amount, meta]. */
    private array $pending = [];

    public function __construct(private LoanRepair $repair, private Ledger $ledger) {}

    /** The loans whose ledger receivable differs from their cycles (or the ones asked for). */
    public function candidates(?array $only = null): Collection
    {
        if ($only) {
            return collect($only)->map(fn ($i) => (int) $i)->unique()->sort()->values();
        }

        return collect($this->ledger->checkReceivables()['issues'])->pluck('loan')->map(fn ($i) => (int) $i)->unique()->sort()->values();
    }

    /** What balancing would do, worked out in a transaction that is rolled back. */
    public function preview(int $loanId): array
    {
        DB::beginTransaction();
        try {
            return $this->run($loanId, null, true);
        } finally {
            DB::rollBack();
        }
    }

    public function apply(int $loanId, ?string $note = null): array
    {
        $out = DB::transaction(fn () => $this->run($loanId, $note, false));
        if (in_array($out['action'] ?? '', ['balance', 'rebook'], true) && Ledger::enabled()) {
            foreach ($this->pending as [$source, $id, $from, $to, $amount, $meta]) {
                $this->ledger->sync($source, $id, [['leg' => $source, 'from' => $from, 'to' => $to, 'amount' => $amount]], $meta);
            }
            $this->rebook($loanId);
        }

        return $out;
    }

    private function run(int $loanId, ?string $note, bool $dry): array
    {
        $loan = Loan::withTrashed()->with('user:id,name')->lockForUpdate()->findOrFail($loanId);
        $cycles = $this->cycles($loanId);
        $base = ['loan_id' => $loanId, 'borrower' => $loan->user?->name, 'status' => $loan->status, 'cycles' => $cycles->count()];
        if ($cycles->isEmpty()) {
            return $base + ['action' => 'skip', 'reason' => 'no cycles'];
        }
        if ($this->repair->agreed($loan, $cycles)) {
            return $base + $this->rebookOnly($loan, 'owner-agreed terms (forbearance, waiver or payment plan): left as they are');
        }
        if ($loan->trashed() || Ledger::neverSent($loan)) {
            return $base + ['action' => 'skip', 'reason' => 'loan never sent or deleted'];
        }

        $before = $this->snapshot($loan);
        $this->pending = [];
        $steps = [];
        $flags = [];
        $closed = in_array($loan->status, self::CLOSED, true);
        $plan = config('repairs.loans.' . $loanId, []);

        // 1. every payment and every disbursement to the cycle running on its date
        $this->segment($loanId, $cycles, $steps);

        // 2. the money that went out matches cycle 1: cash, or a balance rolled in from an earlier loan
        $c1 = $cycles->first();
        $p1 = $c1->lentPrincipal();
        $rows = Disbursement::where('loan_cycle_id', $c1->id)->get();
        $cash = round($rows->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->sum('amount'), 2);
        $rolled = round($rows->filter(fn ($d) => CustomerWallets::kindOf($d) === 'rollover')->sum('amount'), 2);
        if ($p1 - $cash - $rolled >= 0.01) {
            $missing = round($p1 - $cash - $rolled, 2);
            $day = $c1->start_date->toDateString();
            Disbursement::create(['loan_id' => $loanId, 'loan_cycle_id' => $c1->id, 'amount' => $missing, 'net_amount' => $missing,
                'transaction' => 'BACKFILL: not recorded at the time', 'mode' => 'other', 'disburse_date' => $day, 'payment_date' => $day, 'kind' => 'cash',
                'notes' => 'Owner, 22 Oct 2026: the loan lent ' . number_format($p1, 2) . ' but only ' . number_format($cash + $rolled, 2) . ' was recorded going out']);
            $steps[] = 'recorded the missing ' . number_format($missing, 2) . ' disbursement on ' . $day;
        } elseif ($cash + $rolled - $p1 >= 0.01) {
            $flags[] = 'cycle 1 was sent ' . number_format($cash + $rolled - $p1, 2) . ' more than it lent (owner: top-up, another loan, or sent by mistake)';
        }
        if ($rolled > 0.005 && Ledger::enabled()) {
            $uncovered = round(min($rolled, max(0, $p1 - $cash)) - $this->transferredIn($loan), 2);
            if ($uncovered >= 0.01) {
                $this->pending[] = ['rollover_in', $c1->id, $this->ledger->houseAccount('gl-rollover-adjustments'), $this->ledger->account($loan, 'gl-receivable'), $uncovered,
                    ['loan_id' => $loanId, 'date' => $c1->start_date->toDateString(), 'source' => 'ledger', 'reference' => 'ROLLED IN (untraced) #' . $loanId]];
                $steps[] = 'the ' . number_format($uncovered, 2) . ' rolled in from an earlier loan is put on this loan\'s books (from the rollover account)';
                $flags[] = 'rolled-in balance ' . number_format($uncovered, 2) . ' not traced to the loan it came from';
            }
        }

        // 3. book rows: a broker fee is the fee the broker kept (AC cost)
        foreach (Repayment::where('loan_id', $loanId)->get() as $r) {
            if (CustomerWallets::kindOf($r) === 'non_cash') {
                $name = $this->repair->brokerFor($loan);
                $r->forceFill(['kind' => 'discount', 'mode' => 'book', 'transaction' => 'BROKER FEE kept by ' . $name . ' (was ' . $r->transaction . ')'])->saveQuietly();
                $steps[] = 'R' . $r->id . ' ' . number_format((float) $r->amount, 2) . ' broker fee kept by ' . $name;
            }
        }
        if ($closed && ($plan['rollover'] ?? null) === 'write_off') {
            foreach (Repayment::where('loan_cycle_id', $cycles->last()->id)->get() as $r) {
                if (CustomerWallets::kindOf($r) === 'rollover' && ! $this->transferredOut($r)) {
                    $r->forceFill(['kind' => 'bad_debt', 'mode' => 'book', 'notes' => trim($r->notes . ' | ROLL OVER that closed the loan: a write-off (owner, 22 Oct 2026)')])->saveQuietly();
                    $steps[] = 'R' . $r->id . ' ROLL OVER ' . number_format((float) $r->amount, 2) . ' -> write-off (owner\'s plan)';
                }
            }
        }

        // 4. no penalty days, and each cycle carries what the one before still owed
        $late = $cycles->map(fn ($c, $i) => $this->daysLate($c, $cycles[$i + 1] ?? null))->all();
        $grace = $late;   // as many grace days as days late: no penalty anywhere
        $this->chain($cycles, $grace, [], $flags);

        // 5. a closed loan ends at exactly 0
        if ($closed) {
            $owed = $this->owed($loanId);
            if ($owed < -0.005) {
                $owed = $this->cutBookRows($loanId, -$owed, $steps);
            }
            if ($owed < -0.005) {
                [$grace, $rates, $owed] = $this->penaltyDays($cycles, $late, $flags);
                if ($rates) {
                    $steps[] = 'last part-day of penalty by rate: ' . collect($rates)->map(fn ($r, $i) => 'c' . $cycles[$i]->cycle_number . ' ' . round($r, 4) . '%')->implode(', ');
                }
            }
            if ($owed > 0.005) {
                $this->writeOff($loanId, $cycles->last(), $owed, $steps);
                $owed = $this->owed($loanId);
            }
            if (abs($owed) >= 0.01) {
                $flags[] = 'still ' . number_format($owed, 2) . ' after balancing';
            }
            // a ROLL OVER that closed the loan moved its balance to another loan: it leaves this loan's books
            foreach (Repayment::where('loan_cycle_id', $this->cycles($loanId)->last()->id)->get() as $r) {
                if (Ledger::enabled() && CustomerWallets::kindOf($r) === 'rollover' && ! $this->transferredOut($r)) {
                    $this->pending[] = ['rollover_out', $r->id, $this->ledger->account($loan, 'gl-receivable'), $this->ledger->houseAccount('gl-rollover-adjustments'), round((float) $r->amount, 2),
                        ['loan_id' => $loanId, 'date' => Carbon::parse($r->repayment_date)->toDateString(), 'source' => 'ledger', 'reference' => 'ROLLED OUT (untraced) #' . $loanId]];
                    $steps[] = 'R' . $r->id . ' ROLL OVER ' . number_format((float) $r->amount, 2) . ' leaves the books to the rollover account';
                    $flags[] = 'rolled-out balance ' . number_format((float) $r->amount, 2) . ' not traced to the loan it went to';
                }
            }
        }

        $changes = $this->changes($before['cycles'], $this->cycles($loanId));
        $loan = $loan->fresh();
        if (! in_array($loan->status, LoanStatus::LEAVE, true)) {
            LoanStatus::apply($loan);
        }
        $after = $this->snapshot($loan->fresh());
        $after['ledger'] = $this->ledgerTarget($loan);
        $after['difference'] = round($after['ledger'] - $after['owed'], 2);
        if (! $dry) {
            AuditTrail::record('loans', $loanId, 'update', ['owed' => $before['owed'], 'penalty' => $before['penalty'], 'cycles' => $before['summary']],
                ['repair' => 'balance (owner, 22 Oct 2026): payments to their cycles, cycles chained, no penalty days, closed loans end at 0',
                    'steps' => $steps, 'changes' => $changes, 'flags' => $flags, 'cycles' => $after['summary'], 'note' => $note]);
        }

        $action = ($steps || $changes) ? 'balance' : (abs($after['ledger'] - $before['ledger']) >= 0.01 ? 'rebook' : 'nothing');

        return $base + [
            'action' => $action, 'reason' => $action === 'rebook' ? 'nothing to change; the ledger is re-booked from the rows' : '',
            'owed_before' => $before['owed'], 'owed_after' => $after['owed'],
            'penalty_before' => $before['penalty'], 'penalty_after' => $after['penalty'],
            'ledger_before' => $before['ledger'], 'ledger_after' => $after['ledger'],
            'difference_before' => $before['difference'], 'difference_after' => $after['difference'],
            'status_after' => $loan->fresh()->status,
            'cash_sent' => $after['cash'], 'cycle1_lent' => $after['p1'],
            'steps' => implode('; ', $steps), 'changes' => implode('; ', $changes), 'flags' => implode('; ', $flags),
            'cycles_before' => $before['summary'], 'cycles_after' => $after['summary'],
            'next' => abs($after['difference']) < 0.01 ? 'balanced' : $this->why($after, $flags),
        ];
    }


    /** Loans left as they are: only the ledger is re-booked to what the rows say (when it differs). */
    private function rebookOnly(Loan $loan, string $reason): array
    {
        $posted = $this->postedReceivable($loan);
        $target = $this->ledgerTarget($loan);
        $owed = $this->owed($loan->id);

        return ['action' => abs($target - $posted) >= 0.01 ? 'rebook' : 'skip', 'reason' => $reason,
            'owed_before' => $owed, 'owed_after' => $owed, 'ledger_before' => $posted, 'ledger_after' => $target,
            'difference_before' => round($posted - $owed, 2), 'difference_after' => round($target - $owed, 2),
            'next' => abs($target - $owed) < 0.01 ? 'balanced' : 'owner-agreed terms: decide on the loan page'];
    }

    // ------------------------------------------------------------------ steps

    private function segment(int $loanId, Collection $cycles, array &$steps): void
    {
        $n = 0;
        foreach (Repayment::where('loan_id', $loanId)->get() as $r) {
            if (CustomerWallets::kindOf($r) === 'rollover' && $r->loan_cycle_id) {
                continue;   // a ROLL OVER row is the carry of the cycle it sits on
            }
            $c = $this->cycleOn($cycles, Carbon::parse($r->repayment_date), true);
            if ($c && (int) $r->loan_cycle_id !== $c->id) {
                $r->forceFill(['loan_cycle_id' => $c->id])->saveQuietly();
                $n++;
            }
        }
        foreach (Disbursement::where('loan_id', $loanId)->get() as $d) {
            $c = $this->cycleOn($cycles, Carbon::parse($d->disburse_date), false);
            if ($c && (int) $d->loan_cycle_id !== $c->id) {
                $d->forceFill(['loan_cycle_id' => $c->id])->saveQuietly();
                $n++;
            }
        }
        // cash sent in cycle 1 beyond what it lent was carried into cycle 2 by the old system: it is cycle 2's new money
        if ($cycles->count() > 1) {
            $c1 = $cycles[0];
            $rows = Disbursement::where('loan_cycle_id', $c1->id)->orderByDesc('disburse_date')->orderByDesc('id')->get()
                ->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash');
            $extra = round($rows->sum('amount') - $c1->lentPrincipal(), 2);
            foreach ($rows as $d) {
                if ($extra < 0.01) {
                    break;
                }
                if ((float) $d->amount <= $extra + 0.005 && Carbon::parse($d->disburse_date)->gt(Carbon::parse($c1->start_date))) {
                    $d->forceFill(['loan_cycle_id' => $cycles[1]->id])->saveQuietly();
                    $extra = round($extra - (float) $d->amount, 2);
                    $n++;
                }
            }
        }
        if ($n) {
            $steps[] = "{$n} row(s) booked to the cycle running on their date";
        }
    }

    /**
     * Sets the grace days (and, where given, penalty rates) and chains the cycles: each later cycle lends what the one
     * before still owed when it closed, plus the new money sent while it ran. A cycle paid in full before the next one
     * started does not carry: the next one lends only its new money (or keeps what it was booked with if none was sent).
     */
    private function chain(Collection $cycles, array $grace, array $rates, array &$flags): void
    {
        $flagged = [];
        foreach ($cycles as $i => $c) {
            $fill = ['grace_days' => max(0, (int) $grace[$i])];
            if (isset($rates[$i])) {
                $fill['penalty_rate'] = round($rates[$i], 6);
            }
            if ($i > 0) {
                $carry = $this->owedOnClose($cycles[$i - 1]);
                $new = round($this->newMoney($c) + $this->rolledInto($c), 2);
                if ($carry > 0.005 || $new > 0.005) {
                    $p = round(max(0, $carry) + $new, 2);
                    $interest = round($p * (float) $c->interest_rate / 100, 2);
                    $fill += ['principal' => $p, 'previous_balance' => $p, 'interest_capitalized' => $interest, 'new_balance' => round($p + $interest, 2)];
                } else {
                    $flagged[] = 'c' . $c->cycle_number . ' was lent ' . number_format($c->lentPrincipal(), 2) . ' after c' . $cycles[$i - 1]->cycle_number . ' was paid in full, but no money was sent for it';
                }
            }
            $c->forceFill($fill)->saveQuietly();
            $cycles[$i] = $c->fresh();
        }
        foreach (array_unique($flagged) as $f) {
            if (! in_array($f, $flags, true)) {
                $flags[] = $f;
            }
        }
        LoanSync::fromCycles(Loan::withTrashed()->find($cycles->first()->loan_id));
    }

    /** A write-off or ROLL OVER row bigger than the debt comes down by the extra (largest first). Returns what is owed after. */
    private function cutBookRows(int $loanId, float $extra, array &$steps): float
    {
        $rows = Repayment::where('loan_id', $loanId)->get()->filter(fn ($r) => in_array(CustomerWallets::kindOf($r), ['bad_debt', 'rollover'], true))
            ->sortByDesc('amount');
        foreach ($rows as $r) {
            if ($extra < 0.005) {
                break;
            }
            $cut = round(min($extra, (float) $r->amount), 2);
            $new = round((float) $r->amount - $cut, 2);
            if ($new < 0.005) {
                $r->deleteQuietly();
                $steps[] = 'R' . $r->id . ' write-off of ' . number_format($cut, 2) . ' removed (the debt was already paid)';
            } else {
                $r->forceFill(['amount' => $new, 'notes' => trim($r->notes . ' | cut by ' . number_format($cut, 2) . ' to what was owed (owner, 22 Oct 2026)')])->saveQuietly();
                $steps[] = 'R' . $r->id . ' write-off cut by ' . number_format($cut, 2) . ' to ' . number_format($new, 2);
            }
            $extra = round($extra - $cut, 2);
        }

        return $this->owed($loanId);
    }

    /**
     * The borrower paid more than principal + interest: the extra is penalty for the days it covers, earliest late
     * cycle first, one day at a time; the last part-day is charged by lowering that cycle's penalty rate so the loan
     * ends at exactly 0. Returns [grace per cycle, penalty rates changed, owed after].
     */
    private function penaltyDays(Collection $cycles, array $late, array &$flags): array
    {
        $loanId = $cycles->first()->loan_id;
        $grace = $late;
        $owed = $this->owed($loanId);
        foreach ($cycles as $i => $c) {
            while ($grace[$i] > 0 && $owed < -0.005) {
                $grace[$i]--;
                $this->chain($cycles, $grace, [], $flags);
                $owed = $this->owed($loanId);
                if ($owed > 0.005) {
                    // one day too many: charge part of that day, by the cycle's penalty rate
                    $full = (float) (CycleBalance::termsFor($cycles[$i])['penalty_rate'] ?? 0);
                    [$lo, $hi] = [0.0, $full];
                    for ($k = 0; $k < 30; $k++) {
                        $mid = ($lo + $hi) / 2;
                        $this->chain($cycles, $grace, [$i => $mid], $flags);
                        if ($this->owed($loanId) > 0) {
                            $hi = $mid;
                        } else {
                            $lo = $mid;
                        }
                    }
                    // end on the side where a few shillings are still owed: they are written off, never refunded
                    $mid = $hi;
                    $this->chain($cycles, $grace, [$i => $mid], $flags);
                    return [$grace, [$i => $mid], $this->owed($loanId)];
                }
            }
            if ($owed >= -0.005) {
                break;
            }
        }
        if ($owed < -0.005) {
            $flags[] = 'overpaid ' . number_format(-$owed, 2) . ' with no late days to charge: refund it, or it belongs to another loan of the borrower';
        }

        return [$grace, [], $owed];
    }

    /**
     * Cash short of the debt on a closed loan (owner, 21 Oct 2026, 2c): the last cycle's rate comes down first (floor
     * to 2 decimals); what the cash still does not cover is written off on the last payment day.
     */
    private function writeOff(int $loanId, LoanCycle $last, float $owed, array &$steps): void
    {
        $last = $last->fresh();
        $p = $last->lentPrincipal();
        $interest = (float) $last->interest_capitalized;
        if ($p > 0 && $interest > 0.005 && $owed >= 100) {
            $rate = max(0, floor((($interest - min($interest, $owed)) / $p * 100) * 100) / 100);
            $newInterest = round($p * $rate / 100, 2);
            $last->forceFill(['interest_rate' => $rate, 'interest_capitalized' => $newInterest, 'new_balance' => round($p + $newInterest, 2)])->saveQuietly();
            $steps[] = 'c' . $last->cycle_number . ' rate ' . (float) $last->getOriginal('interest_rate') . '% -> ' . $rate . '% (the cash did not cover the interest)';
            $owed = $this->owed($loanId);
        }
        $day = Repayment::where('loan_id', $loanId)->max('repayment_date') ?? $last->due_date;
        $day = Carbon::parse($day)->toDateString();
        // what was still owed on the last payment day (the days after it would only add penalty on the rest)
        $b = CycleBalance::at($last->fresh(), $day);
        $owed = round(max(0, (float) $b['final_outstanding'] - (float) $b['overpaid']), 2);
        if ($owed > 0.005) {
            Repayment::create(['loan_id' => $loanId, 'loan_cycle_id' => $last->id, 'amount' => round($owed, 2), 'repayment_date' => $day,
                'transaction' => 'WRITE OFF: cash short of the debt after balancing (owner, 22 Oct 2026)', 'kind' => 'bad_debt', 'mode' => 'book',
                'notes' => 'The loan is closed; what the cash did not cover is written off']);
            $steps[] = 'wrote off the unpaid ' . number_format($owed, 2);
        }
    }

    /** Re-books every ledger source of the loan (after commit). */
    public function rebook(int $loanId): void
    {
        foreach (LoanCycle::where('loan_id', $loanId)->get() as $c) {
            $this->ledger->syncLoanCycle($c);
        }
        foreach (Disbursement::withTrashed()->where('loan_id', $loanId)->get() as $d) {
            $this->ledger->syncDisbursement($d);
        }
        foreach (Repayment::withTrashed()->where('loan_id', $loanId)->get() as $r) {
            $this->ledger->syncRepayment($r);
            app(CustomerWallets::class)->syncRepayment($r);
        }
        foreach (Disbursement::withTrashed()->where('loan_id', $loanId)->get() as $d) {
            app(CustomerWallets::class)->syncDisbursement($d);
        }
    }

    // ------------------------------------------------------------------ reading

    /** Posted transfers into this loan's receivable (consolidations, moves, an earlier balancing). */
    private function transferredIn(Loan $loan): float
    {
        $wallet = $this->ledger->account($loan, 'gl-receivable')->getKey();
        $sum = 0;
        foreach (\Bavix\Wallet\Models\Transaction::where('wallet_id', $wallet)->get(['type', 'amount', 'meta']) as $t) {
            $key = (string) ($t->meta['ledger'] ?? '');
            if ($t->type === 'deposit' && preg_match('/^(rollover_transfer|repair_transfer|rollover_in):/', $key)) {
                $sum += abs((int) $t->amount);
            }
        }

        return round($sum / (10 ** Ledger::dp()), 2);
    }

    private function transferredOut(Repayment $r): bool
    {
        return $this->ledger->posted('rollover_transfer:' . $r->id)->filter()->isNotEmpty();
    }

    /** A balance from another loan consolidated into a later cycle (2a transfers to the rollover account). */
    private function rolledInto(LoanCycle $c): float
    {
        if ($c->cycle_number <= 1 || ! Ledger::enabled()) {
            return 0.0;
        }
        $cycles = $this->cycles($c->loan_id);
        $adj = $this->ledger->houseAccount('gl-rollover-adjustments')->getKey();
        $sum = 0;
        foreach (\Bavix\Wallet\Models\Transaction::where('wallet_id', $adj)->where('type', 'deposit')->where('meta->loan_id', $c->loan_id)->get(['amount', 'meta']) as $t) {
            if (! str_starts_with((string) ($t->meta['ledger'] ?? ''), 'rollover_transfer:')) {
                continue;
            }
            $on = $this->cycleOn($cycles, Carbon::parse($t->meta['date'] ?? $t->created_at), false);
            if ($on && $on->id === $c->id) {
                $sum += (int) ($t->meta['ledger_dir'] ?? 1) * abs((int) $t->amount);
            }
        }

        return round($sum / (10 ** Ledger::dp()), 2);
    }

    private function cycles(int $loanId): Collection
    {
        return LoanCycle::where('loan_id', $loanId)->orderBy('cycle_number')->get()->values();
    }

    /** The cycle running on a day. A payment on a rollover day pays the closing cycle; money sent that day is the new one's. */
    private function cycleOn(Collection $cycles, Carbon $day, bool $payment): ?LoanCycle
    {
        $day = $day->copy()->startOfDay();
        $pick = $cycles->first();
        foreach ($cycles as $i => $c) {
            if ($i === 0) {
                continue;
            }
            $start = Carbon::parse($c->start_date)->startOfDay();
            // money sent within WINDOW days of cycle 1's start is what cycle 1 lent
            if (! $payment && $i === 1 && $day->lte(Carbon::parse($cycles[0]->start_date)->addDays(self::WINDOW)) && $start->gt(Carbon::parse($cycles[0]->start_date)->addDays(self::WINDOW))) {
                break;
            }
            if ($payment ? $start->lt($day) : $start->lte($day)) {
                $pick = $c;
            }
        }

        return $pick;
    }

    /** What cycle 1 was funded with: cash and rollover money booked to it. */
    private function funding(LoanCycle $c1): float
    {
        return round(Disbursement::where('loan_cycle_id', $c1->id)->get()
            ->filter(fn ($d) => in_array(CustomerWallets::kindOf($d), ['cash', 'rollover'], true))->sum('amount'), 2);
    }

    /** New money sent while a later cycle ran (cash booked to it). */
    public static function newMoney(LoanCycle $c): float
    {
        if ($c->cycle_number <= 1) {
            return 0.0;
        }

        return round(Disbursement::where('loan_cycle_id', $c->id)->get()->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->sum('amount'), 2);
    }

    private function owedOnClose(LoanCycle $c): float
    {
        $b = CycleBalance::at($c);

        return round((float) ($b['owed_on_close'] ?? $b['final_outstanding']) - (float) ($b['overpaid'] ?? 0), 2);
    }

    private function owed(int $loanId): float
    {
        return $this->repair->owedFor($loanId);
    }

    /** Days from the due date to the day the cycle was carried, paid in full, or today (unpaid). */
    private function daysLate(LoanCycle $c, ?LoanCycle $next): int
    {
        $due = Carbon::parse($c->due_date)->startOfDay();
        if ($next) {
            $end = Carbon::parse($c->closed_on ?? $next->start_date)->startOfDay();
            $last = Repayment::where('loan_cycle_id', $c->id)->max('repayment_date');
            if ($last && Carbon::parse($last)->gt($end)) {
                $end = Carbon::parse($last)->startOfDay();
            }
        } else {
            $terms = CycleBalance::termsFor($c);
            $full = CycleBalance::compute(['grace_days' => 100000] + $terms, CycleBalance::paymentsFor($c), now()->toDateString());
            $end = ($full['settled_on'] ?? null) ? Carbon::parse($full['settled_on'])->startOfDay() : now()->startOfDay();
        }

        return max(0, (int) $due->diffInDays($end, false));
    }

    private function snapshot(Loan $loan): array
    {
        $cycles = $this->cycles($loan->id);
        $last = $cycles->last();
        $b = CycleBalance::at($last);
        $owed = round($b['final_outstanding'] - $b['overpaid'], 2);
        $ledger = $this->postedReceivable($loan);

        return [
            'owed' => $owed, 'ledger' => $ledger, 'difference' => round($ledger - $owed, 2),
            'penalty' => round($cycles->sum(fn ($c) => (float) (CycleBalance::at($c)['penalty'] ?? 0)), 2),
            'cash' => round(Disbursement::where('loan_id', $loan->id)->get()->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->sum('amount'), 2),
            'p1' => $cycles->first()->lentPrincipal(),
            'cycles' => $cycles->map(fn ($c) => ['n' => $c->cycle_number, 'p' => $c->lentPrincipal(), 'g' => (int) $c->grace_days, 'r' => (float) $c->interest_rate])->all(),
            'summary' => $cycles->map(fn ($c) => 'c' . $c->cycle_number . ' ' . number_format($c->lentPrincipal(), 2) . ($c->grace_days ? ' g' . $c->grace_days : ''))->implode(' | '),
        ];
    }

    private function changes(array $before, Collection $after): array
    {
        $out = [];
        foreach ($after as $c) {
            $b = collect($before)->firstWhere('n', $c->cycle_number);
            if ($b && abs($b['p'] - $c->lentPrincipal()) >= 0.01) {
                $out[] = 'c' . $c->cycle_number . ' principal ' . number_format($b['p'], 2) . ' -> ' . number_format($c->lentPrincipal(), 2);
            }
            if ($b && $b['g'] !== (int) $c->grace_days) {
                $out[] = 'c' . $c->cycle_number . ' grace ' . $b['g'] . ' -> ' . (int) $c->grace_days;
            }
        }

        return $out;
    }

    private function postedReceivable(Loan $loan): float
    {
        $raw = DB::table('wallets as w')->join('transactions as t', 't.wallet_id', '=', 'w.id')
            ->where('w.holder_type', $loan->getMorphClass())->where('w.holder_id', $loan->id)->where('w.slug', 'gl-receivable')
            ->where('t.confirmed', 1)->whereNull('t.deleted_at')
            ->selectRaw("sum(case when t.type = 'deposit' then t.amount else -abs(t.amount) end) as total")->value('total');

        return round((float) $raw / (10 ** Ledger::dp()), 2);
    }

    /**
     * What the receivable will be once every source of this loan is re-booked from its plan (without posting):
     * posted now, minus what this loan's rows posted, plus what their plans say.
     */
    public function ledgerTarget(Loan $loan): float
    {
        if (! Ledger::enabled()) {
            return $this->postedReceivable($loan);
        }
        $receivable = $this->ledger->account($loan, 'gl-receivable')->getKey();
        $net = function (iterable $legs) use ($receivable) {
            $t = 0;
            foreach ($legs as $l) {
                $minor = Ledger::minor($l['amount']);
                $t += ($l['to']->getKey() === $receivable ? $minor : 0) - ($l['from']->getKey() === $receivable ? $minor : 0);
            }

            return $t;
        };
        $postedNet = function (string $key) use ($receivable) {
            $t = 0;
            foreach ($this->ledger->posted($key) as $k => $minor) {
                [, $from, $to] = explode('|', $k);
                $t += ((int) $to === $receivable ? $minor : 0) - ((int) $from === $receivable ? $minor : 0);
            }

            return $t;
        };
        $total = (int) round($this->postedReceivable($loan) * (10 ** Ledger::dp()));
        foreach (LoanCycle::where('loan_id', $loan->id)->get() as $c) {
            $total += $net($this->ledger->planLoanCycle($c)['legs']) - $postedNet('loan_cycle:' . $c->id);
        }
        foreach (Disbursement::withTrashed()->where('loan_id', $loan->id)->get() as $d) {
            $total += $net($this->ledger->planDisbursement($d)['legs']) - $postedNet('disbursement:' . $d->id);
        }
        foreach (Repayment::withTrashed()->where('loan_id', $loan->id)->get() as $r) {
            $total += $net($this->ledger->planRepayment($r)['legs']) - $postedNet('repayment:' . $r->id);
        }
        foreach ($this->pending as [$source, $id, $from, $to, $amount]) {
            $total += $net([['from' => $from, 'to' => $to, 'amount' => $amount]]) - $postedNet($source . ':' . $id);
        }

        return round($total / (10 ** Ledger::dp()), 2);
    }

    private function why(array $a, array $flags): string
    {
        return $flags ? $flags[0] : 'book rows or a balance on another loan: open the loan';
    }
}
