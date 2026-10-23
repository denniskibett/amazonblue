<?php

namespace App\Services;

use App\Models\Loan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the loan book says about amounts and behaviour (owner, 23 Oct 2026). Read-only.
 *
 *  - heatmaps: by amount band, how much is asked for, approved, sent and paid back on time; and the same by the
 *    weekday the money went out, by the time of the month, and by how many loans the borrower had before;
 *  - one behaviour profile per borrower (borrower_profiles, refreshed nightly by analytics:profiles): on-time
 *    rate, days late, the largest amount they have repaid comfortably, whether loans sent at weekends go bad,
 *    a 0-100 behaviour score and a suggested limit.
 *
 * Each loan's outcome is CreditAssessment::outcome(), the same one the 4Cs use, so the figures agree with it.
 * Merged legacy loans count once, under the loan they were merged into.
 */
class BorrowerAnalytics
{
    public const BANDS = [
        [0, 5000, 'Up to 5k'], [5000, 10000, '5k–10k'], [10000, 25000, '10k–25k'], [25000, 50000, '25k–50k'],
        [50000, 100000, '50k–100k'], [100000, 250000, '100k–250k'], [250000, null, '250k+'],
    ];

    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    public const MONTH_PARTS = ['1–5 (payday)', '6–15', '16–24', '25–31 (month end)'];

    public const REPEATS = ['First loan', '2nd–3rd', '4th–9th', '10th+'];

    /** Outcomes that count as "finished" for rates: paid on time, paid late, rolled, discounted, written off. */
    public const FINISHED = ['on_time', 'late', 'rolled', 'discounted', 'written_off'];

    /**
     * One row per loan: amounts, when the money went out, which loan of the borrower it was, and how it ended.
     *
     * @return Collection<int, object>
     */
    public function loans(bool $fresh = false): Collection
    {
        $build = function () {
            $q = Loan::with(['repayments:id,loan_id,amount,repayment_date,transaction,kind', 'loanType:id,name']);
            if (Schema::hasColumn('loans', 'merged_into_loan_id')) {
                $q->whereNull('merged_into_loan_id');
            }
            $sent = DB::table('disbursements')->whereNull('deleted_at')->where(fn ($w) => $w->where('kind', 'cash')->orWhereNull('kind'))
                ->groupBy('loan_id')->selectRaw('loan_id, min(coalesce(disburse_date, payment_date)) as at, sum(coalesce(net_amount, amount)) as cash')->get()->keyBy('loan_id');
            $credit = app(CreditAssessment::class);
            $seq = [];

            return $q->orderBy('borrow_date')->orderBy('id')->get()->map(function (Loan $loan) use ($sent, $credit, &$seq) {
                $out = $credit->outcome($loan, now());
                $taken = $out['status'] !== 'not_taken';
                $sentAt = $sent[$loan->id]->at ?? null;
                $when = $sentAt ? Carbon::parse($sentAt) : ($loan->borrow_date ? Carbon::parse($loan->borrow_date) : null);
                $nth = $taken ? ($seq[$loan->user_id] = ($seq[$loan->user_id] ?? 0) + 1) : null;
                $asked = (float) ($loan->original_amount ?: $loan->amount);
                $principal = (float) ($sent[$loan->id]->cash ?? 0) ?: $asked;

                return (object) [
                    'id' => $loan->id, 'user_id' => $loan->user_id, 'status' => $loan->status, 'product' => $loan->loanType->name ?? null,
                    'asked' => $asked, 'principal' => $taken ? $principal : 0.0, 'taken' => $taken,
                    'approved' => $taken || $loan->status === Loan::STATUS_APPROVED,
                    'rejected' => $loan->status === Loan::STATUS_REJECTED,
                    'outcome' => $out['status'], 'days_late' => (int) $out['days_late'], 'paid_cash' => (float) $out['paid_cash'],
                    'sent_on' => $when, 'weekday' => $when ? (int) $when->isoWeekday() : null,
                    'month_part' => $when ? self::monthPart((int) $when->day) : null,
                    'nth' => $nth, 'band' => self::band($taken ? $principal : $asked),
                ];
            });
        };

        return $fresh ? $build() : Cache::remember('analytics:loans:' . now()->format('YmdH'), 1800, $build);
    }

    public static function band(float $amount): int
    {
        foreach (self::BANDS as $i => [$from, $to]) {
            if ($amount > $from - 0.001 && ($to === null || $amount < $to)) {
                return $i;
            }
        }

        return count(self::BANDS) - 1;
    }

    public static function monthPart(int $day): int
    {
        return $day <= 5 ? 0 : ($day <= 15 ? 1 : ($day <= 24 ? 2 : 3));
    }

    public static function repeat(?int $nth): ?int
    {
        return $nth === null ? null : ($nth === 1 ? 0 : ($nth <= 3 ? 1 : ($nth <= 9 ? 2 : 3)));
    }

    /** Figures for a set of loans: counts, amounts and rates. */
    public static function cell(Collection $loans): array
    {
        $taken = $loans->where('taken', true);
        $finished = $taken->whereIn('outcome', self::FINISHED);
        $bad = $taken->filter(fn ($l) => $l->outcome === 'written_off' || ($l->outcome === 'overdue' && $l->days_late > 30));
        $judged = $finished->count() + $taken->where('outcome', 'overdue')->count();
        $rate = fn ($n) => $judged ? round($n / $judged * 100, 1) : null;

        return [
            'asked_n' => $loans->count(), 'asked' => round($loans->sum('asked'), 2),
            'approved_n' => $loans->where('approved', true)->count(), 'rejected_n' => $loans->where('rejected', true)->count(),
            'sent_n' => $taken->count(), 'sent' => round($taken->sum('principal'), 2),
            'judged' => $judged,
            'on_time' => $rate($finished->where('outcome', 'on_time')->count()),
            'late' => $rate($finished->where('outcome', 'late')->count()),
            'rolled' => $rate($finished->whereIn('outcome', ['rolled', 'discounted'])->count()),
            'bad' => $rate($bad->count()),
            'overdue_now' => $taken->where('outcome', 'overdue')->count(),
            'avg_late' => $finished->where('outcome', 'late')->avg('days_late') ? round($finished->where('outcome', 'late')->avg('days_late'), 1) : null,
            'collected' => $taken->sum('principal') > 0 ? round($taken->sum('paid_cash') / $taken->sum('principal') * 100, 1) : null,
        ];
    }

    /** The comfort verdict for a band or cell: where to lend more, and where to hold back. */
    public static function verdict(array $c): array
    {
        if (($c['judged'] ?? 0) < 3) {
            return ['Too few loans', 'gray'];
        }
        if ($c['on_time'] >= 75 && $c['bad'] <= 5) {
            return ['Comfortable', 'green'];
        }
        if ($c['bad'] >= 20 || $c['on_time'] < 40) {
            return ['Hold back', 'red'];
        }

        return ['Watch', 'amber'];
    }

    /** Rows = amount bands; plus a matrix of band × another dimension (weekday, month part, repeat). */
    public function heatmaps(): array
    {
        $loans = $this->loans();
        $bands = collect(self::BANDS)->map(fn ($b, $i) => ['label' => $b[2]] + self::cell($loans->where('band', $i)))->all();
        $matrix = function (string $field, array $labels, ?callable $map = null) use ($loans) {
            $rows = [];
            foreach (self::BANDS as $i => $b) {
                foreach ($labels as $k => $label) {
                    $rows[$i][$k] = self::cell($loans->where('band', $i)->filter(fn ($l) => ($map ? $map($l->{$field}) : $l->{$field}) === $k));
                }
            }
            $totals = [];
            foreach ($labels as $k => $label) {
                $totals[$k] = self::cell($loans->filter(fn ($l) => ($map ? $map($l->{$field}) : $l->{$field}) === $k));
            }

            return ['labels' => $labels, 'rows' => $rows, 'totals' => $totals];
        };

        return [
            'bands' => $bands,
            'all' => self::cell($loans),
            'weekday' => $matrix('weekday', self::DAYS),
            'month' => $matrix('month_part', self::MONTH_PARTS),
            'repeat' => $matrix('nth', self::REPEATS, fn ($n) => self::repeat($n)),
            'loans' => $loans->count(),
        ];
    }

    /**
     * One borrower's behaviour from their loans: rates, the largest amount repaid comfortably, the weekend
     * pattern, a 0-100 score and a suggested limit. Flags say in words what staff should know.
     */
    public static function profile(Collection $loans): array
    {
        $taken = $loans->where('taken', true)->sortBy('sent_on')->values();
        $finished = $taken->whereIn('outcome', self::FINISHED);
        $judged = $finished->count() + $taken->where('outcome', 'overdue')->count();
        $onTime = $finished->where('outcome', 'on_time')->count();
        $bad = $taken->filter(fn ($l) => $l->outcome === 'written_off' || ($l->outcome === 'overdue' && $l->days_late > 30))->count();
        $rolled = $finished->whereIn('outcome', ['rolled', 'discounted'])->count();
        $lateDays = $taken->whereIn('outcome', ['late', 'overdue'])->pluck('days_late');
        $comfort = (float) $finished->filter(fn ($l) => $l->outcome === 'on_time' || ($l->outcome === 'late' && $l->days_late <= 3))->max('principal');
        $weekend = $taken->filter(fn ($l) => in_array($l->weekday, [5, 6, 7], true));
        $weekendJudged = $weekend->filter(fn ($l) => in_array($l->outcome, [...self::FINISHED, 'overdue'], true));
        $weekendBad = $weekendJudged->filter(fn ($l) => in_array($l->outcome, ['late', 'overdue', 'written_off', 'rolled'], true) && ($l->outcome !== 'late' || $l->days_late > 3))->count();
        $weekdayJudged = $taken->filter(fn ($l) => ! in_array($l->weekday, [5, 6, 7], true) && in_array($l->outcome, [...self::FINISHED, 'overdue'], true));
        $weekdayBad = $weekdayJudged->filter(fn ($l) => in_array($l->outcome, ['late', 'overdue', 'written_off', 'rolled'], true) && ($l->outcome !== 'late' || $l->days_late > 3))->count();
        $onTimeRate = $judged ? round($onTime / $judged * 100, 1) : null;
        $recent = $taken->slice(-3);

        // 0-100: on-time history counts most, then nothing gone bad, few rollovers, how late, and recent form
        $score = null;
        if ($judged) {
            $score = 40 * ($onTime / $judged)
                + 20 * ($bad ? 0 : 1)
                + 10 * (1 - min(1, $rolled / max(1, $judged)))
                + 15 * (1 - min(1, ($lateDays->avg() ?? 0) / 30))
                + 15 * ($recent->count() ? $recent->filter(fn ($l) => $l->outcome === 'on_time')->count() / $recent->count() : 0.5);
            $score = (int) round($score);
        }

        $first = (int) config('lending.first_time_limit', 5000);
        $good = (int) config('lending.good_standing_limit', 50000);
        $round = fn ($v) => (int) (floor($v / 500) * 500);
        $suggested = match (true) {
            ! $judged => $first,
            $bad > 0 || $taken->contains(fn ($l) => $l->outcome === 'overdue') => $first,
            $score >= 80 => min($good, max($first, $round($comfort * 1.25))),
            $score >= 60 => min($good, max($first, $round($comfort))),
            default => $first,
        };

        $flags = [];
        if ($bad) {
            $flags[] = ['Has a loan that went bad', 'red'];
        }
        if ($taken->contains(fn ($l) => $l->outcome === 'overdue')) {
            $flags[] = ['Overdue now', 'red'];
        }
        if ($weekendJudged->count() >= 2 && $weekendBad / $weekendJudged->count() >= 0.5
            && ($weekdayJudged->count() === 0 || $weekendBad / $weekendJudged->count() > $weekdayBad / max(1, $weekdayJudged->count()) + 0.2)) {
            $flags[] = ['Pays late on Fri–Sun loans: avoid weekend lending', 'amber'];
        }
        if ($rolled >= 2 && $judged && $rolled / $judged >= 0.4) {
            $flags[] = ['Often rolls over', 'amber'];
        }
        if ($judged >= 3 && $onTime === $judged) {
            $flags[] = ['Never late', 'green'];
        }

        return [
            'loans' => $taken->count(), 'judged' => $judged, 'on_time_rate' => $onTimeRate,
            'avg_days_late' => $lateDays->count() ? round($lateDays->avg(), 1) : 0.0, 'max_days_late' => (int) ($lateDays->max() ?? 0),
            'bad' => $bad, 'rolled' => $rolled, 'comfort_amount' => round($comfort, 2), 'largest' => round((float) $taken->max('principal'), 2),
            'weekend_loans' => $weekend->count(), 'weekend_bad' => $weekendBad,
            'score' => $score, 'suggested_limit' => $suggested, 'flags' => $flags,
            'last_outcome' => $taken->last()->outcome ?? null,
        ];
    }

    /** Recomputes and stores every borrower's profile. Returns how many were written. */
    public function refreshProfiles(): int
    {
        $byUser = $this->loans(true)->groupBy('user_id');
        $hasCopy = Schema::hasColumn('borrowers', 'behaviour_score');
        $n = 0;
        foreach ($byUser as $userId => $loans) {
            $p = self::profile($loans);
            DB::table('borrower_profiles')->updateOrInsert(['user_id' => $userId], [
                'loans' => $p['loans'], 'on_time_rate' => $p['on_time_rate'], 'avg_days_late' => $p['avg_days_late'],
                'max_days_late' => $p['max_days_late'], 'bad_loans' => $p['bad'], 'rolled' => $p['rolled'],
                'comfort_amount' => $p['comfort_amount'], 'largest' => $p['largest'], 'weekend_loans' => $p['weekend_loans'],
                'weekend_bad' => $p['weekend_bad'], 'score' => $p['score'], 'suggested_limit' => $p['suggested_limit'],
                'flags' => json_encode(array_map(fn ($f) => ['text' => $f[0], 'tone' => $f[1]], $p['flags'])),
                'last_outcome' => $p['last_outcome'], 'computed_at' => now(), 'updated_at' => now(),
            ]);
            // a copy on the borrower's own row, for lists and exports (owner, 23 Oct 2026)
            if ($hasCopy) {
                DB::table('borrowers')->where('user_id', $userId)->update([
                    'behaviour_score' => $p['score'], 'on_time_rate' => $p['on_time_rate'], 'comfort_amount' => $p['comfort_amount'],
                    'suggested_limit' => $p['suggested_limit'], 'behaviour_flags' => json_encode(array_map(fn ($f) => $f[0], $p['flags'])),
                    'behaviour_at' => now(),
                ]);
            }
            $n++;
        }
        Cache::forget('analytics:loans:' . now()->format('YmdH'));

        return $n;
    }

    /** The stored profile for one borrower (null until analytics:profiles has run). */
    public static function profileFor(int $userId): ?object
    {
        if (! Schema::hasTable('borrower_profiles')) {
            return null;
        }
        $p = DB::table('borrower_profiles')->where('user_id', $userId)->first();
        if ($p) {
            $p->flags = json_decode((string) $p->flags, true) ?: [];
        }

        return $p;
    }
}
