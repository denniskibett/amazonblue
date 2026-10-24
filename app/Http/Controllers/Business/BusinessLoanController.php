<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\LoanType;
use App\Services\AuditTrail;
use App\Services\Business\BusinessLending;
use App\Services\Business\DirectorVerification;
use App\Services\Requests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The owner's side of business loans (owner, 23 Oct 2026). Rules: App\Services\Business\BusinessLending. */
class BusinessLoanController extends Controller
{
    public function __construct(private BusinessLending $lending)
    {
    }

    public function create(Request $request, Business $business): View
    {
        $this->owned($request, $business);

        return view('business.borrow', [
            'business' => $business,
            'blockers' => $this->lending->blockers($business, $request->user()),
            'products' => $this->lending->products(),
            'available' => $this->lending->available($business),
            'owed' => $this->lending->owed($business),
            'loans' => $business->loans()->with('loanType')->latest('id')->limit(10)->get(),
        ]);
    }

    public function store(Request $request, Business $business): RedirectResponse
    {
        $this->owned($request, $business);
        $request->merge(['amount' => preg_replace('/[^\d.]/', '', (string) $request->input('amount'))]);
        $data = $request->validate([
            'loan_type_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'purpose' => ['required', 'string', 'min:10', 'max:500'],
            'consent' => ['accepted'],
        ], ['consent.accepted' => 'Accept the loan terms to continue.', 'purpose.min' => 'Tell us what the money is for (at least 10 characters).']);
        try {
            $loan = $this->lending->apply($business, $request->user(), LoanType::findOrFail($data['loan_type_id']), (float) $data['amount'], $data['purpose'], $request);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('account.business.borrow', $business)->with('alert', [
            'type' => 'success', 'title' => 'Application sent',
            'message' => 'Loan #' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT) . ' for KES ' . number_format((float) $loan->amount, 2) . ' is with our team.',
        ]);
    }

    public function sendLink(Request $request, Business $business, BusinessMember $member): RedirectResponse
    {
        $this->owned($request, $business);
        abort_unless($member->business_id === $business->id, 404);
        try {
            app(DirectorVerification::class)->send($member, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['link' => $e->getMessage()]);
        }

        return back()->with('alert', ['type' => 'success', 'title' => 'Link sent', 'message' => $member->full_name . ' can verify themselves from the link we sent.']);
    }

    /** An approved business can't be edited; the owner asks for the change and staff reopen it (plan default). */
    public function requestChange(Request $request, Business $business): RedirectResponse
    {
        $this->owned($request, $business);
        abort_unless($business->status === Business::STATUS_APPROVED, 409);
        $data = $request->validate(['message' => ['required', 'string', 'min:10', 'max:1000']], ['message.min' => 'Say what needs to change (at least 10 characters).']);
        if (! Requests::enabled()) {
            return back()->withErrors(['message' => 'Message us on WhatsApp for now.']);
        }
        $id = app(Requests::class)->borrower('business_change', $request->user(), null, $data['message'], ['business_id' => $business->id]);
        \Illuminate\Support\Facades\DB::table('borrower_requests')->where('id', $id)->update(['business_id' => $business->id]);
        AuditTrail::record('businesses', $business->id, 'update', null, ['event' => 'change_requested', 'request' => $id]);

        return back()->with('alert', ['type' => 'success', 'title' => 'Change requested', 'message' => 'Our team will reopen the business for you to change it, then check it again.']);
    }

    private function owned(Request $request, Business $business): void
    {
        abort_unless($business->owner_user_id === $request->user()->id, 404);
    }
}
