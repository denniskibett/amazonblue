<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Notifications\BrandedEmail;
use App\Notifications\LoanPaid;
use App\Services\Loans\CycleBalance;
use App\Support\Emails;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The money emails (owner, 3 Nov 2026): money sent, every payment received, and the loan paid in full.
 * Email only, on with NOTIFICATIONS_MONEY_EMAILS (default on) whatever NOTIFICATIONS_ENABLED says, so the
 * branded receipts go out without switching on SMS. Never for book entries, entries made from the console
 * (repairs, imports) or money dated more than a week back (catch-up entries).
 */
class MoneyEmails
{
    /** CustomerEvents that these emails replace (their plain email is skipped while this is on). */
    public const EVENTS = ['money_sent', 'payment_received'];

    private const STALE_DAYS = 7;

    public static function enabled(): bool
    {
        return (bool) config('lending.notifications.money_emails');
    }

    public function disbursed(Disbursement $d): void
    {
        if (! $this->live($d, $d->disburse_date)) {
            return;
        }
        $this->safely(function () use ($d) {
            $loan = Loan::with('user')->find($d->loan_id);
            if (! $loan?->user?->email) {
                return;
            }
            $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
            $total = $cycle && LoanCalculator::v2() ? CycleBalance::at($cycle)['final_outstanding'] : (float) ($cycle->new_balance ?? $loan->amount);
            $loan->user->notify(new BrandedEmail(Emails::fundsSent(
                $this->kes($d->net_amount ?: $d->amount),
                $this->method($d->mode, true),
                $this->ref($loan),
                Carbon::parse($d->disburse_date ?: $d->created_at)->format('j M Y'),
                $cycle?->due_date ? Carbon::parse($cycle->due_date)->format('j M Y') : 'the due date',
                $this->kes($total),
                route('account.loan', $loan),
            )));
        });
    }

    public function paid(Repayment $r): void
    {
        if ((float) $r->amount <= 0 || $r->reversal_of_id || ! $this->live($r, $r->repayment_date)) {
            return;
        }
        $this->safely(function () use ($r) {
            $loan = Loan::with('user')->find($r->loan_id);
            if (! $loan?->user?->email) {
                return;
            }
            $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
            $balance = $cycle && LoanCalculator::v2() ? CycleBalance::at($cycle) : null;
            $owed = $balance['final_outstanding'] ?? null;

            // paid in full: the closing email instead of a receipt, once per loan
            if ($balance && $owed <= 0.004 && $loan->status === Loan::STATUS_REPAID) {
                $this->closed($loan, $cycle, $balance);

                return;
            }
            $loan->user->notify(new BrandedEmail(Emails::paymentReceived(
                Emails::firstName($loan->user->name),
                $this->kes($r->amount),
                Carbon::parse($r->repayment_date ?: $r->created_at)->format('j M Y'),
                $this->method($r->mode),
                (string) ($r->mpesa_ref ?: $r->transaction ?: '—'),
                route('account.loan', $loan),
                $owed !== null ? $this->kes($owed) : null,
            )));
        });
    }

    private function closed(Loan $loan, LoanCycle $cycle, array $balance): void
    {
        $sent = \App\Models\AuditLog::where('table_name', 'loans')->where('record_id', $loan->id)
            ->where('new_values->event', 'loan_paid_email')->exists();
        if ($sent) {
            return;
        }
        $total = Repayment::where('loan_id', $loan->id)->where('amount', '>', 0)->whereNull('reversal_of_id')->get()
            ->filter(fn ($p) => CustomerWallets::kindOf($p) === 'cash')->sum('amount');
        $settled = $balance['settled_on'] ?: now()->toDateString();
        $loan->user->notify(new LoanPaid(
            Emails::firstName($loan->user->name),
            $this->ref($loan),
            $this->kes($total),
            Carbon::parse($settled)->format('j M Y'),
            $cycle->due_date && $settled <= Carbon::parse($cycle->due_date)->toDateString(),
            route('account.loan', $loan),
        ));
        AuditTrail::record('loans', $loan->id, 'update', null, ['event' => 'loan_paid_email', 'total' => $total]);
    }

    /** A real, current cash movement made by a person or a payment callback. */
    private function live($row, $date): bool
    {
        if (! self::enabled() || CustomerWallets::kindOf($row) !== 'cash') {
            return false;
        }
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        return ! $date || Carbon::parse($date)->greaterThanOrEqualTo(now()->subDays(self::STALE_DAYS)->startOfDay());
    }

    private function method(?string $mode, bool $payout = false): string
    {
        return match ($mode) {
            'mpesa', 'bank_to_mpesa' => 'M-Pesa',
            'bank_transfer', 'pesalink' => 'bank account',
            'cash' => 'cash',
            default => $payout ? 'account' : 'payment',
        };
    }

    private function ref(Loan $loan): string
    {
        return 'LN-' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT);
    }

    private function kes($n): string
    {
        return 'KES ' . number_format((float) $n, 2);
    }

    private function safely(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning('Money email not queued', ['error' => $e->getMessage()]);
        }
    }
}
