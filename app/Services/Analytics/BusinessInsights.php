<?php

namespace App\Services\Analytics;

use App\Models\LoanCycle;
use App\Services\CustomerWallets;
use App\Services\LedgerReports;
use App\Services\Loans\CycleBalance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Is the business working? (owner, 4 Nov 2026) A management income statement for a period, the key lending ratios,
 * collection efficiency by month, unit economics per borrower and the contribution margin of each product.
 *
 * Cash basis, from the operational records and their own dates (the ledger's postings carry the date they were
 * posted, and the October backfill posted years at once):
 *  - interest and penalty income: what cash repayments in the period paid towards interest and penalty
 *    (CycleBalance allocations, the same split the partner shares use);
 *  - fee income: processing fees of loans that started in the period;
 *  - cost of funds: what partners earned in the period (their journal: profit share, interest, penalty share,
 *    introducer fees);
 *  - impairment: write-offs (bad-debt rows) dated in the period; discounts and waivers likewise;
 *  - operating expenses: not in the system yet; from config lending.analytics.opex (KES a month by line).
 * Read-only; cached for 10 minutes per period.
 */
class BusinessInsights
{
    public const PERIODS = ['mtd' => 'This month', 'last_month' => 'Last month', 'qtd' => 'This quarter', 'last_quarter' => 'Last quarter', 'ytd' => 'This year', 'last_12' => 'Last 12 months'];

    public function __construct(private LedgerReports $ledger) {}

    /** @return array{0: Carbon, 1: Carbon} */
    public static function range(string $period): array
    {
        $now = now();

        return match ($period) {
            'mtd' => [$now->copy()->startOfMonth(), $now->copy()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_quarter' => [$now->copy()->subQuarterNoOverflow()->startOfQuarter(), $now->copy()->subQuarterNoOverflow()->endOfQuarter()],
            'ytd' => [$now->copy()->startOfYear(), $now->copy()],
            'last_12' => [$now->copy()->subMonths(12)->startOfDay(), $now->copy()],
            default => [$now->copy()->startOfQuarter(), $now->copy()],
        };
    }

    public function report(string $period = 'qtd'): array
    {
        $period = array_key_exists($period, self::PERIODS) ? $period : 'qtd';

        return Cache::remember("insights:pnl:v2:{$period}:" . now()->format('YmdH') . floor(now()->minute / 10), 600, function () use ($period) {
            [$from, $to] = self::range($period);
            $days = max(1, $from->diffInDays($to) + 1);
            $income = $this->collected($from, $to);
            $fees = round((float) DB::table('loans')->whereNull('deleted_at')->whereNotIn('status', ['pending', 'rejected'])
                ->whereBetween('borrow_date', [$from->toDateString(), $to->toDateString()])->sum('total_processing_fees'), 2);
            $costOfFunds = round((float) DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('p.is_house_account', false)
                ->whereIn('t.type', ['profit_distribution', 'interest', 'penalty_share', 'broker_fee_earned', 'bonus'])
                ->whereBetween('t.transaction_date', [$from->toDateString(), $to->toDateString()])->sum('t.amount'), 2);
            $kind = fn (string $k) => round((float) DB::table('repayments')->whereNull('deleted_at')->where('kind', $k)
                ->whereBetween('repayment_date', [$from->toDateString(), $to->toDateString()])->sum('amount'), 2);
            // a write-off or waiver on a loan whose last cycle fell due before the period is a prior-period
            // adjustment (owner decisions, data clean-up), shown below the operating profit, not as this
            // period's impairment (owner, 4 Nov 2026)
            $split = fn (string $k) => DB::table('repayments as r')->join('loans as l', 'l.id', '=', 'r.loan_id')->whereNull('r.deleted_at')->where('r.kind', $k)
                ->whereBetween('r.repayment_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('r.amount, (select max(c.due_date) from loan_cycles c where c.loan_id = l.id) as last_due')->get()
                ->partition(fn ($x) => $x->last_due && $x->last_due >= $from->toDateString());
            [$wNow, $wPrior] = $split('bad_debt');
            [$dNow, $dPrior] = $split('discount');
            $writeOffs = round((float) $wNow->sum('amount'), 2);
            $discounts = round((float) $dNow->sum('amount'), 2);
            $prior = round((float) $wPrior->sum('amount') + (float) $dPrior->sum('amount'), 2);
            $opexLines = collect(config('lending.analytics.opex', []))->map(fn ($monthly) => round((float) $monthly * $days / 30.4, 2))->filter(fn ($v) => $v > 0);
            $opex = round($opexLines->sum(), 2);

            $gross = round($income['interest'] + $income['penalty'] + $fees, 2);
            $nii = round($gross - $costOfFunds, 2);
            $profit = round($nii - $writeOffs - $discounts - $opex, 2);
            $after = round($profit - $prior, 2);
            $book = $this->ledger->portfolio();
            $annual = 365 / $days;

            return [
                'period' => $period, 'label' => self::PERIODS[$period], 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'days' => $days,
                'lines' => [
                    ['Interest and margin income', $income['interest'], 'in'], ['Penalty income', $income['penalty'], 'in'], ['Fee income', $fees, 'in'],
                    ['Gross revenue', $gross, 'sub'],
                    ['Cost of funds (partner distributions)', -$costOfFunds, 'out'],
                    ['Net interest income (NII)', $nii, 'sub'],
                    ['Impairment (loans written off)', -$writeOffs, 'out'], ['Discounts and waivers', -$discounts, 'out'],
                    ...$opexLines->map(fn ($v, $k) => ['Operating expenses: ' . str_replace('_', ' ', $k), -$v, 'out'])->values()->all(),
                    ['Profit before tax', $profit, 'total'],
                    ...($prior > 0 ? [['Prior-period adjustments (older loans written off or waived)', -$prior, 'out'], ['Profit after prior-period adjustments', $after, 'total']] : []),
                ],
                'gross' => $gross, 'nii' => $nii, 'profit' => $profit, 'opex' => $opex, 'opex_set' => $opex > 0, 'cost_of_funds' => $costOfFunds, 'write_offs' => $writeOffs, 'prior' => $prior, 'after' => $after,
                'book' => $book['owed'], 'open_loans' => $book['open_loans'],
                'ratios' => [
                    'nim' => $book['owed'] > 0 ? round($nii / $book['owed'] * $annual * 100, 1) : null,
                    'cost_to_income' => $opex > 0 && $nii > 0 ? round($opex / $nii * 100, 1) : null,
                    'cost_of_risk' => $book['owed'] > 0 ? round($writeOffs / $book['owed'] * $annual * 100, 1) : null,
                    'par30' => $book['par30'], 'par90' => $book['par90'],
                    'npl' => $book['owed'] > 0 ? round($book['npl_owed'] / $book['owed'] * 100, 1) : null,
                    'return_on_book' => $book['owed'] > 0 ? round($profit / $book['owed'] * $annual * 100, 1) : null,
                    'funding_share' => $gross > 0 ? round($costOfFunds / $gross * 100, 1) : null,
                ],
                'efficiency' => $this->efficiencyByMonth(6),
                'unit' => $this->unitEconomics(),
                'products' => $this->products($from, $to, $income['by_loan']),
            ];
        });
    }

    /** Interest and penalty paid by cash repayments dated in [from, to], from each cycle's allocations. */
    public function collected(Carbon $from, Carbon $to, ?array $loanIds = null): array
    {
        $reps = CustomerWallets::whereCash(DB::table('repayments')->whereNull('deleted_at'))->whereNotNull('loan_cycle_id')
            ->whereBetween('repayment_date', [$from->toDateString(), $to->toDateString()])
            ->when($loanIds !== null, fn ($q) => $q->whereIn('loan_id', $loanIds))->get(['id', 'loan_id', 'loan_cycle_id']);
        $out = ['interest' => 0.0, 'penalty' => 0.0, 'principal' => 0.0, 'by_loan' => []];
        foreach ($reps->groupBy('loan_cycle_id') as $cycleId => $rows) {
            $cycle = LoanCycle::find($cycleId);
            $alloc = $cycle ? (CycleBalance::at($cycle)['allocations'] ?? []) : [];
            foreach ($rows as $r) {
                $a = $alloc[$r->id] ?? null;
                if (! $a) {
                    continue;
                }
                $out['interest'] += $a['interest'];
                $out['penalty'] += $a['penalty'];
                $out['principal'] += $a['principal'];
                $out['by_loan'][$r->loan_id] = ($out['by_loan'][$r->loan_id] ?? 0) + $a['interest'] + $a['penalty'];
            }
        }
        $out['interest'] = round($out['interest'], 2);
        $out['penalty'] = round($out['penalty'], 2);
        $out['principal'] = round($out['principal'], 2);

        return $out;
    }

    /**
     * Collection efficiency by the month loans fell due: of what each loan's first cycle asked for, how much cash
     * came in by its due date plus 7 days (capped at what was due). Rolled-over balances show in PAR instead; per
     * cycle they would count a rollover's carried balance as "due" again.
     */
    public function efficiencyByMonth(int $months): array
    {
        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = min($start->copy()->endOfMonth(), now());
            $row = DB::selectOne("select count(*) as n, coalesce(sum(c.new_balance), 0) as due, coalesce(sum(least(c.new_balance, (select coalesce(sum(r.amount), 0) from repayments r
                    where r.loan_id = c.loan_id and r.deleted_at is null and r.kind = 'cash' and r.repayment_date <= date_add(c.due_date, interval 7 day)))), 0) as paid
                from loan_cycles c join loans l on l.id = c.loan_id
                where c.cycle_number = 1 and l.deleted_at is null and l.merged_into_loan_id is null and l.status not in ('pending', 'rejected')
                  and c.due_date between ? and ?", [$start->toDateString(), $end->toDateString()]);
            $out[] = ['month' => $start->format('M'), 'loans' => (int) $row->n, 'due' => round((float) $row->due, 2), 'paid' => round((float) $row->paid, 2),
                'rate' => (float) $row->due > 0 ? round($row->paid / $row->due * 100, 1) : null];
        }

        return $out;
    }

    /** Per borrower: loans each, how many stop after one, what a loan contributes and what a borrower is worth. */
    public function unitEconomics(): array
    {
        $perBorrower = DB::table('loans')->whereNull('deleted_at')->whereNotIn('status', ['pending', 'rejected'])->whereNull('merged_into_loan_id')
            ->groupBy('user_id')->selectRaw('user_id, count(*) as n, max(borrow_date) as last')->get();
        $borrowers = $perBorrower->count();
        $loans = (int) $perBorrower->sum('n');
        $oneAndGone = $perBorrower->filter(fn ($b) => (int) $b->n === 1 && $b->last && $b->last < now()->subDays(90)->toDateString())->count();
        // interest and penalty ever paid, from each cycle's cached totals (all time; dates don't matter here)
        $all = (array) DB::table('loan_cycles as c')->join('loans as l', 'l.id', '=', 'c.loan_id')->whereNull('l.deleted_at')
            ->selectRaw('coalesce(sum(c.interest_paid), 0) as interest, coalesce(sum(c.penalty_paid), 0) as penalty')->first();
        $cost = (float) DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('p.is_house_account', false)
            ->whereIn('t.type', ['profit_distribution', 'interest', 'penalty_share', 'broker_fee_earned', 'bonus'])->sum('t.amount');
        $losses = (float) DB::table('repayments')->whereNull('deleted_at')->whereIn('kind', ['bad_debt', 'discount'])->sum('amount');
        $contribution = $loans > 0 ? round(((float) $all['interest'] + (float) $all['penalty'] - $cost - $losses) / $loans, 2) : null;
        $perB = $borrowers > 0 ? round($loans / $borrowers, 1) : null;
        $cac = config('lending.analytics.cac');

        return ['borrowers' => $borrowers, 'loans' => $loans, 'loans_per_borrower' => $perB, 'contribution_per_loan' => $contribution,
            'ltv' => $contribution !== null && $perB ? round($contribution * $perB, 2) : null, 'cac' => $cac !== null ? (float) $cac : null,
            'ltv_cac' => $cac && $contribution !== null && $perB ? round($contribution * $perB / (float) $cac, 1) : null,
            'churn_after_first' => $borrowers > 0 ? round($oneAndGone / $borrowers * 100, 1) : null];
    }

    /** Contribution margin by product for the period: collected, less what partners earned on it and what was lost. */
    public function products(Carbon $from, Carbon $to, ?array $collected = null): array
    {
        $types = DB::table('loan_types')->pluck('name', 'id');
        $loansByType = DB::table('loans')->whereNull('deleted_at')->whereNotIn('status', ['pending', 'rejected'])->get(['id', 'loan_type_id', 'status', 'days_overdue'])->groupBy('loan_type_id');
        $collected ??= $this->collected($from, $to)['by_loan'];
        $rows = [];
        foreach ($loansByType as $typeId => $loans) {
            $ids = $loans->pluck('id');
            $lent = (float) CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at'))->whereIn('loan_id', $ids)
                ->whereBetween('disburse_date', [$from->toDateString(), $to->toDateString()])->sum(DB::raw(CustomerWallets::DISBURSED_CASH_SQL));
            $income = round(collect($ids)->sum(fn ($id) => $collected[$id] ?? 0), 2);
            $cost = (float) DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('p.is_house_account', false)->whereIn('t.loan_id', $ids)
                ->whereIn('t.type', ['profit_distribution', 'interest', 'penalty_share', 'broker_fee_earned'])->whereBetween('t.transaction_date', [$from->toDateString(), $to->toDateString()])->sum('t.amount');
            $lost = (float) DB::table('repayments')->whereNull('deleted_at')->whereIn('loan_id', $ids)->whereIn('kind', ['bad_debt', 'discount'])
                ->whereBetween('repayment_date', [$from->toDateString(), $to->toDateString()])->sum('amount');
            $open = $loans->whereIn('status', ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery']);
            if ($lent <= 0 && $income <= 0 && $open->isEmpty()) {
                continue;
            }
            $margin = $income > 0 ? round(($income - $cost - $lost) / $income * 100, 1) : null;
            $rows[] = ['product' => $types[$typeId] ?? 'Unknown', 'lent' => round($lent, 2), 'income' => $income, 'yield' => $lent > 0 ? round($income / $lent * 100, 1) : null,
                'cost' => round($cost, 2), 'lost' => round($lost, 2), 'margin' => $margin, 'open' => $open->count(),
                'late' => $open->count() ? round($open->filter(fn ($l) => (int) $l->days_overdue > 30)->count() / $open->count() * 100, 1) : 0.0,
                'verdict' => $margin === null ? ['No income yet', 'gray'] : ($margin >= 25 ? ['Profitable', 'green'] : ($margin >= 0 ? ['Thin margin', 'amber'] : ['Losing money', 'red']))];
        }
        usort($rows, fn ($a, $b) => $b['income'] <=> $a['income']);

        return $rows;
    }
}
