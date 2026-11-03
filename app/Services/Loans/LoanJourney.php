<?php

namespace App\Services\Loans;

use App\Models\Loan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A loan's journey from the audit trail: the loan itself and everything hanging off it (disbursements,
 * repayments, recovery cases and their calls/notes, M-Pesa rows, borrower requests, second sign-offs),
 * oldest first, each as who / when / what. Shown on the loan page (owner, 3 Nov 2026).
 */
class LoanJourney
{
    /** Loan fields worth a line of their own when they change; the rest are summarised. */
    private const LOAN_FIELDS = ['status', 'amount', 'due_date', 'cycle', 'loan_type_id', 'forbearance_until', 'broker_status'];

    /** Bookkeeping columns that are never a step on their own. */
    private const QUIET = ['last_overdue_check', 'calculated_due_date', 'updated_by'];

    public function for(Loan $loan, int $limit = 300): Collection
    {
        $ids = fn (string $table, string $column, $value) => DB::table($table)->where($column, $value)->pluck('id')->all();
        $cases = $ids('debt_recovery_cases', 'loan_id', $loan->id);
        $disbursements = $ids('disbursements', 'loan_id', $loan->id);
        $requests = $ids('borrower_requests', 'loan_id', $loan->id);
        $subjects = DB::table('approvals')->where(fn ($q) => $q->where('subject_type', 'loans')->where('subject_id', $loan->id))
            ->orWhere(fn ($q) => $q->where('subject_type', 'disbursements')->whereIn('subject_id', $disbursements ?: [0]))
            ->orWhere(fn ($q) => $q->where('subject_type', 'debt_recovery_cases')->whereIn('subject_id', $cases ?: [0]))
            ->pluck('id')->all();

        $sources = [
            'loans' => [$loan->id],
            'disbursements' => $disbursements,
            'repayments' => $ids('repayments', 'loan_id', $loan->id),
            'debt_recovery_cases' => $cases,
            'recovery_actions' => $cases ? DB::table('recovery_actions')->whereIn('case_id', $cases)->pluck('id')->all() : [],
            'recovery_case_notes' => $cases ? DB::table('recovery_case_notes')->whereIn('case_id', $cases)->pluck('id')->all() : [],
            'mpesa_transactions' => $ids('mpesa_transactions', 'loan_id', $loan->id),
            'approvals' => $subjects,
        ];

        $rows = DB::table('audit_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->where(function ($q) use ($sources, $requests, $loan) {
                foreach ($sources as $table => $records) {
                    if ($records) {
                        $q->orWhere(fn ($w) => $w->where('a.table_name', $table)->whereIn('a.record_id', $records));
                    }
                }
                // borrower requests are logged against the loan when made, against the request when decided
                $q->orWhere(fn ($w) => $w->where('a.table_name', 'borrower_requests')->where('a.action', 'create')->where('a.record_id', $loan->id));
                if ($requests) {
                    $q->orWhere(fn ($w) => $w->where('a.table_name', 'borrower_requests')->where('a.action', 'update')->whereIn('a.record_id', $requests));
                }
            })
            ->orderBy('a.created_at')->orderBy('a.id')->limit($limit)
            ->get(['a.id', 'a.table_name', 'a.record_id', 'a.action', 'a.old_values', 'a.new_values', 'a.created_at', 'a.user_agent', 'u.name as user_name']);

        return $rows->map(fn ($r) => $this->describe($r))->filter()->values();
    }

    private function describe(object $r): ?array
    {
        $old = json_decode((string) $r->old_values, true) ?: [];
        $new = json_decode((string) $r->new_values, true) ?: [];
        $event = $new['event'] ?? null;
        if ($event === null && $r->action === 'update') {
            // keep only real changes: not the same date or number written differently, not bookkeeping
            $new = array_filter($new, fn ($v, $k) => ! in_array($k, self::QUIET, true) && ! $this->same($old[$k] ?? null, $v), ARRAY_FILTER_USE_BOTH);
            if (! $new) {
                return null;
            }
        }
        $money = fn ($v) => 'KES ' . number_format((float) $v, 2);

        [$kind, $text] = match (true) {
            $event !== null => ['event', ucfirst(str_replace('_', ' ', $event)) . $this->details($new, ['event'])],
            $r->action === 'update' && ! $old && ! isset($new['status']) => ['event', ucfirst(str_replace('_', ' ', (string) array_key_first($new))) . $this->details($new, [])],
            $r->table_name === 'loans' && $r->action === 'create' => ['loan', 'Loan created for ' . $money($new['amount'] ?? 0) . ' (' . ($new['status'] ?? 'pending') . ')'],
            $r->table_name === 'loans' && isset($new['status']) => ['status', 'Status ' . ($old['status'] ?? '?') . ' → ' . $new['status']],
            $r->table_name === 'loans' => ['loan', 'Loan changed: ' . $this->changes($old, $new, self::LOAN_FIELDS)],
            $r->table_name === 'disbursements' && $r->action === 'create' => ['money_out', 'Disbursed ' . $money($new['amount'] ?? 0) . (isset($new['mode']) ? ' by ' . $new['mode'] : '')],
            $r->table_name === 'repayments' && $r->action === 'create' => ['money_in', 'Repayment ' . $money($new['amount'] ?? 0) . (isset($new['mode']) ? ' by ' . $new['mode'] : '')],
            $r->table_name === 'approvals' && $r->action === 'create' => ['approval', 'Second sign-off asked: ' . str_replace('_', ' ', $new['action'] ?? '')],
            $r->table_name === 'approvals' => ['approval', 'Second sign-off ' . ($new['status'] ?? 'decided') . (! empty($new['reason']) ? ': ' . $new['reason'] : '')],
            $r->table_name === 'recovery_actions' && $r->action === 'create' => ['recovery', 'Recovery action: ' . ($new['outcome'] ?? '') . (! empty($new['notes']) ? ' — ' . $new['notes'] : '')],
            $r->table_name === 'recovery_case_notes' && $r->action === 'create' => ['recovery', 'Case note: ' . ($new['note'] ?? '')],
            $r->table_name === 'mpesa_transactions' && $r->action === 'create' => ['money_in', 'M-Pesa ' . ($new['type'] ?? 'transaction') . ' ' . $money($new['amount'] ?? 0) . (isset($new['trans_id']) ? ' (' . $new['trans_id'] . ')' : '')],
            in_array($r->action, ['delete', 'force_delete'], true) => ['removed', $this->noun($r->table_name) . ' #' . $r->record_id . ' deleted'],
            default => ['change', $this->noun($r->table_name) . ' #' . $r->record_id . ' ' . $r->action . ': ' . $this->changes($old, $new)],
        };

        return [
            'at' => \Illuminate\Support\Carbon::parse($r->created_at),
            'who' => $r->user_name ?? ($r->user_agent === 'console' ? 'System (scheduled)' : 'System'),
            'kind' => $kind,
            'text' => mb_strimwidth($text, 0, 300, '…'),
        ];
    }

    private function same($a, $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }
        $day = '/^\d{4}-\d{2}-\d{2}( 00:00:00)?$/';
        if (is_string($a) && is_string($b) && preg_match($day, $a) && preg_match($day, $b)) {
            return substr($a, 0, 10) === substr($b, 0, 10);
        }

        return $a === $b;
    }

    private function changes(array $old, array $new, ?array $only = null): string
    {
        $keys = $only ? array_values(array_intersect(array_keys($new), $only)) : array_keys($new);
        $keys = $keys ?: array_keys($new);
        $parts = array_map(fn ($k) => str_replace('_', ' ', $k) . ' ' . $this->scalar($old[$k] ?? null) . ' → ' . $this->scalar($new[$k] ?? null), array_slice($keys, 0, 4));

        return implode('; ', $parts) . (count($keys) > 4 ? ' (+' . (count($keys) - 4) . ' more)' : '');
    }

    private function details(array $values, array $skip): string
    {
        $values = array_diff_key($values, array_flip($skip));
        $parts = [];
        foreach (array_slice($values, 0, 5, true) as $k => $v) {
            if ($v !== null && $v !== '' && ! is_array($v)) {
                $parts[] = str_replace('_', ' ', $k) . ': ' . $this->scalar($v);
            }
        }

        return $parts ? ' — ' . implode(', ', $parts) : '';
    }

    private function scalar($v): string
    {
        return match (true) {
            $v === null => '—',
            is_bool($v) => $v ? 'yes' : 'no',
            is_array($v) => json_encode($v),
            default => mb_strimwidth((string) $v, 0, 60, '…'),
        };
    }

    private function noun(string $table): string
    {
        return ucfirst(str_replace('_', ' ', \Illuminate\Support\Str::singular($table)));
    }
}
