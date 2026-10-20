<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only nightly report of loans whose status and money disagree (Stage 4 Phase 0.11). Changes nothing.
 *
 *   repaid_with_balance       status repaid, but the last cycle's stored balance (new_balance − its repayments) is above 0
 *   repaid_with_active_cycle  status repaid, but a cycle is still active
 *   repayment_before_cycle    a repayment dated before the start of the cycle it is booked to
 *   repayment_other_loan      a repayment booked to a cycle of a different loan (G3, before the server check)
 *
 * Balances are the stored cycle figures (no penalty to today), so this under-reports rather than over-reports.
 * Phase 2 derives status from the cycle balance and should bring every list to zero.
 */
class LoansCheckIntegrity extends Command
{
    protected $signature = 'loans:check-integrity
        {--csv : Also write storage/app/private/reports/loan-integrity-<date>.csv}
        {--notify : Email admins and super-admins when anything is found}';

    protected $description = 'Report repaid loans that still owe, active cycles after repaid, and misdated or misplaced repayments (read-only)';

    public const CHECKS = [
        'repaid_with_balance' => 'Repaid, but the last cycle still has a balance',
        'repaid_with_active_cycle' => 'Repaid, but a cycle is still active',
        'repayment_before_cycle' => 'Repayment dated before its cycle starts',
        'repayment_other_loan' => 'Repayment booked to another loan\'s cycle',
    ];

    public function handle(): int
    {
        $found = $this->findings();
        $total = array_sum(array_map('count', $found));

        $this->info('Loan integrity check ' . now()->toDateTimeString() . ' (read-only): ' . $total . ' finding(s).');
        foreach (self::CHECKS as $key => $label) {
            $rows = $found[$key];
            $this->line(sprintf('%-26s %4d  %s', $key, count($rows), $label));
            if ($rows) {
                $this->table(array_keys($rows[0]), array_map(fn ($r) => array_values($r), array_slice($rows, 0, 50)));
                if (count($rows) > 50) {
                    $this->line('  … ' . (count($rows) - 50) . ' more (use --csv)');
                }
            }
        }

        if ($this->option('csv')) {
            $path = 'reports/loan-integrity-' . now()->format('Y-m-d') . '.csv';
            Storage::disk('local')->put($path, $this->csv($found));
            $this->info('CSV: ' . Storage::disk('local')->path($path));
        }

        if ($this->option('notify') && $total > 0) {
            $this->notifyStaff($found, $total);
        }

        return self::SUCCESS;
    }

    /** v2: repaid loans whose latest cycle still owes by the v2 rule (penalty included, payments in order). */
    private function repaidWithBalanceV2(): array
    {
        $out = [];
        foreach (DB::table('loans')->where('status', 'repaid')->whereNull('deleted_at')->orderBy('id')->pluck('id') as $id) {
            $cycle = \App\Models\LoanCycle::where('loan_id', $id)->orderByDesc('cycle_number')->first();
            if (! $cycle) {
                continue;
            }
            $b = \App\Services\Loans\CycleBalance::at($cycle);
            if ($b['final_outstanding'] > 0.01) {
                $out[] = ['loan_id' => $id, 'cycle' => $cycle->cycle_number, 'cycle_balance' => $b['full_balance'],
                    'paid_on_cycle' => $b['total_repayments'], 'still_owed' => $b['final_outstanding']];
            }
        }

        return $out;
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function findings(): array
    {
        $rows = fn ($query) => array_map(fn ($r) => (array) $r, $query->get()->all());

        $lastCycle = DB::table('loan_cycles as c')
            ->whereRaw('c.cycle_number = (SELECT MAX(c2.cycle_number) FROM loan_cycles c2 WHERE c2.loan_id = c.loan_id)');

        return [
            'repaid_with_balance' => \App\Services\LoanCalculator::v2() ? $this->repaidWithBalanceV2() : $rows(DB::table('loans as l')
                ->joinSub($lastCycle->select('c.id', 'c.loan_id', 'c.cycle_number', 'c.new_balance'), 'lc', 'lc.loan_id', '=', 'l.id')
                ->leftJoin('repayments as r', fn ($j) => $j->on('r.loan_cycle_id', '=', 'lc.id')->whereNull('r.deleted_at'))
                ->where('l.status', 'repaid')->whereNull('l.deleted_at')
                ->groupBy('l.id', 'lc.id', 'lc.cycle_number', 'lc.new_balance')
                ->havingRaw('lc.new_balance - COALESCE(SUM(r.amount), 0) > 0.01')
                ->orderBy('l.id')
                ->selectRaw('l.id AS loan_id, lc.cycle_number AS cycle, lc.new_balance AS cycle_balance, COALESCE(SUM(r.amount), 0) AS paid_on_cycle, ROUND(lc.new_balance - COALESCE(SUM(r.amount), 0), 2) AS still_owed')),

            'repaid_with_active_cycle' => $rows(DB::table('loans as l')
                ->join('loan_cycles as c', 'c.loan_id', '=', 'l.id')
                ->where('l.status', 'repaid')->whereNull('l.deleted_at')->where('c.status', 'active')
                ->orderBy('l.id')->orderBy('c.cycle_number')
                ->select('l.id as loan_id', 'c.id as cycle_id', 'c.cycle_number as cycle', 'c.start_date', 'c.due_date', 'c.new_balance as cycle_balance')),

            'repayment_before_cycle' => $rows(DB::table('repayments as r')
                ->join('loan_cycles as c', 'c.id', '=', 'r.loan_cycle_id')
                ->whereNull('r.deleted_at')->whereColumn('r.loan_id', 'c.loan_id')
                ->whereRaw('DATE(r.repayment_date) < c.start_date')
                ->orderBy('r.loan_id')->orderBy('r.id')
                ->select('r.loan_id', 'r.id as repayment_id', 'r.repayment_date', 'r.amount', 'c.cycle_number as cycle', 'c.start_date as cycle_start')),

            'repayment_other_loan' => $rows(DB::table('repayments as r')
                ->join('loan_cycles as c', 'c.id', '=', 'r.loan_cycle_id')
                ->whereNull('r.deleted_at')->whereColumn('r.loan_id', '!=', 'c.loan_id')
                ->orderBy('r.loan_id')->orderBy('r.id')
                ->select('r.loan_id', 'r.id as repayment_id', 'r.amount', 'r.loan_cycle_id', 'c.loan_id as cycle_loan_id')),
        ];
    }

    private function csv(array $found): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['check', 'loan_id', 'detail']);
        foreach ($found as $check => $rows) {
            foreach ($rows as $row) {
                $detail = collect($row)->except('loan_id')->map(fn ($v, $k) => $k . '=' . $v)->implode('; ');
                fputcsv($out, [$check, $row['loan_id'], $detail]);
            }
        }
        rewind($out);

        return stream_get_contents($out);
    }

    private function notifyStaff(array $found, int $total): void
    {
        $summary = collect(self::CHECKS)->map(fn ($label, $key) => $label . ': ' . count($found[$key]))->implode('; ');
        $alert = new StaffAlert(
            'Loan integrity check: ' . $total . ' finding(s)',
            'The nightly read-only check found loans whose status and money disagree. ' . $summary . '. Nothing was changed. Run php artisan loans:check-integrity --csv on the server for the full list.',
            'Open loans', route('loans.index')
        );

        User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'admin']))->whereNotNull('email')->get()
            ->each(function (User $user) use ($alert) {
                try {
                    $user->notify($alert);
                } catch (\Throwable $e) {
                    Log::warning('Integrity report email failed', ['user' => $user->id, 'error' => $e->getMessage()]);
                }
            });
    }
}
