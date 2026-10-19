<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\DebtRecoveryCase;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanCalculator;
use Illuminate\Support\Carbon;

/**
 * What a borrower is told about a loan in each state (under review, approved, rejected, overdue,
 * defaulted / in recovery, written off, payment plan): a title, a plain message, "what happens next"
 * and the actions they can take. Read-only: figures come from LoanProgress / LoanCalculator unchanged,
 * the rejection reason from the "Rejected ...: reason" note staff leave in loans.recovery_notes, and the
 * recovery status from the borrower's own DebtRecoveryCase (no amounts shown from the case).
 * Used by home.account.partials.loan-state (account home and loan detail).
 */
class BorrowerLoanState
{
    /** Interim requests (no table until Stage 4) are audit rows with this table name. */
    public const REQUESTS = 'borrower_requests';

    public const CONTACT = [
        'phone' => '+254 701 607 959',
        'tel' => '+254701607959',
        'whatsapp' => 'https://wa.me/254701607959',
        'email' => 'hello@aimzoneventures.com',
    ];

    /** Statuses a borrower can ask to extend (the ones LoanCalculator::executeRollover accepts). */
    public const EXTENDABLE = ['disbursed', 'active', 'overdue'];

    /**
     * The loan the account home should talk about: the open loan, else the latest one when it ended
     * in a way the borrower needs to hear about (rejected in the last 30 days, written off).
     */
    public static function homeLoan(User $user, ?Loan $openLoan): ?Loan
    {
        if ($openLoan) {
            return $openLoan;
        }
        $latest = Loan::where('user_id', $user->id)->latest('id')->first();
        if (! $latest) {
            return null;
        }
        if ($latest->status === 'written_off') {
            return $latest;
        }
        if ($latest->status === 'rejected' && $latest->updated_at && $latest->updated_at->gt(now()->subDays(30))) {
            return $latest;
        }

        return null;
    }

    /**
     * @param  array  $progress  LoanProgress::for($loan)
     * @return array|null  null when the normal loan screens say it all (active and on time, repaid)
     */
    public static function for(Loan $loan, array $progress, bool $onHome = false): ?array
    {
        $status = $loan->status === 'active' ? 'disbursed' : $loan->status;
        $date = fn ($d) => $d ? Carbon::parse($d)->format('j M Y') : null;
        $due = $progress['due_date'] ?? null;
        $daysLate = $due ? max(0, (int) Carbon::parse($due)->startOfDay()->diffInDays(now()->startOfDay(), false)) : 0;
        $penaltyRate = (float) ($loan->loanType->penalty_rate ?? 0);
        $help = route('account.help', ['loan' => $loan->id]);

        if (in_array($status, ['disbursed', 'overdue'], true) && $progress['is_late']) {
            $status = 'overdue';
        }

        $state = match ($status) {
            'pending' => [
                'tone' => 'info',
                'title' => 'We\'re reviewing your application',
                'message' => 'You asked for KES ' . number_format((float) $loan->amount, 2) . ' on ' . $date($loan->created_at) . '. Our team checks every application by hand, usually within a working day.',
                'next' => [
                    'We may call you or your guarantor to confirm a few details.',
                    'Once approved, the money is sent to your M-Pesa and you get the M-Pesa message.',
                    'Your due date is set from the day the money is sent.',
                ],
            ],
            'approved' => [
                'tone' => 'ok',
                'title' => 'Approved: sending to your M-Pesa soon',
                'message' => 'Your loan of KES ' . number_format((float) $loan->amount, 2) . ' is approved. Our team sends approved loans to your M-Pesa during working hours.',
                'next' => [
                    'You get an M-Pesa message from us when the money is sent.',
                    'Your repayment period starts on the day the money is sent.',
                ],
            ],
            'rejected' => [
                'tone' => 'err',
                'title' => 'This application was not approved',
                'message' => ($reason = self::rejectionReason($loan))
                    ? 'Reason from our team: ' . $reason
                    : 'Our team could not approve this application. Talk to us to find out why.',
                'next' => [
                    'Check that your details are up to date (income, M-Pesa number, contacts).',
                    'You can apply again from Borrow; a new application is reviewed from the start.',
                    'Not sure what to change? Contact us about this application and we\'ll explain.',
                ],
            ],
            'overdue' => self::overdue($loan, $daysLate, $penaltyRate, $date($due)),
            'defaulted', 'recovery' => self::recovery($loan, $status),
            'written_off' => [
                'tone' => 'err',
                'title' => 'This loan has been written off',
                'message' => 'Our records show this loan as written off. It still counts on your repayment history with us.',
                'next' => [
                    'Talk to us about settling it; settled loans are noted on your record.',
                    'A written-off loan keeps the amount you can borrow at the starting limit.',
                ],
            ],
            'forbearance' => [
                'tone' => 'warn',
                'title' => 'You\'re on a payment plan',
                'message' => 'Our team agreed a payment plan for this loan' . ($loan->forbearance_until ? ' until ' . $date($loan->forbearance_until) : '') . '. Late penalties are paused while you keep to it.',
                'next' => [
                    'Review the plan and confirm you accept it.',
                    'Pay what the plan asks by its due date.',
                    'If you miss the plan, the loan goes back to overdue.',
                ],
            ],
            default => null,
        };

        if (! $state) {
            return null;
        }

        $state['status'] = $status;
        $state['actions'] = self::actions($loan, $status, $onHome, $help);

        return $state;
    }

    /** The reason part of the latest "Rejected by X on Y: reason" note (staff name and time left out). */
    public static function rejectionReason(Loan $loan): ?string
    {
        $lines = preg_split('/\r?\n/', (string) $loan->recovery_notes) ?: [];
        foreach (array_reverse($lines) as $line) {
            if (preg_match('/^\s*Rejected(?: by .+? on \d{4}-\d{2}-\d{2}(?: \d{2}:\d{2})?)?\s*:\s*(.+)$/i', $line, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }

    /** The borrower's own recovery case for this loan (latest), read-only. */
    public static function recoveryCase(Loan $loan): ?DebtRecoveryCase
    {
        return DebtRecoveryCase::with(['status', 'assignedTo'])
            ->where('loan_id', $loan->id)->where('user_id', $loan->user_id)
            ->latest('id')->first();
    }

    /** The latest interim request of a type the borrower sent about this loan (an audit row), if any. */
    public static function lastRequest(Loan $loan, string $type): ?AuditLog
    {
        return AuditLog::where('table_name', self::REQUESTS)->where('record_id', $loan->id)
            ->where('user_id', $loan->user_id)->where('new_values->type', $type)
            ->latest('id')->first();
    }

    private static function overdue(Loan $loan, int $daysLate, float $penaltyRate, ?string $dueDate): array
    {
        $threshold = null;
        try {
            $cycle = app(LoanCalculator::class)->getActiveCycle($loan);
            if ($cycle) {
                $threshold = app(LoanCalculator::class)->calculateCycleBalance($loan, $cycle)['default_threshold_days'] ?? null;
            }
        } catch (\Throwable $e) {
            $threshold = null;
        }

        return [
            'tone' => 'err',
            'title' => $daysLate > 0 ? 'Overdue by ' . $daysLate . ' ' . ($daysLate === 1 ? 'day' : 'days') : 'Payment overdue',
            'message' => 'This loan was due on ' . $dueDate . '. Pay what you can today, or talk to us; we\'ll find a way.',
            'next' => array_values(array_filter([
                $penaltyRate > 0 ? 'A late penalty of ' . rtrim(rtrim(number_format($penaltyRate, 2), '0'), '.') . '%, as set out in your agreement, is added while the loan is overdue.' : null,
                $threshold ? 'If it is still unpaid ' . (int) $threshold . ' days after the due date, the loan is marked defaulted and passed to our recovery team.' : 'If it stays unpaid, the loan is marked defaulted and passed to our recovery team.',
                'Late repayment lowers the amount you can borrow next time.',
                'Can\'t pay in full? Ask for an extension or a payment plan before it gets to recovery.',
            ])),
            'days_late' => $daysLate,
        ];
    }

    private static function recovery(Loan $loan, string $status): array
    {
        $case = self::recoveryCase($loan);
        $date = fn ($d) => $d ? Carbon::parse($d)->format('j M Y') : null;

        return [
            'tone' => 'err',
            'title' => $status === 'recovery' ? 'This loan is with our recovery team' : 'This loan is in default',
            'message' => 'The loan was not repaid in time. Our recovery team will contact you to agree how to settle it. Talking to us early keeps your options open.',
            'next' => [
                'Call or WhatsApp the recovery team on ' . self::CONTACT['phone'] . '.',
                'You can still pay by M-Pesa at any time; every payment reduces what you owe.',
                'We can agree a payment plan you can keep to.',
            ],
            'case' => $case ? array_filter([
                'Case' => $case->case_number,
                'Case status' => $case->status?->name,
                'Your officer' => $case->assignedTo?->name,
                'In default since' => $date($case->default_date ?? $loan->default_date),
                'Last contact' => $date($case->last_contact_date),
                'Next step planned' => $date($case->next_action_date),
            ]) : null,
        ];
    }

    private static function actions(Loan $loan, string $status, bool $onHome, string $help): array
    {
        $a = [];
        // the home hero already links to an open loan; ended ones (rejected, written off) need the link here
        if ($onHome && in_array($status, ['rejected', 'written_off'], true)) {
            $a[] = ['View loan', route('account.loan', $loan), 'primary'];
        }
        if ($status === 'rejected') {
            $a[] = ['Apply again', route('account.borrow'), $onHome ? 'ghost' : 'primary'];
        }
        if (in_array($status, ['overdue', 'disbursed'], true) && in_array($loan->status, self::EXTENDABLE, true)) {
            $a[] = ['Ask for more time', route('account.extension', $loan), 'ghost'];
        }
        if ($status === 'forbearance') {
            $a[] = ['See the plan', route('account.plan', $loan), $onHome ? 'ghost' : 'primary'];
        }
        if (in_array($status, ['defaulted', 'recovery'], true)) {
            $a[] = ['Call the recovery team', 'tel:' . self::CONTACT['tel'], 'ghost'];
        }
        $a[] = [$status === 'rejected' ? 'Ask about this decision' : 'Contact us about this loan', $help, 'ghost'];

        return $a;
    }
}
