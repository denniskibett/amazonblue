<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\AuditTrail;
use App\Services\CustomerEvents;
use App\Services\Loans\TopUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Staff send a top-up a borrower asked for (owner, 23 Oct 2026). Sending is the approval: the new cycle is booked
 * and the money recorded in one go (Loans\TopUp::execute), with the M-Pesa code of the payment that went out.
 * Declining uses the Requests page's usual Decline.
 */
class TopUpController extends Controller
{
    public function approve(Request $request, int $id, TopUp $topUp): RedirectResponse
    {
        abort_unless($request->user()->can('create disbursements'), 403, 'Sending a top-up needs the "create disbursements" permission.');
        $data = $request->validate([
            'transaction' => 'required|string|max:64',
            'mode' => 'required|in:mpesa,bank_transfer,cash,cheque',
        ]);
        $r = DB::table('borrower_requests')->where('id', $id)->first();
        abort_unless($r && $r->type === 'topup', 404);
        if ($r->status !== 'open') {
            return back()->with('error', 'This request is no longer open.');
        }
        $amount = (float) (json_decode((string) $r->payload, true)['amount'] ?? 0);
        $loan = Loan::findOrFail($r->loan_id);
        try {
            $plan = $topUp->execute($loan, $amount, ['transaction' => $data['transaction'], 'mode' => $data['mode']], $request->user(), $id);
        } catch (\DomainException $e) {
            return back()->with('error', 'Top-up not sent: ' . $e->getMessage());
        }
        DB::table('borrower_requests')->where('id', $id)->update(['status' => 'approved', 'decided_by' => $request->user()->id, 'decided_at' => now(),
            'decision_note' => 'Sent KES ' . number_format($plan['amount'], 2) . ', ref ' . $data['transaction'], 'updated_at' => now()]);
        AuditTrail::record('borrower_requests', $id, 'update', ['status' => 'open'], ['status' => 'approved', 'top_up' => $plan['amount']]);
        $text = 'We sent your top-up of KES ' . number_format($plan['amount'], 2) . ' on loan ' . $loan->id . '. Repay KES '
            . number_format($plan['new_balance'], 2) . ' by ' . \Illuminate\Support\Carbon::parse($plan['due_date'])->format('j M Y') . '.';
        DB::afterCommit(fn () => app(CustomerEvents::class)->loan('request_decided', $loan->fresh(), ['approved' => true, 'text' => $text]));

        return back()->with('success', 'Top-up sent: KES ' . number_format($plan['amount'], 2) . ' on loan #' . $loan->id . '. New balance KES '
            . number_format($plan['new_balance'], 2) . ', due ' . \Illuminate\Support\Carbon::parse($plan['due_date'])->format('j M Y') . '.');
    }
}
