<?php

namespace App\Services\Messaging;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use App\Services\Loans\CycleBalance;
use App\Support\Emails;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Collection notices sent in batches a person approves (owner, 23 Oct 2026): who should get each kind, and the
 * wording of the email and the SMS. Nothing here sends; Staff\MessagesController builds and sends the batches.
 *
 *   due_soon      open loans not yet late, due within the next N days                   (firm reminder)
 *   overdue       1 to 30 days late                                                    (stern warning)
 *   final_notice  more than 30 days late, defaulted or in recovery: clear or set up a plan (corporate final notice)
 *
 * Owed is today's balance from the cycle engine (penalty included). Loans in forbearance are left out unless asked:
 * they have an arrangement. Merged legacy loans count once, under the loan they were merged into.
 */
class CollectionNotices
{
    public const KINDS = [
        'due_soon' => ['label' => 'Due soon', 'tone' => 'blue', 'about' => 'Open loans due in the next few days. A firm reminder to pay on time.'],
        'overdue' => ['label' => 'Overdue (1–30 days)', 'tone' => 'amber', 'about' => 'Loans up to 30 days late. A stern warning: pay now or ask for a plan.'],
        'final_notice' => ['label' => 'Final notice (30+ days, defaulted)', 'tone' => 'red', 'about' => 'Old and defaulted loans. Clear the balance or log in to set up a payment plan within 7 days.'],
        'complete_profile' => ['label' => 'Complete your profile', 'tone' => 'purple', 'about' => 'Verified borrowers missing their income, employer or pay schedule. Asks them to fill it in from their account.'],
    ];

    /** Kinds about a person, not a loan: rows are keyed by user (owner, 23 Oct 2026). */
    public const PERSON_KINDS = ['complete_profile'];

    public const OPEN = [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY];

    /**
     * Who would get this kind of notice today. Each row: user, loan, owed, days_late, due_date, email, phone,
     * last_sent (the last time this kind went to them), and why it would be skipped (no email, sent recently).
     *
     * @return Collection<int, object>
     */
    public function candidates(string $kind, int $dueWithin = 3, bool $includeForbearance = false, int $cooldownDays = 3): Collection
    {
        abort_unless(isset(self::KINDS[$kind]), 404);
        if (in_array($kind, self::PERSON_KINDS, true)) {
            return $this->people($kind, max($cooldownDays, 7));
        }
        $statuses = self::OPEN;
        if ($includeForbearance) {
            $statuses[] = Loan::STATUS_FORBEARANCE;
        }
        $q = Loan::with(['user:id,name,email,phone,deleted_at', 'loanType:id,name'])->whereIn('status', $statuses);
        if (\Illuminate\Support\Facades\Schema::hasColumn('loans', 'merged_into_loan_id')) {
            $q->whereNull('merged_into_loan_id');
        }
        $lastSent = DB::table('message_batch_items as i')->join('message_batches as b', 'b.id', '=', 'i.batch_id')
            ->where('b.kind', $kind)->where('i.status', 'sent')->groupBy('i.loan_id')
            ->selectRaw('i.loan_id, max(i.sent_at) as at')->pluck('at', 'loan_id');
        return $q->get()->map(function (Loan $loan) use ($lastSent, $cooldownDays) {
            $r = $this->row($loan);
            if (! $r) {
                return null;
            }
            $last = $lastSent[$loan->id] ?? null;
            $r->last_sent = $last;
            $r->skip = empty($loan->user->email) ? 'no email on file'
                : ($last && Carbon::parse($last)->gt(now()->subDays($cooldownDays)) ? 'sent ' . Carbon::parse($last)->diffForHumans() : null);

            return $r;
        })->filter()->filter(fn ($r) => $this->fits($kind, $r, $dueWithin))
            ->sortByDesc(fn ($r) => $kind === 'due_soon' ? -($r->due_in ?? 0) : $r->days_late)->values();
    }

    /** One loan's notice figures today, or null when this kind no longer applies (e.g. paid since the batch). */
    public function current(string $kind, Loan $loan, int $dueWithin = 30): ?object
    {
        $loan->loadMissing(['user:id,name,email,phone', 'loanType:id,name']);
        $r = $this->row($loan);

        return $r && $this->fits($kind, $r, $dueWithin) ? $r : null;
    }

    private function row(Loan $loan): ?object
    {
        $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
        if (! $cycle || ! $loan->user) {
            return null;
        }
        $owed = round((float) (CycleBalance::at($cycle)['final_outstanding'] ?? 0), 2);
        if ($owed <= 0) {
            return null;
        }
        $today = now()->startOfDay();
        $due = Carbon::parse($cycle->due_date)->startOfDay();

        return (object) [
            'loan' => $loan, 'user' => $loan->user, 'owed' => $owed,
            'days_late' => $due->lt($today) ? (int) $due->diffInDays($today) : 0,
            'due_in' => $due->gte($today) ? (int) $today->diffInDays($due) : null,
            'due_date' => $due, 'status' => $loan->status, 'last_sent' => null, 'skip' => null,
        ];
    }

    private function fits(string $kind, object $r, int $dueWithin): bool
    {
        $late = in_array($r->status, [Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY], true);

        return match ($kind) {
            'due_soon' => $r->due_in !== null && $r->due_in <= $dueWithin,
            'overdue' => $r->days_late >= 1 && $r->days_late <= 30 && ! $late,
            'final_notice' => $r->days_late > 30 || $late,
            default => false,
        };
    }

    /**
     * Verified borrowers with something missing from their profile (borrowers::missing()). Each row: key (user id),
     * user, missing, last_sent, skip. Not sent again within $cooldownDays.
     */
    public function people(string $kind, int $cooldownDays = 7): Collection
    {
        $last = DB::table('message_batch_items as i')->join('message_batches as b', 'b.id', '=', 'i.batch_id')
            ->where('b.kind', $kind)->where('i.status', 'sent')->groupBy('i.user_id')
            ->selectRaw('i.user_id, max(i.sent_at) as at')->pluck('at', 'user_id');

        return User::role('borrower')->whereNull('deleted_at')->where('kyc_status', 'approved')->with('borrower')->orderBy('name')->get()
            ->reject(fn (User $u) => $u->isStaff())
            ->map(function (User $u) use ($last, $cooldownDays) {
                $missing = $u->borrower ? $u->borrower->missing() : ['income type', 'net income', 'employer or business', 'pay schedule'];
                if (! $missing) {
                    return null;
                }
                $at = $last[$u->id] ?? null;

                return (object) ['key' => $u->id, 'user' => $u, 'loan' => null, 'missing' => $missing, 'last_sent' => $at,
                    'skip' => empty($u->email) ? 'no email on file' : ($at && Carbon::parse($at)->gt(now()->subDays($cooldownDays)) ? 'sent ' . Carbon::parse($at)->diffForHumans() : null)];
            })->filter()->values();
    }

    /** The "complete your profile" email. */
    public function personEmail(string $kind, User $user, array $missing): array
    {
        $first = Emails::firstName($user->name);

        return [
            'subject' => 'Please complete your Aimzone profile',
            'preheader' => 'Tell us your ' . implode(', ', $missing) . '.',
            'badge' => ['Two minutes', 'info'],
            'heading' => 'Help us set the right limit for you',
            'paragraphs' => [
                "Dear {$first}, a few details are missing from your Aimzone profile: " . implode(', ', $missing) . '.',
                'We use them to set your borrowing limit and to send reminders around your payday rather than before it. It takes about two minutes.',
            ],
            'buttons' => [['Complete my profile', route('account.details'), true]],
            'signoff' => ['Regards,', 'The AIMZONE Ventures team'],
        ];
    }

    public function personSms(string $kind, User $user, array $missing): string
    {
        return 'Aimzone: please complete your profile (' . implode(', ', $missing) . ') so we can set your limit and time your reminders. Log in: ' . route('account.details');
    }

    /** The subject line shown on the batch. */
    public static function subject(string $kind): string
    {
        return ['due_soon' => 'Repayment due soon', 'overdue' => 'Overdue repayment: action required', 'final_notice' => 'Final notice before recovery action',
            'complete_profile' => 'Please complete your profile'][$kind];
    }

    /** The branded email for one recipient (Support\Emails layout). */
    public function email(string $kind, User $user, Loan $loan, float $owed, int $daysLate, Carbon $due): array
    {
        $first = Emails::firstName($user->name);
        $kes = 'KES ' . number_format($owed, 2);
        $ref = 'Loan #' . $loan->id;
        $dueText = $due->format('j F Y');
        $loanUrl = route('account.loan', $loan);
        $planUrl = \Illuminate\Support\Facades\Route::has('account.plan') ? route('account.plan', $loan) : $loanUrl;
        $rows = [['Loan', $ref . ($loan->loanType ? ' · ' . $loan->loanType->name : '')], ['Due date', $dueText]];
        $paybill = config('lending.mpesa.paybill');
        $payBy = [$paybill ? ['Pay by M-Pesa:', "Paybill {$paybill}, account {$loan->id}"] : ['Pay by M-Pesa:', 'see the Repay page in your account']];

        return match ($kind) {
            'due_soon' => [
                'subject' => "Repayment of {$kes} due on {$dueText}",
                'preheader' => "Loan #{$loan->id}: {$kes} is due on {$dueText}.",
                'badge' => [$due->isToday() ? 'Due today' : 'Due on ' . $due->format('j M'), 'warn'],
                'heading' => "Your repayment is due on {$dueText}",
                'paragraphs' => [
                    "Dear {$first}, this is a reminder that {$kes} on your {$ref} falls due on {$dueText}.",
                    'Please pay on or before the due date. A late payment attracts a daily penalty under your loan agreement and lowers the limit available to you next time.',
                ],
                'panel' => ['label' => 'Amount due', 'amount' => $kes, 'rows' => $rows],
                'info' => [...$payBy, ['', 'If you have already paid, thank you. You can ignore this message.']],
                'buttons' => [['Pay now', $loanUrl, true], ['View my loan', $loanUrl, false]],
                'signoff' => ['Regards,', 'The AIMZONE Ventures team'],
            ],
            'overdue' => [
                'subject' => "Overdue: {$ref} is {$daysLate} " . ($daysLate === 1 ? 'day' : 'days') . ' past due',
                'preheader' => "{$kes} was due on {$dueText} and remains unpaid.",
                'badge' => [$daysLate . ' ' . ($daysLate === 1 ? 'day' : 'days') . ' overdue', 'danger'],
                'heading' => 'Your repayment is overdue',
                'paragraphs' => [
                    "Dear {$first}, our records show that your {$ref} was due on {$dueText} and {$kes} remains unpaid.",
                    'A penalty is added for every day the balance stays unpaid. Clear the full amount now to stop further charges and protect your borrowing record with us.',
                    'If you cannot pay in full, log in today and request a payment plan. Contact us before the balance grows further.',
                ],
                'panel' => ['label' => 'Amount outstanding today', 'amount' => $kes, 'rows' => [...$rows, ['Days overdue', (string) $daysLate]]],
                'info' => [...$payBy, ['', 'Already paid? Reply with your M-Pesa code so we can match it to your loan.']],
                'buttons' => [['Pay now', $loanUrl, true], ['Request a payment plan', $planUrl, false]],
                'signoff' => ['Regards,', 'Credit Control, AIMZONE Ventures'],
            ],
            'final_notice' => [
                'subject' => "Final notice: {$kes} outstanding on {$ref}",
                'preheader' => "Clear the balance or set up a payment plan within 7 days.",
                'badge' => ['Final notice', 'danger'],
                'heading' => 'Final notice before recovery action',
                'paragraphs' => [
                    "Dear {$first}, despite earlier reminders, {$kes} remains outstanding on your {$ref}. The loan fell due on {$dueText} and is now {$daysLate} days in arrears.",
                    'Within 7 days of this notice, please either clear the balance in full, or log in to your account and set up a payment plan that we can agree with you.',
                    'If we do not hear from you within that time, your account will be passed to our recovery team and we will take further steps to recover the amount owed in line with your loan agreement. This will also affect your ability to borrow from us in future.',
                ],
                'panel' => ['label' => 'Amount outstanding today', 'amount' => $kes, 'rows' => [...$rows, ['Days in arrears', (string) $daysLate]]],
                'info' => [...$payBy, ['', 'Already paid, or disputing this amount? Contact us with your M-Pesa code or details so we can review it.']],
                'buttons' => [['Set up a payment plan', $planUrl, true], ['Pay now', $loanUrl, false]],
                'signoff' => ['Yours faithfully,', 'Credit Control, AIMZONE Ventures'],
            ],
        };
    }

    /** The SMS for one recipient (kept under 160 characters where it can be). */
    public function sms(string $kind, Loan $loan, float $owed, int $daysLate, Carbon $due): string
    {
        $kes = 'KES ' . number_format($owed, 2);
        $paybill = config('lending.mpesa.paybill');
        $pay = $paybill ? " Paybill {$paybill}, acc {$loan->id}." : ' Pay via your account.';

        return match ($kind) {
            'due_soon' => "Aimzone: {$kes} on loan {$loan->id} is due " . ($due->isToday() ? 'TODAY' : 'on ' . $due->format('j M')) . ". Pay on time to avoid penalty.{$pay}",
            'overdue' => "Aimzone: loan {$loan->id} is {$daysLate} days overdue. {$kes} due now; penalty is added daily. Pay today or log in to request a plan.{$pay}",
            'final_notice' => "Aimzone FINAL NOTICE: {$kes} unpaid on loan {$loan->id}. Clear it or log in to set up a payment plan within 7 days, or it goes to recovery.",
        };
    }
}
