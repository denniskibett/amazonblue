<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\AuditTrail;
use App\Services\LoanProgress;
use App\Services\Loans\TopUp;
use App\Services\Requests;
use App\Support\CustomerNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * A borrower asks for a top-up on a loan they are repaying on time (owner, 23 Oct 2026). The page shows whether
 * they can, how much, and exactly what the new cycle would be (Loans\TopUp::plan, the same plan staff book).
 * Asking stores a request for staff (Requests page) and emails them; nothing changes until staff send the money.
 */
class AccountTopUpController extends Controller
{
    public function show(Request $request, Loan $loan, TopUp $topUp, LoanProgress $progress): View
    {
        abort_unless($loan->user_id === $request->user()->id, 404);
        $check = $topUp->check($loan);
        $amount = (float) $request->query('amount', 0);
        $preview = null;
        $error = null;
        if ($check['ok'] && $amount > 0) {
            try {
                $preview = $topUp->plan($loan, $amount);
            } catch (\DomainException $e) {
                $error = $e->getMessage();
            }
        }
        $asked = DB::table('borrower_requests')->where('loan_id', $loan->id)->where('type', 'topup')->latest('id')->first();

        return view('home.account.topup', ['user' => $request->user(), 'progress' => $progress->for($loan), 'loan' => $loan,
            'check' => $check, 'amount' => $amount, 'preview' => $preview, 'error' => $error, 'asked' => $asked, 'stored' => Requests::enabled()]);
    }

    public function store(Request $request, Loan $loan, TopUp $topUp): RedirectResponse
    {
        abort_unless($loan->user_id === $request->user()->id, 404);
        $back = redirect()->route('account.topup', $loan);
        $data = $request->validate(['amount' => 'required|numeric|min:1', 'confirm' => 'accepted'],
            ['confirm.accepted' => 'Please confirm you will repay the new amount by the new due date.']);
        try {
            $plan = $topUp->plan($loan, (float) $data['amount']);
        } catch (\DomainException $e) {
            return $back->withInput()->withErrors(['amount' => $e->getMessage()]);
        }
        $ref = str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT);
        $line = 'asks for a top-up of KES ' . number_format($plan['amount'], 2) . ' on loan #' . $ref . '. New cycle: KES '
            . number_format($plan['principal'], 2) . ' (KES ' . number_format($plan['owed'], 2) . ' still owed + the top-up), repay KES '
            . number_format($plan['new_balance'], 2) . ' by ' . \Illuminate\Support\Carbon::parse($plan['due_date'])->format('j M Y') . '.';
        AuditTrail::record('borrower_requests', $loan->id, 'create', null, ['type' => 'topup_request', 'loan_id' => $loan->id, 'amount' => $plan['amount']]);
        app(Requests::class)->borrower('topup', $request->user(), $loan, 'Top-up of KES ' . number_format($plan['amount'], 2), ['amount' => $plan['amount'], 'owed' => $plan['owed'], 'due_date' => $plan['due_date']]);
        CustomerNotifier::borrowerRequest($request->user(), 'Top-up request: loan #' . $ref, $line . ' Review it on the Requests page.', $loan);

        return $back->with('alert', ['type' => 'success', 'title' => 'Top-up request sent', 'message' => 'Our team will check it and send the money to your M-Pesa. Your loan page shows the new amount and due date once it is sent.']);
    }
}
