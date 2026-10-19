<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DebtRecoveryCase;
use App\Models\Loan;
use App\Models\RecoveryAction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Collections worklist: overdue / defaulted / recovery loans and their open cases (read-only).
 * Work happens on the existing case pages (cases.show: add action, recover, write off).
 */
class CollectionsController extends Controller
{
    public const STATUSES = ['overdue', 'defaulted', 'recovery'];
    public const CLOSED_CASE = ['recovered', 'written_off', 'closed'];

    public function index(Request $request)
    {
        $me = $request->user()->id;
        $view = in_array($request->query('view'), ['mine', 'unassigned', 'all'], true) ? $request->query('view') : 'all';

        $cases = DebtRecoveryCase::with(['status:id,name,slug', 'assignedTo:id,name'])
            ->whereHas('status', fn ($q) => $q->whereNotIn('slug', self::CLOSED_CASE))
            ->latest('id')->get()->unique('loan_id')->keyBy('loan_id');

        $loans = Loan::with(['user:id,name,phone', 'loanType:id,name'])
            ->withSum('repayments as repaid', 'amount')
            ->where(fn ($q) => $q->whereIn('status', self::STATUSES)->orWhereIn('id', $cases->keys()))
            ->get();

        $lastActions = RecoveryAction::whereIn('case_id', $cases->pluck('id'))
            ->latest('action_date')->get(['id', 'case_id', 'action_date', 'outcome', 'promised_date', 'follow_up_date'])
            ->unique('case_id')->keyBy('case_id');

        $rows = $loans->map(function (Loan $loan) use ($cases, $lastActions) {
            $case = $cases[$loan->id] ?? null;
            $due = $loan->getDueDate();
            return (object) [
                'loan' => $loan,
                'case' => $case,
                'last' => $case ? ($lastActions[$case->id] ?? null) : null,
                'days' => $due && $due->isPast() ? (int) floor($due->diffInDays(Carbon::now())) : 0,
                'repaid' => (float) ($loan->repaid ?? 0),
            ];
        });

        $buckets = [
            '1–7 days late' => $rows->filter(fn ($r) => $r->days >= 1 && $r->days <= 7),
            '8–30 days' => $rows->filter(fn ($r) => $r->days >= 8 && $r->days <= 30),
            'Over 30 days' => $rows->filter(fn ($r) => $r->days > 30),
            'Open cases on me' => $rows->filter(fn ($r) => $r->case && $r->case->assigned_to === $me),
        ];

        $rows = match ($view) {
            'mine' => $rows->filter(fn ($r) => $r->case && $r->case->assigned_to === $me),
            'unassigned' => $rows->filter(fn ($r) => ! $r->case || ! $r->case->assigned_to),
            default => $rows,
        };

        return view('staff.desk.collections', [
            'rows' => $rows->sortByDesc('days')->values(),
            'buckets' => $buckets,
            'view' => $view,
        ]);
    }
}
