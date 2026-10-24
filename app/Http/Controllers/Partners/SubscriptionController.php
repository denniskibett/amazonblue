<?php

namespace App\Http\Controllers\Partners;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\PartnerAccount;
use App\Services\Partners\Subscription;
use App\Support\PartnerFunding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Partner KYC and online subscription, partner side (owner, 23 Oct 2026). Routes: routes/features/partner-subscriptions.php.
 * Business rules live in App\Services\Partners\Subscription.
 */
class SubscriptionController extends Controller
{
    public function __construct(private Subscription $subscriptions, private PartnerAccount $account)
    {
    }

    public function verify(Request $request): View
    {
        $partner = $this->partner($request);

        return view('partners.subscribe.verify', [
            'partner' => $partner,
            'check' => $this->subscriptions->verification($partner),
            'sources' => Subscription::SOURCES,
            'businesses' => $this->subscriptions->ownBusinesses($partner),
            'isBusiness' => $this->subscriptions->isBusinessPartner($partner),
        ]);
    }

    public function saveVerify(Request $request): RedirectResponse
    {
        $partner = $this->partner($request);
        $data = $request->validate([
            'source_of_funds' => ['required', 'string'],
            'business_id' => ['nullable', 'integer'],
        ], [], ['source_of_funds' => 'where the money comes from']);
        try {
            $this->subscriptions->saveVerification($partner, $data['source_of_funds'], $data['business_id'] ?? null);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['source_of_funds' => $e->getMessage()]);
        }

        return redirect()->route('partner.verify')->with('partner_sent', ['title' => 'Saved', 'body' => 'Thanks. Finish any step still open below.']);
    }

    public function create(Request $request): View
    {
        $partner = $this->partner($request);

        return view('partners.subscribe.create', [
            'partner' => $partner,
            'enabled' => Subscription::enabled(),
            'check' => $this->subscriptions->verification($partner),
            'terms' => $this->subscriptions->terms($partner),
            'min' => PartnerFunding::minAmount(),
            'subscriptions' => $this->subscriptions->forPartner($partner),
            'preview' => $this->subscriptions->text($partner, (float) ($request->query('amount') ?: PartnerFunding::minAmount()),
                in_array($request->query('term'), ['short_term', 'long_term'], true) ? $request->query('term') : 'short_term',
                (int) ($request->query('months') ?: 12)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $partner = $this->partner($request);
        $request->merge(['amount' => preg_replace('/[^\d.]/', '', (string) $request->input('amount'))]);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . PartnerFunding::minAmount(), 'max:1000000000'],
            'term' => ['required', 'in:short_term,long_term'],
            'months' => ['nullable', 'integer'],
            'agree' => ['accepted'],
        ], ['agree.accepted' => 'Tick the box to sign the subscription.']);
        try {
            $id = $this->subscriptions->subscribe($partner, $request->user(), (float) $data['amount'], $data['term'], isset($data['months']) ? (int) $data['months'] : null, $request);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('partner.subscriptions.show', $id)->with('partner_sent', [
            'title' => 'Subscription signed',
            'body' => 'Now send the money and tell us the code below. Your line opens once our team confirms it has arrived.',
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $partner = $this->partner($request);
        $r = $this->subscriptions->find($partner, $id);
        abort_unless($r, 404);
        $line = $r->investment_id ? DB::table('investments')->where('id', $r->investment_id)->first() : null;

        return view('partners.subscribe.show', [
            'partner' => $partner, 'r' => $r, 'line' => $line,
            'cfg' => PartnerFunding::all(), 'reference' => PartnerFunding::reference($partner),
        ]);
    }

    public function sent(Request $request, int $id): RedirectResponse
    {
        $partner = $this->partner($request);
        $data = $request->validate([
            'method' => ['required', 'in:mpesa,bank'],
            'transaction_code' => ['required', 'string', 'max:60'],
            'sent_on' => ['required', 'date', 'before_or_equal:today'],
        ], [], ['transaction_code' => 'transaction code', 'sent_on' => 'date sent']);
        try {
            $this->subscriptions->markSent($partner, $id, $data['transaction_code'], $data['method'], $data['sent_on']);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['transaction_code' => $e->getMessage()]);
        }

        return back()->with('partner_sent', ['title' => 'Thanks, we have let our team know', 'body' => 'They check the statement and confirm it. You will see the line here and on your statement.']);
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        try {
            $this->subscriptions->cancel($this->partner($request), $id);
        } catch (\DomainException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return redirect()->route('partner.subscribe')->with('partner_sent', ['title' => 'Subscription cancelled', 'body' => 'Nothing was sent, so nothing changes.']);
    }

    public function file(Request $request, int $id): BinaryFileResponse
    {
        $r = $this->subscriptions->find($this->partner($request), $id);
        abort_unless($r && ($path = $this->subscriptions->agreementFile($r)), 404);

        return response()->file($path, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="subscription-' . $id . '.pdf"']);
    }

    /** Staff open the signed copy from Requests. */
    public function staffFile(int $id): BinaryFileResponse
    {
        $r = DB::table('partner_requests')->where('id', $id)->first();
        abort_unless($r, 404);
        $r->payload = json_decode((string) $r->payload, true) ?: [];
        abort_unless($path = $this->subscriptions->agreementFile($r), 404);

        return response()->file($path, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="subscription-' . $id . '.pdf"']);
    }

    private function partner(Request $request): Partner
    {
        $partner = $this->account->partnerFor($request->user());
        abort_unless($partner, 404);

        return $partner;
    }
}
