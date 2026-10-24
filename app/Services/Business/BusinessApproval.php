<?php

namespace App\Services\Business;

use App\Models\Business;
use App\Models\User;
use App\Services\Aml\Screening;
use App\Services\Approvals;
use App\Services\AuditTrail;
use App\Support\CustomerNotifier;
use App\Support\Handle;
use Illuminate\Support\Facades\DB;

/**
 * Approving a business (owner, 23 Oct 2026; plan defaults when business loans are on):
 *  - the registration number is not on another business under review or approved
 *  - a limited company lists 100% of its shareholding
 *  - bank statements are on file (staff tick that they cover the last 3 months)
 *  - every owner, director and signatory has verified themselves (DirectorVerification)
 *  - AML screening is clear (when screening is on)
 *  - the approver sets the business's limit
 *  - with second sign-offs on, a second person with `edit users` approves (Approvals action approve_business)
 * With business loans off, approval works as before (documents + the four checks + AML when on).
 */
class BusinessApproval
{
    /** Why the business can't be approved yet (empty = it can). */
    public function blockers(Business $business, ?float $limit = null): array
    {
        $business->loadMissing('members', 'documents');
        $out = [];
        if ($missing = $business->missingDocumentTypes()) {
            $out[] = 'Required documents are missing: ' . collect($missing)->map(fn ($t) => \App\Models\BusinessDocument::TYPES[$t][0] ?? $t)->implode(', ') . '.';
        }
        if (BusinessLending::enabled()) {
            if ($business->registration_number && Business::where('registration_number', $business->registration_number)->whereKeyNot($business->id)
                ->whereIn('status', [Business::STATUS_SUBMITTED, Business::STATUS_APPROVED])->exists()) {
                $out[] = 'Registration number ' . $business->registration_number . ' is already on another business.';
            }
            if ($business->isLimitedCompany() && abs($business->ownershipTotal() - 100) > 0.01) {
                $out[] = 'The shareholders listed own ' . rtrim(rtrim(number_format($business->ownershipTotal(), 2), '0'), '.') . '%, not 100%.';
            }
            if (! $business->documents->contains('type', 'bank_statement')) {
                $out[] = 'Bank statements for the last ' . (int) config('lending.business_loans.statement_months', 3) . ' months are not uploaded.';
            }
            if ($names = app(DirectorVerification::class)->unverified($business)->pluck('full_name')->all()) {
                $out[] = 'Not verified yet: ' . implode(', ', $names) . '. Send them their link.';
            }
            if ($limit !== null && $limit <= 0) {
                $out[] = 'Set the business limit.';
            }
        }
        if ($aml = app(Screening::class)->blocker($business)) {
            $out[] = $aml;
        }

        return $out;
    }

    /** Approve now, or open a second sign-off. Returns 'approved' or 'second'. */
    public function approve(Business $business, User $by, ?float $limit, ?string $notes): string
    {
        if ($business->status !== Business::STATUS_SUBMITTED) {
            throw new \DomainException('Only submitted businesses can be approved.');
        }
        if (BusinessLending::enabled() && ! $limit) {
            throw new \DomainException('Set the business limit.');
        }
        if ($b = $this->blockers($business, $limit)) {
            throw new \DomainException($b[0]);
        }
        $approvals = app(Approvals::class);
        if (BusinessLending::enabled() && $approvals->needsSecond($by, 'approve_business', $limit)) {
            $approvals->request($by, 'approve_business', 'businesses', $business->id, $limit, ['credit_limit' => $limit, 'notes' => $notes]);

            return 'second';
        }
        $this->perform($business, $by, $limit, $notes, null);

        return 'approved';
    }

    /** The approval itself (now, or when the second person approves). */
    public function perform(Business $business, User $checker, ?float $limit, ?string $notes, ?User $maker): Business
    {
        if ($business->status !== Business::STATUS_SUBMITTED) {
            throw new \DomainException($business->legal_name . ' is ' . $business->status . ', no longer waiting for approval.');
        }
        if ($b = $this->blockers($business->fresh(), $limit)) {
            throw new \DomainException($b[0]);
        }
        DB::transaction(function () use ($business, $checker, $limit, $notes, $maker) {
            $kept = is_array($business->checks) ? array_intersect_key($business->checks, ['aml' => true]) : [];
            $business->update([
                'status' => Business::STATUS_APPROVED,
                'reviewed_by' => $checker->id,
                'reviewed_at' => now(),
                'review_notes' => trim(($notes ?? '') . ($maker ? "\nApproved by " . $maker->name . ' and ' . $checker->name . ' (second sign-off).' : '')) ?: null,
                'checks' => array_fill_keys(array_keys(Business::checks()), true) + $kept,
                'credit_limit' => $limit ?? $business->credit_limit,
            ]);
            if (! $business->handle && ($handle = Handle::forBusiness($business))) {
                $business->update(['handle' => $handle]);
            }
            // people on the CR12 who are verified borrowers count as verified members
            $business->members()->whereNotNull('user_id')->whereNull('verified_at')->update(['verified_at' => now()]);
            AuditTrail::record('businesses', $business->id, 'update', ['status' => Business::STATUS_SUBMITTED], ['status' => Business::STATUS_APPROVED, 'credit_limit' => $limit]);
        });
        CustomerNotifier::businessReviewed($business, true);

        return $business;
    }
}
