<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RepaymentController;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\CustomerWallets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Teller desk: receive a repayment (posts to the existing repayments.store), reverse one (the existing
 * RepaymentController@destroy, unchanged, plus a reason in the audit trail) and the end-of-day sheet.
 */
class CashDeskController extends Controller
{
    /** Statuses a teller can take a payment against. */
    public const REPAYABLE = ['disbursed', 'active', 'overdue', 'defaulted', 'recovery', 'forbearance'];

    public function receive(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $results = collect();

        if ($q !== '') {
            $results = Loan::with(['user:id,name,email,phone', 'loanType:id,name'])
                ->whereIn('status', self::REPAYABLE)
                ->where(function ($w) use ($q) {
                    if (ctype_digit(ltrim($q, '#'))) {
                        $w->where('id', (int) ltrim($q, '#'));
                    }
                    $w->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('id_number', 'like', "%{$q}%"));
                })
                ->latest('id')->limit(15)->get();
        }

        $loan = null;
        $cycles = collect();
        $recent = collect();
        if ($request->filled('loan')) {
            $loan = Loan::with(['user:id,name,email,phone', 'loanType:id,name'])->findOrFail((int) $request->query('loan'));
            // the loan's own cycles only, with what was paid against each (read-only)
            $paid = Repayment::where('loan_id', $loan->id)->selectRaw('loan_cycle_id, sum(amount) as paid')
                ->groupBy('loan_cycle_id')->pluck('paid', 'loan_cycle_id');
            $cycles = $loan->cycles()->orderBy('cycle_number')->get()->map(function ($c) use ($paid) {
                $c->paid_so_far = (float) ($paid[$c->id] ?? 0);
                return $c;
            });
            $recent = Repayment::where('loan_id', $loan->id)->latest('repayment_date')->latest('id')->limit(10)->get();
        }

        return view('staff.desk.receive', compact('q', 'results', 'loan', 'cycles', 'recent'));
    }

    /**
     * Reverse (delete) a repayment through the existing RepaymentController@destroy, unchanged, and record
     * the reason. True reversal entries (a negative posting that keeps the original) are Stage 4.
     */
    public function reverse(Request $request, Repayment $repayment)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:500']);
        $snapshot = $repayment->only(['id', 'loan_id', 'loan_cycle_id', 'amount', 'transaction', 'mode', 'repayment_date']);
        $back = route('staff.repayments.receive', ['loan' => $repayment->loan_id]);

        try {
            $response = app()->call([app(RepaymentController::class), 'destroy'], ['repayment' => $repayment]);
        } catch (HttpExceptionInterface $e) {
            // the existing destroy also requires the admin (or broker) role on top of "delete repayments"
            $why = $e->getMessage() === 'Unauthorized action.' ? 'The existing repayment delete is limited to the admin role.' : $e->getMessage();
            return redirect($back)->with('error', 'Not reversed. ' . ($why ?: 'You are not allowed to reverse repayments.'));
        }

        if ($response->getStatusCode() >= 300) {
            $why = json_decode($response->getContent(), true)['message'] ?? null;

            return redirect($back)->with('error', 'The repayment could not be reversed.' . ($why ? ' ' . $why : ''));
        }

        $reversalId = json_decode($response->getContent(), true)['reversal_id'] ?? null;
        AuditTrail::record('repayments', $repayment->id, $reversalId ? 'update' : 'delete', $snapshot, [
            'reversal_reason' => $data['reason'],
            'reversal_id' => $reversalId,
            'via' => $reversalId ? 'staff repayment desk (reversal entry)' : 'staff repayment desk (same-day delete)',
        ]);

        return redirect($back)->with('success', 'Repayment #' . $repayment->id . ' reversed'
            . ($reversalId ? ' by reversal #' . $reversalId . '; the original is kept' : '') . '. The reason is in the audit log.');
    }

    /** Today's disbursements and repayments entered by a teller (from the audit trail), cash vs book. */
    public function endOfDay(Request $request)
    {
        $user = $request->user();
        $date = rescue(fn () => Carbon::parse($request->query('date', today()->toDateString()))->startOfDay(), today(), false);
        $tellerId = $user->can('view reports') && $request->filled('teller') ? (int) $request->query('teller') : $user->id;
        $teller = User::find($tellerId, ['id', 'name']);

        // "entered by" = created_by (Stage 4 Phase 1); rows from before the column was filled fall back to the
        // audit trail's create row
        $created = fn (string $table) => DB::table($table)->where('created_by', $tellerId)->whereDate('created_at', $date)->pluck('id')
            ->merge(AuditLog::where('table_name', $table)->where('action', 'create')->where('user_id', $tellerId)
                ->whereDate('created_at', $date)->pluck('record_id')
                ->filter(fn ($id) => DB::table($table)->where('id', $id)->whereNull('created_by')->exists()))
            ->unique()->values();

        $disbIds = $created('disbursements');
        $repIds = $created('repayments');

        $disbursements = Disbursement::with('loan.user:id,name')->whereIn('id', $disbIds)->orderBy('id')->get();
        $repayments = Repayment::withTrashed()->with('loan.user:id,name')->whereIn('id', $repIds)->orderBy('id')->get();

        // cash = the same rule as wallets and reports (CustomerWallets::whereCash, DISBURSED_CASH_SQL)
        $disbCash = (float) CustomerWallets::whereCash(Disbursement::whereIn('id', $disbIds))
            ->sum(\Illuminate\Support\Facades\DB::raw(CustomerWallets::DISBURSED_CASH_SQL));
        $repCash = (float) CustomerWallets::whereCash(Repayment::whereIn('id', $repIds))->sum('amount');

        $totals = [
            'disbursed_all' => (float) $disbursements->whereNull('deleted_at')->sum('amount'),
            'disbursed_cash' => $disbCash,
            'received_all' => (float) $repayments->whereNull('deleted_at')->sum('amount'),
            'received_cash' => $repCash,
        ];
        $byMode = $repayments->whereNull('deleted_at')
            ->filter(fn ($r) => CustomerWallets::kindOf($r) === 'cash')
            ->groupBy(fn ($r) => strtolower($r->mode ?: 'unspecified'))->map->sum('amount');

        $tellers = $user->can('view reports')
            ? User::role(['teller', 'admin', 'manager', 'recovery-officer', 'accountant'])->orderBy('name')->get(['id', 'name'])
            : collect();

        $close = DB::table('teller_day_closes')->where('teller_id', $tellerId)->where('day', $date->toDateString())->first();

        return view('staff.desk.end-of-day', compact('date', 'teller', 'disbursements', 'repayments', 'totals', 'byMode', 'tellers', 'close'));
    }

    /**
     * Close the day (Stage 4 Phase 5): the teller enters what they counted per mode; expected = the cash they
     * recorded that day per mode (cash paid out comes off the "cash" mode). Variance = counted - expected.
     * Once closed it can't be redone; a manager approves it.
     */
    public function closeDay(Request $request)
    {
        $user = $request->user();
        $date = rescue(fn () => Carbon::parse($request->input('date', today()->toDateString()))->startOfDay(), today(), false);
        $data = $request->validate(['counted' => 'required|array', 'counted.*' => 'nullable|numeric|min:0', 'note' => 'nullable|string|max:500']);
        if (DB::table('teller_day_closes')->where('teller_id', $user->id)->where('day', $date->toDateString())->exists()) {
            return back()->with('error', 'This day is already closed.');
        }
        $repIds = DB::table('repayments')->where('created_by', $user->id)->whereDate('created_at', $date)->whereNull('deleted_at')->pluck('id');
        $expected = CustomerWallets::whereCash(Repayment::whereIn('id', $repIds))->get(['mode', 'amount'])
            ->groupBy(fn ($r) => strtolower($r->mode ?: 'unspecified'))->map(fn ($g) => round((float) $g->sum('amount'), 2))->all();
        $paidOut = (float) CustomerWallets::whereCash(Disbursement::where('created_by', $user->id)->whereDate('created_at', $date))
            ->sum(DB::raw(CustomerWallets::DISBURSED_CASH_SQL));
        if ($paidOut > 0) {
            $expected['cash'] = round(($expected['cash'] ?? 0) - $paidOut, 2);
        }
        $counted = collect($data['counted'])->map(fn ($v) => round((float) $v, 2))->all();
        $variance = round(array_sum($counted) - array_sum($expected), 2);
        DB::table('teller_day_closes')->insert(['teller_id' => $user->id, 'day' => $date->toDateString(), 'expected' => json_encode($expected),
            'counted' => json_encode($counted), 'variance' => $variance, 'note' => $data['note'] ?? null, 'status' => 'closed',
            'closed_by' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        AuditTrail::record('teller_day_closes', $user->id, 'create', null, ['day' => $date->toDateString(), 'expected' => $expected, 'counted' => $counted, 'variance' => $variance]);

        return back()->with('success', 'Day closed. Variance KES ' . number_format($variance, 2) . '. A manager approves it next.');
    }

    /** A manager (not the teller) approves a closed day. */
    public function approveDay(Request $request, int $close)
    {
        $row = DB::table('teller_day_closes')->find($close);
        abort_unless($row, 404);
        if ((int) $row->teller_id === (int) $request->user()->id) {
            return back()->with('error', 'Someone other than the teller approves the cash-up.');
        }
        DB::table('teller_day_closes')->where('id', $close)->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now(), 'updated_at' => now()]);
        AuditTrail::record('teller_day_closes', $close, 'update', ['status' => $row->status], ['status' => 'approved']);

        return back()->with('success', 'Cash-up approved.');
    }
}
