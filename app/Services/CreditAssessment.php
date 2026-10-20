<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanRiskAssessment;
use App\Models\RiskCategory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 4Cs credit scoring from our own repayment history (0-100 each):
 *
 *   Character  - how past loans ended: paid on time, late, rolled over, part waived, written off
 *   Capacity   - can they afford this amount: declared income, and the largest loan they've repaid
 *   Capital    - their track record with us: loans repaid, months with us, cash repaid vs cash received
 *   Conditions - their position right now: other open or overdue loans, rollover streaks, recovery
 *
 * Every loan gets one system assessment (source = auto) "as at" its borrow date, so it only uses what
 * we knew when it was applied for; its factors also carry how that loan turned out, so the list of
 * assessments reads as the borrower's repayment journey. They are re-scored whenever a loan,
 * repayment or disbursement changes. The live assessment (today, all history) checks the limit.
 *
 * Only real cash counts as paying: rollovers, discounts and write-offs close a loan without money
 * changing hands (same rules as CustomerWallets).
 */
class CreditAssessment
{
    public const WEIGHTS = ['character' => 0.40, 'capacity' => 0.25, 'capital' => 0.15, 'conditions' => 0.20];

    // statuses where money went out to the borrower
    private const TAKEN = [
        Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED,
        Loan::STATUS_RECOVERY, Loan::STATUS_FORBEARANCE, Loan::STATUS_REPAID, Loan::STATUS_WRITTEN_OFF,
    ];

    // loan outcomes and what each is worth for character
    public const OUTCOMES = [
        'on_time' => 'Paid on time',
        'late' => 'Paid late',
        'discounted' => 'Paid with a discount',
        'rolled' => 'Rolled over',
        'written_off' => 'Written off',
        'overdue' => 'Overdue',
        'running' => 'Running',
        'not_taken' => 'Not taken',
    ];

    private ?Collection $categories = null;

    /** @var array<int, true> borrowers waiting to be re-scored at the end of the request */
    private static array $pending = [];

    // ---------------------------------------------------------------- re-scoring

    /**
     * Re-score this borrower once the request or command finishes (after the response is sent),
     * however many of their records changed. Failures are logged; `php artisan risk:assess` repairs.
     */
    public static function rescoreLater(int $userId): void
    {
        if (! self::$pending) {
            app()->terminating(function () {
                $ids = array_keys(self::$pending);
                self::$pending = [];
                foreach ($ids as $id) {
                    try {
                        if ($user = User::withTrashed()->find($id)) {
                            app(self::class)->syncUser($user);
                        }
                    } catch (\Throwable $e) {
                        Log::error("Risk re-score failed for user #{$id}", ['error' => $e->getMessage()]);
                    }
                }
            });
        }
        self::$pending[$userId] = true;
    }

    // ---------------------------------------------------------------- public API

    /**
     * The borrower as of today, for a new application of $amount (null = their ladder limit).
     *
     * @return array{scores: array, overall: int, category: ?RiskCategory, limit: array, history: array, factors: array}
     */
    public function current(User $user, ?float $amount = null): array
    {
        $loans = $this->loansFor($user);

        return $this->assess($user, $loans, now(), null, $amount);
    }

    /**
     * Re-score every loan of the borrower (one auto assessment per loan). Returns how many rows changed.
     */
    public function syncUser(User $user): int
    {
        $loans = $this->loansFor($user);
        $changed = 0;

        DB::transaction(function () use ($user, $loans, &$changed) {
            foreach ($loans as $loan) {
                $result = $this->assess($user, $loans, Carbon::parse($loan->borrow_date)->endOfDay(), $loan, (float) $loan->amount);
                $result['factors']['outcome'] = $this->outcome($loan, now());
                // the ladder limit is for the self-service product; staff-arranged products are sized by staff
                $selfService = (int) $loan->loan_type_id === (int) config('lending.borrower_loan_type_id');
                $limit = $selfService ? $result['limit']['limit'] : null;

                $row = LoanRiskAssessment::withTrashed()->firstOrNew(['loan_id' => $loan->id, 'source' => 'auto']);
                $row->fill([
                    'character_score' => $result['scores']['character'],
                    'capacity_score' => $result['scores']['capacity'],
                    'capital_score' => $result['scores']['capital'],
                    'conditions_score' => $result['scores']['conditions'],
                    'overall_score' => $result['overall'],
                    'risk_category' => $result['category']?->name,
                    'risk_category_id' => $result['category']?->id,
                    'limit_amount' => $limit,
                    'recommendation' => $this->recommendation($result['overall'], (float) $loan->amount, $limit),
                    'assessment_notes' => $this->summary($result['factors']),
                    'assessed_at' => Carbon::parse($loan->borrow_date)->startOfDay(),
                    'assessed_by' => null,
                ]);
                // compare decoded: the database may store JSON keys in another order
                if ($row->factors != $result['factors']) {
                    $row->factors = $result['factors'];
                }
                $row->deleted_at = null;
                if ($row->isDirty()) {
                    $row->save();
                    $changed++;
                }
            }

            // loans that were deleted lose their system assessment
            $changed += LoanRiskAssessment::where('source', 'auto')
                ->whereIn('loan_id', Loan::onlyTrashed()->where('user_id', $user->id)->pluck('id'))
                ->delete();
        });

        return $changed;
    }

    /**
     * How a loan stood on $asOf (repayments after that date are ignored).
     *
     * @return array{status: string, label: string, days_late: int, paid_cash: float, closed_on: ?string}
     */
    public function outcome(Loan $loan, Carbon $asOf): array
    {
        $result = fn (string $status, int $daysLate = 0, ?string $closedOn = null, float $cash = 0) => [
            'status' => $status,
            'label' => $status === 'late' ? 'Paid ' . $daysLate . ' ' . ($daysLate === 1 ? 'day' : 'days') . ' late'
                : ($status === 'overdue' ? $daysLate . ' ' . ($daysLate === 1 ? 'day' : 'days') . ' overdue' : self::OUTCOMES[$status]),
            'days_late' => $daysLate,
            'paid_cash' => round($cash, 2),
            'closed_on' => $closedOn,
        ];

        if (! in_array($loan->status, self::TAKEN, true)) {
            return $result('not_taken');
        }

        $payments = $loan->repayments->filter(fn ($r) => Carbon::parse($r->repayment_date)->lte($asOf));
        $kinds = $payments->map(fn ($r) => CustomerWallets::kindOf($r))->unique();
        $cash = (float) $payments->filter(fn ($r) => CustomerWallets::kindOf($r) === 'cash')->sum('amount');
        $lastPaid = $payments->max('repayment_date');
        $due = $loan->due_date ? Carbon::parse($loan->due_date)->endOfDay() : null;

        // closed: repaid / written off, and (looking back) its final payment had happened by then
        $finalPaid = $loan->repayments->max('repayment_date');
        $closedStatus = in_array($loan->status, [Loan::STATUS_REPAID, Loan::STATUS_WRITTEN_OFF], true);
        $closed = $closedStatus && (! $finalPaid || Carbon::parse($finalPaid)->lte($asOf));

        if ($closed) {
            $closedOn = $lastPaid ? Carbon::parse($lastPaid)->toDateString() : null;
            if ($loan->status === Loan::STATUS_WRITTEN_OFF || $kinds->contains('bad_debt')) {
                return $result('written_off', 0, $closedOn, $cash);
            }
            if ($kinds->contains('rollover')) {
                return $result('rolled', 0, $closedOn, $cash);
            }
            if ($kinds->contains('discount')) {
                return $result('discounted', 0, $closedOn, $cash);
            }
            $late = ($due && $lastPaid && Carbon::parse($lastPaid)->gt($due)) ? (int) $due->copy()->startOfDay()->diffInDays(Carbon::parse($lastPaid)->startOfDay()) : 0;

            return $result($late > 0 ? 'late' : 'on_time', $late, $closedOn, $cash);
        }

        if ($kinds->contains('bad_debt') || in_array($loan->status, [Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY], true) && $asOf->isToday()) {
            $days = $due ? max(1, (int) $due->copy()->startOfDay()->diffInDays($asOf->copy()->startOfDay())) : 1;

            return $result('overdue', $days, null, $cash);
        }
        if ($due && $asOf->gt($due)) {
            return $result('overdue', (int) $due->copy()->startOfDay()->diffInDays($asOf->copy()->startOfDay()), null, $cash);
        }

        return $result('running', 0, null, $cash);
    }

    /**
     * Ladder limit (first-time vs good standing), checked against the score and declared income.
     *
     * @return array{tier: string, limit: int, label: string, ladder: int, capped_by: ?string}
     */
    public function limit(array $scores, ?RiskCategory $category, array $factors, ?float $income, float $rate): array
    {
        $first = (int) config('lending.first_time_limit');
        $good = (int) config('lending.good_standing_limit');
        $goodStanding = $factors['last_outcome'] === 'on_time' && ! $factors['ever_written_off'] && ! $factors['ever_in_recovery'];
        $ladder = $goodStanding ? $good : $first;
        $limit = $ladder;
        $cappedBy = null;

        $riskCap = config('lending.risk_limit_caps.' . ($category?->slug ?? ''));
        if ($riskCap !== null && $riskCap < $limit) {
            [$limit, $cappedBy] = [(int) $riskCap, 'risk'];
        }

        // what they repay must fit in a month's declared income
        $multiple = (float) config('lending.income_multiple', 1.0);
        if ($income > 0 && $multiple > 0) {
            $incomeCap = (int) (floor($income * $multiple / (1 + $rate / 100) / 500) * 500);
            if ($incomeCap < $limit) {
                [$limit, $cappedBy] = [$incomeCap, 'income'];
            }
        }

        $limit = max($first, $limit);

        return [
            'tier' => $goodStanding ? 'good_standing' : 'first_time',
            'limit' => $limit,
            'label' => $goodStanding ? 'Good standing' : 'Starter limit',
            'ladder' => $ladder,
            'capped_by' => $limit < $ladder ? $cappedBy : null,
        ];
    }

    public function category(int $score): ?RiskCategory
    {
        $this->categories ??= RiskCategory::orderBy('min_score')->get();

        return $this->categories->first(fn ($c) => $score >= $c->min_score && $score <= $c->max_score);
    }

    public function recommendation(int $overall, float $amount, ?float $limit): string
    {
        $base = match (true) {
            $overall >= 80 => 'Approve: strong repayment record.',
            $overall >= 65 => 'Approve within the limit.',
            $overall >= 50 => 'Refer: call the borrower and guarantor before approving.',
            default => 'Decline, or approve a smaller amount with a guarantor.',
        };

        return $limit !== null && $amount > $limit
            ? $base . ' Amount is above the limit of KES ' . number_format($limit, 2) . '.'
            : $base;
    }

    // ---------------------------------------------------------------- scoring

    private function loansFor(User $user): Collection
    {
        return Loan::with(['repayments', 'loanType'])->where('user_id', $user->id)
            ->orderBy('borrow_date')->orderBy('id')->get();
    }

    /**
     * Score as at $asOf using the loans before $subject (or all loans when there's no subject).
     */
    private function assess(User $user, Collection $loans, Carbon $asOf, ?Loan $subject, ?float $amount): array
    {
        $prior = $subject
            ? $loans->filter(fn ($l) => $l->id !== $subject->id && (
                Carbon::parse($l->borrow_date)->lt(Carbon::parse($subject->borrow_date))
                || (Carbon::parse($l->borrow_date)->eq(Carbon::parse($subject->borrow_date)) && $l->id < $subject->id)))
            : $loans;

        $outcomes = $prior->map(fn ($l) => ['loan' => $l] + $this->outcome($l, $asOf))
            ->reject(fn ($o) => $o['status'] === 'not_taken')->values();
        $closed = $outcomes->whereIn('status', ['on_time', 'late', 'discounted', 'rolled', 'written_off'])->values();
        $open = $outcomes->whereIn('status', ['running', 'overdue'])->values();

        $recovery = $user->debtRecoveryCases()->where('created_at', '<=', $asOf)->exists();
        $openRecovery = $asOf->isToday() && $user->debtRecoveryCases()->open()->exists();
        $income = (float) ($user->borrower?->net_salary ?? 0);
        $rate = (float) (($subject?->loanType ?? null)?->interest_rate ?? app(BorrowingEligibility::class)->loanType()?->interest_rate ?? 0);

        $rolledStreak = 0;
        foreach ($closed->reverse() as $o) {
            if ($o['status'] !== 'rolled') {
                break;
            }
            $rolledStreak++;
        }

        $cashOut = $this->cashOut($closed->pluck('loan'));
        $cashIn = (float) $closed->sum('paid_cash');
        $firstBorrow = $outcomes->isNotEmpty() ? Carbon::parse($outcomes->first()['loan']->borrow_date) : null;

        $factors = [
            'as_at' => $asOf->toDateString(),
            'loans_before' => $outcomes->count(),
            'closed' => $closed->count(),
            'on_time' => $closed->where('status', 'on_time')->count(),
            'late' => $closed->where('status', 'late')->count(),
            'rolled' => $closed->where('status', 'rolled')->count(),
            'discounted' => $closed->where('status', 'discounted')->count(),
            'written_off' => $closed->where('status', 'written_off')->count(),
            'open_loans' => $open->count(),
            'overdue_loans' => $open->where('status', 'overdue')->count(),
            'rolled_streak' => $rolledStreak,
            'last_outcome' => $closed->last()['status'] ?? null,
            'ever_written_off' => $closed->contains('status', 'written_off') || $prior->contains('status', Loan::STATUS_WRITTEN_OFF),
            'ever_in_recovery' => $recovery,
            'non_performing_before' => $prior->contains(fn ($l) => (bool) $l->is_non_performing),
            'largest_repaid' => (float) $closed->whereIn('status', ['on_time', 'late'])->filter(fn ($o) => $o['days_late'] <= 3)->max(fn ($o) => (float) $o['loan']->amount) ?: 0,
            'cash_received' => round($cashOut, 2),
            'cash_repaid' => round($cashIn, 2),
            'months_with_us' => $firstBorrow ? (int) $firstBorrow->diffInMonths($asOf) : 0,
            'income' => $income ?: null,
        ];

        $scores = [
            'character' => $this->character($closed, $factors, $openRecovery),
            'capital' => $this->capital($factors),
            'conditions' => $this->conditions($factors, $openRecovery),
        ];

        // capacity needs an amount: the requested one, or (for "today") the ladder limit
        $ladderAmount = $amount ?? $this->limit(['character' => 0], null, $factors, null, $rate)['ladder'];
        $scores['capacity'] = $this->capacity((float) $ladderAmount, $rate, $factors);
        $scores = array_merge(array_flip(array_keys(self::WEIGHTS)), $scores);

        $overall = (int) round(collect(self::WEIGHTS)->map(fn ($w, $c) => $scores[$c] * $w)->sum());
        $category = $this->category($overall);

        return [
            'scores' => $scores,
            'overall' => $overall,
            'category' => $category,
            'limit' => $this->limit($scores, $category, $factors, $income ?: null, $rate),
            'history' => $outcomes->map(fn ($o) => array_diff_key($o, ['loan' => 1]) + [
                'loan_id' => $o['loan']->id,
                'amount' => (float) $o['loan']->amount,
                'borrow_date' => Carbon::parse($o['loan']->borrow_date)->toDateString(),
                'due_date' => $o['loan']->due_date ? Carbon::parse($o['loan']->due_date)->toDateString() : null,
            ])->all(),
            'factors' => $factors,
        ];
    }

    private function character(Collection $closed, array $f, bool $openRecovery): int
    {
        if ($closed->isEmpty()) {
            $score = 60; // no history yet: neutral
        } else {
            // the last five loans count double
            $recentFrom = max(0, $closed->count() - 5);
            $total = $weight = 0;
            foreach ($closed->values() as $i => $o) {
                $points = match ($o['status']) {
                    'on_time' => 100,
                    'late' => max(40, 85 - 3 * $o['days_late']),
                    'rolled' => 45,
                    'discounted' => 35,
                    default => 0,
                };
                $w = $i >= $recentFrom ? 2 : 1;
                $total += $points * $w;
                $weight += $w;
            }
            $score = $total / $weight;
        }

        if ($f['ever_written_off']) {
            $score = min($score, 35);
        }
        if ($f['ever_in_recovery'] || $openRecovery) {
            $score = min($score, 30);
        }

        return (int) round(max(0, min(100, $score)));
    }

    private function capacity(float $amount, float $rate, array $f): int
    {
        $repay = $amount * (1 + $rate / 100);
        $parts = [];

        if ($f['income'] > 0) {
            $ratio = $repay / $f['income'];
            $parts[] = match (true) {
                $ratio <= 0.3 => 100,
                $ratio <= 0.5 => 85,
                $ratio <= 0.8 => 65,
                $ratio <= 1.0 => 50,
                default => 30,
            };
        }
        if ($f['largest_repaid'] > 0) {
            $ratio = $amount / $f['largest_repaid'];
            $parts[] = match (true) {
                $ratio <= 1.0 => 90,
                $ratio <= 1.5 => 70,
                $ratio <= 2.0 => 55,
                default => 40,
            };
        }

        return $parts ? (int) round(array_sum($parts) / count($parts)) : 50;
    }

    private function capital(array $f): int
    {
        if ($f['closed'] === 0) {
            return 40;
        }

        $score = 40 + min(25, $f['on_time'] * 5) + min(15, $f['months_with_us']);
        if ($f['cash_received'] > 0) {
            $cover = $f['cash_repaid'] / $f['cash_received'];
            $score += match (true) {
                $cover >= 1.1 => 20,
                $cover >= 1.0 => 10,
                $cover >= 0.8 => 0,
                default => -20,
            };
        }

        return (int) max(0, min(100, $score));
    }

    private function conditions(array $f, bool $openRecovery): int
    {
        $score = 100
            - min(40, $f['open_loans'] * 20)
            - ($f['overdue_loans'] > 0 ? 30 : 0)
            - min(40, $f['rolled_streak'] * 10)
            - ($f['non_performing_before'] ? 20 : 0)
            - ($openRecovery ? 40 : 0);

        return (int) max(0, min(100, $score));
    }

    /**
     * Cash we actually sent for these loans (rollover "disbursements" are not cash).
     */
    private function cashOut(Collection $loans): float
    {
        if ($loans->isEmpty()) {
            return 0;
        }

        return (float) DB::table('disbursements')->whereIn('loan_id', $loans->pluck('id'))->whereNull('deleted_at')
            ->get(['amount', 'net_amount', 'transaction', 'kind'])
            ->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')
            ->sum(fn ($d) => (float) ($d->net_amount ?: $d->amount));
    }

    private function summary(array $f): string
    {
        if ($f['closed'] === 0) {
            return 'First loan with us: no repayment history yet.';
        }

        return sprintf(
            '%d earlier %s: %d on time, %d late, %d rolled over, %d discounted, %d written off. Cash repaid KES %s of KES %s received.',
            $f['closed'], $f['closed'] === 1 ? 'loan' : 'loans', $f['on_time'], $f['late'], $f['rolled'], $f['discounted'], $f['written_off'],
            number_format($f['cash_repaid'], 2), number_format($f['cash_received'], 2)
        );
    }
}
