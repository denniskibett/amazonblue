<?php

namespace App\Services;

use App\Models\Loan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cash and growth (owner, 3 Nov 2026): where cash stands, what the loan book should bring in week by week, what is
 * committed to go out, and how lending, collections and interest on new loans have grown month by month.
 *
 * The forecast is a model, not a promise: what each open loan owes today is expected back with a chance set by how
 * late it is (config lending.forecast.collect), when it falls due or, for late loans, spread over the next weeks.
 * Pending applications go out at the recent approval rate. A second line keeps lending at the recent weekly pace.
 */
class CashForecast
{
    public function __construct(private LedgerReports $ledger) {}

    /** Cash per the ledger (gl-cash): bank and M-Pesa, as booked. */
    public function cashNow(): ?float
    {
        $w = DB::table('wallets')->where('slug', 'gl-cash')->first(['balance', 'decimal_places']);

        return $w ? round((float) $w->balance / (10 ** (int) ($w->decimal_places ?? 2)), 2) : null;
    }

    /** @return array{weeks: array<int, array>, assumptions: array, book: array} */
    public function weeks(int $count = 12): array
    {
        $p = config('lending.forecast.collect');
        $spread = max(1, (int) config('lending.forecast.late_spread_weeks', 4));
        $start = now()->startOfWeek();
        $weeks = collect(range(0, $count - 1))->map(fn ($i) => ['from' => $start->copy()->addWeeks($i), 'in_book' => 0.0, 'out_committed' => 0.0, 'out_pace' => 0.0, 'in_pace' => 0.0])->all();
        $at = fn (\Carbon\CarbonInterface $d) => max(0, (int) floor($start->diffInDays($d->copy()->startOfDay()) / 7));

        $book = ['current' => 0.0, 'late_1_30' => 0.0, 'late_31_90' => 0.0, 'late_90' => 0.0, 'forbearance' => 0.0];
        foreach ($this->ledger->portfolio()['rows'] as $r) {
            $loan = $r['loan'];
            $owed = (float) $r['owed'];
            $days = (int) $r['days'];
            $bucket = $loan->status === Loan::STATUS_FORBEARANCE ? 'forbearance'
                : ($days <= 0 ? 'current' : ($days <= 30 ? 'late_1_30' : ($days <= 90 ? 'late_31_90' : 'late_90')));
            $book[$bucket] += $owed;
            $expected = $owed * (float) ($p[$bucket] ?? 0);
            $due = $loan->getDueDate();
            if ($bucket === 'current' && $due && $due->isFuture()) {
                if ($at($due) < $count) {          // due after the horizon: not in these weeks
                    $weeks[$at($due)]['in_book'] += $expected;
                }
            } else {
                foreach (range(0, $spread - 1) as $i) {
                    $weeks[min($count - 1, $i)]['in_book'] += $expected / $spread;
                }
            }
        }

        // committed: applications waiting, at the recent approval rate, go out next week
        $pending = (float) Loan::where('status', Loan::STATUS_PENDING)->sum('amount') + (float) Loan::where('status', Loan::STATUS_APPROVED)->sum('amount');
        $approval = $this->approvalRate();
        $weeks[min(1, $count - 1)]['out_committed'] += round($pending * $approval, 2);

        // scenario: keep lending at the 8-week pace; that money comes back (with what it earns) about two weeks later
        $pace = $this->weeklyLendingPace();
        $return = $this->returnRatio();
        $lag = (int) config('lending.forecast.return_lag_weeks', 2);
        foreach (range(0, $count - 1) as $i) {
            $weeks[$i]['out_pace'] = $pace;
            if ($i + $lag < $count) {
                $weeks[$i + $lag]['in_pace'] += $pace * $return * (float) ($p['current'] ?? 0.9);
            }
        }

        $cash = $this->cashNow() ?? 0.0;
        $run = $cash;
        $runPace = $cash;
        foreach ($weeks as &$w) {
            $run += $w['in_book'] - $w['out_committed'];
            $runPace += $w['in_book'] + $w['in_pace'] - $w['out_committed'] - $w['out_pace'];
            $w['cash_book'] = round($run, 2);
            $w['cash_pace'] = round($runPace, 2);
            foreach (['in_book', 'out_committed', 'out_pace', 'in_pace'] as $k) {
                $w[$k] = round($w[$k], 2);
            }
        }

        return ['weeks' => array_values($weeks), 'book' => array_map(fn ($v) => round($v, 2), $book), 'assumptions' => [
            'collect' => $p, 'late_spread_weeks' => $spread, 'approval_rate' => $approval, 'pending' => round($pending, 2),
            'weekly_pace' => $pace, 'return_ratio' => $return, 'return_lag_weeks' => $lag,
        ]];
    }

    /** Lending, collections, net cash and income by month, the last $months months plus this month so far. */
    public function months(int $months = 6): Collection
    {
        $from = now()->startOfMonth()->subMonths($months);
        $lent = CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at'))->where('disburse_date', '>=', $from)
            ->selectRaw("date_format(disburse_date, '%Y-%m') as m, count(*) as n, sum(" . CustomerWallets::DISBURSED_CASH_SQL . ') as total')->groupBy('m')->get()->keyBy('m');
        $repaid = CustomerWallets::whereCash(DB::table('repayments')->whereNull('deleted_at'))->where('repayment_date', '>=', $from)
            ->selectRaw("date_format(repayment_date, '%Y-%m') as m, count(*) as n, sum(amount) as total")->groupBy('m')->get()->keyBy('m');
        // interest charged on new loans (first cycles), by the month they start: what new lending earns. Rollover
        // cycles are left out: they re-charge late balances and would inflate it; the ledger's own dates are the backfill's.
        $income = DB::table('loan_cycles')->where('cycle_number', 1)->where('start_date', '>=', $from->toDateString())
            ->selectRaw("date_format(start_date, '%Y-%m') as m, sum(principal * interest_rate / 100) as total")->groupBy('m')->pluck('total', 'm')
            ->map(fn ($v) => round((float) $v, 2));
        $firsts = DB::table('loans')->whereNotIn('status', ['pending', 'rejected'])->groupBy('user_id')->selectRaw('user_id, min(borrow_date) as first')->get()
            ->filter(fn ($r) => $r->first && $r->first >= $from->toDateString())
            ->groupBy(fn ($r) => substr((string) $r->first, 0, 7))->map->count();

        $rows = collect(range($months, 0))->map(function ($back) use ($lent, $repaid, $income, $firsts) {
            $m = now()->startOfMonth()->subMonths($back)->format('Y-m');
            $l = (float) ($lent[$m]->total ?? 0);
            $c = (float) ($repaid[$m]->total ?? 0);

            return ['month' => $m, 'partial' => $back === 0, 'loans' => (int) ($lent[$m]->n ?? 0), 'new_borrowers' => (int) ($firsts[$m] ?? 0),
                'lent' => round($l, 2), 'collected' => round($c, 2), 'net' => round($c - $l, 2), 'income' => (float) ($income[$m] ?? 0)];
        });

        // month-on-month growth of what we collect, full months only
        return $rows->values()->map(function ($r, $i) use ($rows) {
            $prev = $rows->values()[$i - 1] ?? null;
            $r['growth'] = $prev && ! $r['partial'] && $prev['collected'] > 0 ? round(($r['collected'] - $prev['collected']) / $prev['collected'] * 100, 1) : null;

            return $r;
        });
    }

    /**
     * The next $ahead months: the last three full months' average, grown at the monthly rate that turns the three
     * months before into them (single months swing too much to use), capped at ±25% a month.
     */
    public function projection(Collection $months, int $ahead = 3): array
    {
        $full = $months->where('partial', false)->values();
        $recent = $full->take(-3);
        $before = $full->slice(-6, 3);
        $avg = fn (Collection $c, string $k) => $c->count() ? $c->avg($k) : 0.0;
        $rate = $avg($before, 'collected') > 0 ? (($avg($recent, 'collected') / $avg($before, 'collected')) ** (1 / 3) - 1) * 100 : 0.0;
        $rate = round(max(-25, min(25, $rate)), 1);
        $out = [];
        foreach (range(1, $ahead) as $i) {
            $f = (1 + $rate / 100) ** $i;
            $out[] = ['month' => now()->startOfMonth()->addMonths($i)->format('Y-m'), 'collected' => round($avg($recent, 'collected') * $f, 2),
                'lent' => round($avg($recent, 'lent') * $f, 2), 'income' => round($avg($recent, 'income') * $f, 2)];
        }

        return ['rate' => $rate, 'months' => $out];
    }

    private function approvalRate(): float
    {
        $since = now()->subMonths(6);
        $decided = DB::table('loans')->where('created_at', '>=', $since)->whereNotIn('status', ['pending'])->count();
        $rejected = DB::table('loans')->where('created_at', '>=', $since)->where('status', 'rejected')->count();

        return $decided > 0 ? round(($decided - $rejected) / $decided, 2) : 1.0;
    }

    private function weeklyLendingPace(): float
    {
        $sum = (float) CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at'))
            ->where('disburse_date', '>=', now()->subWeeks(8)->startOfDay())->sum(DB::raw(CustomerWallets::DISBURSED_CASH_SQL));

        return round($sum / 8, 2);
    }

    /** Cash back for each shilling lent, over the whole book (capped at the 20% flat rate plus a little penalty). */
    private function returnRatio(): float
    {
        $c = $this->ledger->cash();
        $lent = $c->sum('lent');

        return $lent > 0 ? round(min(1.3, $c->sum('repaid') / $lent), 3) : 1.0;
    }
}
