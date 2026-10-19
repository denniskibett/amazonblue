<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * audit_logs retention (decision 23; limits in config/lending.php `retention`). Deletes only rows past their limit:
 *   money   older than 7 years; rows about a loan (or its repayments/disbursements) only once the loan closed 7+ years ago
 *   kyc     users / businesses rows, only when the record was deleted 7+ years ago (kept while the relationship lasts)
 *   access  role and permission changes older than 2 years
 * Any other table_name is never pruned. Always run with --dry-run first.
 */
class AuditPrune extends Command
{
    protected $signature = 'audit:prune {--dry-run : Count what would be deleted, delete nothing}';

    protected $description = 'Delete audit_logs rows past the retention policy (money 7y after closure, KYC 7y after the relationship, access 2y)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $rows = [];
        $total = 0;

        foreach ($this->groups() as $group => $query) {
            $count = (clone $query)->count();
            $total += $count;
            $rows[] = [$group, $count];
            if (! $dry && $count > 0) {
                // delete by id in chunks, so the join conditions above are what decides
                (clone $query)->select('a.id')->orderBy('a.id')->chunkById(1000, function ($chunk) {
                    DB::table('audit_logs')->whereIn('id', $chunk->pluck('id'))->delete();
                }, 'a.id', 'id');
            }
        }

        $this->table(['group', $dry ? 'would delete' : 'deleted'], $rows);
        $this->info(($dry ? '[dry run] ' : '') . $total . ' audit row(s) past retention. Kept: everything else, and any table not in the policy.');

        return self::SUCCESS;
    }

    /** @return array<string, Builder> */
    public function groups(): array
    {
        $policy = config('lending.retention');
        $moneyCutoff = now()->subYears($policy['money_years']);
        $kycCutoff = now()->subYears($policy['kyc_years']);
        $accessCutoff = now()->subYears($policy['access_years']);
        $final = Loan::STATUS_FINAL_LIST;

        // loan each money row belongs to (loans directly, repayments/disbursements through their loan_id)
        $loanOf = "CASE a.table_name WHEN 'loans' THEN a.record_id
            WHEN 'repayments' THEN (SELECT r.loan_id FROM repayments r WHERE r.id = a.record_id)
            WHEN 'disbursements' THEN (SELECT d.loan_id FROM disbursements d WHERE d.id = a.record_id) END";

        $money = DB::table('audit_logs as a')
            ->whereIn('a.table_name', $policy['money_tables'])
            ->where('a.created_at', '<', $moneyCutoff)
            ->whereNotExists(fn ($q) => $q->from('loans as l')
                ->whereRaw("l.id = ({$loanOf})")
                ->where(fn ($open) => $open->whereNotIn('l.status', $final)->orWhere('l.updated_at', '>=', $moneyCutoff)));

        $kyc = DB::table('audit_logs as a')
            ->where('a.created_at', '<', $kycCutoff)
            ->where(function ($q) use ($policy, $kycCutoff) {
                foreach ($policy['kyc_tables'] as $auditTable => $table) {
                    $q->orWhere(fn ($t) => $t->where('a.table_name', $auditTable)
                        ->whereExists(fn ($e) => $e->from($table . ' as k')->whereColumn('k.id', 'a.record_id')
                            ->whereNotNull('k.deleted_at')->where('k.deleted_at', '<', $kycCutoff)));
                }
            });

        $access = DB::table('audit_logs as a')
            ->whereIn('a.table_name', $policy['access_tables'])
            ->where('a.created_at', '<', $accessCutoff);

        return ['money' => $money, 'kyc' => $kyc, 'access' => $access];
    }
}
