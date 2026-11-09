<?php

namespace App\Http\Controllers\Sharet;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Loan;
use App\Models\SharetPaymentRequest;
use App\Models\SharetPayout;
use App\Services\AuditTrail;
use App\Services\LoanProgress;
use App\Services\Sharet\SharetClient;
use App\Services\Sharet\SharetCollections;
use App\Services\Sharet\SharetPayouts;
use App\Services\Sms\SmsSender;
use App\Support\Integrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sharet Pay, the second rail (9 Nov 2026): borrower pay links, staff pay links, payouts and the connection test. */
class SharetController extends Controller
{
    /** Borrower: "Pay with Sharet" on the repay screen. Own loan only; goes straight to Sharet's pay page. */
    public function borrower(Request $request, Loan $loan, LoanProgress $progress, SharetCollections $collections): RedirectResponse
    {
        abort_unless((int) $loan->user_id === (int) $request->user()->id, 404);
        abort_unless(SharetCollections::available(), 404);
        $due = max(0.0, (float) ($progress->for($loan)['balance']['due'] ?? 0));
        $min = SharetCollections::MIN_AMOUNT;
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:' . $min, 'max:' . max($min, ceil($due))]],
            ['amount.max' => 'That is more than the amount due (KES ' . number_format($due, 2) . ').', 'amount.min' => 'Sharet Pay takes at least KES ' . $min . '.']);
        try {
            $link = $collections->payLink($loan, (float) $data['amount'], $request->user());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('account.repay', $loan)->with('alert', ['type' => 'warning', 'title' => 'Sharet Pay not available right now',
                'message' => 'Please try again in a minute, or pay by Paybill.']);
        }

        return redirect()->away($link->pay_url);
    }

    /** Staff: make a pay link for what the loan owes and text it to the borrower. */
    public function sendPayLink(Request $request, Loan $loan, LoanProgress $progress, SharetCollections $collections, SmsSender $sms): RedirectResponse
    {
        $due = max(0.0, (float) ($progress->for($loan)['balance']['due'] ?? 0));
        $min = SharetCollections::MIN_AMOUNT;
        $data = $request->validate(['amount' => ['nullable', 'numeric', 'min:' . $min, 'max:' . max($min, ceil($due))]]);
        try {
            $link = $collections->payLink($loan, (float) ($data['amount'] ?? $due), $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        $sent = $loan->user?->phone ? $sms->send($loan->user->phone, 'Aimzone: pay KES ' . number_format((float) $link->amount) . ' for loan ' . $loan->id
            . ' here: ' . $link->pay_url) : false;
        AuditTrail::record('sharet_payment_requests', $link->id, 'update', null, ['event' => 'sharet_pay_link_sent', 'sms' => $sent]);

        return back()->with('success', 'Pay link for KES ' . number_format((float) $link->amount, 2) . ($sent ? ' texted to the borrower' : ' made (no SMS sent)')
            . ': ' . $link->pay_url);
    }

    /** Staff: send an approved loan's money through Sharet (next payout run, after any second sign-off). */
    public function disburse(Request $request, Loan $loan, SharetPayouts $payouts): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20'], 'amount' => ['required', 'numeric', 'min:10']]);
        try {
            $row = $payouts->requestDisbursement($loan, (float) $data['amount'], $data['phone'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $row->status === 'awaiting_approval'
            ? 'Asked for. A second person approves it on Second sign-offs; it goes at the next Sharet run (08:00, 12:00 or 16:00).'
            : 'Queued. It goes through Sharet at the next run (08:00, 12:00 or 16:00).');
    }

    public function index(): View
    {
        return view('sharet.index', [
            'links' => SharetPaymentRequest::with('loan.user:id,name')->latest('id')->limit(200)->get(),
            'payouts' => SharetPayout::with(['loan.user:id,name', 'partner:id,name'])->latest('id')->limit(200)->get(),
            'available' => SharetCollections::available(),
            'payoutsOn' => SharetPayouts::enabled(),
            'rail' => SharetCollections::preferred(),
        ]);
    }

    public function cancel(Request $request, SharetPayout $payout, SharetPayouts $payouts): RedirectResponse
    {
        $this->canTouch($request, $payout);
        try {
            $payouts->cancel($payout, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payout #' . $payout->id . ' cancelled.');
    }

    public function retry(Request $request, SharetPayout $payout, SharetPayouts $payouts): RedirectResponse
    {
        $this->canTouch($request, $payout);
        try {
            $payouts->retry($payout, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payout #' . $payout->id . ' goes again at the next run.');
    }

    /**
     * Integrations › Sharet Pay › Test connection: checks the key and registers our webhook once, keeping the signing
     * secret Sharet returns (encrypted with the other settings; never shown).
     */
    public function test(Request $request): RedirectResponse
    {
        if (! filled(config('sharet.api_key'))) {
            return back()->with('error', 'Save the Sharet API key first.');
        }
        $client = SharetClient::make();
        $me = $client->get('/accounts/me');
        if (! $me->successful()) {
            return back()->with('error', 'Sharet refused the key: ' . ($me->json('detail') ?? $me->json('message') ?? ('HTTP ' . $me->status())));
        }
        $hooked = filled(config('sharet.webhook_secret'));
        $why = null;
        if (! $hooked) {
            $hook = $client->post('/webhooks', ['url' => route('sharet.webhook'), 'events' => SharetClient::WEBHOOK_EVENTS]);
            if ($hook->successful() && $hook->json('secret')) {
                $def = Integrations::CATALOGUE['sharet'];
                $row = Integration::firstOrNew(['slug' => 'sharet']);
                $row->fill(['name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'], 'logo' => $def['logo'],
                    'settings' => array_merge($row->settings ?? [], ['SHARET_WEBHOOK_SECRET' => (string) $hook->json('secret')]),
                    'enabled' => $row->exists ? $row->enabled : true, 'updated_by' => $request->user()->id])->save();
                Integrations::forget();
                AuditTrail::record('integrations', $row->id, 'update', null, ['event' => 'settings_changed', 'integration' => 'sharet', 'keys' => ['SHARET_WEBHOOK_SECRET']]);
                $hooked = true;
            } else {
                $why = $hook->json('detail') ?? $hook->json('message') ?? ('HTTP ' . $hook->status());
            }
        }

        return back()->with($hooked ? 'success' : 'warning', 'Connected to Sharet as ' . ($me->json('name') ?? $me->json('business_name') ?? $me->json('sharet_id') ?? 'your account')
            . ' (' . ($client->environment === 'live' ? 'live' : 'sandbox') . ').' . ($hooked ? ' Webhook registered.' : ' Webhook not registered yet (' . ($why ?? 'no answer') . '): Sharet needs the public HTTPS address.'));
    }

    private function canTouch(Request $request, SharetPayout $payout): void
    {
        abort_unless($request->user()->can($payout->kind === 'disbursement' ? 'disburse loans' : 'edit partners'), 403);
    }
}
