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
    /**
     * My inbox (owner, 22 Oct 2026): a TailAdmin-style mailbox. Each folder is a queue the user's
     * permissions open; each message is one thing waiting, linked to the page where it is done.
     */
    public function inbox(Request $request)
    {
        $user = $request->user();
        $folders = [];
        $msg = fn ($who, $subject, $snippet, $when, $href, $tag = null, $amount = null) => [
            'who' => $who ?: 'Unknown', 'subject' => $subject, 'snippet' => $snippet, 'amount' => $amount,
            'when' => $when ? \Illuminate\Support\Carbon::parse($when) : null, 'href' => $href, 'tag' => $tag,
        ];
        $add = function (string $key, string $label, string $icon, callable $count, callable $items) use (&$folders) {
            try {
                $folders[$key] = ['label' => $label, 'icon' => $icon, 'count' => (int) $count(), 'items' => collect($items())];
            } catch (\Throwable $e) {
                report($e);
            }
        };

        if ($user->can('view loans')) {
            $add('approve', 'Loans to approve', 'check-circle',
                fn () => Loan::where('status', Loan::STATUS_PENDING)->count(),
                fn () => Loan::with(['user:id,name', 'loanType:id,name'])->where('status', Loan::STATUS_PENDING)->oldest()->limit(50)->get()
                    ->map(fn ($l) => $msg($l->user->name ?? null, 'Loan #' . $l->id . ' needs a decision', ($l->loanType->name ?? 'Loan') . ' application', $l->created_at, route('staff.approvals'), 'Approval', $l->amount)));
        }
        if ($user->can('view disbursements')) {
            $add('disburse', 'Waiting to be sent', 'send',
                fn () => Loan::where('status', Loan::STATUS_APPROVED)->count(),
                fn () => Loan::with(['user:id,name', 'loanType:id,name'])->where('status', Loan::STATUS_APPROVED)->oldest('updated_at')->limit(50)->get()
                    ->map(fn ($l) => $msg($l->user->name ?? null, 'Send loan #' . $l->id, 'Approved, money not sent yet', $l->updated_at, route('staff.disbursements.queue'), 'Disbursement', $l->amount)));
        }
        if ($user->can('view users')) {
            $add('kyc', 'KYC to review', 'user-check',
                fn () => User::where('kyc_status', 'submitted')->count(),
                fn () => User::where('kyc_status', 'submitted')->oldest('kyc_submitted_at')->limit(50)->get(['id', 'name', 'kyc_submitted_at'])
                    ->map(fn ($p) => $msg($p->name, 'KYC submitted', 'Check the documents and approve or reject', $p->kyc_submitted_at, route('kyc-reviews.show', $p->id), 'KYC')));
            if (Schema::hasTable('businesses')) {
                $add('business', 'Business to review', 'building',
                    fn () => DB::table('businesses')->where('status', 'submitted')->whereNull('deleted_at')->count(),
                    fn () => DB::table('businesses')->where('status', 'submitted')->whereNull('deleted_at')->oldest('submitted_at')->limit(50)->get()
                        ->map(fn ($b) => $msg($b->trading_name ?: $b->legal_name, 'Business submitted for review', trim(($b->industry ?? '') . ' ' . ($b->city ?? '')) ?: 'Business onboarding', $b->submitted_at, route('business-reviews.show', $b->id), 'Business')));
            }
        }
        if ($user->canAny(['rollover loans', 'edit partners']) && Schema::hasTable('borrower_requests')) {
            $add('requests', 'Requests', 'mail',
                fn () => DB::table('borrower_requests')->where('status', 'open')->count(),
                fn () => DB::table('borrower_requests as r')->leftJoin('users as u', 'u.id', '=', 'r.user_id')->where('r.status', 'open')
                    ->orderBy('r.created_at')->limit(50)->get(['r.*', 'u.name'])
                    ->map(fn ($r) => $msg($r->name, ucfirst(str_replace('_', ' ', $r->type)) . ($r->loan_id ? ' on loan #' . $r->loan_id : ''), \Illuminate\Support\Str::limit((string) $r->message, 90) ?: 'No message', $r->created_at, route('staff.requests'), 'Request')));
        }
        if (Schema::hasTable('approvals')) {
            $add('signoffs', 'Second sign-offs', 'shield',
                fn () => DB::table('approvals')->where('status', 'pending')->where('requested_by', '!=', $user->id)->count(),
                fn () => DB::table('approvals as a')->leftJoin('users as u', 'u.id', '=', 'a.requested_by')->where('a.status', 'pending')->where('a.requested_by', '!=', $user->id)
                    ->orderBy('a.created_at')->limit(50)->get(['a.*', 'u.name'])
                    ->map(fn ($a) => $msg($a->name, ucfirst(str_replace('_', ' ', $a->action)) . ' needs a second signature', class_basename((string) $a->subject_type) . ' #' . $a->subject_id, $a->created_at, route('staff.sign-offs'), 'Sign-off', $a->amount)));
        }
        if ($user->can('view recovery cases')) {
            $open = fn () => DebtRecoveryCase::whereHas('status', fn ($q) => $q->whereNotIn('slug', ['recovered', 'written_off', 'closed']));
            $add('recovery', 'Recovery on me', 'alert',
                fn () => $open()->where('assigned_to', $user->id)->count(),
                fn () => $open()->with(['loan.user:id,name'])->where('assigned_to', $user->id)->oldest('updated_at')->limit(50)->get()
                    ->map(fn ($c) => $msg($c->loan->user->name ?? null, 'Recovery case #' . $c->id . ($c->loan_id ? ' · loan #' . $c->loan_id : ''), 'Follow up and log the next action', $c->updated_at, route('cases.show', $c->id), 'Recovery')));
        }
        if ($user->can('view loans')) {
            $due = fn () => Loan::whereIn('status', \App\Support\PageMetrics::OPEN)->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()]);
            $add('due', 'Due this week', 'calendar',
                fn () => $due()->count(),
                fn () => $due()->with(['user:id,name,phone', 'loanType:id,name'])->orderBy('due_date')->limit(50)->get()
                    ->map(fn ($l) => $msg($l->user->name ?? null, 'Loan #' . $l->id . ' due ' . \Illuminate\Support\Carbon::parse($l->due_date)->format('D j M'), 'Call before the due date' . ($l->user->phone ?? null ? ', ' . $l->user->phone : ''), $l->due_date, route('loans.show', $l->id), 'Due')));
        }
        if ($user->can('create repayments') && Schema::hasTable('mpesa_transactions')) {
            $add('mpesa', 'M-Pesa to assign', 'phone',
                fn () => \App\Models\MpesaTransaction::whereIn('status', ['unmatched', 'received'])->count(),
                fn () => \App\Models\MpesaTransaction::whereIn('status', ['unmatched', 'received'])->latest('id')->limit(50)->get()
                    ->map(fn ($t) => $msg('M-Pesa ' . $t->trans_id, 'Payment with no loan', 'Account ref ' . ($t->bill_ref ?: 'none') . ', put it on the right loan', $t->trans_time ?? $t->created_at, route('staff.payments'), 'M-Pesa', $t->amount)));
        }

        $active = $request->query('folder');
        if (! isset($folders[$active])) {
            $active = collect($folders)->filter(fn ($f) => $f['count'] > 0)->keys()->first() ?? array_key_first($folders);
        }

        return view('staff.desk.inbox', compact('folders', 'active'));
    }

    /** Pending / approved loans with the 4Cs assessment (read-only) and approve / reject. */
    public function approvals(Request $request)
    {
        $status = $request->query('status') === 'approved' ? Loan::STATUS_APPROVED : Loan::STATUS_PENDING;

        $loans = Loan::with(['user:id,name,email,phone,kyc_status', 'loanType:id,name,interest_rate,period,unit', 'riskAssessments'])
            ->where('status', $status)
            ->orderBy($status === Loan::STATUS_PENDING ? 'created_at' : 'updated_at')
            ->limit(1000)->get(); // the list pages itself in the browser (partials/ui/datatable)

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
            ->limit(1000)->get();

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

        // who brought the client and whose money it is (owner, 23 Oct 2026)
        $links = app(\App\Services\Partners\PartnerLinks::class);

        return view('staff.desk.disburse', [
            'loan' => $loan,
            'cycle' => $loan->getCurrentCycle(),
            'investments' => $investments,
            'houseId' => $house?->id,
            'partners' => $partners,
            'partnerList' => \App\Models\Partner::where('is_house_account', false)->orderBy('name')->pluck('name', 'id'),
            'openLines' => app(\App\Services\Partners\PartnerLines::class)->openLines(),
            'ownPartner' => $links->ownPartner($loan),
            'defaultMaturity' => \App\Http\Controllers\Staff\PartnerFundingController::dueDate($loan),
        ]);
    }
}
