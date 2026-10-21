<?php

namespace App\Services\Loans;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Services\AuditTrail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Feature 2c (owner, 21 Oct 2026): CREDIT DISCOUNT was never money. It was used for two things:
 *
 *  1. a rollover in disguise: a CREDIT DISCOUNT repayment closing one loan and a CREDIT DISCOUNT disbursement for the
 *     same amount opening the same borrower's next loan (within 15 days), or a CREDIT DISCOUNT disbursement alone.
 *     Those rows become ROLL OVER rows, so loans:rollover-cleanup merges or transfers them like any other rollover.
 *  2. forgiveness: a CREDIT DISCOUNT repayment alone. It is removed and the cycle is made to close on the cash that was
 *     really paid: first grace days for the days it was late (the fewest that close it, so extra cash still pays its
 *     days of penalty, as in Feature 1); if the cash still falls short of principal + interest, the cycle's interest
 *     rate is lowered to fit (rounded down to 2 decimals, so at most a few shillings of overpayment remain); if the
 *     cash is short of the principal itself, the rate goes to 0% and exactly what is unpaid is written off (owner).
 *
 * For a cycle carried into a later one, "closes" means it owed exactly what the next cycle took over.
 */
class CreditCleanup
{
    public const MARK = 'CREDIT DISCOUNT';

    public const PAIR_WINDOW = 15;

    public function proposals(): Collection
    {
        $reps = Repayment::with('loan:id,user_id,deleted_at')->where('transaction', 'like', '%' . self::MARK . '%')->orderBy('repayment_date')->orderBy('id')->get()
            ->filter(fn ($r) => $r->loan && ! $r->loan->trashed())->values();
        $disbs = Disbursement::with('loan:id,user_id,deleted_at')->where('transaction', 'like', '%' . self::MARK . '%')->orderBy('disburse_date')->get()
            ->filter(fn ($d) => $d->loan && ! $d->loan->trashed())->values();

        $out = collect();
        $usedD = [];
        $alone = collect();
        foreach ($reps as $r) {
            $d = $disbs->first(fn ($d) => ! isset($usedD[$d->id]) && (int) $d->loan->user_id === (int) $r->loan->user_id && (int) $d->loan_id !== (int) $r->loan_id
                && abs((float) $d->amount - (float) $r->amount) < 0.01
                && abs(Carbon::parse($d->disburse_date)->diffInDays(Carbon::parse($r->repayment_date), false)) <= self::PAIR_WINDOW);
            $other = $d ? null : $this->openedWith($r, $usedD);
            if (! $d && $other) {
                $out->push(['action' => 'rollover', 'loan_id' => (int) $r->loan_id, 'to_loan' => $other['loan'], 'repayment_ids' => [$r->id], 'disbursement_id' => null,
                    'amount' => (float) $r->amount, 'date' => Carbon::parse($r->repayment_date)->toDateString(),
                    'reason' => "CREDIT DISCOUNT closes #{$r->loan_id}; #{$other['loan']} opened with exactly that ({$other['how']}); becomes a ROLL OVER"]);
                continue;
            }
            if ($d) {
                $usedD[$d->id] = true;
                $out->push(['action' => 'rollover', 'loan_id' => (int) $r->loan_id, 'to_loan' => (int) $d->loan_id, 'repayment_ids' => [$r->id], 'disbursement_id' => $d->id,
                    'amount' => (float) $r->amount, 'date' => Carbon::parse($r->repayment_date)->toDateString(),
                    'reason' => "CREDIT DISCOUNT pair: closes #{$r->loan_id}, opens #{$d->loan_id}; becomes a ROLL OVER"]);
            } else {
                $alone->push($r);
            }
        }
        foreach ($disbs as $d) {
            if (! isset($usedD[$d->id])) {
                $out->push(['action' => 'rollover', 'loan_id' => (int) $d->loan_id, 'to_loan' => (int) $d->loan_id, 'repayment_ids' => [], 'disbursement_id' => $d->id,
                    'amount' => (float) $d->amount, 'date' => Carbon::parse($d->disburse_date)->toDateString(),
                    'reason' => 'CREDIT DISCOUNT disbursement: the loan opened by carrying another balance; becomes a ROLL OVER']);
            }
        }
        foreach ($alone->groupBy('loan_cycle_id') as $cycleId => $rows) {
            $cycle = LoanCycle::find($cycleId);
            if ($cycle) {
                $out->push($this->fit($cycle, $rows));
            }
        }

        return $out->sortBy('loan_id')->values()->map(fn ($p) => $p + ['borrower' => DB::table('loans')->join('users', 'users.id', '=', 'loans.user_id')->where('loans.id', $p['loan_id'])->value('users.name')]);
    }

    /**
     * The same borrower's other loan that opened with exactly this amount within PAIR_WINDOW days: a ROLL OVER
     * disbursement (soft-deleted ones count: 2a removed those a cycle start already shows), or a first cycle that lent
     * exactly this with no disbursement row at all. Returns [loan, how] for the visible loan it now lives in.
     */
    private function openedWith(Repayment $r, array &$usedD): ?array
    {
        $day = Carbon::parse($r->repayment_date);
        $amount = (float) $r->amount;
        $loanIds = Loan::withTrashed()->where('user_id', $r->loan->user_id)->where('id', '!=', $r->loan_id)->pluck('id');
        foreach (Disbursement::withTrashed()->whereIn('loan_id', $loanIds)->get() as $d) {
            if (isset($usedD[$d->id]) || abs((float) $d->amount - $amount) >= 0.01
                || abs(Carbon::parse($d->disburse_date)->diffInDays($day, false)) > self::PAIR_WINDOW
                || ! in_array(\App\Services\CustomerWallets::kindOf($d), ['rollover', 'discount'], true)) {
                continue;
            }
            $root = $this->root((int) $d->loan_id);
            if ($root && $root !== (int) $r->loan_id) {
                $usedD[$d->id] = true;

                return ['loan' => $root, 'how' => 'a ROLL OVER disbursement' . ($d->trashed() ? ', already folded into its cycles' : '')];
            }
        }
        foreach (Loan::whereIn('id', $loanIds)->get() as $loan) {
            $c1 = LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->first();
            if ($c1 && ! Disbursement::withTrashed()->where('loan_id', $loan->id)->exists() && abs($c1->lentPrincipal() - $amount) < 0.01
                && abs($c1->start_date->diffInDays($day, false)) <= self::PAIR_WINDOW) {
                return ['loan' => $loan->id, 'how' => 'its first cycle, no disbursement row'];
            }
        }

        return null;
    }

    private function root(int $loanId): ?int
    {
        $seen = [];
        $loan = Loan::withTrashed()->find($loanId);
        while ($loan && $loan->merged_into_loan_id && ! isset($seen[$loan->id])) {
            $seen[$loan->id] = true;
            $loan = Loan::withTrashed()->find($loan->merged_into_loan_id);
        }

        return $loan && ! $loan->trashed() ? (int) $loan->id : null;
    }

    /** Remove the cycle's lone CREDIT DISCOUNT rows, then grace days, then a lower rate, until it closes on the cash paid. */
    private function fit(LoanCycle $cycle, Collection $rows): array
    {
        $ids = $rows->pluck('id')->map(fn ($i) => (int) $i)->all();
        $next = LoanCycle::where('loan_id', $cycle->loan_id)->where('cycle_number', '>', $cycle->cycle_number)->orderBy('cycle_number')->first();
        $terms = CycleBalance::termsFor($cycle);
        $payments = array_values(array_filter(CycleBalance::paymentsFor($cycle), fn ($p) => ! in_array((int) $p['id'], $ids, true)
            && (! $next || ($p['kind'] ?? 'cash') !== 'rollover')));
        // the day the cycle is judged on: the day it was carried, or (latest cycle) the day of its last real payment
        $endDay = $next ? ($cycle->closed_on?->toDateString() ?? $next->start_date->toDateString())
            : (collect($payments)->max('date') ?? $terms['due']);
        if ($next) {
            $terms['penalty_until'] = $endDay;
        }
        $target = $next ? $next->lentPrincipal() : 0.0;
        $owed = fn (array $t) => round(CycleBalance::compute($t, $payments, $next ? max($endDay, collect($payments)->max('date') ?? $endDay) : $endDay)['final_outstanding'], 2);

        $withCredit = array_values(array_filter(CycleBalance::paymentsFor($cycle), fn ($p) => ! $next || ($p['kind'] ?? 'cash') !== 'rollover'));
        $before = CycleBalance::compute($terms, $withCredit, $next ? max($endDay, collect($withCredit)->max('date') ?? $endDay) : now()->toDateString());
        $lastPaid = collect($payments)->max('date') ?? $endDay;
        $late = max(0, (int) Carbon::parse($terms['due'])->diffInDays(Carbon::parse($next ? $endDay : $lastPaid), false));

        $grace = (int) $terms['grace_days'];
        $now = $owed($terms);
        while ($now - $target > 0.004 && $grace < $late) {
            $grace++;
            $now = $owed(['grace_days' => $grace] + $terms);
        }
        $rate = (float) $terms['interest_rate'];
        $principal = (float) $terms['principal'];
        $residual = round($now - $target, 2);
        $short = 0.0;
        if ($residual > 0.004 && $principal > 0) {
            $interest = round($principal * $rate / 100, 2);
            $newInterest = $interest - $residual;
            if ($newInterest >= 0) {
                $rate = floor($newInterest / $principal * 100 * 100) / 100;   // 2 decimals, rounded down
            } else {
                $rate = 0.0;
                $short = round(-$newInterest, 2);
            }
            $now = $owed(['grace_days' => $grace, 'interest_rate' => $rate] + $terms);
        }
        // owner, 21 Oct 2026: cash short of the principal itself is written off at exactly what is unpaid on the judging
        // day (0% interest, no penalty), so the loan stays closed and the books say honestly that it was not collected
        $writeOff = 0.0;
        if ($short > 0) {
            $grace = max($grace, $late);
            $writeOff = round($owed(['grace_days' => $grace, 'interest_rate' => 0.0] + $terms) - $target, 2);
            $short = 0.0;
            $now = $target;
        } elseif (! $next) {
            // what it owes today (penalty runs on anything still unpaid after the last payment)
            $now = round(CycleBalance::compute(['grace_days' => $grace, 'interest_rate' => $rate] + $terms, $payments, now()->toDateString())['final_outstanding'], 2);
        }

        return ['action' => 'forgive', 'loan_id' => (int) $cycle->loan_id, 'to_loan' => null, 'cycle_id' => $cycle->id, 'cycle_number' => $cycle->cycle_number,
            'carried' => (bool) $next, 'repayment_ids' => $ids, 'disbursement_id' => null, 'amount' => round($rows->sum('amount'), 2),
            'date' => Carbon::parse($rows->first()->repayment_date)->toDateString(), 'due' => $terms['due'], 'days_late' => $late,
            'grace_before' => (int) $terms['grace_days'], 'grace_after' => $grace, 'rate_before' => (float) $terms['interest_rate'], 'rate_after' => $rate,
            'owed_before' => round($before['final_outstanding'] - $before['overpaid'], 2), 'owed_without_credit' => $owed($terms), 'target' => $target, 'owed_after' => $now,
            'left_owed' => 0.0, 'write_off' => $writeOff, 'write_off_date' => $endDay,
            'reason' => 'CREDIT DISCOUNT ' . number_format($rows->sum('amount'), 2) . ' removed'
                . ($grace > (int) $terms['grace_days'] ? "; {$grace} grace day(s)" : '')
                . ($rate !== (float) $terms['interest_rate'] ? '; rate ' . $terms['interest_rate'] . '% -> ' . $rate . '%' : '')
                . ($writeOff > 0 ? '; cash short of the principal: ' . number_format($writeOff, 2) . ' written off' : '')
                . ($now - $target < -0.004 ? '; ' . number_format($target - $now, 2) . ' overpaid' : '')];
    }

    public function apply(array $p, ?string $note = null): void
    {
        DB::transaction(function () use ($p, $note) {
            if ($p['action'] === 'rollover') {
                foreach ($p['repayment_ids'] as $id) {
                    $r = Repayment::findOrFail($id);
                    $r->forceFill(['kind' => 'rollover', 'mode' => 'book', 'transaction' => 'ROLL OVER (was ' . $r->transaction . ')'])->save();
                }
                if ($p['disbursement_id']) {
                    $d = Disbursement::findOrFail($p['disbursement_id']);
                    $d->forceFill(['kind' => 'rollover', 'transaction' => 'ROLL OVER (was ' . $d->transaction . ')'])->save();
                }
            } else {
                $cycle = LoanCycle::whereKey($p['cycle_id'])->lockForUpdate()->firstOrFail();
                foreach ($p['repayment_ids'] as $id) {
                    Repayment::find($id)?->delete();
                }
                if (($p['write_off'] ?? 0) > 0.004) {
                    Repayment::create(['loan_id' => $cycle->loan_id, 'loan_cycle_id' => $cycle->id, 'amount' => $p['write_off'],
                        'repayment_date' => $p['write_off_date'], 'transaction' => 'WRITE OFF: cash short of principal (was CREDIT DISCOUNT)',
                        'kind' => 'bad_debt', 'mode' => 'book', 'notes' => 'Owner decision 21 Oct 2026 (2c)']);
                }
                $principal = $cycle->lentPrincipal();
                $interest = round($principal * $p['rate_after'] / 100, 2);
                $cycle->forceFill(['grace_days' => $p['grace_after'], 'interest_rate' => $p['rate_after'], 'principal' => $principal,
                    'interest_capitalized' => $interest, 'new_balance' => round($principal + $interest, 2),
                    'notes' => trim($cycle->notes . ' | CREDIT DISCOUNT removed (owner, 21 Oct 2026)'
                        . ($p['grace_after'] !== $p['grace_before'] ? "; grace {$p['grace_after']}d" : '')
                        . ($p['rate_after'] != $p['rate_before'] ? "; rate {$p['rate_before']}% -> {$p['rate_after']}%" : ''))])->save();
            }
            $loan = Loan::find($p['loan_id']);
            if ($loan && ! in_array($loan->status, LoanStatus::LEAVE, true)) {
                LoanStatus::apply($loan);
            }
            AuditTrail::record('loans', $p['loan_id'], 'update', null, ['credit_cleanup' => $p, 'note' => $note]);
        });
    }
}
