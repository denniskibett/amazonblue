<?php

namespace App\Services\Collections;

use App\Models\CollectionAgency;
use App\Models\CollectionContact;
use App\Models\CollectionPlacement;
use App\Models\Loan;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\LedgerReports;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Outside debt collection agencies (owner, 3 Nov 2026).
 *
 * Staff place late loans (overdue, defaulted, recovery, forbearance) with an agency until an end date. A collector
 * sees only the open placements of their own agency. What they see is the data a collector needs and the borrower
 * agreed to share: name, ID (masked), phones, next of kin and referee (to reach the borrower only), employer, what is
 * owed, payments and promises. ID photos, selfies and KPLC meters are never shown. Contact details appear only after
 * the borrower was told which agency will call and the notice period has run.
 */
class AgencyPlacements
{
    public function __construct(private LedgerReports $ledger) {}

    /** Loans staff may place: late or in forbearance, and not already with an agency. */
    public function placeable(): Collection
    {
        $taken = CollectionPlacement::open()->pluck('loan_id');

        return Loan::with(['user:id,name,phone', 'loanType:id,name'])
            ->whereIn('status', config('lending.collections.placeable_statuses'))
            ->whereNotIn('id', $taken)->orderBy('id')->get()
            ->map(fn (Loan $loan) => (object) ['loan' => $loan, 'owed' => $this->owed($loan->id), 'days' => $this->daysLate($loan)]);
    }

    /** @param int[] $loanIds */
    public function place(CollectionAgency $agency, array $loanIds, Carbon $endsOn, ?Carbon $noticeOn, User $by, ?string $note = null): int
    {
        $allowed = Loan::whereIn('id', $loanIds)->whereIn('status', config('lending.collections.placeable_statuses'))->pluck('id');
        $taken = CollectionPlacement::open()->whereIn('loan_id', $allowed)->pluck('loan_id');
        $n = 0;
        DB::transaction(function () use ($agency, $allowed, $taken, $endsOn, $noticeOn, $by, $note, &$n) {
            foreach ($allowed->diff($taken) as $loanId) {
                $p = CollectionPlacement::create(['agency_id' => $agency->id, 'loan_id' => $loanId, 'placed_by' => $by->id, 'placed_at' => now(),
                    'notice_sent_on' => $noticeOn?->toDateString(), 'ends_on' => $endsOn->toDateString(), 'status' => 'active', 'note' => $note]);
                AuditTrail::record('collection_placements', $p->id, 'create', null, ['loan_id' => $loanId, 'agency' => $agency->name, 'ends_on' => $p->ends_on->toDateString()]);
                $n++;
            }
        });

        return $n;
    }

    public function takeBack(CollectionPlacement $p, User $by): void
    {
        $p->update(['status' => 'returned', 'returned_at' => now(), 'returned_by' => $by->id]);
        AuditTrail::record('collection_placements', $p->id, 'update', ['status' => 'active'], ['status' => 'returned']);
    }

    /** The open placements a collector may work, newest first. */
    public function forCollector(User $user): Collection
    {
        $agency = CollectionAgency::of($user);
        if (! $agency || $agency->status !== 'active') {
            return collect();
        }

        return CollectionPlacement::open()->where('agency_id', $agency->id)->with(['loan.user:id,name,phone', 'loan.loanType:id,name'])
            ->withMax('contacts as last_contact_at', 'created_at')->latest('placed_at')->get()
            ->map(fn (CollectionPlacement $p) => (object) ['placement' => $p, 'loan' => $p->loan, 'owed' => $this->owed($p->loan_id), 'days' => $this->daysLate($p->loan)]);
    }

    /** A placement the collector may open, or null (another agency's, returned or past its end). */
    public function findFor(User $user, int $placementId): ?CollectionPlacement
    {
        $agency = CollectionAgency::of($user);

        return $agency && $agency->status === 'active'
            ? CollectionPlacement::open()->where('agency_id', $agency->id)->whereKey($placementId)->with(['loan.user.borrower', 'loan.loanType', 'agency'])->first()
            : null;
    }

    /** Everything the collector may see on one placed loan. */
    public function profile(CollectionPlacement $p): array
    {
        $loan = $p->loan;
        $u = $loan->user;
        $b = $u?->borrower;
        $bal = $this->ledger->balance($loan->id);
        $contact = $p->contactAllowed();
        $hide = fn ($v) => $contact ? $v : null;

        return [
            'identity' => [
                'Name' => $u?->name,
                'ID number' => $u?->id_number ? mb_substr($u->id_number, 0, 1) . str_repeat('•', max(0, mb_strlen($u->id_number) - 3)) . mb_substr($u->id_number, -2) : null,
                'Born' => $u?->dob?->format('Y'),
            ],
            'reach' => [
                'Phone' => $hide($u?->phone),
                'Email' => $hide($u?->email),
                'Next of kin' => $hide($u?->kin_name ? trim($u->kin_name . ' · ' . ($u->kin_relation ?? '') . ' · ' . ($u->kin_phone ?? ''), ' ·') : null),
            ],
            'work' => [
                'Employer' => $b?->employer_name ?: $b?->workplace,
                'Job' => $b?->job_title,
                'Employer email' => $hide($b?->employer_email),
                'Pay day' => $b?->pay_day ? 'Day ' . $b->pay_day : null,
            ],
            'owed' => [
                'Principal' => (float) ($bal['unpaid_principal'] ?? 0),
                'Interest' => (float) ($bal['unpaid_interest'] ?? 0),
                'Late penalty' => (float) ($bal['unpaid_penalty'] ?? 0),
                'Owed today' => (float) ($bal['final_outstanding'] ?? 0),
            ],
            'status' => $loan->status,
            'forbearance_until' => $loan->forbearance_until,
            'days_late' => $this->daysLate($loan),
            'due' => $loan->getDueDate(),
            'payments' => DB::table('repayments')->where('loan_id', $loan->id)->whereNull('deleted_at')->orderByDesc('repayment_date')->limit(10)
                ->get(['repayment_date', 'amount', 'mpesa_ref']),
            'our_promises' => DB::table('recovery_actions as a')->join('debt_recovery_cases as c', 'c.id', '=', 'a.case_id')->where('c.loan_id', $loan->id)
                ->whereNotNull('a.promised_date')->orderByDesc('a.action_date')->limit(5)->get(['a.action_date', 'a.outcome', 'a.promised_date']),
            'contacts' => CollectionContact::with('user:id,name')->where('loan_id', $loan->id)->latest()->limit(30)->get(),
            'contact_allowed' => $contact,
            'contact_from' => $p->contactFrom(),
        ];
    }

    public function logContact(CollectionPlacement $p, User $by, array $data): CollectionContact
    {
        $c = CollectionContact::create(['placement_id' => $p->id, 'loan_id' => $p->loan_id, 'user_id' => $by->id] + $data);
        AuditTrail::record('collection_contacts', $c->id, 'create', null, ['loan_id' => $p->loan_id, 'channel' => $c->channel, 'outcome' => $c->outcome]);

        return $c;
    }

    private function owed(int $loanId): float
    {
        return round((float) ($this->ledger->balance($loanId)['final_outstanding'] ?? 0), 2);
    }

    private function daysLate(Loan $loan): int
    {
        $due = $loan->getDueDate();

        return $due && $due->isPast() ? (int) floor($due->diffInDays(now())) : 0;
    }
}
