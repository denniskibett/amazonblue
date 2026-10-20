<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Models\PaymentClaim;
use App\Models\Repayment;
use App\Services\AuditTrail;
use App\Services\Mpesa\PaymentRecorder;
use App\Services\Mpesa\StatementReconciler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff payments queue (Stage 4 Phase 4): M-Pesa payments that could not be matched, borrowers' "I've paid"
 * claims still being checked, today's matched payments, and the M-Pesa statement reconciliation.
 */
class PaymentsController extends Controller
{
    public function index(Request $request): View
    {
        return view('staff.desk.payments', [
            'unmatched' => MpesaTransaction::whereIn('status', ['unmatched', 'received'])->latest('id')->limit(100)->get(),
            'claims' => PaymentClaim::with(['loan', 'user:id,name'])->where('status', 'checking')->latest('id')->limit(100)->get(),
            'matched' => MpesaTransaction::with('repayment')->where('status', 'matched')->latest('updated_at')->limit(20)->get(),
            'recon' => session('recon'),
            'mpesaOn' => (bool) config('lending.mpesa.enabled'),
        ]);
    }

    /** Assigns an unmatched M-Pesa payment to a loan: it becomes that loan's repayment. */
    public function assign(Request $request, MpesaTransaction $transaction, PaymentRecorder $recorder): RedirectResponse
    {
        $data = $request->validate(['loan_id' => 'required|integer|exists:loans,id']);
        $loan = Loan::findOrFail($data['loan_id']);
        if (! in_array($loan->status, PaymentRecorder::PAYABLE, true)) {
            return back()->with('error', 'Loan #' . $loan->id . ' is ' . $loan->status . ' and can\'t take payments.');
        }
        try {
            $r = $recorder->assign($transaction, $loan, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditTrail::record('mpesa_transactions', $transaction->id, 'update', ['status' => 'unmatched'], ['status' => 'matched', 'loan_id' => $loan->id, 'repayment_id' => $r->id]);

        return back()->with('success', 'M-Pesa ' . $transaction->trans_id . ' recorded on loan #' . $loan->id . ' (repayment #' . $r->id . ').');
    }

    /** A claim is confirmed (the code is matched now) or rejected with a reason the borrower sees. */
    public function decideClaim(Request $request, PaymentClaim $claim, PaymentRecorder $recorder): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:match,reject', 'reason' => 'required_if:decision,reject|nullable|string|max:200']);
        if ($claim->status !== 'checking') {
            return back()->with('error', 'This claim is already ' . $claim->status . '.');
        }
        if ($data['decision'] === 'reject') {
            $claim->update(['status' => 'rejected', 'reason' => $data['reason'], 'decided_by' => $request->user()->id]);
            AuditTrail::record('payment_claims', $claim->id, 'update', ['status' => 'checking'], ['status' => 'rejected', 'reason' => $data['reason']]);

            return back()->with('success', 'Claim ' . $claim->code . ' marked not found. The borrower sees the reason.');
        }
        $recorder->matchClaim($claim);
        $claim->refresh();

        return $claim->status === 'received'
            ? back()->with('success', 'Claim ' . $claim->code . ' matched to repayment #' . $claim->repayment_id . '.')
            : back()->with('error', 'No M-Pesa payment ' . $claim->code . ' of KES ' . number_format((float) $claim->amount, 2) . ' has arrived yet. Record it on Receive payment once you see it on the statement.');
    }

    /** Upload M-Pesa's statement CSV: every receipt is matched against what was recorded, both ways. */
    public function reconcile(Request $request, StatementReconciler $reconciler): RedirectResponse
    {
        $request->validate(['statement' => 'required|file|mimes:csv,txt|max:5120']);
        $report = $reconciler->reconcile($request->file('statement')->getRealPath());

        return back()->with('recon', $report);
    }
}
