<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Loan;
use App\Services\AuditTrail;
use App\Services\CustomerWallets;
use App\Services\LoanCalculator;
use App\Services\LoanProgress;
use App\Support\BorrowerLoanState;
use App\Support\CustomerNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Stage 2 borrower journeys in /account (used by HomeController): extension request with an indicative
 * cost preview, payment-plan acceptance, statement of account, notifications, help and "contact us about
 * this loan". Own data only (role:borrower routes, every loan checked against the signed-in user).
 *
 * Nothing here moves money or changes a loan. Requests and acceptances are not stored in their own
 * table yet (Stage 4): each one is an audit_logs row (table_name 'borrower_requests', record_id = loan id,
 * new_values.type) plus an email to staff through CustomerNotifier::borrowerRequest.
 */
trait BorrowerJourneys
{
    // ============ EXTENSION (ROLLOVER) REQUEST ============

    public function accountExtension(Request $request, Loan $loan, LoanProgress $progress, LoanCalculator $calculator): View
    {
        $this->ownJourneyLoan($request, $loan);

        $preview = null;
        if (in_array($loan->status, BorrowerLoanState::EXTENDABLE, true)) {
            try {
                // read-only: works out the next period without saving anything
                $preview = $calculator->getRolloverPreview($loan);
            } catch (\Throwable $e) {
                $preview = null;
            }
        }

        return view('home.account.extension', [
            'user' => $request->user(),
            'progress' => $progress->for($loan),
            'preview' => $preview,
            'canAsk' => in_array($loan->status, BorrowerLoanState::EXTENDABLE, true),
            'asked' => BorrowerLoanState::lastRequest($loan, 'extension_request'),
        ]);
    }

    public function requestExtension(Request $request, Loan $loan): RedirectResponse
    {
        $this->ownJourneyLoan($request, $loan);
        $back = redirect()->route('account.extension', $loan);
        if (! in_array($loan->status, BorrowerLoanState::EXTENDABLE, true)) {
            return $back->with('alert', ['type' => 'warning', 'title' => 'This loan can\'t be extended', 'message' => 'Talk to us and we\'ll look at the options.']);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'confirm' => ['accepted'],
        ], [
            'reason.required' => 'Tell us briefly why you need more time.',
            'confirm.accepted' => 'Please confirm you understand the extension has a cost.',
        ]);

        $ref = str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT);
        AuditTrail::record(BorrowerLoanState::REQUESTS, $loan->id, 'create', null, [
            'type' => 'extension_request', 'loan_id' => $loan->id, 'loan_status' => $loan->status, 'reason' => $data['reason'],
        ]);
        app(\App\Services\Requests::class)->borrower('extension', $request->user(), $loan, $data['reason'], ['loan_status' => $loan->status]);
        CustomerNotifier::borrowerRequest($request->user(), 'Extension request: loan #' . $ref,
            'asks for an extension (rollover) of loan #' . $ref . ' (status ' . $loan->status . '). Reason: "' . $data['reason'] . '". Review it and, if agreed, run the rollover from the loan page.', $loan);

        return $back->with('alert', ['type' => 'success', 'title' => 'Request sent',
            'message' => 'Our team will call you to agree the new due date and cost. Nothing changes on your loan until they confirm.']);
    }

    // ============ PAYMENT PLAN (FORBEARANCE) ACCEPTANCE ============

    public function accountPlan(Request $request, Loan $loan, LoanProgress $progress): View
    {
        $this->ownJourneyLoan($request, $loan);

        $cycles = $loan->cycles()->orderBy('cycle_number')->get();

        return view('home.account.plan', [
            'user' => $request->user(),
            'progress' => $progress->for($loan),
            'plan' => $loan->status === 'forbearance' ? ($cycles->firstWhere('status', 'active') ?? $cycles->last()) : null,
            'accepted' => BorrowerLoanState::lastRequest($loan, 'payment_plan_accepted'),
        ]);
    }

    public function acceptPlan(Request $request, Loan $loan): RedirectResponse
    {
        $this->ownJourneyLoan($request, $loan);
        $back = redirect()->route('account.plan', $loan);
        if ($loan->status !== 'forbearance') {
            return $back->with('alert', ['type' => 'warning', 'title' => 'No plan to accept', 'message' => 'This loan is not on a payment plan.']);
        }
        $cycle = $loan->cycles()->where('status', 'active')->orderByDesc('cycle_number')->first();

        $ref = str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT);
        $terms = ['cycle_id' => $cycle?->id, 'cycle_number' => $cycle?->cycle_number, 'balance' => (string) $cycle?->new_balance,
            'rate' => (string) $cycle?->interest_rate, 'due' => $cycle?->due_date?->format('Y-m-d'), 'waived_until' => $cycle?->penalty_waived_until?->format('Y-m-d')];
        // consent stored with the exact terms accepted (version = the plan cycle, hash of its terms)
        app(\App\Services\Requests::class)->borrower('plan', $request->user(), $loan, null,
            ['version' => 'cycle-' . ($cycle?->id ?? 0), 'terms' => $terms, 'terms_sha256' => hash('sha256', json_encode($terms))]);
        AuditTrail::record(BorrowerLoanState::REQUESTS, $loan->id, 'create', null, [
            'type' => 'payment_plan_accepted', 'loan_id' => $loan->id, 'cycle_id' => $cycle?->id,
            'cycle_number' => $cycle?->cycle_number, 'plan_balance' => $cycle?->new_balance,
            'plan_due_date' => $cycle?->due_date?->format('Y-m-d'), 'forbearance_until' => $loan->forbearance_until?->format('Y-m-d'),
        ]);
        CustomerNotifier::borrowerRequest($request->user(), 'Payment plan accepted: loan #' . $ref,
            'accepted the payment plan on loan #' . $ref . ($cycle ? ' (period ' . $cycle->cycle_number . ', KES ' . number_format((float) $cycle->new_balance, 2) . ' due ' . $cycle->due_date?->format('j M Y') . ')' : '') . '.', $loan);

        return $back->with('alert', ['type' => 'success', 'title' => 'Thank you',
            'message' => 'We\'ve recorded that you accept this plan and told our team.']);
    }

    // ============ STATEMENT OF ACCOUNT ============

    /**
     * Every disbursement and repayment on the borrower's loans (or one loan), oldest first, with cash
     * and book entries (rollover, discount, write-off) told apart by CustomerWallets::kind.
     */
    public function accountLoanStatement(Request $request, LoanProgress $progress, ?Loan $loan = null): View
    {
        $user = $request->user();
        if ($loan) {
            $this->ownJourneyLoan($request, $loan);
        }
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;

        $loans = $loan ? collect([$loan]) : Loan::with('loanType')->where('user_id', $user->id)->orderBy('id')->get();
        $entries = $this->statementEntries($loans->pluck('id'), $from, $to);

        $cash = $entries->where('kind', 'cash');

        return view('home.account.statement-of-account', [
            'user' => $user,
            'loan' => $loan,
            'progress' => $loan ? $progress->for($loan) : null,
            'loans' => $loans,
            'entries' => $entries,
            'from' => $from,
            'to' => $to,
            'totals' => [
                'sent' => $cash->sum('sent'),
                'paid' => $cash->sum('paid'),
                'book' => $entries->where('kind', '!=', 'cash')->sum(fn ($e) => $e['sent'] + $e['paid']),
            ],
        ]);
    }

    private function statementEntries(Collection $loanIds, ?Carbon $from, ?Carbon $to): Collection
    {
        $range = function ($q, string $col) use ($from, $to) {
            if ($from) {
                $q->where($col, '>=', $from->toDateString());
            }
            if ($to) {
                $q->where($col, '<=', $to->toDateString());
            }
        };

        $sent = DB::table('disbursements')->whereIn('loan_id', $loanIds)->whereNull('deleted_at')
            ->where(fn ($q) => $range($q, 'disburse_date'))
            ->get(['id', 'loan_id', 'amount', 'net_amount', 'disburse_date as date', 'transaction', 'kind', 'mode'])
            ->map(fn ($d) => [
                'date' => $d->date, 'loan_id' => $d->loan_id, 'ref' => $d->transaction, 'kind' => CustomerWallets::kindOf($d),
                'type' => 'sent', 'sent' => (float) ($d->net_amount ?: $d->amount), 'paid' => 0.0, 'mode' => $d->mode, 'order' => 0, 'id' => $d->id,
            ]);
        $paid = DB::table('repayments')->whereIn('loan_id', $loanIds)->whereNull('deleted_at')
            ->where(fn ($q) => $range($q, 'repayment_date'))
            ->get(['id', 'loan_id', 'amount', 'repayment_date as date', 'transaction', 'kind', 'mode'])
            ->map(fn ($r) => [
                'date' => $r->date, 'loan_id' => $r->loan_id, 'ref' => $r->transaction, 'kind' => CustomerWallets::kindOf($r),
                'type' => 'paid', 'sent' => 0.0, 'paid' => (float) $r->amount, 'mode' => $r->mode, 'order' => 1, 'id' => $r->id,
            ]);

        return $sent->concat($paid)->sortBy([['date', 'asc'], ['order', 'asc'], ['id', 'asc']])->values();
    }

    // ============ NOTIFICATIONS ============

    public function accountNotifications(Request $request): View
    {
        $user = $request->user();
        $loans = Loan::where('user_id', $user->id)->latest('id')->take(10)->get();
        $loanIds = $loans->pluck('id');
        $ref = fn ($id) => 'Loan #' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);

        // what we have told them (or would have): built from their own records, newest first
        $updates = collect();
        if ($user->kyc_submitted_at) {
            $updates->push(['date' => $user->kyc_submitted_at, 'title' => 'Verification received', 'detail' => 'We emailed you a reference.', 'tone' => 'info', 'href' => route('account.profile')]);
        }
        if ($user->kyc_reviewed_at && in_array($user->kyc_status, ['approved', 'rejected'], true)) {
            $updates->push(['date' => $user->kyc_reviewed_at, 'title' => $user->kyc_status === 'approved' ? 'You\'re verified' : 'Verification needs attention', 'detail' => $user->kyc_status === 'approved' ? 'You can borrow from your account.' : 'Check your details and resubmit.', 'tone' => $user->kyc_status === 'approved' ? 'ok' : 'err', 'href' => route('account.profile')]);
        }
        foreach ($loans as $loan) {
            $updates->push(['date' => $loan->created_at, 'title' => 'Application received', 'detail' => $ref($loan->id) . ' · KES ' . number_format((float) $loan->amount, 2), 'tone' => 'info', 'href' => route('account.loan', $loan)]);
            if ($loan->status === 'rejected') {
                $updates->push(['date' => $loan->updated_at, 'title' => 'Application not approved', 'detail' => $ref($loan->id), 'tone' => 'err', 'href' => route('account.loan', $loan)]);
            }
        }
        foreach ($this->statementEntries($loanIds, null, null)->where('kind', 'cash') as $e) {
            $updates->push($e['type'] === 'sent'
                ? ['date' => $e['date'], 'title' => 'Money sent to your M-Pesa', 'detail' => $ref($e['loan_id']) . ' · KES ' . number_format($e['sent'], 2), 'tone' => 'ok', 'href' => route('account.loan', $e['loan_id'])]
                : ['date' => $e['date'], 'title' => 'Payment recorded', 'detail' => $ref($e['loan_id']) . ' · KES ' . number_format($e['paid'], 2) . ($e['ref'] ? ' · ' . $e['ref'] : ''), 'tone' => 'ok', 'href' => route('account.loan', $e['loan_id'])]);
        }

        return view('home.account.notifications', [
            'user' => $user,
            'updates' => $updates->sortByDesc(fn ($u) => Carbon::parse($u['date'])->timestamp)->take(20)->values(),
        ]);
    }

    // ============ HELP / CONTACT ABOUT A LOAN ============

    public const HELP_TOPICS = [
        'loan' => 'A question about my loan',
        'payment' => 'A payment I made',
        'extension' => 'I need more time to pay',
        'details' => 'Change my details',
        'other' => 'Something else',
    ];

    public function accountHelp(Request $request): View
    {
        $user = $request->user();

        return view('home.account.help', [
            'user' => $user,
            'loans' => Loan::where('user_id', $user->id)->latest('id')->take(20)->get(['id', 'amount', 'status']),
            'selected' => (int) $request->query('loan'),
            'topics' => self::HELP_TOPICS,
        ]);
    }

    public function sendHelp(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'topic' => ['required', 'in:' . implode(',', array_keys(self::HELP_TOPICS))],
            'loan_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'message.required' => 'Tell us how we can help.',
        ]);
        // only their own loan can be named
        $loan = ! empty($data['loan_id']) ? Loan::where('user_id', $user->id)->find($data['loan_id']) : null;

        AuditTrail::record(BorrowerLoanState::REQUESTS, $loan?->id ?? 0, 'create', null, [
            'type' => 'help_message', 'topic' => $data['topic'], 'loan_id' => $loan?->id, 'message' => $data['message'],
        ]);
        CustomerNotifier::borrowerRequest($user,
            'Customer message: ' . self::HELP_TOPICS[$data['topic']] . ($loan ? ' (loan #' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT) . ')' : ''),
            'wrote: "' . $data['message'] . '"', $loan);

        return redirect()->route('account.help')->with('alert', ['type' => 'success', 'title' => 'Message sent',
            'message' => 'Thanks. Our team will get back to you by phone or email, usually the same working day.']);
    }

    private function ownJourneyLoan(Request $request, Loan $loan): void
    {
        abort_unless($loan->user_id === $request->user()->id, 404);
    }
}
