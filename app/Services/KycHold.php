<?php

namespace App\Services;

use App\Http\Controllers\HomeController;
use App\Models\User;

/**
 * Clients on hold until KYC is finished (owner, 21 Nov 2026): "any client that has no full details put them on hold
 * in their UI until they finish kyc", borrowers and partners alike, with what KYC asks for stated in their UI.
 *
 * A client is clear only when staff approved their KYC and every KYC section is complete. Until then the account home
 * shows the on-hold screen and new business is refused (borrow, top-up, partner funds and withdrawals, subscriptions).
 * Paying back and reading their own loans and statements stay open: a hold never stops anyone repaying.
 */
class KycHold
{
    /** @var array<int, array> per-request memo, keyed by user id */
    private array $memo = [];

    /** Staff and other non-client accounts are never held. */
    public function applies(?User $user): bool
    {
        return $user !== null && $user->isCustomer() && $user->hasAnyRole(['borrower', 'partner']);
    }

    public function onHold(?User $user): bool
    {
        return $this->applies($user) && $this->status($user)['state'] !== 'clear';
    }

    public function partnerOnly(User $user): bool
    {
        return $user->hasRole('partner') && ! $user->hasRole('borrower');
    }

    /**
     * state: not_started | in_progress | ready (all filled, not sent) | submitted | rejected | missing (approved, details
     * missing) | clear. next: where to send them to carry on.
     */
    public function status(User $user): array
    {
        // keyed by request too, so a long-lived instance (tests, queue workers) never serves an old answer
        return $this->memo[spl_object_id(request()) . ':' . $user->id] ??= $this->build($user);
    }

    private function build(User $user): array
    {
        $sections = app(HomeController::class)->kycSections($user);
        $done = count(array_filter($sections, fn ($s) => $s['complete']));
        $firstMissing = collect($sections)->firstWhere('complete', false);
        $kyc = $user->kyc_status;

        $state = match (true) {
            $kyc === 'approved' => $firstMissing ? 'missing' : 'clear',
            $kyc === 'submitted' => 'submitted',
            $kyc === 'rejected' => 'rejected',
            $done === count($sections) => 'ready',
            $done > 0 => 'in_progress',
            default => 'not_started',
        };

        return [
            'state' => $state,
            'sections' => $sections,
            'done' => $done,
            'total' => count($sections),
            'next' => $firstMissing['href'] ?? route('kyc.review'),
            'notes' => $state === 'rejected' ? ($user->kyc_review_notes ?: null) : null,
            'emailConfirmed' => $user->hasVerifiedEmail(),
        ];
    }

    /** Short wording of the state, for chips and headings. */
    public static function label(string $state): string
    {
        return [
            'not_started' => 'Not started',
            'in_progress' => 'In progress',
            'ready' => 'Ready to submit',
            'submitted' => 'With our team',
            'rejected' => 'Needs changes',
            'missing' => 'Details missing',
            'clear' => 'Verified',
        ][$state] ?? $state;
    }

    /**
     * What KYC asks for, in the client's words. Titles match HomeController::kycSections() so each line can show
     * done or missing. A partner (not also a borrower) is verified on identity only (owner, 24 Oct 2026).
     */
    public function requirements(User $user): array
    {
        $all = [
            'Personal details' => [
                'Your full name as on your ID',
                'National ID or passport number',
                'Date of birth',
                'KRA PIN',
                'County and home address',
            ],
            'Income & payout' => [
                'How you earn: employer, business or other',
                'Your monthly income after deductions',
                'Your M-Pesa number, for payouts',
            ],
            'Next of kin & guarantor' => [
                'Next of kin: name, relationship and phone',
                'A guarantor: name and phone (email optional)',
                'Neither can be your own number: we call both to confirm',
                'We text your next of kin a link to confirm they know you, with their ID',
                'When you borrow, we text (and email) your guarantor a link to sign and add their ID before we send the money',
            ],
            'Identity documents' => [
                'Photo of your ID, front and back (or your passport bio page)',
                'A clear passport-style photo of you',
                'Three quick selfies: facing the camera, left and right',
                'Your signature, drawn on screen or uploaded',
            ],
        ];

        if ($this->partnerOnly($user)) {
            unset($all['Income & payout'], $all['Next of kin & guarantor']);
        }

        return $all;
    }

    /** The last step, shown under the list. */
    public function finalStep(User $user): array
    {
        return array_values(array_filter([
            'Confirm your email address (we send a link)',
            'Tick the declarations: your details are true, and you are not a politically exposed person',
            $this->partnerOnly($user) ? null : 'Agree to a credit reference bureau (CRB) check',
        ]));
    }

    /** What stays open while on hold, so nobody is stopped from paying. */
    public function stillOpen(User $user): array
    {
        return $this->partnerOnly($user)
            ? ['See your statement and investments', 'Get help from our team']
            : ['Pay any loan you already have', 'See your loans, receipts and statements', 'Get help from our team'];
    }
}
