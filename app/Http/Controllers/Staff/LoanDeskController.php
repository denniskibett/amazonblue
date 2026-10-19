<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DebtRecoveryCase;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 2 staff screens for loan decisions and disbursement (read + forms only).
 * Every action posts to the existing routes: loans.update (approve), loans.reject, disbursements.store.
 */
class LoanDeskController extends Controller
{
    /** One page with everything waiting for the signed-in user. */
    public function inbox(Request $request)
    {
        $user = $request->user();
        $cards = [];

        if ($user->can('approve loans')) {
            $cards[] = [
                'title' => 'Loans to approve',
                'count' => Loan::where('status', Loan::STATUS_PENDING)->count(),
                'rows' => Loan::with('user:id,name')->where('status', Loan::STATUS_PENDING)->oldest()->limit(5)->get(),
                'link' => route('staff.approvals'),
                'action' => 'Open approval queue',
            ];
        }

        if ($user->canAny(['create disbursements', 'disburse loans'])) {
            $cards[] = [
                'title' => 'Approved, not yet disbursed',
                'count' => Loan::where('status', Loan::STATUS_APPROVED)->count(),
                'rows' => Loan::with('user:id,name')->where('status', Loan::STATUS_APPROVED)->oldest('updated_at')->limit(5)->get(),
                'link' => route('staff.disbursements.queue'),
                'action' => 'Open disbursement queue',
            ];
        }

        if ($user->can('edit users')) {
            $cards[] = [
                'title' => 'KYC submissions to review',
                'count' => User::where('kyc_status', 'submitted')->count(),
                'people' => User::where('kyc_status', 'submitted')->oldest('kyc_submitted_at')->limit(5)->get(['id', 'name', 'kyc_submitted_at']),
                'link' => route('kyc-reviews.index'),
                'action' => 'Open KYC reviews',
            ];
        }

        // business onboarding is built in parallel; only count when its table exists
        $businesses = null;
        if ($user->can('edit users') && Schema::hasTable('businesses')) {
            $q = DB::table('businesses');
            if (Schema::hasColumn('businesses', 'status')) {
                $q->where('status', 'submitted');
            }
            if (Schema::hasColumn('businesses', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
            $businesses = $q->count();
        }

        if ($user->can('view recovery cases')) {
            $open = DebtRecoveryCase::whereHas('status', fn ($q) => $q->whereNotIn('slug', ['recovered', 'written_off', 'closed']));
            $cards[] = [
                'title' => 'Recovery cases assigned to me',
                'count' => (clone $open)->where('assigned_to', $user->id)->count(),
                'sub' => (clone $open)->whereNull('assigned_to')->count() . ' open case(s) have no officer',
                'link' => route('staff.collections', ['view' => 'mine']),
                'action' => 'Open collections',
            ];
        }

        return view('staff.desk.inbox', compact('cards', 'businesses'));
    }

    /** Pending / approved loans with the 4Cs assessment (read-only) and approve / reject. */
    public function approvals(Request $request)
    {
        $status = $request->query('status') === 'approved' ? Loan::STATUS_APPROVED : Loan::STATUS_PENDING;

        $loans = Loan::with(['user:id,name,email,phone,kyc_status', 'loanType:id,name,interest_rate,period,unit', 'riskAssessments'])
            ->where('status', $status)
            ->orderBy($status === Loan::STATUS_PENDING ? 'created_at' : 'updated_at')
            ->paginate(15)->withQueryString();

        $counts = Loan::whereIn('status', [Loan::STATUS_PENDING, Loan::STATUS_APPROVED])
            ->select('status', DB::raw('count(*) as n'), DB::raw('sum(amount) as total'))
            ->groupBy('status')->get()->keyBy('status');

        return view('staff.desk.approvals', compact('loans', 'status', 'counts'));
    }

    /** Approved loans waiting for money to go out. */
    public function disbursementQueue(Request $request)
    {
        $loans = Loan::with(['user:id,name,email,phone', 'loanType:id,name', 'riskAssessments'])
            ->where('status', Loan::STATUS_APPROVED)
            ->oldest('updated_at')
            ->paginate(15);

        $today = \App\Models\Disbursement::whereDate('disburse_date', today());

        return view('staff.desk.disbursement-queue', [
            'loans' => $loans,
            'waitingTotal' => (float) Loan::where('status', Loan::STATUS_APPROVED)->sum(DB::raw('coalesce(original_amount, amount)')),
            'todayCount' => (clone $today)->count(),
            'todayTotal' => (float) (clone $today)->sum('amount'),
        ]);
    }

    /** Disburse form: posts to the existing disbursements.store (JSON). */
    public function disburseForm(Loan $loan)
    {
        $loan->load(['user:id,name,email,phone', 'loanType', 'cycles', 'riskAssessments']);

        $investments = Investment::with(['fundings.partner:id,name,is_house_account'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'funding_partners']);

        // house default: the line the store() method falls back to when no investment is chosen
        $house = $investments->firstWhere('name', 'General Lending Book')
            ?? $investments->first(fn ($i) => $i->fundings->contains(fn ($f) => $f->partner?->is_house_account));

        $partners = $investments->mapWithKeys(fn ($i) => [$i->id => $i->fundings
            ->map(fn ($f) => ($f->partner?->name ?? 'Partner #' . $f->partner_id) . ($f->partner?->is_house_account ? ' (house)' : ''))
            ->unique()->values()->all()]);

        return view('staff.desk.disburse', [
            'loan' => $loan,
            'cycle' => $loan->getCurrentCycle(),
            'investments' => $investments,
            'houseId' => $house?->id,
            'partners' => $partners,
        ]);
    }
}
