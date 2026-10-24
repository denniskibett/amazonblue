<?php

namespace App\Services\Partners;

use App\Models\Business;
use App\Models\Partner;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Requests;
use App\Support\CustomerNotifier;
use App\Support\PartnerFunding;
use App\Support\PrivateFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Partner KYC and online subscription (owner, 23 Oct 2026; switch lending.partner_subscriptions.enabled).
 *
 *   1. Verify: the partner's own KYC (the same identity steps and staff review as borrowers, users.kyc_*), their
 *      business for a business partner (the business KYC already used for business loans; partners.business_id),
 *      and where the money comes from (partners.source_of_funds). AML screening is part of the staff review.
 *   2. Subscribe: pick a short-term or long-term line and an amount, read the subscription and sign it with the
 *      signature on file (as a loan agreement is signed). The signed PDF is frozen with its hash.
 *   3. Send the money and enter the M-Pesa or bank code.
 *   4. Staff confirm the money on Requests; that opens the line (PartnerLines::open) and adds the capital.
 *
 * A subscription is a partner_requests row of type funding: what was chosen and the signed copy are in `payload`,
 * the line it opened in `investment_id`. No table of its own.
 */
class Subscription
{
    public const SOURCES = [
        'business_income' => 'Income from my business',
        'salary_savings' => 'Salary or savings',
        'investments' => 'Returns on other investments',
        'asset_sale' => 'Sale of land, property or another asset',
        'inheritance_gift' => 'Inheritance or a gift',
        'other' => 'Other',
    ];

    public static function enabled(): bool
    {
        return (bool) config('lending.partner_subscriptions.enabled') && Requests::enabled();
    }

    public function isBusinessPartner(Partner $partner): bool
    {
        return $partner->type === 'business';
    }

    /** Where the partner is with verification; 'ready' when they may subscribe. */
    public function verification(Partner $partner): array
    {
        $user = $partner->user;
        $kyc = $user?->kyc_status;
        $business = $partner->business_id ? Business::find($partner->business_id) : null;
        $steps = [
            'identity' => [
                'title' => 'Your identity',
                'done' => $kyc === 'approved',
                'status' => ['approved' => 'Verified', 'submitted' => 'With our team', 'rejected' => 'Needs changes'][$kyc] ?? 'Not started',
                'href' => route('kyc.review'),
            ],
        ];
        if ($this->isBusinessPartner($partner)) {
            $steps['business'] = [
                'title' => 'Your business',
                'done' => $business?->status === Business::STATUS_APPROVED,
                'status' => $business ? (Business::STATUSES[$business->status] ?? $business->status) : 'Not linked',
                'href' => $business ? route('account.business.show', $business) : route('account.business.start'),
            ];
        }
        $steps['source'] = [
            'title' => 'Where the money comes from',
            'done' => filled($partner->source_of_funds),
            'status' => self::SOURCES[$partner->source_of_funds] ?? 'Not given',
            'href' => route('partner.verify'),
        ];
        $signature = (bool) ($user?->signature);

        return [
            'steps' => $steps,
            'signature' => $signature,
            'ready' => collect($steps)->every(fn ($s) => $s['done']) && $signature,
        ];
    }

    /** Approved businesses this partner's login owns, for linking. */
    public function ownBusinesses(Partner $partner)
    {
        return Business::where('owner_user_id', $partner->user_id)->orderBy('legal_name')->get();
    }

    public function saveVerification(Partner $partner, string $source, ?int $businessId): void
    {
        if (! array_key_exists($source, self::SOURCES)) {
            throw new \DomainException('Choose where the money comes from.');
        }
        if ($businessId && ! Business::whereKey($businessId)->where('owner_user_id', $partner->user_id)->exists()) {
            throw new \DomainException('That business is not yours.');
        }
        $old = $partner->only(['source_of_funds', 'business_id']);
        $partner->forceFill(['source_of_funds' => $source] + ($this->isBusinessPartner($partner) ? ['business_id' => $businessId] : []))->save();
        AuditTrail::record('partners', $partner->id, 'update', $old, $partner->only(['source_of_funds', 'business_id']));
    }

    /** The two kinds of line, with what this partner earns. */
    public function terms(Partner $partner): array
    {
        $share = (float) ($partner->profit_share_rate ?? 0);

        return [
            'short_term' => [
                'title' => 'Short term',
                'body' => 'Funds one or a few loans. When they are repaid the line closes and your money and earnings are paid back to you.',
                'months' => null,
            ],
            'long_term' => [
                'title' => 'Long term',
                'body' => 'Funds loans until the pay-back date you choose. Principal that comes back is lent again; at the end your money and earnings are paid back.',
                'months' => array_map('intval', (array) config('lending.partner_subscriptions.long_term_months', [6, 12, 24])),
            ],
            'share' => $share,
        ];
    }

    public function maturity(string $term, ?int $months, ?Carbon $from = null): ?string
    {
        return $term === 'long_term' && $months ? ($from ?? now())->copy()->addMonthsNoOverflow($months)->toDateString() : null;
    }

    /** The subscription wording with this partner's figures filled in. */
    public function text(Partner $partner, float $amount, string $term, ?int $months): string
    {
        $template = DB::table('loan_agreement_templates')->where('type', 'partner_subscription')
            ->whereIn('status', ['active', 'published'])->latest('id')->value('content');
        $text = is_string($template) && trim($template) !== '' ? $template : $this->defaultText();
        $terms = $this->terms($partner);
        $maturity = $this->maturity($term, $months);

        return strtr($text, [
            '{{partner_name}}' => $partner->name,
            '{{partner_reference}}' => PartnerFunding::reference($partner),
            '{{amount}}' => 'KES ' . number_format($amount, 2),
            '{{term}}' => $terms[$term]['title'] ?? $term,
            '{{term_detail}}' => $term === 'long_term' ? $months . ' months, to be paid back from ' . Carbon::parse($maturity)->format('j F Y') : 'until the loans it funds are repaid',
            '{{profit_share}}' => rtrim(rtrim(number_format($terms['share'], 2), '0'), '.') . '%',
            '{{date}}' => now()->format('j F Y'),
            '{{company}}' => 'AIMZONE VENTURES',
        ]);
    }

    /** Sign and store the subscription; returns the partner_requests id. */
    public function subscribe(Partner $partner, User $user, float $amount, string $term, ?int $months, Request $request): int
    {
        if (! self::enabled()) {
            throw new \DomainException('Online subscription is not open yet.');
        }
        if (! $this->verification($partner)['ready']) {
            throw new \DomainException('Finish verification first.');
        }
        $terms = $this->terms($partner);
        if (! array_key_exists($term, PartnerLines::TERMS)) {
            throw new \DomainException('Choose short term or long term.');
        }
        if ($term === 'long_term' && ! in_array((int) $months, $terms['long_term']['months'], true)) {
            throw new \DomainException('Choose how long the money stays in.');
        }
        $amount = round($amount, 2);
        if ($amount < PartnerFunding::minAmount()) {
            throw new \DomainException('The smallest amount is KES ' . number_format(PartnerFunding::minAmount(), 2) . '.');
        }
        if ($this->open($partner)->isNotEmpty()) {
            throw new \DomainException('You already have a subscription waiting for its money. Finish or cancel that one first.');
        }
        $months = $term === 'long_term' ? (int) $months : null;

        return DB::transaction(function () use ($partner, $user, $amount, $term, $months, $request) {
            [$relative, $hash] = $this->render($partner, $user, $amount, $term, $months, $request);
            $payload = [
                'kind' => 'subscription', 'term' => $term, 'months' => $months, 'maturity' => $this->maturity($term, $months),
                'version' => config('lending.partner_subscriptions.version'), 'agreement_path' => $relative, 'agreement_sha256' => $hash,
                'signed_at' => now()->toDateTimeString(), 'ip' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ];
            $id = DB::table('partner_requests')->insertGetId([
                'type' => 'funding', 'partner_id' => $partner->id, 'amount' => $amount, 'note' => 'Online subscription',
                'payload' => json_encode($payload), 'status' => 'pending', 'requested_by' => $user->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            AuditTrail::record('partner_requests', $id, 'create', null, ['event' => 'subscription_signed', 'amount' => $amount, 'term' => $term, 'months' => $months, 'sha256' => $hash]);

            return $id;
        });
    }

    /** The partner says the money has gone: the code staff will look for on the statement. */
    public function markSent(Partner $partner, int $id, string $code, string $method, string $sentOn): void
    {
        $code = strtoupper(trim($code));
        if ($code === '' || ! in_array($method, ['mpesa', 'bank'], true)) {
            throw new \DomainException('Enter how you sent it and the transaction code.');
        }
        if (app(PartnerLines::class)->codeUsed($code)
            || DB::table('partner_requests')->where('reference', $code)->whereIn('status', ['pending', 'prepared'])->where('id', '!=', $id)->exists()) {
            throw new \DomainException("{$code} has already been used. Check the code.");
        }
        DB::transaction(function () use ($partner, $id, $code, $method, $sentOn) {
            $r = DB::table('partner_requests')->where('id', $id)->where('partner_id', $partner->id)->lockForUpdate()->first();
            $payload = $r ? (json_decode((string) $r->payload, true) ?: []) : [];
            if (! $r || ($payload['kind'] ?? null) !== 'subscription' || $r->status !== 'pending') {
                throw new \DomainException('This subscription is no longer waiting for money.');
            }
            $payload['sent'] = ['method' => $method, 'on' => $sentOn, 'at' => now()->toDateTimeString()];
            DB::table('partner_requests')->where('id', $id)->update(['reference' => $code, 'payload' => json_encode($payload), 'updated_at' => now()]);
            AuditTrail::record('partner_requests', $id, 'update', ['reference' => $r->reference], ['reference' => $code, 'method' => $method, 'sent_on' => $sentOn]);
            $amount = (float) $r->amount;
            DB::afterCommit(fn () => CustomerNotifier::partnerRequest($partner,
                'Partner subscription paid: ' . $partner->name . ' (KES ' . number_format($amount, 2) . ')',
                $partner->name . ' signed a ' . str_replace('_', ' ', $payload['term']) . ' subscription for KES ' . number_format($amount, 2)
                    . ' and says they sent it by ' . ($method === 'bank' ? 'bank transfer' : 'M-Pesa') . ' on ' . $sentOn . ', code ' . $code
                    . '. Check the statement, then confirm it on Requests: that opens the line.'));
        });
    }

    public function cancel(Partner $partner, int $id): void
    {
        $n = DB::table('partner_requests')->where('id', $id)->where('partner_id', $partner->id)->where('status', 'pending')
            ->whereNull('reference')->update(['status' => 'declined', 'decision_note' => 'Cancelled by the partner', 'updated_at' => now()]);
        if (! $n) {
            throw new \DomainException('Only a subscription with no money sent yet can be cancelled.');
        }
        AuditTrail::record('partner_requests', $id, 'update', ['status' => 'pending'], ['status' => 'declined', 'by' => 'partner']);
    }

    /** The partner's subscriptions, newest first, with the payload decoded. */
    public function forPartner(Partner $partner)
    {
        // decoded here rather than matched as text: MySQL stores JSON reformatted, MariaDB as written
        return DB::table('partner_requests')->where('partner_id', $partner->id)->where('type', 'funding')
            ->whereNotNull('payload')->latest('id')->get()
            ->map(function ($r) {
                $r->payload = json_decode((string) $r->payload, true) ?: [];

                return $r;
            })
            ->filter(fn ($r) => ($r->payload['kind'] ?? null) === 'subscription')->values();
    }

    public function open(Partner $partner)
    {
        return $this->forPartner($partner)->where('status', 'pending')->values();
    }

    public function find(Partner $partner, int $id): ?object
    {
        return $this->forPartner($partner)->firstWhere('id', $id);
    }

    /** Absolute path of the signed copy, or null. */
    public function agreementFile(object $r): ?string
    {
        return ! empty($r->payload['agreement_path']) ? PrivateFiles::absolute($r->payload['agreement_path']) : null;
    }

    private function render(Partner $partner, User $user, float $amount, string $term, ?int $months, Request $request): array
    {
        $pdf = new \TCPDF();
        $pdf->SetCreator('AIMZONE VENTURES');
        $pdf->SetTitle('Partner subscription - ' . $partner->name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(18, 18, 18);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(0, 10, 'Partner subscription', 0, 1);
        $pdf->SetFont('helvetica', '', 10.5);
        foreach (preg_split("/\n{2,}/", trim($this->text($partner, $amount, $term, $months))) as $para) {
            $pdf->MultiCell(0, 0, trim($para), 0, 'L', false, 1);
            $pdf->Ln(2.5);
        }
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->Cell(0, 6, 'Signed by the partner', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $y = $pdf->GetY();
        if ($sig = PrivateFiles::absolute((string) $user->signature)) {
            $pdf->Image($sig, 18, $y, 45, 18);
            $pdf->SetY($y + 20);
        }
        $pdf->MultiCell(0, 0, $user->name . ($user->id_number ? ' · ID ' . $user->id_number : '') . "\n"
            . 'Signed ' . now('Africa/Nairobi')->format('j F Y, H:i') . ' (Nairobi) from ' . $request->ip() . "\n"
            . 'Wording version ' . config('lending.partner_subscriptions.version'), 0, 'L', false, 1);

        $contents = $pdf->Output('', 'S');
        $relative = 'subscriptions/partner_' . $partner->id . '_' . Str::random(16) . '.pdf';
        if (! Storage::disk(PrivateFiles::DISK)->put($relative, $contents)) {
            throw new \RuntimeException('Could not save the signed subscription.');
        }

        return [$relative, hash('sha256', $contents)];
    }

    private function defaultText(): string
    {
        return <<<'TXT'
This subscription is made on {{date}} between {{company}} ("the Company") and {{partner_name}} (partner {{partner_reference}}).

1. Amount. The partner will send {{amount}} to the Company's account quoting their partner reference. The subscription starts on the day the Company confirms the money has arrived.

2. Line. The money is a {{term}} funding line ({{term_detail}}). The Company lends it to its borrowers under its usual lending rules and keeps a record of every loan the line funds.

3. Earnings. The partner earns {{profit_share}} of the interest collected on the loans the line funds, worked out on every repayment and shown on the partner's statement.

4. Losses. If a borrower does not repay, the loss is shown on the line and is not hidden. What happens to a shortfall is set out on the payout when the line closes.

5. Pay-back. When the line closes, the partner's money and earnings, less any loss, wait on the Company's payout list and are sent to the partner's registered bank account or M-Pesa number.

6. Verification. The partner confirms the identity details and the source of funds they gave are true, and that the money is not the proceeds of crime. The Company may refuse or return money it cannot verify.

7. Records. The partner can see the line, its loans and their earnings in their account at any time. This signed copy is kept with a fingerprint (hash) so it cannot be changed.
TXT;
    }
}
