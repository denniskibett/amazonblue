<?php

namespace App\Services\Loans;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\Repayment;
use App\Services\AuditTrail;
use App\Services\CustomerWallets;
use App\Services\Ledger;
use App\Services\PartnerLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Feature 2b (owner, 21 Oct 2026): one decision at a time on a loan whose ledger receivable differs from what its
 * cycles say is owed. Each action re-reads the loan, saves in one transaction, is audited, and lets the existing
 * observers re-book the ledger, the customer wallet and the broker/funder split. The page is the sign-off.
 *
 * Groups (what the difference usually means):
 *   broker_fee  a BROKER FEE / SPLIT / TO BE PAID book row the ledger never posted (non_cash)
 *   cash        cash sent differs from what the cycles lent (top-ups, or less sent than lent)
 *   no_cash     no disbursement recorded at all for a loan that was lent and repaid
 *   over_booked a write-off or discount bigger than the debt (a credit nobody paid)
 *   rollover    ROLL OVER rows with nothing left to match
 */
class LoanRepair
{
    public const GROUPS = [
        'broker_fee' => 'Broker-fee rows',
        'cash' => 'Cash sent ≠ lent',
        'no_cash' => 'No disbursement recorded',
        'over_booked' => 'Write-off/discount above the debt',
        'rollover' => 'Rollover leftovers',
        'other' => 'Other',
    ];

    public const WINDOW = 3;

    /** Every mismatched loan with what the page shows and offers. */
    public function findings(): Collection
    {
        $all = collect(app(Ledger::class)->checkReceivables()['issues'])
            ->map(fn ($i) => $this->detail((int) $i['loan'], $i))->filter()->values();

        return $all->map(fn ($r) => $r + ['suggestion' => $this->suggest($r, $all)]);
    }

    public function detail(int $loanId, ?array $issue = null): ?array
    {
        $loan = Loan::with('user:id,name')->find($loanId);
        if (! $loan) {
            return null;
        }
        $issue ??= collect(app(Ledger::class)->checkReceivables()['issues'])->firstWhere('loan', $loanId)
            ?? ['loan' => $loanId, 'owed' => null, 'ledger' => null, 'difference' => 0.0];
        $cycles = LoanCycle::where('loan_id', $loanId)->orderBy('cycle_number')->get();
        $disb = Disbursement::where('loan_id', $loanId)->orderBy('disburse_date')->orderBy('id')->get();
        $reps = Repayment::where('loan_id', $loanId)->orderBy('repayment_date')->orderBy('id')->get();
        $cash = round($disb->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->sum('amount'), 2);
        $p1 = $cycles->first()?->lentPrincipal() ?? 0.0;
        $book = $reps->filter(fn ($r) => CustomerWallets::kindOf($r) !== 'cash');
        $nonCash = $book->filter(fn ($r) => CustomerWallets::kindOf($r) === 'non_cash')->sum('amount');
        $hasRollover = $book->contains(fn ($r) => CustomerWallets::kindOf($r) === 'rollover')
            || $disb->contains(fn ($d) => CustomerWallets::kindOf($d) === 'rollover');
        $diff = (float) $issue['difference'];

        $group = match (true) {
            $hasRollover => 'rollover',
            $nonCash > 0 && abs($diff - $nonCash) < 1 => 'broker_fee',
            $issue['owed'] !== null && $issue['owed'] < -1 => 'over_booked',
            $cash > 0 && abs($cash - $p1) >= 1 => 'cash',
            $cash == 0.0 => 'no_cash',
            default => 'other',
        };

        return [
            'loan_id' => $loanId, 'user_id' => (int) $loan->user_id, 'borrower' => $loan->user?->name, 'status' => $loan->status, 'group' => $group,
            'owed' => $issue['owed'], 'ledger' => $issue['ledger'], 'difference' => $diff,
            'cash_sent' => $cash, 'first_principal' => $p1, 'broker_id' => $loan->broker_status && $loan->partner_id ? (int) $loan->partner_id : null,
            'cycles' => $cycles->map(fn ($c) => ['id' => $c->id, 'n' => $c->cycle_number, 'start' => $c->start_date->toDateString(), 'due' => $c->due_date->toDateString(),
                'principal' => $c->lentPrincipal(), 'rate' => (float) $c->interest_rate, 'status' => $c->status])->all(),
            'disbursements' => $disb->map(fn ($d) => ['id' => $d->id, 'date' => Carbon::parse($d->disburse_date)->toDateString(), 'amount' => (float) $d->amount,
                'kind' => CustomerWallets::kindOf($d), 'transaction' => $d->transaction])->all(),
            'book' => $book->map(fn ($r) => ['id' => $r->id, 'date' => Carbon::parse($r->repayment_date)->toDateString(), 'amount' => (float) $r->amount,
                'kind' => CustomerWallets::kindOf($r), 'transaction' => $r->transaction, 'cycle_id' => $r->loan_cycle_id])->all(),
            'cash_repaid' => round($reps->filter(fn ($r) => CustomerWallets::kindOf($r) === 'cash')->sum('amount'), 2),
        ];
    }

    // ------------------------------------------------------------------ actions

    /** A book row becomes an AC discount or a write-off (the ledger posts it to gl-discounts / gl-bad-debt). */
    public function bookAs(int $repaymentId, string $kind, ?string $note = null): string
    {
        abort_unless(in_array($kind, ['discount', 'bad_debt'], true), 422, 'Choose discount or write-off.');

        return $this->onRepayment($repaymentId, function (Repayment $r) use ($kind) {
            $r->forceFill(['kind' => $kind, 'mode' => 'book'])->save();

            return 'booked as ' . ($kind === 'discount' ? 'an AC discount' : 'a write-off');
        }, $note);
    }

    /** The borrower still owes it: the book row goes (soft delete) and the loan reopens by that amount. */
    public function bookRemove(int $repaymentId, ?string $note = null): string
    {
        return $this->onRepayment($repaymentId, function (Repayment $r) {
            $r->delete();

            return 'removed; the loan owes it again';
        }, $note);
    }

    /** A book row cut down to what was owed, so it leaves no credit nobody paid. */
    public function bookCut(int $repaymentId, ?string $note = null): string
    {
        return $this->onRepayment($repaymentId, function (Repayment $r) {
            $credit = -($this->owedFor((int) $r->loan_id));
            abort_if($credit < 0.01, 422, 'The loan has no credit to remove.');
            $new = round((float) $r->amount - $credit, 2);
            if ($new <= 0.004) {
                $r->delete();

                return 'removed (the whole row was credit)';
            }
            $r->forceFill(['amount' => $new, 'notes' => trim($r->notes . ' | cut by ' . number_format($credit, 2) . ' to what was owed (owner, 2b)')])->save();

            return 'cut by KES ' . number_format($credit, 2) . ' to ' . number_format($new, 2);
        }, $note);
    }

    /**
     * Owner, 21 Oct 2026: a BROKER FEE row is the fee the broker already kept out of what it collected, so it is AC's
     * cost (posted as a discount) and nothing more is paid to the broker. It is named after the broker: the loan's own
     * broker (ISIRO or Victor) for loans from the last 3 months, ISIRO for anything older.
     */
    public function brokerKept(int $repaymentId, ?string $note = null): string
    {
        return $this->onRepayment($repaymentId, function (Repayment $r) {
            $name = $this->brokerFor(Loan::withTrashed()->findOrFail($r->loan_id));
            $r->forceFill(['kind' => 'discount', 'mode' => 'book', 'transaction' => 'BROKER FEE kept by ' . $name . ' (was ' . $r->transaction . ')'])->save();

            return 'booked as the broker fee ' . $name . ' kept (AC cost)';
        }, $note);
    }

    /** Whose fee it was: the loan's broker if the loan is from the last 3 months, else ISIRO. */
    public function brokerFor(Loan $loan): string
    {
        $isiro = Partner::where('name', 'like', 'ISIRO%')->value('name') ?? 'ISIRO AGENCIES';
        $start = LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->value('start_date') ?? $loan->borrow_date;
        $recent = $start && Carbon::parse($start)->gte(now()->subMonthsNoOverflow(3)->startOfDay());
        $own = $loan->broker_status && $loan->partner_id ? Partner::whereKey($loan->partner_id)->value('name') : null;

        return $recent && $own ? $own : $isiro;
    }

    /** A broker fee the borrower kept back: paid out of the broker's earned fees (their journal goes down by it). */
    public function brokerSetOff(int $repaymentId, ?string $note = null): string
    {
        return $this->onRepayment($repaymentId, function (Repayment $r) {
            $loan = Loan::withTrashed()->findOrFail($r->loan_id);
            $partner = $loan->broker_status && $loan->partner_id ? Partner::find($loan->partner_id) : null;
            abort_unless($partner, 422, 'This loan has no broker to set the fee off against.');
            $r->forceFill(['kind' => 'discount', 'mode' => 'book', 'transaction' => 'BROKER FEE SET-OFF (' . $r->transaction . ')'])->save();
            app(PartnerLedger::class)->post($partner, 'broker_fee', 'broker_fee_earned', -1 * (float) $r->amount,
                loanId: $loan->id, repaymentId: $r->id, reference: 'SETOFF-R' . $r->id,
                notes: 'Broker fee kept back on repayment #' . $r->id . ' (loan #' . $loan->id . '), set off against earned fees (owner, 2b)',
                transactionDate: Carbon::parse($r->repayment_date)->toDateString(), force: true);

            return 'set off against ' . $partner->name . "'s earned fees";
        }, $note);
    }

    /** The loan is right: the money that went out was never recorded. Adds it on the first cycle's start. */
    public function addMissingDisbursement(int $loanId, ?string $note = null, ?float $amount = null, ?string $date = null): string
    {
        return $this->onLoan($loanId, function (Loan $loan) use ($amount, $date) {
            $c1 = LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->firstOrFail();
            $cash = Disbursement::where('loan_id', $loan->id)->get()->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->sum('amount');
            $missing = $amount !== null ? round($amount, 2) : round($c1->lentPrincipal() - $cash, 2);
            abort_if($missing < 0.01, 422, 'Nothing is missing: the cash recorded already covers cycle 1.');
            $day = $date ? Carbon::parse($date)->toDateString() : $c1->start_date->toDateString();
            $cycle = LoanCycle::where('loan_id', $loan->id)->where('start_date', '<=', $day)->orderByDesc('cycle_number')->first() ?? $c1;
            Disbursement::create(['loan_id' => $loan->id, 'loan_cycle_id' => $cycle->id, 'amount' => $missing, 'net_amount' => $missing,
                'transaction' => 'BACKFILL: not recorded at the time', 'mode' => 'other', 'disburse_date' => $day, 'payment_date' => $day,
                'kind' => 'cash', 'notes' => 'Owner decision 21 Oct 2026 (2b): the loan was right; the disbursement was never recorded']);

            return 'recorded KES ' . number_format($missing, 2) . ' sent on ' . $day;
        }, $note);
    }

    /** The cycles are right: this disbursement row was recorded by mistake (soft delete). */
    public function removeDisbursement(int $disbursementId, ?string $note = null): string
    {
        $d = Disbursement::findOrFail($disbursementId);

        return $this->onLoan((int) $d->loan_id, function () use ($d) {
            $d->delete();

            return 'disbursement #' . $d->id . ' (KES ' . number_format((float) $d->amount, 2) . ') removed';
        }, $note);
    }

    /**
     * A book row (not cash) set to the amount staff typed, e.g. a write-off cut to an agreed figure. Cash rows are never
     * changed here (onRepayment refuses them); 0 removes the row.
     */
    public function bookSet(int $repaymentId, float $amount, ?string $note = null): string
    {
        abort_if($amount < 0, 422, 'The amount cannot be below 0.');

        return $this->onRepayment($repaymentId, function (Repayment $r) use ($amount) {
            $was = (float) $r->amount;
            if ($amount < 0.005) {
                $r->delete();

                return 'book row #' . $r->id . ' (KES ' . number_format($was, 2) . ') removed';
            }
            $r->forceFill(['amount' => round($amount, 2), 'notes' => trim($r->notes . ' | set from ' . number_format($was, 2) . ' to ' . number_format($amount, 2) . ' (2b)')])->save();

            return 'book row #' . $r->id . ' set from KES ' . number_format($was, 2) . ' to ' . number_format($amount, 2);
        }, $note);
    }

    /**
     * One cycle's principal (and optionally its rate) set by hand. Only that cycle changes: later cycles keep the
     * principal they were booked with, so nothing is re-carried (the automatic rebuild re-carries; see the 22 Oct plan,
     * group B). Agreed loans (forbearance, waiver, payment plan) are refused.
     */
    public function cycleSet(int $cycleId, float $principal, ?float $rate = null, ?string $note = null): string
    {
        abort_if($principal <= 0, 422, 'The principal must be above 0.');
        $cycle = LoanCycle::findOrFail($cycleId);

        return $this->onLoan((int) $cycle->loan_id, function (Loan $loan) use ($cycle, $principal, $rate) {
            abort_if($this->agreed($loan), 422, 'This loan is in forbearance or on a payment plan the owner agreed; change it from the loan page.');
            $c = LoanCycle::whereKey($cycle->id)->lockForUpdate()->firstOrFail();
            $was = [$c->lentPrincipal(), (float) $c->interest_rate];
            $r = $rate ?? (float) $c->interest_rate;
            $p = round($principal, 2);
            $interest = round($p * $r / 100, 2);
            $c->forceFill(['principal' => $p, 'previous_balance' => $c->cycle_number > 1 ? $p : 0, 'interest_rate' => $r,
                'interest_capitalized' => $interest, 'new_balance' => round($p + $interest, 2)])->save();

            return 'cycle ' . $c->cycle_number . ' principal ' . number_format($was[0], 2) . ' -> ' . number_format($p, 2)
                . ($rate !== null && abs($rate - $was[1]) > 0.0001 ? ', rate ' . $was[1] . '% -> ' . $r . '%' : '');
        }, $note);
    }

    /**
     * Owner, 22 Oct 2026: a loan that started in the last 3 months was handled by the current system from day one, so its
     * rollovers are right. No rollover is created, rewritten or moved for it here.
     */
    public function recent(Loan $loan): bool
    {
        $start = LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->value('start_date') ?? $loan->borrow_date;

        return $start !== null && Carbon::parse($start)->gte(now()->subMonthsNoOverflow(3)->startOfDay());
    }

    /**
     * A disbursement recorded on the wrong loan of the same borrower moves to the right one (e.g. cash on a repaid loan
     * that the borrower's other loan is missing). It joins the target's cycle running on its date; the ledger and the
     * wallet re-book through the observers. Nothing is created or deleted.
     */
    public function moveDisbursement(int $disbursementId, int $toLoanId, ?string $note = null): string
    {
        $d = Disbursement::findOrFail($disbursementId);
        $from = Loan::withTrashed()->findOrFail($d->loan_id);
        $to = Loan::findOrFail($toLoanId);
        abort_if($from->id === $to->id, 422, 'Choose another loan.');
        abort_unless((int) $from->user_id === (int) $to->user_id, 422, 'Both loans must be the same borrower\'s.');
        $day = Carbon::parse($d->disburse_date)->toDateString();
        $cycle = LoanCycle::where('loan_id', $to->id)->where('start_date', '<=', $day)->orderByDesc('cycle_number')->first()
            ?? LoanCycle::where('loan_id', $to->id)->orderBy('cycle_number')->firstOrFail();

        $msg = $this->onLoan($to->id, function () use ($d, $to, $cycle) {
            $d->forceFill(['loan_id' => $to->id, 'loan_cycle_id' => $cycle->id])->save();

            return 'disbursement #' . $d->id . ' (KES ' . number_format((float) $d->amount, 2) . ') moved here into cycle ' . $cycle->cycle_number;
        }, $note, ['disbursement' => $d->id, 'from_loan' => $from->id]);
        if (! in_array($from->fresh()->status, LoanStatus::LEAVE, true)) {
            LoanStatus::apply($from->fresh());
        }
        AuditTrail::record('loans', $from->id, 'update', ['disbursement' => $d->id], ['repair' => 'disbursement moved to loan #' . $to->id, 'note' => $note]);

        return $msg;
    }

    /**
     * Owner, 22 Oct 2026: a closed loan the borrower paid more on than it owed, with no late days to charge, gives the
     * extra to another loan of the same borrower. The latest cash repayment is split (same date and reference; the
     * cash in total does not change) and the part moves to the other loan, into its cycle running that day.
     */
    public function moveOverpayment(int $fromLoanId, int $toLoanId, float $amount, ?string $note = null): string
    {
        abort_if($fromLoanId === $toLoanId || $amount <= 0, 422, 'Choose another loan and an amount above 0.');
        $from = Loan::findOrFail($fromLoanId);
        $to = Loan::findOrFail($toLoanId);
        abort_unless((int) $from->user_id === (int) $to->user_id, 422, 'Both loans must be the same borrower\'s.');
        $over = -$this->owedFor($fromLoanId);
        abort_if($amount > $over + 0.01, 422, 'Loan #' . $fromLoanId . ' is only overpaid by ' . number_format(max(0, $over), 2) . '.');

        return $this->onLoan($fromLoanId, function () use ($from, $to, $amount) {
            $row = Repayment::where('loan_id', $from->id)->orderByDesc('repayment_date')->orderByDesc('id')->get()
                ->first(fn ($r) => CustomerWallets::kindOf($r) === 'cash' && (float) $r->amount >= $amount - 0.005);
            abort_unless($row, 422, 'No single cash repayment on #' . $from->id . ' is as large as ' . number_format($amount, 2) . '.');
            $day = Carbon::parse($row->repayment_date)->toDateString();
            $cycle = LoanCycle::where('loan_id', $to->id)->where('start_date', '<', $day)->orderByDesc('cycle_number')->first()
                ?? LoanCycle::where('loan_id', $to->id)->orderBy('cycle_number')->firstOrFail();
            if (abs((float) $row->amount - $amount) < 0.005) {
                $row->forceFill(['loan_id' => $to->id, 'loan_cycle_id' => $cycle->id, 'notes' => trim($row->notes . ' | moved from loan #' . $from->id . ' (overpaid there)')])->save();

                return 'repayment #' . $row->id . ' (KES ' . number_format($amount, 2) . ') moved to loan #' . $to->id;
            }
            $row->forceFill(['amount' => round((float) $row->amount - $amount, 2), 'notes' => trim($row->notes . ' | ' . number_format($amount, 2) . ' of it moved to loan #' . $to->id)])->save();
            $part = $row->replicate(['id']);
            $part->forceFill(['loan_id' => $to->id, 'loan_cycle_id' => $cycle->id, 'amount' => round($amount, 2),
                'notes' => 'Part of repayment #' . $row->id . ' on loan #' . $from->id . ' (overpaid there), owner 22 Oct 2026'])->save();

            return 'KES ' . number_format($amount, 2) . ' of repayment #' . $row->id . ' moved to loan #' . $to->id . ' (new row #' . $part->id . ')';
        }, $note);
    }

    /** The loan was really what was sent: cycle 1 lends the cash booked to it (the cycles after it carry from there). */
    public function lendWhatWasSent(int $loanId, ?string $note = null): string
    {
        $c1 = LoanCycle::where('loan_id', $loanId)->orderBy('cycle_number')->firstOrFail();
        $cash = round(Disbursement::where('loan_cycle_id', $c1->id)->get()->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->sum('amount'), 2);
        abort_if($cash < 0.01 || abs($cash - $c1->lentPrincipal()) < 0.01, 422, 'Cycle 1 already lends what was sent.');

        return $this->cycleSet($c1->id, $cash, null, $note ?: 'the loan was what was sent (owner, 22 Oct 2026)');
    }

    /** A cash row typed wrong (e.g. principal + interest, or a digit off): the amount sent is corrected. */
    public function disbursementSet(int $disbursementId, float $amount, ?string $note = null): string
    {
        abort_if($amount <= 0, 422, 'The amount must be above 0. To remove a row recorded by mistake, use "Recorded by mistake".');
        $d = Disbursement::findOrFail($disbursementId);

        return $this->onLoan((int) $d->loan_id, function () use ($d, $amount) {
            $was = (float) $d->amount;
            $d->forceFill(['amount' => round($amount, 2), 'net_amount' => round($amount - (float) $d->processing_fee, 2),
                'notes' => trim($d->notes . ' | amount corrected from ' . number_format($was, 2) . ' (owner, 22 Oct 2026)')])->save();

            return 'disbursement #' . $d->id . ' corrected from KES ' . number_format($was, 2) . ' to ' . number_format($amount, 2);
        }, $note, ['disbursement' => $d->id, 'was' => $d->only(['amount'])]);
    }

    /**
     * What the owner can decide for a loan the balancer cannot close on its own (from its flags): duplicate or wrongly
     * typed money sent, a top-up, cash that belongs to another loan, an overpayment to give to another loan.
     */
    public function options(int $loanId, array $balance): array
    {
        $loan = Loan::withTrashed()->find($loanId);
        $c1 = LoanCycle::where('loan_id', $loanId)->orderBy('cycle_number')->first();
        if (! $loan || ! $c1) {
            return [];
        }
        $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
        $out = [];
        $others = Loan::where('user_id', $loan->user_id)->where('id', '!=', $loanId)->whereNotIn('status', ['rejected'])->orderByDesc('id')->get(['id', 'status', 'amount']);
        $cashRows = Disbursement::where('loan_cycle_id', $c1->id)->get()->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->values();
        $sent = round($cashRows->sum('amount'), 2);
        $p1 = $c1->lentPrincipal();
        if ($sent - $p1 >= 0.01) {
            $extra = round($sent - $p1, 2);
            foreach ($cashRows as $d) {
                $twin = $cashRows->first(fn ($x) => $x->id !== $d->id && abs((float) $x->amount - (float) $d->amount) < 0.005
                    && Carbon::parse($x->disburse_date)->isSameDay(Carbon::parse($d->disburse_date)) && strtoupper(trim((string) $x->transaction)) === strtoupper(trim((string) $d->transaction)));
                if ($twin && $d->id > $twin->id) {
                    $out[] = ['action' => 'remove_disbursement', 'row' => $d->id, 'best' => true,
                        'label' => 'D' . $d->id . ' is a duplicate of D' . $twin->id . ' (same day, amount and reference): remove it',
                        'why' => 'The same M-Pesa reference was entered twice.'];
                }
                $plusInterest = round($p1 * (1 + (float) $c1->interest_rate / 100), 2);
                if (abs((float) $d->amount - $plusInterest) < 0.01 && $cashRows->count() === 1) {
                    $out[] = ['action' => 'disbursement_set', 'row' => $d->id, 'amount' => $p1, 'best' => true,
                        'label' => 'D' . $d->id . ' was typed as principal + interest (' . $kes($d->amount) . '): correct it to ' . $kes($p1),
                        'why' => 'The amount sent equals what the borrower had to repay.'];
                }
            }
            $out[] = ['action' => 'lend_sent', 'label' => 'The loan was ' . $kes($sent) . ' (what was sent): cycle 1 lends that',
                'why' => 'Cycle 1 lends ' . $kes($p1) . ' but ' . $kes($sent) . ' went out. The cycles after it carry from there; no penalty days.'];
            foreach ($others as $o) {
                foreach ($cashRows->filter(fn ($d) => (float) $d->amount <= $extra + 0.005) as $d) {
                    $out[] = ['action' => 'move_disbursement', 'row' => $d->id, 'to' => $o->id,
                        'label' => 'D' . $d->id . ' (' . $kes($d->amount) . ') belongs to loan #' . $o->id . ' (' . $o->status . '): move it',
                        'why' => 'It was recorded on the wrong loan of the same borrower.'];
                }
            }
        }
        $owed = (float) ($balance['owed_after'] ?? $this->owedFor($loanId));
        if ($owed < -0.01) {
            $open = $others->filter(fn ($o) => ! in_array($o->status, ['repaid', 'written_off', 'pending', 'approved'], true));
            foreach ($open as $o) {
                $out[] = ['action' => 'move_overpayment', 'to' => $o->id, 'amount' => round(-$owed, 2), 'best' => $open->count() === 1,
                    'label' => 'Give the ' . $kes(-$owed) . ' overpaid to loan #' . $o->id . ' (' . $o->status . ')',
                    'why' => 'The borrower paid more than this loan owed and was never late: the extra pays their other loan.'];
            }
            $out[] = ['action' => null, 'label' => 'Or refund the ' . $kes(-$owed) . ' to the borrower (record the refund as a disbursement when paid)', 'why' => ''];
        }

        return $out;
    }

    /**
     * Everything the loan detail modal shows: the 22 Oct plan for the loan, the system's finding and suggestion, the
     * loan, every cycle with its balance, every money row (deleted ones flagged) and the fields that can be changed.
     */
    public function dossier(int $loanId): ?array
    {
        $loan = Loan::withTrashed()->with(['user:id,name,phone', 'loanType:id,name,period,unit'])->find($loanId);
        if (! $loan) {
            return null;
        }
        $all = $this->findings();
        $finding = $all->firstWhere('loan_id', $loanId) ?? (($d = $this->detail($loanId)) ? $d + ['suggestion' => $this->suggest($d, $all)] : null);
        $plans = config('repairs.loans', []);
        $plan = $plans[$loanId] ?? (($finding['group'] ?? null) === 'broker_fee' ? config('repairs.broker_fee_default') : null);
        if ($plan) {
            $plan += ['group_label' => config('repairs.groups.' . $plan['group'] . '.label'), 'group_meaning' => config('repairs.groups.' . $plan['group'] . '.meaning'),
                'tone' => config('repairs.groups.' . $plan['group'] . '.tone'), 'plan_date' => config('repairs.plan_date')];
        }

        $balance = null;
        try {
            $balance = app(LoanBalancer::class)->preview($loanId);
        } catch (\Throwable $e) {
            $balance = ['action' => 'error', 'reason' => strtok($e->getMessage(), "\n")];
        }
        $options = $this->options($loanId, $balance ?? []);

        $cycles = LoanCycle::where('loan_id', $loanId)->orderBy('cycle_number')->get();
        $agreed = $this->agreed($loan, $cycles);
        $cycleRows = $cycles->map(function (LoanCycle $c) {
            $b = CycleBalance::at($c);

            return ['id' => $c->id, 'n' => $c->cycle_number, 'start' => $c->start_date?->toDateString(), 'due' => $c->due_date?->toDateString(),
                'closed_on' => $c->closed_on?->toDateString(), 'status' => $c->status, 'principal' => $c->lentPrincipal(), 'rate' => (float) $c->interest_rate,
                'interest' => (float) $c->interest_capitalized, 'grace_days' => (int) $c->grace_days, 'waived_until' => $c->penalty_waived_until?->toDateString(),
                'penalty' => round((float) ($b['penalty'] ?? 0), 2), 'paid' => round((float) ($b['total_repayments'] ?? 0), 2),
                'owed_on_close' => isset($b['owed_on_close']) ? round((float) $b['owed_on_close'], 2) : null,
                'carried' => $b['carried_forward'] ?? null, 'carry_difference' => $b['carry_difference'] ?? null,
                'owed' => round((float) ($b['final_outstanding'] ?? 0) - (float) ($b['overpaid'] ?? 0), 2), 'notes' => $c->notes];
        })->values();

        $disb = Disbursement::withTrashed()->where('loan_id', $loanId)->orderBy('disburse_date')->orderBy('id')->get()
            ->map(fn ($d) => ['id' => $d->id, 'date' => Carbon::parse($d->disburse_date)->toDateString(), 'amount' => (float) $d->amount,
                'kind' => CustomerWallets::kindOf($d), 'transaction' => $d->transaction, 'cycle_id' => $d->loan_cycle_id, 'deleted' => $d->deleted_at?->toDateTimeString()])->values();
        $reps = Repayment::withTrashed()->where('loan_id', $loanId)->orderBy('repayment_date')->orderBy('id')->get()
            ->map(fn ($r) => ['id' => $r->id, 'date' => Carbon::parse($r->repayment_date)->toDateString(), 'amount' => (float) $r->amount,
                'kind' => CustomerWallets::kindOf($r), 'transaction' => $r->transaction, 'cycle_id' => $r->loan_cycle_id, 'notes' => $r->notes,
                'deleted' => $r->deleted_at?->toDateTimeString()])->values();

        // the fields that can be changed, most likely first
        $sg = $finding['suggestion'] ?? null;
        $owed = $this->owedFor($loanId);
        $edits = [];
        foreach ($reps->where('deleted', null)->where('kind', '!=', 'cash') as $r) {
            $suggested = $owed < -0.01 && ($sg['row'] ?? null) === $r['id'] ? max(0, round($r['amount'] + $owed, 2)) : $r['amount'];
            $edits[] = ['type' => 'book', 'id' => $r['id'], 'label' => 'Book row #' . $r['id'] . ' ' . $r['date'] . ' (' . str_replace('_', ' ', $r['kind']) . ', ' . ($r['transaction'] ?? '') . ')',
                'amount' => $r['amount'], 'suggested' => $suggested, 'kind' => $r['kind'], 'highlight' => ($sg['row'] ?? null) === $r['id']];
        }
        if (! $agreed) {
            foreach ($cycleRows as $c) {
                $edits[] = ['type' => 'cycle', 'id' => $c['id'], 'label' => 'Cycle ' . $c['n'] . ' ' . $c['start'] . ' → ' . $c['due'],
                    'principal' => $c['principal'], 'rate' => $c['rate'], 'highlight' => false];
            }
        }

        $byLoan = $all->keyBy('loan_id');
        $siblings = Loan::withTrashed()->where('user_id', $loan->user_id)->orderBy('id')->get(['id', 'status', 'borrow_date', 'amount'])
            ->map(fn ($l) => ['id' => $l->id, 'status' => $l->status, 'borrow_date' => $l->borrow_date ? Carbon::parse($l->borrow_date)->toDateString() : null,
                'difference' => (float) ($byLoan[$l->id]['difference'] ?? 0), 'group' => $byLoan[$l->id]['group'] ?? null,
                'cash_sent' => $byLoan[$l->id]['cash_sent'] ?? null, 'first_principal' => $byLoan[$l->id]['first_principal'] ?? null,
                'recent' => $this->recent($l)])->values();

        return [
            'recent' => $this->recent($loan),
            'borrower_loans' => $siblings,
            'borrower_net' => round($siblings->sum('difference'), 2),
            'plan' => $plan,
            'finding' => $finding ? ['group' => $finding['group'], 'group_label' => self::GROUPS[$finding['group']] ?? $finding['group'],
                'owed' => $finding['owed'], 'ledger' => $finding['ledger'], 'difference' => $finding['difference'],
                'cash_sent' => $finding['cash_sent'], 'first_principal' => $finding['first_principal'], 'cash_repaid' => $finding['cash_repaid']] : null,
            'suggestion' => $sg,
            'balance' => $balance,
            'options' => $options,
            'agreed' => $agreed,
            'owed_now' => $owed,
            'loan' => ['id' => $loan->id, 'borrower' => $loan->user?->name, 'user_id' => (int) $loan->user_id, 'status' => $loan->status,
                'product' => $loan->loanType?->name, 'amount' => (float) $loan->amount, 'original_amount' => (float) $loan->original_amount,
                'borrow_date' => $loan->borrow_date ? Carbon::parse($loan->borrow_date)->toDateString() : null,
                'due_date' => $loan->due_date ? Carbon::parse($loan->due_date)->toDateString() : null,
                'forbearance_until' => $loan->forbearance_until?->toDateString(), 'broker' => $loan->broker_status && $loan->partner_id ? Partner::whereKey($loan->partner_id)->value('name') : null,
                'deleted' => $loan->deleted_at?->toDateTimeString()],
            'cycles' => $cycleRows,
            'disbursements' => $disb,
            'repayments' => $reps,
            'edits' => $edits,
        ];
    }

    /**
     * The cash is right: the cycles are rebuilt from the money actually sent. Cash within WINDOW days of cycle 1's start
     * is what cycle 1 lent; every later cash row is added to the cycle running that day, at that cycle's rate; each
     * later cycle carries the extra (its principal moves by exactly what the cycle before now owes more or less).
     */
    public function rebuildFromCash(int $loanId, ?string $note = null): string
    {
        return $this->onLoan($loanId, function (Loan $loan) {
            $cycles = LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->lockForUpdate()->get()->values();
            abort_if($cycles->isEmpty(), 422, 'The loan has no cycles.');
            abort_if($this->agreed($loan, $cycles), 422, 'This loan is in forbearance or on a payment plan the owner agreed; its cycles are not rebuilt.');
            $cash = Disbursement::where('loan_id', $loan->id)->orderBy('disburse_date')->get()->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash');
            abort_if($cash->isEmpty(), 422, 'No cash was recorded: use "Add the missing disbursement".');

            $c1 = $cycles[0];
            $first = 0.0;
            $add = array_fill(0, $cycles->count(), 0.0);
            foreach ($cash as $d) {
                $day = Carbon::parse($d->disburse_date)->startOfDay();
                if (abs($c1->start_date->diffInDays($day, false)) <= self::WINDOW || $day->lt($c1->start_date)) {
                    $first += (float) $d->amount;
                    continue;
                }
                $k = $cycles->keys()->filter(fn ($i) => $cycles[$i]->start_date->lte($day))->last() ?? 0;
                $add[$k] += (float) $d->amount;
            }
            $old = $cycles->map(fn ($c, $i) => $i < $cycles->count() - 1 ? (float) (CycleBalance::at($c)['owed_on_close'] ?? 0) : null)->all();

            $before = $cycles->map->lentPrincipal()->all();
            $carry = 0.0;
            foreach ($cycles as $i => $c) {
                $p = $i === 0 ? ($first > 0 ? $first : $c->lentPrincipal()) + $add[0] : $c->lentPrincipal() + $carry + $add[$i];
                $p = round($p, 2);
                $interest = round($p * (float) $c->interest_rate / 100, 2);
                $c->forceFill(['principal' => $p, 'previous_balance' => $i === 0 ? 0 : $p, 'interest_capitalized' => $interest, 'new_balance' => round($p + $interest, 2)])->save();
                if ($i < $cycles->count() - 1) {
                    $carry = round((float) (CycleBalance::at($c->fresh())['owed_on_close'] ?? 0) - $old[$i], 2);
                }
            }

            return 'cycles rebuilt from the cash sent: principal ' . collect($before)->map(fn ($v) => number_format($v, 0))->implode(' / ')
                . ' -> ' . $cycles->map(fn ($c) => number_format($c->fresh()->lentPrincipal(), 0))->implode(' / ');
        }, $note);
    }

    /**
     * Moves a receivable in the ledger from one loan to another (a rollover that went to a loan the rows don't say).
     * One transfer per pair of loans; doing it again replaces the amount.
     */
    public function moveReceivable(int $fromLoanId, int $toLoanId, float $amount, ?string $note = null): string
    {
        abort_if($fromLoanId === $toLoanId || $amount <= 0, 422, 'Choose another loan and an amount above 0.');
        $from = Loan::findOrFail($fromLoanId);
        $to = Loan::findOrFail($toLoanId);
        abort_unless((int) $from->user_id === (int) $to->user_id, 422, 'Both loans must be the same borrower\'s.');
        abort_if($this->recent($from) || $this->recent($to), 422, 'A loan from the last 3 months keeps its rollovers (owner, 22 Oct): no receivable is moved.');
        $ledger = app(Ledger::class);
        $ledger->sync('repair_transfer', $fromLoanId * 100000 + $toLoanId, [[
            'leg' => 'rollover_transfer', 'from' => $ledger->account($from, 'gl-receivable'), 'to' => $ledger->account($to, 'gl-receivable'), 'amount' => round($amount, 2),
        ]], ['loan_id' => $to->id, 'date' => now()->toDateString(), 'source' => 'ledger', 'reference' => "MOVE #{$from->id} -> #{$to->id}"]);
        AuditTrail::record('loans', $to->id, 'update', null, ['repair' => 'move_receivable', 'from' => $fromLoanId, 'amount' => $amount, 'note' => $note]);

        return 'KES ' . number_format($amount, 2) . " moved from #{$fromLoanId} to #{$toLoanId}";
    }

    /**
     * The fix most likely right for a loan, from the same signals a person would read off the page: one action, why,
     * and the loan to post it to (a move is posted from the loan that holds the receivable). Null: look first.
     *
     * @param  Collection|null  $all  every finding, to look for the matching loan of the same borrower
     */
    public function suggest(array $r, ?Collection $all = null): ?array
    {
        $sg = $this->suggestion($r, $all);
        if ($sg && in_array($sg['action'], ['move', 'book_bad_debt', 'book_remove'], true)) {
            $touch = array_filter([$sg['loan'] ?? null, $sg['to'] ?? null, $r['loan_id']]);
            foreach ($touch as $id) {
                $l = Loan::withTrashed()->find($id);
                if ($l && $this->recent($l)) {
                    return null;   // owner: loans from the last 3 months keep their rollovers
                }
            }
        }

        return $sg;
    }

    private function suggestion(array $r, ?Collection $all = null): ?array
    {
        $kes = fn ($v) => 'KES ' . number_format(abs((float) $v), 2);
        $book = collect($r['book']);
        $owed = (float) ($r['owed'] ?? 0);
        $diff = (float) $r['difference'];
        $s = fn ($action, $label, $why, array $extra = [], ?int $loan = null) => ['loan' => $loan ?? $r['loan_id'], 'action' => $action, 'label' => $label, 'why' => $why] + $extra;

        // a fake credit first: whatever else is wrong, a book row bigger than the debt comes down
        if ($owed < -1) {
            $row = $book->whereIn('kind', ['bad_debt', 'discount', 'rollover', 'non_cash'])->sortByDesc('amount')->first();
            if ($row) {
                return $s('book_cut', 'Cut the ' . str_replace('_', ' ', $row['kind']) . ' of ' . $kes($row['amount']) . ' to what was owed',
                    'The loan shows ' . $kes($owed) . ' in the borrower\'s favour that nobody paid.', ['row' => $row['id']]);
            }
        }

        // a matching loan of the same borrower carrying the opposite difference: the receivable sits on the wrong loan
        if ($all && abs($diff) >= 1) {
            $match = $this->agreed(Loan::withTrashed()->find($r['loan_id'])) ? null
                : $all->first(fn ($o) => $o['loan_id'] !== $r['loan_id'] && $o['user_id'] === $r['user_id']
                    && abs((float) $o['difference'] + $diff) < 1 && ! $this->agreed(Loan::withTrashed()->find($o['loan_id'])));
            if ($match) {
                [$from, $to] = $diff > 0 ? [$r['loan_id'], $match['loan_id']] : [$match['loan_id'], $r['loan_id']];

                return $s('move', "Move {$kes($diff)} from #{$from} to #{$to}",
                    "#{$match['loan_id']} of the same borrower is off by exactly the opposite amount: the balance was carried there.",
                    ['to' => $to, 'amount' => round(abs($diff), 2)], $from);
            }
        }

        $agreed = $this->agreed(Loan::withTrashed()->find($r['loan_id']));
        $other = $this->counterpart($r, $all);
        $move = function () use ($r, $other, $s, $kes) {
            [$from, $to] = (float) $r['difference'] > 0 ? [$r['loan_id'], $other['loan_id']] : [$other['loan_id'], $r['loan_id']];
            $amount = round(min(abs((float) $r['difference']), abs((float) $other['difference'])), 2);

            return $s('move', "Move {$kes($amount)} from #{$from} to #{$to}",
                "#{$other['loan_id']} of the same borrower is off the other way (" . $kes($other['difference']) . "): the balance was carried there. Check the dates before applying.",
                ['to' => $to, 'amount' => $amount], $from);
        };

        switch ($r['group']) {
            case 'broker_fee':
                $row = $book->firstWhere('kind', 'non_cash');
                if (! $row) {
                    return null;
                }
                $broker = $this->brokerFor(Loan::withTrashed()->find($r['loan_id']));

                return $s('broker_kept', 'Book the ' . $kes($row['amount']) . ' as the broker fee ' . $broker . ' kept',
                    'The broker took its fee out of what it collected: AC\'s cost, nothing more to pay (owner: last 3 months to the loan\'s broker, older pooled to ISIRO).', ['row' => $row['id']]);

            case 'cash':
            case 'over_booked':
                if ($agreed) {
                    return null;   // the owner set this loan's terms tonight: look first
                }
                $repaid = $r['status'] === Loan::STATUS_REPAID;
                $contract = $r['first_principal'] * (1 + (float) ($r['cycles'][0]['rate'] ?? 0) / 100);
                if ($r['cash_sent'] > $r['first_principal'] + 0.5) {
                    // owner, 22 Oct 2026: never "add the extra" for now; loans stay as lent (balance the cycles instead)
                    if (! config('repairs.allow_add_extra', false)) {
                        return null;
                    }
                    // a closed loan only takes the extra if its repayments show it was lent (it is in credit as it stands)
                    if ($repaid && $owed > -1) {
                        return null;
                    }

                    return null;   // owner, 22 Oct 2026: never "add the extra"; the loan is balanced instead (LoanBalancer)
                }
                if ($r['first_principal'] - $r['cash_sent'] >= 0.01) {
                    return $r['cash_repaid'] >= $contract - 1
                        ? $s('add_disbursement', 'Record the missing ' . $kes($r['first_principal'] - $r['cash_sent']) . ' disbursement',
                            'The borrower repaid as if the full ' . $kes($r['first_principal']) . ' was lent, so the money went out but was not recorded.')
                        : null;   // less went out than lent and the repayments don't settle which is right: look first
                }

                return null;

            case 'no_cash':
                return $s('add_disbursement', 'Record the missing ' . $kes($r['first_principal']) . ' disbursement',
                    'Lent and ' . ($owed <= 0.01 ? 'repaid' : 'charged') . ' as ' . $kes($r['first_principal']) . ', but no money going out was ever recorded.');

            case 'rollover':
                // a rollover's balance came from (or went to) another loan: never invent cash for it
                if ($other && ! $agreed) {
                    return $move();
                }
                $row = $book->firstWhere('kind', 'rollover');
                if ($row && $diff > 0) {
                    return $s('book_bad_debt', 'Treat the ' . $kes($row['amount']) . ' ROLL OVER as a write-off',
                        'It closed this loan but no other loan of the borrower took the balance, so the receivable never left.', ['row' => $row['id']]);
                }

                return null;

            default:
                return null;
        }
    }

    /** Applies the suggestion each listed loan has right now (worked out again, never from the page). */
    public function applySuggestions(array $loanIds, ?string $note = null): array
    {
        $done = [];
        $skipped = [];
        $all = $this->findings();   // once: a move suggested on both loans of a pair is one transfer (posting it again changes nothing)
        foreach ($loanIds as $id) {
            $row = $all->firstWhere('loan_id', (int) $id);
            $sg = $row['suggestion'] ?? null;
            if (! $sg) {
                $skipped[] = "#{$id} (no suggestion now)";
                continue;
            }
            try {
                $done[] = $this->run($sg, $note);
            } catch (\Throwable $e) {
                $skipped[] = "#{$id} (" . strtok($e->getMessage(), "\n") . ')';
            }
        }

        return [$done, $skipped];
    }

    /** One action, as the page sends it. */
    public function run(array $p, ?string $note = null): string
    {
        $row = isset($p['row']) ? (int) $p['row'] : null;

        return match ($p['action']) {
            'book_discount' => $this->bookAs($row, 'discount', $note),
            'book_bad_debt' => $this->bookAs($row, 'bad_debt', $note),
            'book_remove' => $this->bookRemove($row, $note),
            'book_cut' => $this->bookCut($row, $note),
            'broker_kept' => $this->brokerKept($row, $note),
            'add_disbursement' => $this->addMissingDisbursement((int) $p['loan'], $note, isset($p['amount']) ? (float) $p['amount'] : null, $p['date'] ?? null),
            'book_set' => $this->bookSet($row, (float) $p['amount'], $note),
            'move_disbursement' => $this->moveDisbursement($row, (int) $p['to'], $note),
            'move_overpayment' => $this->moveOverpayment((int) $p['loan'], (int) $p['to'], (float) $p['amount'], $note),
            'lend_sent' => $this->lendWhatWasSent((int) $p['loan'], $note),
            'disbursement_set' => $this->disbursementSet($row, (float) $p['amount'], $note),
            'cycle_set' => $this->cycleSet((int) $p['cycle'], (float) $p['principal'], isset($p['rate']) && $p['rate'] !== '' ? (float) $p['rate'] : null, $note),
            'remove_disbursement' => $this->removeDisbursement($row, $note),
            'rebuild' => $this->rebuildFromCash((int) $p['loan'], $note),
            'move' => $this->moveReceivable((int) $p['loan'], (int) $p['to'], (float) $p['amount'], $note),
        };
    }

    // ------------------------------------------------------------------ helpers

    /** In forbearance, or carrying a payment plan / waiver the owner agreed: never rebuilt from cash. */
    public function agreed(Loan $loan, ?Collection $cycles = null): bool
    {
        $cycles ??= LoanCycle::where('loan_id', $loan->id)->get();

        return $loan->status === Loan::STATUS_FORBEARANCE
            || $cycles->contains(fn ($c) => $c->penalty_waived_until !== null || str_contains(strtoupper((string) $c->notes), 'PAYMENT PLAN'));
    }

    /** The same borrower's other mismatched loan with the opposite difference, closest in size (the balance went there). */
    private function counterpart(array $r, ?Collection $all): ?array
    {
        if (! $all || abs((float) $r['difference']) < 1) {
            return null;
        }

        return $all->filter(fn ($o) => $o['loan_id'] !== $r['loan_id'] && $o['user_id'] === $r['user_id']
                && (float) $o['difference'] * (float) $r['difference'] < 0 && abs((float) $o['difference']) >= 1
                && ! $this->agreed(Loan::withTrashed()->find($o['loan_id'])))
            ->sortBy(fn ($o) => abs(abs((float) $o['difference']) - abs((float) $r['difference'])))->first();
    }

    /** What the loan owes now (negative: in credit), as wallets:check reads it. */
    public function owedFor(int $loanId): float
    {
        $cycle = LoanCycle::where('loan_id', $loanId)->orderByDesc('cycle_number')->firstOrFail();
        $b = CycleBalance::at($cycle);

        return round($b['final_outstanding'] - $b['overpaid'], 2);
    }

    private function onRepayment(int $repaymentId, callable $do, ?string $note): string
    {
        $r = Repayment::findOrFail($repaymentId);
        abort_if(CustomerWallets::kindOf($r) === 'cash', 422, 'Cash repayments are not changed here.');
        abort_if(CustomerWallets::kindOf($r) === 'rollover' && $this->recent(Loan::withTrashed()->findOrFail($r->loan_id)), 422,
            'A loan from the last 3 months keeps its rollovers (owner, 22 Oct): this ROLL OVER row is left as it is.');

        return $this->onLoan((int) $r->loan_id, fn () => $do($r), $note, ['repayment' => $repaymentId, 'was' => $r->only(['amount', 'kind', 'transaction'])]);
    }

    private function onLoan(int $loanId, callable $do, ?string $note, array $extra = []): string
    {
        $msg = DB::transaction(function () use ($loanId, $do, $note, $extra) {
            $loan = Loan::whereKey($loanId)->lockForUpdate()->firstOrFail();
            $msg = $do($loan);
            if (! in_array($loan->fresh()->status, LoanStatus::LEAVE, true)) {
                LoanStatus::apply($loan->fresh());
            }
            AuditTrail::record('loans', $loanId, 'update', $extra ?: null, ['repair' => $msg, 'note' => $note]);

            return $msg;
        });

        return "Loan #{$loanId}: {$msg}.";
    }
}
