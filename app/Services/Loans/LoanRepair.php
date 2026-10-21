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
    public function addMissingDisbursement(int $loanId, ?string $note = null): string
    {
        return $this->onLoan($loanId, function (Loan $loan) {
            $c1 = LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->firstOrFail();
            $cash = Disbursement::where('loan_id', $loan->id)->get()->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->sum('amount');
            $missing = round($c1->lentPrincipal() - $cash, 2);
            abort_if($missing < 0.01, 422, 'Nothing is missing: the cash recorded already covers cycle 1.');
            $day = $c1->start_date->toDateString();
            Disbursement::create(['loan_id' => $loan->id, 'loan_cycle_id' => $c1->id, 'amount' => $missing, 'net_amount' => $missing,
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
                    // a closed loan only takes the extra if its repayments show it was lent (it is in credit as it stands)
                    if ($repaid && $owed > -1) {
                        return null;
                    }

                    return $s('rebuild', 'Add the extra ' . $kes($r['cash_sent'] - $r['first_principal']) . ' to the loan',
                        $repaid ? 'The repayments only add up if the larger amount was lent: rebuilding removes the credit.'
                            : 'More cash went out than cycle 1 lent; the extra goes into the cycle running the day it was sent.');
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
            'add_disbursement' => $this->addMissingDisbursement((int) $p['loan'], $note),
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
