<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\AuditTrail;
use App\Services\Ledger;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\Period;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OperationsReviewController extends Controller
{
    public function index(Request $request, Ledger $ledger)
    {
        $canViewLogs = $request->user()->can('view logs');
        $canViewLedger = $request->user()->can('view ledger');
        $logs = $canViewLogs ? $this->errorEntries() : collect();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:40'],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date', 'after_or_equal:due_from'],
            'rollover' => ['nullable', 'in:yes,no'],
        ]);
        $issues = $canViewLedger ? $this->receivableIssues($ledger, $filters) : collect();
        $section = $request->query('section', $canViewLedger ? 'receivables' : 'errors');
        $grace = $canViewLedger && $section === 'grace' ? app(\App\Services\Loans\GraceFix::class)->proposals() : collect();
        $graceAction = $request->query('action');
        if ($graceAction) {
            $grace = $grace->where('action', $graceAction)->values();
        }

        $rollover = $canViewLedger && $section === 'rollovers' ? (function () {
            $svc = app(\App\Services\Loans\RolloverCleanup::class);

            return $svc->chains() + ['stray' => $svc->strayRows(), 'never' => $svc->neverSent(), 'transfers' => $svc->transfers()];
        })() : null;

        return view('staff.operations-review.index', compact('canViewLogs', 'canViewLedger', 'logs', 'issues', 'section', 'filters', 'grace', 'graceAction', 'rollover'));
    }

    public function grantForbearance(Request $request, Loan $loan)
    {
        abort_unless($request->user()->can('grant forbearance'), 403);

        $validated = $request->validate([
            'until' => ['required', 'date', 'after:today'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        DB::transaction(function () use ($loan, $validated) {
            $locked = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->isFinal(), 422, 'A closed loan cannot be moved to forbearance.');
            $cycle = $locked->cycles()->orderByDesc('cycle_number')->lockForUpdate()->first();
            abort_unless($cycle && $cycle->status === 'active', 422, 'The loan needs an active cycle before forbearance can be granted.');

            $until = Carbon::parse($validated['until'])->startOfDay();
            $old = ['status' => $locked->status, 'forbearance_until' => $locked->forbearance_until?->toDateString(),
                'penalty_waived_until' => $cycle->penalty_waived_until?->toDateString()];
            if (\App\Services\LoanCalculator::v2()) {
                $cycle->update(['penalty_waived_until' => $until->toDateString()]);
                $locked->forceFill([
                    'status' => Loan::STATUS_FORBEARANCE,
                    'forbearance_until' => $until,
                    'recovery_notes' => trim(($locked->recovery_notes ? $locked->recovery_notes . "\n" : '')
                        . 'Forbearance granted by ' . auth()->user()->name . ' until ' . $until->toDateString() . '. Reason: ' . $validated['reason']),
                ])->save();
            } else {
                $days = (int) now()->startOfDay()->diffInDays($until);
                $locked->grantForbearance($days, $validated['reason']);
            }

            AuditTrail::record('loans', $locked->id, 'update', $old, [
                'event' => 'forbearance_granted',
                'status' => Loan::STATUS_FORBEARANCE,
                'forbearance_until' => $until->toDateString(),
                'penalty_waived_until' => $until->toDateString(),
                'reason' => $validated['reason'],
            ]);
        });

        return redirect()->route('staff.operations-review', ['section' => 'receivables'])
            ->with('success', 'Forbearance recorded for loan #' . $loan->id . '.');
    }

    public function correctDueDate(Request $request, Loan $loan)
    {
        abort_unless($request->user()->canAny(['edit loans', 'manage loan cycles']), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        DB::transaction(function () use ($loan, $validated) {
            $locked = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->isFinal(), 422, 'A closed loan due date cannot be changed here.');
            $locked->loadMissing('loanType');
            $cycle = $locked->cycles()->orderByDesc('cycle_number')->lockForUpdate()->first();
            abort_unless($cycle && $cycle->status === 'active', 422, 'The loan needs an active cycle before its due date can be corrected.');
            abort_unless($locked->loanType && $cycle->start_date, 422, 'The product period and cycle start date are needed to correct the due date.');
            $expectedDueDate = Period::add($cycle->start_date, (int) $locked->loanType->period, (string) $locked->loanType->unit)->toDateString();

            $old = ['loan_due_date' => $locked->due_date ? Carbon::parse($locked->due_date)->toDateString() : null,
                'cycle_due_date' => $cycle->due_date?->toDateString()];
            $cycle->update(['due_date' => $expectedDueDate]);
            $locked->forceFill(['due_date' => $expectedDueDate, 'calculated_due_date' => $expectedDueDate])->save();
            if (\App\Services\LoanCalculator::v2()) {
                \App\Services\Loans\LoanStatus::apply($locked->fresh());
            }

            AuditTrail::record('loans', $locked->id, 'update', $old, [
                'event' => 'cycle_due_date_corrected',
                'due_date' => $expectedDueDate,
                'cycle_id' => $cycle->id,
                'reason' => $validated['reason'],
                'repayments_changed' => false,
            ]);
        });

        return redirect()->route('staff.operations-review', ['section' => 'receivables'])
            ->with('success', 'Due date aligned to the loan product period for loan #' . $loan->id . '. Repayments were not changed.');
    }

    private function receivableIssues(Ledger $ledger, array $filters)
    {
        $rows = collect($ledger->checkReceivables()['issues']);
        $ids = $rows->pluck('loan')->all();
        $loans = Loan::withTrashed()->with(['user', 'loanType', 'cycles'])->whereIn('id', $ids)->get()->keyBy('id');
        $rolloverRepayments = DB::table('repayments')->whereIn('loan_id', $ids)->where('kind', 'rollover')->whereNull('deleted_at')
            ->selectRaw('loan_id, count(*) as total')->groupBy('loan_id')->pluck('total', 'loan_id');
        $rolloverDisbursements = DB::table('disbursements')->whereIn('loan_id', $ids)->where('kind', 'rollover')->whereNull('deleted_at')
            ->selectRaw('loan_id, count(*) as total')->groupBy('loan_id')->pluck('total', 'loan_id');
        $rows = $rows->map(function (array $issue) use ($loans, $rolloverRepayments, $rolloverDisbursements) {
            $loan = $loans->get($issue['loan']);
            $allCycles = $loan?->cycles ?? collect();
            $cycles = $allCycles->sortByDesc('cycle_number')->take(2)->values();
            $cycle = $cycles->first();
            $issue['rollover_repayments'] = (int) ($rolloverRepayments[$issue['loan']] ?? 0);
            $issue['rollover_disbursements'] = (int) ($rolloverDisbursements[$issue['loan']] ?? 0);
            $issue['borrower_name'] = $loan?->user?->name ?? 'Unknown borrower';
            $issue['cycles'] = $cycles->map(function ($item) use ($loan, $allCycles) {
                $dueDate = $item->due_date?->toDateString();
                $expectedDueDate = $loan?->loanType && $item->start_date
                    ? Period::add($item->start_date, (int) $loan->loanType->period, (string) $loan->loanType->unit)->toDateString()
                    : null;
                $payments = CycleBalance::paymentsFor($item);
                $hasNextCycle = $allCycles->contains(fn ($next) => $next->cycle_number > $item->cycle_number);
                if ($hasNextCycle) {
                    $payments = array_values(array_filter($payments, fn ($payment) => ($payment['kind'] ?? 'cash') !== 'rollover'));
                }
                $duePayments = array_filter($payments, fn ($payment) => $dueDate && $payment['date'] <= $dueDate);
                $storedDue = max(0, round((float) $item->new_balance - array_sum(array_column($duePayments, 'amount')), 2));
                $calculatedDue = $dueDate
                    ? CycleBalance::compute(CycleBalance::termsFor($item), $payments, $dueDate)['final_outstanding']
                    : null;

                return [
                    'id' => $item->id,
                    'number' => $item->cycle_number,
                    'status' => $item->status,
                    'start_date' => $item->start_date?->toDateString(),
                    'due_date' => $dueDate,
                    'expected_due_date' => $expectedDueDate,
                    'date_matches_product' => $expectedDueDate !== null && $dueDate === $expectedDueDate,
                    'stored_due_amount' => $storedDue,
                    'calculated_due_amount' => $calculatedDue,
                    'due_amount_difference' => $calculatedDue === null ? null : round($calculatedDue - $storedDue, 2),
                ];
            })->all();
            $issue['status'] = $loan?->status ?? 'missing loan';
            $issue['cycle'] = $cycle?->cycle_number;
            $issue['cycle_status'] = $cycle?->status;
            $issue['due_date'] = $cycle?->due_date?->toDateString();
            $issue['forbearance_until'] = $loan?->forbearance_until?->toDateString();
            $issue['latest_cycle'] = $issue['cycles'][0] ?? null;
            $issue['cycle_date_mismatch'] = $issue['latest_cycle'] && ! $issue['latest_cycle']['date_matches_product'];
            $issue['can_grant'] = $loan && ! $loan->isFinal() && $cycle?->status === 'active' && $issue['cycle_date_mismatch'];
            $issue['review_hint'] = $issue['rollover_repayments'] || $issue['rollover_disbursements']
                ? 'Legacy rollover row found; inspect the continuation chain for a duplicated receivable.'
                : ($loan?->status === Loan::STATUS_FORBEARANCE
                    ? 'Forbearance status is set; verify the active cycle waiver date and due date.'
                    : 'No rollover row found on this loan; inspect cycle dates, terms, and repayments.');

            return $issue;
        });

        $query = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $rows = $rows->filter(function (array $issue) use ($filters, $query) {
            if ($query !== '' && ! str_contains(mb_strtolower($issue['borrower_name']), $query)
                && ! str_contains((string) $issue['loan'], $query)) {
                return false;
            }
            if (! empty($filters['status']) && $issue['status'] !== $filters['status']) {
                return false;
            }
            if (! empty($filters['due_from']) && (! $issue['due_date'] || $issue['due_date'] < $filters['due_from'])) {
                return false;
            }
            if (! empty($filters['due_to']) && (! $issue['due_date'] || $issue['due_date'] > $filters['due_to'])) {
                return false;
            }
            $hasRollover = $issue['rollover_repayments'] > 0 || $issue['rollover_disbursements'] > 0;
            if (($filters['rollover'] ?? null) === 'yes' && ! $hasRollover) {
                return false;
            }
            if (($filters['rollover'] ?? null) === 'no' && $hasRollover) {
                return false;
            }

            return true;
        })->values();

        return $this->paginate($rows, 25, 'receivables_page');
    }

    private function errorEntries()
    {
        $directory = storage_path('logs');
        if (! File::isDirectory($directory)) {
            return $this->paginate(collect(), 25, 'errors_page');
        }

        $files = collect(File::files($directory))
            ->filter(fn ($file) => preg_match('/^laravel-\d{4}-\d{2}-\d{2}\.log$/', $file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getFilename())
            ->take(7);
        $entries = [];
        foreach ($files as $file) {
            $current = null;
            foreach (preg_split('/\r?\n/', File::get($file->getPathname())) as $line) {
                if (preg_match('/^\[([^]]+)\]\s+[^.]+\.(ERROR|CRITICAL|ALERT|EMERGENCY):\s*(.*)$/', $line, $match)) {
                    if ($current) {
                        $entries[] = $current;
                    }
                    $current = ['time' => $match[1], 'level' => $match[2], 'message' => $this->redact($match[3])];
                }
            }
            if ($current) {
                $entries[] = $current;
            }
        }

        return $this->paginate(collect(array_reverse($entries)), 25, 'errors_page');
    }

    private function redact(string $message): string
    {
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $message);
        $message = preg_replace('/(?<!\d)(?:\+?\d[\d ()-]{7,}\d)(?!\d)/', '[phone/account]', $message);
        $message = preg_replace('/\b(password|passkey|consumer_secret|api_key|token|secret)\s*[=:]\s*[^\s,;]+/i', '$1=[redacted]', $message);

        return mb_substr($message, 0, 500);
    }

    private function paginate($items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => $pageName, 'query' => request()->query()]
        );
    }

    /** Grace days or restored forbearance for one loan (GraceFix). Re-checked against today's data before saving. */
    public function applyGrace(Request $request, Loan $loan)
    {
        $data = $request->validate(['until' => ['nullable', 'date', 'after:today'], 'note' => ['nullable', 'string', 'max:500']]);
        try {
            $p = app(\App\Services\Loans\GraceFix::class)->apply($loan, $data['until'] ?? null, $data['note'] ?? 'operations review, ' . $request->user()->name);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['grace' => $e->getMessage()]);
        }

        return back()->with('success', "Loan #{$loan->id}: " . ($p['action'] === 'grace' ? "{$p['grace_after']} grace day(s), now " . $loan->fresh()->status : 'forbearance restored') . '.');
    }

    /** The ticked rows, one by one; a row that no longer qualifies is skipped and reported. */
    public function applyGraceBatch(Request $request)
    {
        $ids = $request->validate(['loan_ids' => ['required', 'array', 'max:100'], 'loan_ids.*' => ['integer']])['loan_ids'];
        $fix = app(\App\Services\Loans\GraceFix::class);
        $done = [];
        $skipped = [];
        foreach ($ids as $id) {
            try {
                $fix->apply(Loan::findOrFail($id), null, 'operations review batch, ' . $request->user()->name);
                $done[] = $id;
            } catch (\Throwable $e) {
                $skipped[] = "#{$id}";
            }
        }

        return back()->with('success', count($done) . ' loan(s) fixed.' . ($skipped ? ' Skipped: ' . implode(', ', $skipped) . '.' : ''));
    }

    /** Merge one ROLL OVER chain (re-detected now) and remove the ROLL OVER rows its cycles hold. */
    public function applyChain(Request $request, Loan $loan)
    {
        try {
            $done = app(\App\Services\Loans\RolloverCleanup::class)->applyChain($loan->id, 'operations review, ' . $request->user()->name);
        } catch (\Throwable $e) {
            return back()->withErrors(['chain' => "Loan #{$loan->id}: " . strtok($e->getMessage(), "\n")]);
        }

        return back()->with('success', "Loan #{$loan->id}: merged #" . implode(', #', array_slice($done['loans'], 1)) . "; {$done['rows_removed']} ROLL OVER row(s) removed.");
    }

    /** Stray ROLL OVER rows (all loans) and never-sent charges. */
    public function cleanRollovers(Request $request)
    {
        $svc = app(\App\Services\Loans\RolloverCleanup::class);
        $postings = $svc->clearNeverSent();
        $rows = $svc->removeStray(null, 'operations review, ' . $request->user()->name);
        $moved = $svc->postTransfers($svc->transfers()->where('posted', false)->values());

        return back()->with('success', "{$rows} ROLL OVER row(s) removed; {$postings} ledger posting(s) reversed on never-sent loans; {$moved} consolidation transfer(s) posted.");
    }
}
