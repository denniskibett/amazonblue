<?php

namespace App\Support;

use App\Models\Loan;
use App\Services\CustomerWallets;
use App\Services\LedgerReports;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Chart data for Reports & Analytics (owner, 22 Oct 2026). Cash figures count real cash only (rollover,
 * discount and write-off rows are not money in or out); balances come from the ledger, like the Portfolio page.
 * Cached for ten minutes: the page is a management view, not a till.
 */
class ReportAnalytics
{
    public const AGING = ['Current', '1-30 days', '31-60 days', '61-90 days', 'Over 90 days'];

    public static function get(): array
    {
        return Cache::remember('report-analytics', 600, fn () => (new self)->build());
    }

    private function build(): array
    {
        return [
            'monthly' => $this->monthly(),
            'aging' => $this->aging(),
            'products' => $this->products(),
            'status' => $this->status(),
        ];
    }

    /** Cash sent and received in each of the last twelve months. */
    private function monthly(): array
    {
        $from = now()->startOfMonth()->subMonths(11);
        $months = collect(range(0, 11))->map(fn ($i) => $from->copy()->addMonths($i));
        $keys = $months->map->format('Y-m')->all();

        $out = CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at')->where('disburse_date', '>=', $from->toDateString()))
            ->get(['disburse_date', 'amount'])
            ->groupBy(fn ($r) => Carbon::parse($r->disburse_date)->format('Y-m'))->map->sum('amount');
        $in = CustomerWallets::whereCash(DB::table('repayments')->whereNull('deleted_at')->where('repayment_date', '>=', $from->copy()->startOfDay()))
            ->get(['repayment_date', 'amount'])
            ->groupBy(fn ($r) => Carbon::parse($r->repayment_date)->format('Y-m'))->map->sum('amount');

        return [
            'labels' => $months->map->format('M y')->all(),
            'disbursed' => array_map(fn ($k) => round((float) ($out[$k] ?? 0)), $keys),
            'collected' => array_map(fn ($k) => round((float) ($in[$k] ?? 0)), $keys),
        ];
    }

    /** Open book by days late (standard PAR aging buckets). */
    private function aging(): array
    {
        $buckets = array_fill_keys(self::AGING, ['loans' => 0, 'owed' => 0.0]);
        foreach ($this->rows() as $r) {
            $d = (int) ($r['days'] ?? 0);
            $b = $d <= 0 ? 'Current' : ($d <= 30 ? '1-30 days' : ($d <= 60 ? '31-60 days' : ($d <= 90 ? '61-90 days' : 'Over 90 days')));
            $buckets[$b]['loans']++;
            $buckets[$b]['owed'] += (float) $r['owed'];
        }
        $total = array_sum(array_column($buckets, 'owed')) ?: 1;

        return collect($buckets)->map(fn ($v, $k) => ['bucket' => $k, 'loans' => $v['loans'], 'owed' => round($v['owed']), 'share' => round($v['owed'] / $total * 100, 1)])->values()->all();
    }

    /** What the open book owes, by loan product. */
    private function products(): array
    {
        $names = DB::table('loan_types')->pluck('name', 'id');

        return collect($this->rows())
            ->groupBy(fn ($r) => $names[$r['loan']->loan_type_id ?? 0] ?? 'Other')
            ->map(fn ($g, $name) => ['product' => $name, 'loans' => $g->count(), 'owed' => round($g->sum('owed'))])
            ->sortByDesc('owed')->values()->take(8)->all();
    }

    /** How many loans sit in each status. */
    private function status(): array
    {
        return Loan::select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status')->map(fn ($n) => (int) $n)->all();
    }

    private ?array $rows = null;

    private function rows(): array
    {
        return $this->rows ??= collect(app(LedgerReports::class)->portfolio()['rows'] ?? [])
            ->map(fn ($r) => (array) $r)->filter(fn ($r) => (float) ($r['owed'] ?? 0) > 0)->values()->all();
    }
}
