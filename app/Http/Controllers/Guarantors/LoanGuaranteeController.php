<?php

namespace App\Http\Controllers\Guarantors;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanGuarantee;
use App\Services\Loans\Guarantees;
use App\Support\PrivateFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Staff and borrower side of the guarantor sign-off (owner, 21 Nov 2026): text the link, sign in the office, waive,
 * see the signed guarantee and the guarantor's ID. The borrower can only have the link texted again to their guarantor;
 * the link itself is never shown to them, so they cannot sign in their guarantor's place.
 */
class LoanGuaranteeController extends Controller
{
    private const FILES = ['id-front' => 'id_front_path', 'id-back' => 'id_back_path', 'signature' => 'signature_path'];

    public function __construct(private Guarantees $guarantees)
    {
    }

    /** Staff: start it (if the borrower has a guarantor on file) or send a fresh link. */
    public function send(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($request->user()->brokerMaySee($loan), 403);
        try {
            $row = $this->guarantees->forLoan($loan) ?? $this->guarantees->open($loan, $request->user(), false)
                ?? throw new \DomainException('This borrower has no guarantor on file. Add one to their KYC first.');
            $this->guarantees->send($row, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Link texted to ' . $row->name . ' (' . Guarantees::masked($row->phone) . ')' . ($row->email ? ' and emailed to ' . $row->email : '') . '.');
    }

    /** Staff: the guarantor is in the office. A fresh link opens the signing page here, on this device. */
    public function signHere(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($request->user()->brokerMaySee($loan), 403);
        $row = $this->guarantees->forLoan($loan) ?? $this->guarantees->open($loan, $request->user(), false);
        if (! $row || $row->released() || ! in_array($loan->status, Guarantees::OPEN, true)) {
            return back()->with('error', $row ? 'There is nothing left to sign on this loan.' : 'This borrower has no guarantor on file. Add one to their KYC first.');
        }
        $token = \Illuminate\Support\Str::random(48);
        $row->forceFill(['token_hash' => hash('sha256', $token), 'link_expires_at' => now()->addHour()])->save();
        \App\Services\AuditTrail::record('loan_guarantees', $row->id, 'update', null, ['event' => 'signing_in_office', 'by' => $request->user()->id]);

        return redirect()->route('guarantor.sign', $token);
    }

    public function waive(Request $request, Loan $loan): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']], ['reason.required' => 'Say why, e.g. "Signed the paper guarantee in the office".']);
        $row = $this->guarantees->forLoan($loan);
        abort_unless($row, 404);
        try {
            $this->guarantees->waive($row, $request->user(), $data['reason']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Guarantor sign-off waived. The money can be sent.');
    }

    /** The signed guarantee, printable. */
    public function document(Request $request, Loan $loan): View
    {
        abort_unless($request->user()->brokerMaySee($loan), 403);
        $row = $this->guarantees->forLoan($loan);
        abort_unless($row && $row->status === LoanGuarantee::SIGNED, 404);

        return view('guarantor.document', ['row' => $row, 'loan' => $loan->loadMissing('user')]);
    }

    public function file(Request $request, Loan $loan, string $kind): BinaryFileResponse
    {
        abort_unless($request->user()->brokerMaySee($loan) && isset(self::FILES[$kind]), 404);
        $row = $this->guarantees->forLoan($loan);
        $path = $row ? PrivateFiles::absolute($row->{self::FILES[$kind]}) : null;
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Borrower: text the link to my guarantor again. */
    public function remind(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless((int) $loan->user_id === (int) $request->user()->id, 404);
        $row = $this->guarantees->forLoan($loan);
        abort_unless($row, 404);
        if ($row->link_sent_at && $row->link_sent_at->gt(now()->subMinutes(10))) {
            return back()->with('alert', ['type' => 'info', 'title' => 'Already sent', 'message' => 'We texted ' . $row->firstName() . ' a few minutes ago. Ask them to check their messages.']);
        }
        try {
            $this->guarantees->send($row, $request->user());
        } catch (\DomainException $e) {
            return back()->with('alert', ['type' => 'info', 'title' => 'Nothing to send', 'message' => $e->getMessage()]);
        }

        return back()->with('alert', ['type' => 'success', 'title' => 'Sent', 'message' => 'We texted the signing link to ' . $row->firstName() . ' again.']);
    }
}
