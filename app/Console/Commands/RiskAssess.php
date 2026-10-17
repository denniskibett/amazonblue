<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanRiskAssessment;
use App\Models\User;
use App\Services\CreditAssessment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Score every borrower's loans on the 4Cs (one system assessment per loan) and report where each
 * borrower stands today. Safe to run any time: rows are matched by loan, so nothing is duplicated.
 * The app also re-scores a borrower automatically whenever their loans or payments change.
 */
class RiskAssess extends Command
{
    protected $signature = 'risk:assess
        {--user= : Only this borrower (user id)}
        {--dry-run : Show the results without writing anything}';

    protected $description = 'Populate loan_risk_assessments (4Cs) from repayment history and show each borrower\'s score and limit.';

    public function handle(CreditAssessment $credit): int
    {
        $dry = (bool) $this->option('dry-run');
        $users = User::withTrashed()->whereIn('id', Loan::select('user_id'))
            ->when($this->option('user'), fn ($q, $id) => $q->whereKey($id))
            ->with('borrower')->orderBy('id')->get();

        $changed = 0;
        $rows = [];
        $bar = $this->output->createProgressBar($users->count());
        foreach ($users as $user) {
            if ($dry) {
                DB::beginTransaction();
            }
            $changed += $credit->syncUser($user);
            $now = $credit->current($user);
            if ($dry) {
                DB::rollBack();
            }
            $rows[] = [
                $user->id,
                mb_strimwidth($user->name, 0, 26, '…'),
                $now['factors']['closed'],
                implode('/', [$now['factors']['on_time'], $now['factors']['late'], $now['factors']['rolled'], $now['factors']['discounted'], $now['factors']['written_off']]),
                implode(' ', array_map(fn ($s) => str_pad((string) $s, 3, ' ', STR_PAD_LEFT), $now['scores'])),
                $now['overall'],
                $now['category']?->name ?? '-',
                number_format($now['limit']['limit'], 2) . ($now['limit']['capped_by'] ? ' (' . $now['limit']['capped_by'] . ')' : ''),
            ];
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->table(['user', 'name', 'closed', 'on time/late/rolled/disc/w-off', 'chr cap cpt cnd', 'score', 'category', 'limit today (KES)'], $rows);

        $byCategory = collect($rows)->countBy(6)->sortKeys();
        $this->line('Today: ' . $byCategory->map(fn ($n, $c) => "{$c} {$n}")->implode(' · '));
        $this->info(($dry ? 'Would change ' : 'Changed ') . $changed . ' assessment rows. Auto assessments on file: ' . LoanRiskAssessment::where('source', 'auto')->count() . '.');

        return self::SUCCESS;
    }
}
